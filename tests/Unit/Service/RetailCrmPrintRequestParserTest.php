<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace Tests\Unit\Service;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WishboxRetailCrmModulBankLibrary\Service\RetailCrmPrintRequestParser;

/**
 * Tests RetailCRM package label request parsing.
 *
 * @since 1.0.0
 */
final class RetailCrmPrintRequestParserTest extends TestCase
{
	/**
	 * Verify legacy grouped delivery identifiers are flattened.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function testParsesLegacyDeliveryIds(): void
	{
		$payload = json_encode(
			[
				'deliveryIds' => [
					'101' => ['12345-0001-1', '12345-0002-1'],
					'102' => ['12345-0002-1'],
				],
			],
			JSON_THROW_ON_ERROR
		);

		$retailCrmPrintRequestParser = new RetailCrmPrintRequestParser();
		$result = $retailCrmPrintRequestParser->parse($payload);

		self::assertSame(['12345-0001-1', '12345-0002-1'], $result);
	}

	/**
	 * Verify malformed JSON is rejected.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function testRejectsMalformedJson(): void
	{
		$this->expectException(InvalidArgumentException::class);

		$retailCrmPrintRequestParser = new RetailCrmPrintRequestParser();
		$retailCrmPrintRequestParser->parse('{invalid');
	}

	/**
	 * Verify missing delivery identifiers are rejected.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function testRejectsMissingDeliveryIds(): void
	{
		$this->expectException(InvalidArgumentException::class);

		$retailCrmPrintRequestParser = new RetailCrmPrintRequestParser();
		$retailCrmPrintRequestParser->parse('{}');
	}

	/**
	 * Verify the registered print limit is enforced.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function testRejectsMoreThanNinetyNineLabels(): void
	{
		$payload = json_encode(
			['deliveryIds' => ['orders' => array_map('strval', range(1, 100))]],
			JSON_THROW_ON_ERROR
		);
		$this->expectException(InvalidArgumentException::class);

		$retailCrmPrintRequestParser = new RetailCrmPrintRequestParser();
		$retailCrmPrintRequestParser->parse($payload);
	}
}
