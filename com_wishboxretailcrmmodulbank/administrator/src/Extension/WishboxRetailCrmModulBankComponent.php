<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace Joomla\Component\WishboxRetailCrmModulBank\Administrator\Extension;

use Joomla\CMS\Extension\MVCComponent;
use WishboxRetailCrmModulBankLibrary\Service\RetailCrmOrderPaymentLinkCreationService;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * WishBox RetailCRM ModulBank component.
 *
 * @since 1.0.0
 */
final class WishboxRetailCrmModulBankComponent extends MVCComponent
{
	private RetailCrmOrderPaymentLinkCreationService $retailCrmOrderPaymentLinkCreationService;

	/**
	 * Set the payment link creation service.
	 *
	 * @param RetailCrmOrderPaymentLinkCreationService $service RetailCRM order payment link service.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function setRetailCrmOrderPaymentLinkCreationService(
		RetailCrmOrderPaymentLinkCreationService $service
	): void
	{
		$this->retailCrmOrderPaymentLinkCreationService = $service;
	}

	/**
	 * Get the payment link creation service.
	 *
	 * @return RetailCrmOrderPaymentLinkCreationService RetailCRM order payment link service.
	 *
	 * @since 1.0.0
	 */
	public function getRetailCrmOrderPaymentLinkCreationService(): RetailCrmOrderPaymentLinkCreationService
	{
		return $this->retailCrmOrderPaymentLinkCreationService;
	}
}
