<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace Joomla\Component\WishboxRetailCrmModulBank\Administrator\Model;

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
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
use RuntimeException;
use WishboxRetailCrmModulBankLibrary\Dto\IntegrationModuleStatus;
use WishboxRetailCrmModulBankLibrary\Factory\RetailCrmClientFactory;
use WishboxRetailCrmModulBankLibrary\Service\OzonIntegrationModuleFactory;
use WishboxRetailCrmModulBankLibrary\Service\RetailCrmIntegrationModuleService;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Provides RetailCRM integration module operations.
 *
 * @since 1.0.0
 */
final class IntegrationModel extends BaseDatabaseModel
{
	/**
	 * Get the current integration module status.
	 *
	 * @return IntegrationModuleStatus
	 *
	 * @throws ApiExceptionInterface
	 * @throws BuilderException
	 * @throws AccountDoesNotExistException
	 * @throws ApiErrorException
	 * @throws MissingCredentialsException
	 * @throws MissingParameterException
	 * @throws ValidationException
	 * @throws HandlerException
	 * @throws HttpClientException
	 * @throws ClientExceptionInterface
	 *
	 * @since 1.0.0
	 */
	public function getStatus(): IntegrationModuleStatus
	{
		return $this->createService()
			->getStatus();
	}

	/**
	 * Create or update the integration module.
	 *
	 * @return void
	 *
	 * @throws AccountDoesNotExistException
	 * @throws ApiErrorException
	 * @throws ApiExceptionInterface
	 * @throws BuilderException
	 * @throws ClientExceptionInterface
	 * @throws HandlerException
	 * @throws HttpClientException
	 * @throws MissingCredentialsException
	 * @throws MissingParameterException
	 * @throws ValidationException
	 *
	 * @since 1.0.0
	 */
	public function updateIntegration(): void
	{
		$this->createService()
			->update();
	}

	/**
	 * Build the configured integration module service.
	 *
	 * @return RetailCrmIntegrationModuleService
	 *
	 * @throws BuilderException
	 * @throws Exception
	 *
	 * @since 1.0.0
	 */
	private function createService(): RetailCrmIntegrationModuleService
	{
		$params = $this->getTaskPluginParams();
		$apiUrl = trim((string) $params->get('retailcrm_api_url'));
		$apiKey = trim((string) $params->get('retailcrm_api_key'));

		if ($apiUrl === '' || $apiKey === '')
		{
			throw new RuntimeException('RetailCRM API URL and API key must be configured in the task plugin.');
		}

		$siteRoot = rtrim(Uri::root(), '/');
		$clientId = OzonIntegrationModuleFactory::createClientId(
			(string) Factory::getApplication()->get('secret')
		);
		$expected = new OzonIntegrationModuleFactory()->create(
			$siteRoot . '/api/index.php/v1/wishboxretailcrmmodulbank',
			$siteRoot . '/administrator/index.php?option=com_wishboxretailcrmmodulbank',
			$siteRoot . '/media/com_wishboxretailcrmmodulbank/images/ozon.svg',
			$clientId
		);
		$client = RetailCrmClientFactory::createClient(
			$apiUrl,
			$apiKey,
			(string) $params->get('retailcrm_proxy_token')
		);

		return new RetailCrmIntegrationModuleService($client, $expected);
	}

	/**
	 * Load shared RetailCRM settings from the task plugin.
	 *
	 * @return Registry
	 *
	 * @since 1.0.0
	 */
	private function getTaskPluginParams(): Registry
	{
		$plugin = PluginHelper::getPlugin('task', 'wishboxretailcrmmodulbank');

		if (!is_object($plugin))
		{
			throw new RuntimeException('The WishBox Ozon RetailCRM task plugin must be enabled.');
		}

		return new Registry($plugin->params ?? '');
	}
}
