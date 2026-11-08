<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace Tests\Stubs\WishboxOzonLibrary\Service;

use RuntimeException;
use stdClass;

/**
 * Records Ozon price and stock batches without making network requests.
 *
 * @since 1.0.0
 */
final class OzonProductService
{
	/** @var list<list<array<string, mixed>>> */
	public array $priceBatches = [];

	/** @var list<list<array<string, mixed>>> */
	public array $stockBatches = [];

	public bool $fail = false;

	/**
	 * @param   list<array<string, mixed>>  $prices  Price update records.
	 *
	 * @since 1.0.0
	 */
	public function updatePrices(array $prices): stdClass
	{
		if ($this->fail)
		{
			throw new RuntimeException('Ozon request failed.');
		}

		$this->priceBatches[] = $prices;

		return (object) [];
	}

	/**
	 * @param   list<array<string, mixed>>  $stocks  Stock update records.
	 *
	 * @since 1.0.0
	 */
	public function updateStocks(array $stocks): stdClass
	{
		if ($this->fail)
		{
			throw new RuntimeException('Ozon request failed.');
		}

		$this->stockBatches[] = $stocks;

		return (object) [];
	}
}
