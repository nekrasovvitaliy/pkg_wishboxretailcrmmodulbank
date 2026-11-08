<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license         GNU General Public License version 2 or later;
 */

namespace Joomla\Plugin\Webservices\WishboxRetailCrmModulBank\Extension;

use Joomla\CMS\Event\Application\BeforeApiRouteEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\Router\Route;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * @since 1.0.0
 */
final class WishboxRetailCrmModulBank extends CMSPlugin implements SubscriberInterface
{
	/**
	 * Return the subscribed Joomla events.
	 *
	 * @return array<string, string>
	 *
	 * @since        1.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onBeforeApiRoute' => 'onBeforeApiRoute',
			'onAfterApiRoute'  => 'onAfterApiRoute',
		];
	}

	/**
	 * Register the RetailCRM create payment link route.
	 *
	 * @param   BeforeApiRouteEvent  $event  API routing event.
	 *
	 * @return void
	 *
	 * @since        1.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public function onBeforeApiRoute(BeforeApiRouteEvent $event): void
	{
		$callbackRoute = new Route(
			['PATCH'],
			'v1/wishboxretailcrmmodulbank/orders/:retailcrmorder_id/createpaymentlink',
			'orders.createPaymentLink',
			['retailcrmorder_id' => '(\d+)'],
			[
				'component' => 'com_wishboxretailcrmmodulbank',
				'format'    => ['application/json'],
			]
		);
		$router        = $event->getRouter();
		$router->addRoute($callbackRoute);
	}
}
