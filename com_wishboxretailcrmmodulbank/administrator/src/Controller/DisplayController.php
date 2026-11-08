<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace Joomla\Component\WishboxRetailCrmModulBank\Administrator\Controller;

use Joomla\CMS\MVC\Controller\BaseController;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Displays the integration module status page.
 *
 * @since 1.0.0
 */
final class DisplayController extends BaseController
{
	/**
	 * Default administrator view.
	 *
	 * @var string
	 *
	 * @since 1.0.0
	 */
	protected $default_view = 'integration'; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore
}
