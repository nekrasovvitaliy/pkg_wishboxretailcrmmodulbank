<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace WishboxRetailCrmModulBankLibrary\Service;

use InvalidArgumentException;
use JsonException;
use RetailCrm\Api\Client;
use RetailCrm\Api\Exception\Api\AccountDoesNotExistException;
use RetailCrm\Api\Exception\Api\ApiErrorException;
use RetailCrm\Api\Exception\Api\MissingCredentialsException;
use RetailCrm\Api\Exception\Api\MissingParameterException;
use RetailCrm\Api\Exception\Api\ValidationException;
use RetailCrm\Api\Exception\Client\BuilderException;
use RetailCrm\Api\Exception\Client\HandlerException;
use RetailCrm\Api\Exception\Client\HttpClientException;
use RetailCrm\Api\Interfaces\ApiExceptionInterface;
use RetailCrm\Api\Interfaces\ClientExceptionInterface;
use RetailCrm\Api\Model\Request\BySiteRequest;
use RuntimeException;
use WishboxModulBankLibrary\Service\PaymentLinkCreationService;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Creates a ModulBank payment link from a RetailCRM order.
 *
 * @since 1.0.0
 */
readonly class RetailCrmOrderPaymentLinkCreationService
{
	/**
	 * Initialize the payment link creation service.
	 *
	 * @param Client                     $retailCrmClient                      RetailCRM API client.
	 * @param PaymentLinkCreationService $modulBankPaymentLinkCreationService ModulBank payment client.
	 * @param string                     $retailCrmSite                        RetailCRM site code.
	 * @param string                     $merchantId                           ModulBank merchant identifier.
	 * @param string                     $secretKey                            ModulBank signing secret.
	 * @param bool                       $testMode                             ModulBank test mode flag.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function __construct(
		private Client $retailCrmClient,
		private PaymentLinkCreationService $modulBankPaymentLinkCreationService,
		private string $retailCrmSite,
		private string $merchantId,
		private string $secretKey,
		private bool $testMode,
	)
	{
		if (trim($this->retailCrmSite) === '')
		{
			throw new InvalidArgumentException('The RetailCRM site code must not be empty.');
		}

		if (trim($this->merchantId) === '')
		{
			throw new InvalidArgumentException('The ModulBank merchant ID must not be empty.');
		}

		if (trim($this->secretKey) === '')
		{
			throw new InvalidArgumentException('The ModulBank secret key must not be empty.');
		}
	}

	/**
	 * Create a payment link for a RetailCRM order.
	 *
	 * @param int $retailCrmOrderId RetailCRM order identifier.
	 *
	 * @return string Created payment page URL.
	 *
	 * @throws ApiExceptionInterface
	 * @throws ClientExceptionInterface
	 * @throws AccountDoesNotExistException
	 * @throws ApiErrorException
	 * @throws MissingCredentialsException
	 * @throws MissingParameterException
	 * @throws ValidationException
	 * @throws BuilderException
	 * @throws HandlerException
	 * @throws HttpClientException
	 * @throws JsonException
	 * @throws RuntimeException
	 *
	 * @since 1.0.0
	 */
	public function createForOrder(int $retailCrmOrderId): string
	{
		if ($retailCrmOrderId <= 0)
		{
			throw new InvalidArgumentException('$retailCrmOrderId param must be more than zero', 500);
		}

		$request       = new BySiteRequest;
		$request->by   = 'externalId';
		$request->site = $this->retailCrmSite;

		$response = $this->retailCrmClient->orders->get($retailCrmOrderId, $request);

		if (!$response->success)
		{
			throw new RuntimeException('RetailCRM did not return the requested order.');
		}

		// Получаем объект заказа retailCRM
		$order = $response->order;

		// Массив товаров чека
		$receiptItems = [];

		foreach ($order->items as $orderItem)
		{
			$product = [];

			// Наименование товара
			$product['name'] = $orderItem->offer->displayName;

			// Количество
			$product['quantity'] = $orderItem->quantity;

			// Цена за единицу товара (рубли, в формате 10.99)
			$product['price'] = $orderItem->initialPrice;

			// Скидка на позицию (рубли, в формате 5.99).

			//discount_sum
			// Используется только в работе через Модулькассу!

			// Система налогообложения. Поле принимает следующие значения:
			// osn - Общая СН;
			// usn_income - Упрощенная СН (доходы);
			// usn_income_outcome - Упрощенная СН (доходы минус расходы);
			// envd - Единый налог на вмененный доход;
			// esn - Единый сельскохозяйственный налог;
			// patent - Патентная СН
			//
			$product['sno'] = 'usn_income';


			// Предмет расчета. Поле принимает следующие значения:
			//	commodity - Товар;
			//	excise - Подакцизный товар;
			//	job - Работа;
			//	service - Услуга;
			//	gambling_bet - Ставка азартной игры;
			//	gambling_prize - Выигрыш азартной игры;
			//	lottery - Лотерейный билет;
			//	lottery_prize - Выигрыш лотереи;
			//	intellectual_activity - Предоставление результатов интеллектуальной деятельности;
			//	payment - Платеж;
			//	agent_commission - Агентское вознаграждение;
			//	composite - Составной предмет расчета;
			//	another - Другое
			$product['payment_object'] = 'commodity';


			// Метод платежа. Поле принимает следующие значения:
			// full_prepayment - Предоплата 100%;
			//	prepayment - Предоплата;
			//	advance - Аванс;
			//	full_payment - Полный расчет;
			//	partial_payment - Частичный расчет и кредит;
			//	credit - Передача в кредит;
			//	credit_payment - Оплата кредита
			$product['payment_method'] = 'full_prepayment';


			// Ставка НДС. Поле принимает следующие значения:
			//	none - Без НДС
			//	vat0 - НДС по ставке 0%
			//	vat10 - НДС чека по ставке 10%
			//	vat20 - НДС чека по ставке 20%
			//	vat110 - НДС чека по расчетной ставке 10%
			//	vat120 - НДС чека по расчетной ставке 20%
			$product['vat'] = 'none';
			//
			//
			$receiptItems[] = $product;
		}

		if ($order->delivery->cost > 0)
		{
			$product = [];

			// Наименование товара
			$product['name'] = 'Доставка';

			// Количество
			$product['quantity'] = 1;

			// Цена за единицу товара (рубли, в формате 10.99)
			$product['price'] = $order->delivery->cost;

			// Система налогообложения
			$product['sno'] = 'usn_income';

			// Предмет расчета
			$product['payment_object'] = 'service';

			// Метод платежа
			$product['payment_method'] = 'full_prepayment';

			// Ставка НДС
			$product['vat'] = 'none';

			$receiptItems[] = $product;
		}

		$params                = [];
		$params['merchant']    = $this->merchantId;
		$params['amount']      = $order->totalSumm;
		$params['description'] = $order->id;
		$params['testing']     = $this->testMode ? '1' : '0';

		if (isset($order->email))
		{
			$params['client_email'] = (string) $order->email;
		}

		$params['send_letter']    = '1';
		$params['receipt_items']  = json_encode($receiptItems, JSON_THROW_ON_ERROR);
		$params['unix_timestamp'] = time();
		$params['signature']      = $this->getSignature($params);

		return $this->modulBankPaymentLinkCreationService->create($params);
	}

	/**
	 * Build the signature expected by ModulBank.
	 *
	 * @param   array<string, scalar|null>  $params  ModulBank request parameters.
	 *
	 * @return string Request signature.
	 *
	 * @since 1.0.0
	 */
	private function getSignature(array $params): string
	{
		$keys = array_keys($params);
		sort($keys);
		$chunks = [];

		foreach ($keys as $key)
		{
			$value = (string) $params[$key];

			if ($value !== '' && $key !== 'signature')
			{
				$chunks[] = $key . '=' . base64_encode($value);
			}
		}

		$data      = implode('&', $chunks);
		$signature = $this->doubleSha1($data);

		return $signature;
	}

	/**
	 * Hash signature data twice with the configured secret key.
	 *
	 * @param   string  $data  Encoded signature data.
	 *
	 * @return string Double SHA-1 signature.
	 *
	 * @since 1.0.0
	 */
	private function doubleSha1(string $data): string
	{
		for ($i = 0; $i < 2; $i++)
		{
			$data = sha1($this->secretKey . $data);
		}

		return $data;
	}
}
