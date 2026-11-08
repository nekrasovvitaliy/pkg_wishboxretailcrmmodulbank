<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace Joomla\Component\WishboxRetailCrmModulBank\Administrator\View\Integration;

use Exception;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\WishboxRetailCrmModulBank\Administrator\Model\IntegrationModel;
use Throwable;
use WishboxRetailCrmModulBankLibrary\Dto\IntegrationModuleStatus;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Renders the RetailCRM integration module status.
 *
 * @since 1.0.0
 */
final class HtmlView extends BaseHtmlView
{
	/**
	 * Integration module status.
	 *
	 * @var IntegrationModuleStatus|null
	 *
	 * @since 1.0.0
	 */
	public ?IntegrationModuleStatus $status = null;

	/**
	 * Status loading error.
	 *
	 * @var string|null
	 *
	 * @since 1.0.0
	 */
	public ?string $errorMessage = null;

	/**
	 * Render the status page.
	 *
	 * @param   null  $tpl  Optional layout name.
	 *
	 * @return void
	 *
	 * @throws Exception
	 *
	 * @since 1.0.0
	 */
	public function display($tpl = null): void
	{
		ToolbarHelper::title(Text::_('COM_WISHBOXRETAILCRMMODULBANK_TITLE'), 'link');

		try
		{
			/** @var IntegrationModel $model */
			$model = $this->getModel();

			$this->status = $model->getStatus();
		}
		catch (Throwable $throwable)
		{
			$this->errorMessage = $throwable->getMessage();
			Log::add((string) $throwable, Log::ERROR, 'com_wishboxretailcrmmodulbank');
		}

		parent::display($tpl);
	}
}
