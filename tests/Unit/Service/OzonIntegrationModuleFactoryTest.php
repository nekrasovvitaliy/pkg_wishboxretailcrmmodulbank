<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use WishboxRetailCrmModulBankLibrary\Service\OzonIntegrationModuleFactory;
use WishboxRetailCrmModulBankLibrary\Service\RetailCrmIntegrationModuleService;

/**
 * Tests the RetailCRM Ozon module definition.
 *
 * @since 1.0.0
 */
final class OzonIntegrationModuleFactoryTest extends TestCase
{
	/**
	 * Verify the integration client identifier remains deterministic.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function testCreatesStableClientId(): void
	{
		self::assertSame(
			hash_hmac('sha256', 'wishboxozonshipping', 'site-secret'),
			OzonIntegrationModuleFactory::createClientId('site-secret')
		);
	}

	/**
	 * Verify the legacy integration contract is represented.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function testCreatesOzonDeliveryModule(): void
	{
		$module = (new OzonIntegrationModuleFactory())->create(
			'https://example.test/api/',
			'https://example.test/account',
			'https://example.test/logo.svg',
			'client-id'
		);

		self::assertSame('wishboxozonshipping', $module->code);
		self::assertSame('wishboxozonshipping', $module->integrationCode);
		self::assertTrue($module->active);
		self::assertSame('https://example.test/api', $module->baseUrl);
		self::assertSame(['RU'], $module->availableCountries);
		self::assertSame(['print' => '/print'], $module->integrations->delivery->actions);
		self::assertSame(['sender'], $module->integrations->delivery->payerType);
		self::assertSame(99, $module->integrations->delivery->platePrintLimit);
		self::assertSame('package-label', $module->integrations->delivery->plateList[0]->code);
	}

	/**
	 * Verify equal module definitions do not require updates.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function testEqualModulesHaveNoDifferences(): void
	{
		$factory = new OzonIntegrationModuleFactory();
		$current = $factory->create('https://example.test/api', 'account', 'logo', 'client');
		$expected = $factory->create('https://example.test/api/', 'account', 'logo', 'client');

		self::assertSame([], RetailCrmIntegrationModuleService::compare($current, $expected));
	}

	/**
	 * Verify changed managed fields are reported.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function testChangedFieldsAreReported(): void
	{
		$factory = new OzonIntegrationModuleFactory();
		$current = $factory->create('https://old.test/api', 'account', 'logo', 'client');
		$expected = $factory->create('https://new.test/api', 'account', 'logo', 'client');
		$current->integrations->delivery->plateList[0]->code = 'old-label';

		self::assertSame(
			['baseUrl', 'integrations.delivery.plate.code'],
			RetailCrmIntegrationModuleService::compare($current, $expected)
		);
	}
}
