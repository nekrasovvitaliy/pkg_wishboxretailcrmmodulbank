<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\WishboxRetailCrmModulBank\Administrator\Extension\WishboxRetailCrmModulBankComponent;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Registry\Registry;
use RetailCrm\Api\Factory\SimpleClientFactory;
use WishboxModulBankLibrary\Service\PaymentLinkCreationService;
use WishboxRetailCrmModulBankLibrary\Service\RetailCrmOrderPaymentLinkCreationService;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Provides the WishBox RetailCRM ModulBank component services.
 *
 * @since 1.0.0
 */
return new class implements ServiceProviderInterface
{
	/**
	 * Register the component services.
	 *
	 * @param   Container  $container  Dependency injection container.
	 *
	 * @return void
	 *
	 * @noinspection PhpUnused
	 *
	 * @since 1.0.0
	 */
	public function register(Container $container): void
	{
		require_once JPATH_SITE . '/vendor/autoload.php';

		$container->registerServiceProvider(
			new MVCFactory('\\Joomla\\Component\\WishboxRetailCrmModulBank')
		);
		$container->registerServiceProvider(
			new ComponentDispatcherFactory('\\Joomla\\Component\\WishboxRetailCrmModulBank')
		);
		$container->set(
			RetailCrmOrderPaymentLinkCreationService::class,
			fn (): RetailCrmOrderPaymentLinkCreationService => $this->createRetailCrmOrderPaymentLinkCreationService()
		);

		$container->set(
			ComponentInterface::class,
			static function (Container $container): ComponentInterface {
				$component = new WishboxRetailCrmModulBankComponent(
					$container->get(ComponentDispatcherFactoryInterface::class)
				);
				$component->setMVCFactory($container->get(MVCFactoryInterface::class));
				$component->setRetailCrmOrderPaymentLinkCreationService(
					$container->get(RetailCrmOrderPaymentLinkCreationService::class)
				);

				return $component;
			}
		);
	}

	/**
	 * Create the payment link service from extension configuration.
	 *
	 * @return RetailCrmOrderPaymentLinkCreationService Configured payment link service.
	 *
	 * @since 1.0.0
	 */
	private function createRetailCrmOrderPaymentLinkCreationService(): RetailCrmOrderPaymentLinkCreationService
	{
		$retailCrmParams = $this->getPluginParams('system', 'wishboxretailcrm');
		$modulBankParams = $this->getPluginParams('system', 'wishboxmodulbank');
		$testMode        = (bool) $modulBankParams->get('test_mode', true);
		$secretKeyName   = $testMode ? 'test_secret_key' : 'secret_key';

		$retailCrmClient = SimpleClientFactory::createClient(
			$this->getRequiredString($retailCrmParams, 'retailcrm_api_url'),
			$this->getRequiredString($retailCrmParams, 'retailcrm_api_key')
		);

		return new RetailCrmOrderPaymentLinkCreationService(
			$retailCrmClient,
			new PaymentLinkCreationService,
			$this->getRequiredString($retailCrmParams, 'retailcrm_site'),
			$this->getRequiredString($modulBankParams, 'merchant_id'),
			$this->getRequiredString($modulBankParams, $secretKeyName),
			$testMode
		);
	}

	/**
	 * Load enabled Joomla plugin parameters.
	 *
	 * @param string $group   Joomla plugin group.
	 * @param string $element Joomla plugin element.
	 *
	 * @return Registry Plugin parameters.
	 *
	 * @since 1.0.0
	 */
	private function getPluginParams(string $group, string $element): Registry
	{
		$plugin = PluginHelper::getPlugin($group, $element);

		if (!is_object($plugin))
		{
			throw new RuntimeException(
				sprintf('The %s/%s plugin must be enabled.', $group, $element)
			);
		}

		return new Registry($plugin->params ?? '');
	}

	/**
	 * Read a required string parameter.
	 *
	 * @param Registry $params Plugin parameters.
	 * @param string   $name   Parameter name.
	 *
	 * @return string Parameter value.
	 *
	 * @since 1.0.0
	 */
	private function getRequiredString(Registry $params, string $name): string
	{
		$value = trim((string) $params->get($name, ''));

		if ($value === '')
		{
			throw new RuntimeException(sprintf('The %s parameter must be configured.', $name));
		}

		return $value;
	}
};
