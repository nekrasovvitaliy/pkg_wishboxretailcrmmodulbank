<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\Webservices\WishboxRetailCrmModulBank\Extension\WishboxRetailCrmModulBank;
use Joomla\Registry\Registry;
use RetailCrm\Api\Client;
use WishboxOzonLibrary\Api\OzonApiClient;
use WishboxOzonLibrary\Service\OzonFbsService;
use WishboxRetailCrmModulBankLibrary\Factory\RetailCrmClientFactory;
use WishboxRetailCrmModulBankLibrary\Repositories\RetailCrmOfferRepository;
use WishboxRetailCrmModulBankLibrary\Repositories\RetailCrmOrderRepository;
use WishboxRetailCrmModulBankLibrary\Service\OzonOrderImportService;

defined('_JEXEC') or die;

return new class implements ServiceProviderInterface {
	private const string PARAMS_SERVICE = 'wishboxretailcrmmodulbank.webservices.params';

	public function register(Container $container): void
	{
		require_once JPATH_SITE . '/vendor/autoload.php';

		if (!class_exists(OzonApiClient::class))
		{
			throw new RuntimeException('The WishBox Ozon library must be installed before enabling this plugin.');
		}
		$container->set(
			Client::class,
			static function (Container $container): Client {
				$params = $container->get(self::PARAMS_SERVICE);
				$apiUrl = trim((string) $params->get('retailcrm_api_url'));
				$apiKey = trim((string) $params->get('retailcrm_api_key'));

				if ($apiUrl === '' || $apiKey === '')
				{
					throw new RuntimeException('RetailCRM API URL and API key must be configured.');
				}

				return RetailCrmClientFactory::createClient(
					$apiUrl,
					$apiKey,
					(string) $params->get('retailcrm_proxy_token')
				);
			}
		);
		$container->set(
			RetailCrmOfferRepository::class,
			static fn (Container $container): RetailCrmOfferRepository => new RetailCrmOfferRepository(
				$container->get(Client::class),
				$container->get(self::PARAMS_SERVICE)
			)
		);
		$container->set(
			RetailCrmOrderRepository::class,
			static fn (Container $container): RetailCrmOrderRepository => new RetailCrmOrderRepository(
				$container->get(Client::class)
			)
		);
		$container->set(
			OzonOrderImportService::class,
			static fn (Container $container): OzonOrderImportService => new OzonOrderImportService(
				$container->get(OzonFbsService::class),
				$container->get(RetailCrmOfferRepository::class),
				$container->get(RetailCrmOrderRepository::class),
				$container->get(self::PARAMS_SERVICE)
			)
		);
		$container->set(
			PluginInterface::class,
			static function (Container $container): PluginInterface {
				$plugin = new WishboxRetailCrmModulBank(
					$container->get(DispatcherInterface::class),
					(array) PluginHelper::getPlugin('webservices', 'wishboxretailcrmmodulbank')
				);

				$plugin->setApplication(Factory::getApplication());
				$plugin->setOzonOrderImportService($container->get(OzonOrderImportService::class));

				return $plugin;
			}
		);
	}
};
