<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace Tests\Unit\Service;

use Joomla\Registry\Registry;
use PHPUnit\Framework\TestCase;
use RetailCrm\Api\Client;
use RetailCrm\Api\Model\Entity\Orders\Order;
use RetailCrm\Api\Model\Entity\Pagination;
use RetailCrm\Api\Model\Entity\Store\OfferPrice;
use RetailCrm\Api\Model\Entity\Store\Product;
use RetailCrm\Api\Model\Entity\Store\StoreOffer;
use RetailCrm\Api\Model\Request\Orders\OrdersCreateRequest;
use RetailCrm\Api\Model\Request\Orders\OrdersRequest;
use RetailCrm\Api\Model\Response\Orders\OrdersCreateResponse;
use RetailCrm\Api\Model\Response\Orders\OrdersResponse;
use RetailCrm\Api\Model\Response\Store\OffersResponse;
use RetailCrm\Api\ResourceGroup\Orders;
use RetailCrm\Api\ResourceGroup\Store;
use RuntimeException;
use stdClass;
use WishboxOzonLibrary\Service\OzonFbsService;
use WishboxRetailCrmModulBankLibrary\Repositories\RetailCrmOfferRepository;
use WishboxRetailCrmModulBankLibrary\Repositories\RetailCrmOrderRepository;
use WishboxRetailCrmModulBankLibrary\Service\OzonOrderImportService;

final class OzonOrderImportServiceTest extends TestCase
{
	public function testImportsOzonPostingIntoRetailCrm(): void
	{
		$ozonFbsService = new OzonFbsService((object) ['postings' => [$this->createPosting()]]);
		$store = $this->createStub(Store::class);
		$store->method('offers')->willReturn($this->createOfferResponse());
		$ordersResponse = new OrdersResponse();
		$ordersResponse->orders = [];
		$createResponse = new OrdersCreateResponse();
		$createResponse->id = 501;
		$createdRequest = null;
		$orders = $this->createMock(Orders::class);
		$orders->expects(self::once())
			->method('list')
			->with(
				self::callback(
					static fn (OrdersRequest $request): bool => $request->filter->externalIds === ['1000001-0001-1']
				)
			)
			->willReturn($ordersResponse);
		$orders->expects(self::once())
			->method('create')
			->willReturnCallback(
				static function (OrdersCreateRequest $request) use (&$createdRequest, $createResponse): OrdersCreateResponse {
					$createdRequest = $request;

					return $createResponse;
				}
			);

		$result = $this->createService($ozonFbsService, $store, $orders)->importNewOrders();

		self::assertSame(1, $result->created);
		self::assertSame(0, $result->skipped);
		self::assertSame(['1000001-0001-1' => 501], $result->orderIds);
		self::assertSame('shop', $createdRequest->site);
		self::assertSame('1000001-0001-1', $createdRequest->order->externalId);
		self::assertSame(2.0, $createdRequest->order->items[0]->quantity);
		self::assertSame(1490.0, $createdRequest->order->items[0]->initialPrice);
		self::assertSame(9001, $createdRequest->order->items[0]->offer->id);
		self::assertSame('Moscow', $createdRequest->order->delivery->address->city);
		self::assertSame('awaiting_packaging', $ozonFbsService->lastFilter['status'][0]);
	}

	public function testSkipsPreviouslyImportedPosting(): void
	{
		$ozonFbsService = new OzonFbsService((object) ['postings' => [$this->createPosting()]]);
		$store = $this->createMock(Store::class);
		$store->expects(self::never())->method('offers');
		$response = new OrdersResponse();
		$response->orders = [new Order()];
		$orders = $this->createMock(Orders::class);
		$orders->expects(self::once())->method('list')->willReturn($response);
		$orders->expects(self::never())->method('create');

		$result = $this->createService($ozonFbsService, $store, $orders)->importNewOrders();

		self::assertSame(0, $result->created);
		self::assertSame(1, $result->skipped);
		self::assertSame(0, $result->failed);
	}

	public function testReportsRetailCrmCreateFailure(): void
	{
		$ozonFbsService = new OzonFbsService((object) ['postings' => [$this->createPosting()]]);
		$store = $this->createStub(Store::class);
		$store->method('offers')->willReturn($this->createOfferResponse());
		$response = new OrdersResponse();
		$response->orders = [];
		$orders = $this->createMock(Orders::class);
		$orders->expects(self::once())
			->method('list')
			->willReturn($response);
		$orders->expects(self::once())
			->method('create')
			->willThrowException(new RuntimeException('RetailCRM order creation failed.'));

		$result = $this->createService($ozonFbsService, $store, $orders)->importNewOrders();

		self::assertSame(0, $result->created);
		self::assertSame(1, $result->failed);
		self::assertSame('RetailCRM order creation failed.', $result->errors['1000001-0001-1']);
	}

	private function createService(OzonFbsService $ozonFbsService, Store $store, Orders $orders): OzonOrderImportService
	{
		$client = $this->createStub(Client::class);
		$client->store = $store;
		$client->orders = $orders;
		$params = new Registry([
			'order_currency'               => 'RUB',
			'ozon_order_statuses'          => 'awaiting_packaging',
			'ozon_sku_property'            => 'ozon_sku',
			'retailcrm_offer_lookup_limit' => 100,
			'retailcrm_order_method'       => 'shopping-cart',
			'retailcrm_order_status'       => 'new',
			'retailcrm_order_type'         => 'eshop-individual',
			'retailcrm_price_type'         => 'base',
			'retailcrm_site'               => 'shop',
		]);

		return new OzonOrderImportService(
			$ozonFbsService,
			new RetailCrmOfferRepository($client, $params),
			new RetailCrmOrderRepository($client),
			$params
		);
	}

	private function createOfferResponse(): OffersResponse
	{
		$price = new OfferPrice();
		$price->priceType = 'base';
		$price->price = 1490.0;
		$product = new Product();
		$product->active = true;
		$offer = new StoreOffer();
		$offer->id = 9001;
		$offer->externalId = 'CRM-1';
		$offer->name = 'Test product';
		$offer->properties = ['ozon_sku' => 321];
		$offer->prices = [$price];
		$offer->quantity = 10;
		$offer->active = true;
		$offer->product = $product;
		$response = new OffersResponse();
		$response->offers = [$offer];
		$response->pagination = new Pagination();
		$response->pagination->totalPageCount = 1;

		return $response;
	}

	private function createPosting(): stdClass
	{
		return (object) [
			'posting_number' => '1000001-0001-1',
			'order_number'   => '1000001',
			'status'         => 'awaiting_packaging',
			'addressee'      => (object) ['name' => 'Ivan Ivanov', 'phone' => '+79990000000'],
			'customer'       => (object) [
				'customer_email' => 'ivan@example.com',
				'address'        => (object) [
					'country'      => 'RU',
					'region'       => 'Moscow',
					'city'         => 'Moscow',
					'address_tail' => 'Tverskaya 1',
					'zip_code'     => '125009',
				],
			],
			'products'       => [
				(object) ['sku' => 321, 'quantity' => 2, 'price' => '1490.00', 'name' => 'Test product'],
			],
		];
	}
}
