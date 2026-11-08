<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace Tests\Unit\Service;

use Joomla\Registry\Registry;
use PHPUnit\Framework\TestCase;
use RetailCrm\Api\Client;
use RetailCrm\Api\Model\Entity\Pagination;
use RetailCrm\Api\Model\Entity\Store\OfferPrice;
use RetailCrm\Api\Model\Entity\Store\Product;
use RetailCrm\Api\Model\Entity\Store\StoreOffer;
use RetailCrm\Api\Model\Request\Store\OffersRequest;
use RetailCrm\Api\Model\Response\Store\OffersResponse;
use RetailCrm\Api\ResourceGroup\Store;
use WishboxOzonLibrary\Service\OzonProductService;
use WishboxRetailCrmModulBankLibrary\Repositories\RetailCrmOfferRepository;
use WishboxRetailCrmModulBankLibrary\Service\OzonProductSyncService;

final class OzonProductSyncServiceTest extends TestCase
{
	public function testUpdatesMappedPricesAndStocks(): void
	{
		$response = new OffersResponse();
		$response->offers = [
			$this->createOffer(101, 'CRM-101', 1490.0, 3.0),
			$this->createOffer(102, 'CRM-102', 990.0, 0.0),
			$this->createOffer(0, 'CRM-103', 500.0, 2.0),
		];
		$response->pagination = new Pagination();
		$response->pagination->totalPageCount = 1;
		$store = $this->createMock(Store::class);
		$store->expects(self::once())
			->method('offers')
			->with(self::callback($this->isOffersRequestForSite('shop')))
			->willReturn($response);
		$client = $this->createStub(Client::class);
		$client->store = $store;
		$params = new Registry([
			'ozon_warehouse_id'       => 77,
			'ozon_currency_code'      => 'RUB',
			'ozon_product_id_property' => 'ozon_product_id',
			'retailcrm_price_type'    => 'base',
			'retailcrm_site'          => 'shop',
		]);
		$ozonProductService = new OzonProductService();
		$service = new OzonProductSyncService(
			new RetailCrmOfferRepository($client, $params),
			$ozonProductService,
			$params
		);

		$result = $service->updateProducts();

		self::assertSame(2, $result->updated);
		self::assertSame(1, $result->skipped);
		self::assertSame(0, $result->failed);
		self::assertSame(
			[
				'offer_id'     => 'CRM-101',
				'product_id'   => 101,
				'price'        => '1490.00',
				'currency_code' => 'RUB',
			],
			$ozonProductService->priceBatches[0][0]
		);
		self::assertSame(3, $ozonProductService->stockBatches[0][0]['stock']);
		self::assertSame(77, $ozonProductService->stockBatches[0][0]['warehouse_id']);
		self::assertSame(0, $ozonProductService->stockBatches[0][1]['stock']);
	}

	public function testRecordsFailedOzonBatch(): void
	{
		$response = new OffersResponse();
		$response->offers = [$this->createOffer(101, 'CRM-101', 1490.0, 3.0)];
		$response->pagination = new Pagination();
		$response->pagination->totalPageCount = 1;
		$store = $this->createStub(Store::class);
		$store->method('offers')->willReturn($response);
		$client = $this->createStub(Client::class);
		$client->store = $store;
		$params = new Registry(['ozon_warehouse_id' => 77]);
		$ozonProductService = new OzonProductService();
		$ozonProductService->fail = true;
		$service = new OzonProductSyncService(
			new RetailCrmOfferRepository($client, $params),
			$ozonProductService,
			$params
		);

		$result = $service->updateProducts();

		self::assertSame(0, $result->updated);
		self::assertSame(1, $result->failed);
		self::assertSame(['Ozon request failed.'], $result->errors);
	}

	private function createOffer(int $productId, string $externalId, float $priceValue, float $quantity): StoreOffer
	{
		$price = new OfferPrice();
		$price->priceType = 'base';
		$price->price = $priceValue;
		$product = new Product();
		$product->active = true;
		$offer = new StoreOffer();
		$offer->externalId = $externalId;
		$offer->properties = $productId > 0 ? ['ozon_product_id' => $productId] : [];
		$offer->prices = [$price];
		$offer->quantity = $quantity;
		$offer->active = true;
		$offer->product = $product;

		return $offer;
	}

	private function isOffersRequestForSite(string $site): callable
	{
		return static fn (OffersRequest $request): bool => $request->limit === 100
			&& $request->page === 1
			&& $request->filter->sites === [$site];
	}
}
