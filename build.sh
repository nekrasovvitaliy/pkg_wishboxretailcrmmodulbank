#!/usr/bin/env bash

set -euo pipefail

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

COMPONENT_BASENAME="com_wishboxretailcrmmodulbank"
LIBRARY_BASENAME="lib_wishboxretailcrmmodulbank"
TASK_PLUGIN_BASENAME="plg_task_wishboxretailcrmmodulbank"
WEBSERVICES_PLUGIN_BASENAME="plg_webservices_wishboxretailcrmmodulbank"
PACKAGE_BASENAME="pkg_wishboxretailcrmmodulbank"

COMPONENT_SOURCE_DIR="$PROJECT_DIR/$COMPONENT_BASENAME"
LIBRARY_SOURCE_DIR="$PROJECT_DIR/$LIBRARY_BASENAME"
TASK_PLUGIN_SOURCE_DIR="$PROJECT_DIR/$TASK_PLUGIN_BASENAME"
WEBSERVICES_PLUGIN_SOURCE_DIR="$PROJECT_DIR/$WEBSERVICES_PLUGIN_BASENAME"
PACKAGE_MANIFEST_PATH="$PROJECT_DIR/pkg_wishboxozonretailcrm.xml"
PACKAGE_SCRIPT_PATH="$PROJECT_DIR/script.php"
PACKAGE_LANGUAGE_DIR="$PROJECT_DIR/language"

BUILD_DIR="$(mktemp -d "${TMPDIR:-/tmp}/${PACKAGE_BASENAME}-build.XXXXXX")"
PACKAGE_STAGE_DIR="$BUILD_DIR/package"

GREEN='\033[0;32m'
RED='\033[0;31m'
RESET='\033[0m'

cleanup() {
    rm -rf -- "$BUILD_DIR"
}

fail() {
    printf '%bERROR:%b %s\n' "$RED" "$RESET" "$1" >&2
    exit 1
}

manifest_version() {
    sed -n 's/.*<version>[[:space:]]*\([^<][^<]*\)[[:space:]]*<\/version>.*/\1/p' "$1" \
        | head -n 1
}

clean_metadata() {
    find "$1" -name '.DS_Store' -delete
    find "$1" -name '._*' -delete

    if command -v dot_clean >/dev/null 2>&1; then
        dot_clean "$1" 2>/dev/null || true
    fi

    if command -v xattr >/dev/null 2>&1; then
        xattr -rc "$1" 2>/dev/null || true
    fi
}

build_extension_archive() {
    local source_dir="$1"
    local basename="$2"
    local stage_dir="$BUILD_DIR/$basename"

    mkdir -p "$stage_dir"
    cp -a -- "$source_dir/." "$stage_dir/"
    clean_metadata "$stage_dir"

    (
        cd "$stage_dir"
        COPYFILE_DISABLE=1 zip -X -q -r "$PACKAGE_STAGE_DIR/${basename}.zip" .
    )
}

trap cleanup EXIT

[[ -d "$COMPONENT_SOURCE_DIR" ]] || fail "Component source directory not found: $COMPONENT_SOURCE_DIR"
[[ -d "$LIBRARY_SOURCE_DIR" ]] || fail "Library source directory not found: $LIBRARY_SOURCE_DIR"
[[ -d "$TASK_PLUGIN_SOURCE_DIR" ]] || fail "Task plugin source directory not found: $TASK_PLUGIN_SOURCE_DIR"
[[ -d "$WEBSERVICES_PLUGIN_SOURCE_DIR" ]] || fail "Webservices plugin source directory not found: $WEBSERVICES_PLUGIN_SOURCE_DIR"
[[ -f "$COMPONENT_SOURCE_DIR/wishboxretailcrmmodulbank.xml" ]] \
    || fail "Joomla component manifest not found"
[[ -f "$LIBRARY_SOURCE_DIR/lib_wishboxretailcrmmodulbank.xml" ]] \
    || fail "Joomla library manifest not found"
[[ -f "$TASK_PLUGIN_SOURCE_DIR/wishboxretailcrmmodulbank.xml" ]] \
    || fail "Joomla task plugin manifest not found"
[[ -f "$WEBSERVICES_PLUGIN_SOURCE_DIR/wishboxretailcrmmodulbank.xml" ]] \
    || fail "Joomla webservices plugin manifest not found"
[[ -f "$PACKAGE_MANIFEST_PATH" ]] || fail "Joomla package manifest not found: $PACKAGE_MANIFEST_PATH"
[[ -f "$PACKAGE_SCRIPT_PATH" ]] || fail "Joomla package installer script not found: $PACKAGE_SCRIPT_PATH"
[[ -d "$PACKAGE_LANGUAGE_DIR" ]] || fail "Package language directory not found: $PACKAGE_LANGUAGE_DIR"

command -v zip >/dev/null 2>&1 || fail "The zip command is required"

PACKAGE_VERSION="$(manifest_version "$PACKAGE_MANIFEST_PATH")"
[[ -n "$PACKAGE_VERSION" ]] || fail "Package version not found in $PACKAGE_MANIFEST_PATH"

VERSIONED_ARCHIVE_PATH="$PROJECT_DIR/${PACKAGE_BASENAME}_${PACKAGE_VERSION}.zip"

mkdir -p "$PACKAGE_STAGE_DIR"

build_extension_archive "$COMPONENT_SOURCE_DIR" "$COMPONENT_BASENAME"
build_extension_archive "$LIBRARY_SOURCE_DIR" "$LIBRARY_BASENAME"
build_extension_archive "$TASK_PLUGIN_SOURCE_DIR" "$TASK_PLUGIN_BASENAME"
build_extension_archive "$WEBSERVICES_PLUGIN_SOURCE_DIR" "$WEBSERVICES_PLUGIN_BASENAME"

cp -a -- "$PACKAGE_MANIFEST_PATH" "$PACKAGE_STAGE_DIR/"
cp -a -- "$PACKAGE_SCRIPT_PATH" "$PACKAGE_STAGE_DIR/"
cp -a -- "$PACKAGE_LANGUAGE_DIR" "$PACKAGE_STAGE_DIR/"

clean_metadata "$PACKAGE_STAGE_DIR"
rm -f -- "$VERSIONED_ARCHIVE_PATH"

(
    cd "$PACKAGE_STAGE_DIR"
    COPYFILE_DISABLE=1 zip -X -q -r "$VERSIONED_ARCHIVE_PATH" .
)

if command -v shasum >/dev/null 2>&1; then
    SHA256="$(shasum -a 256 "$VERSIONED_ARCHIVE_PATH" | awk '{print $1}')"
elif command -v openssl >/dev/null 2>&1; then
    SHA256="$(openssl dgst -sha256 "$VERSIONED_ARCHIVE_PATH" | sed 's/^.*= //')"
else
    SHA256="unavailable"
fi

printf '%bOK:%b package -> %s\n' "$GREEN" "$RESET" "$VERSIONED_ARCHIVE_PATH"
printf '%bOK:%b sha256 -> %s\n' "$GREEN" "$RESET" "$SHA256"
