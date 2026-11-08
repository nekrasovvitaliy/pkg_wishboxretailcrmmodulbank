<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

define('_JEXEC', 1);

require dirname(__DIR__) . '/vendor/autoload.php';

$environmentFileDirectory = dirname(__DIR__);

if (is_file($environmentFileDirectory . '/.env'))
{
	Dotenv\Dotenv::createImmutable($environmentFileDirectory)->safeLoad();
}

class_alias(
	Tests\Stubs\WishboxOzonLibrary\Service\OzonProductService::class,
	WishboxOzonLibrary\Service\OzonProductService::class
);
class_alias(
	Tests\Stubs\WishboxOzonLibrary\Service\OzonFbsService::class,
	WishboxOzonLibrary\Service\OzonFbsService::class
);
