<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace Joomla\Component\WishboxRetailCrmModulBank\Api\Controller;

use InvalidArgumentException;
use Joomla\CMS\Application\ApiApplication;
use Joomla\CMS\Log\Log;
use Joomla\Component\WishboxRetailCrmModulBank\Administrator\Extension\WishboxRetailCrmModulBankComponent;
use Throwable;
use WishboxRetailCrmLibrary\Controller\RetailCrmApiController;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Handles RetailCRM requests to the ModulBank payment integration.
 *
 * @since 1.0.0
 *
 * @noinspection PhpUnused
 */
final class OrdersController extends RetailCrmApiController
{
	/**
	 * Create a ModulBank payment link for a RetailCRM order.
	 *
	 * @param   int|null  $retailCrmOrderId  RetailCRM order identifier.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public function createPaymentLink(?int $retailCrmOrderId = null): void
	{
		try
		{
			if ($retailCrmOrderId === null)
			{
				$retailCrmOrderId = $this->input->getInt('retailcrmorder_id');
			}

			if ($retailCrmOrderId <= 0)
			{
				throw new InvalidArgumentException('retailcrmorder_id must be greater than zero.', 400);
			}

			/** @var ApiApplication $app */
			$app = $this->app;

			/** @var WishboxRetailCrmModulBankComponent $wishboxRetailCrmModulBankComponent */
			$wishboxRetailCrmModulBankComponent = $app->bootComponent('com_wishboxretailcrmmodulbank');

			$retailCrmOrderPaymentLinkCreationService = $wishboxRetailCrmModulBankComponent
				->getRetailCrmOrderPaymentLinkCreationService();

			$retailCrmOrderPaymentLinkCreationService->createForOrder($retailCrmOrderId);
		}
		catch (Throwable $throwable)
		{
			Log::add((string) $throwable, Log::ERROR, 'com_wishboxretailcrmmodulbank');
			$statusCode = (int) $throwable->getCode();
			$errorMessage = trim($throwable->getMessage());

			if ($statusCode < 400 || $statusCode > 599)
			{
				$statusCode = 500;
			}

			if ($errorMessage === '')
			{
				$errorMessage = $throwable::class;
			}

			$this->setRetailCrmResponse(
				[
					'success'  => false,
					'errorMsg' => $errorMessage,
					'errors'   => [
						'exception' => $throwable::class,
						'message'   => $errorMessage,
					],
				],
				$statusCode
			);

			return;
		}

		$this->setRetailCrmResponse(['success' => true], 200);
	}
}
