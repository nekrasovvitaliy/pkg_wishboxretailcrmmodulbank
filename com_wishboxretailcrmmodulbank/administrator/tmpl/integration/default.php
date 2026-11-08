<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

$status = $this->status;
$statusClass = 'alert-danger';
$statusText = Text::_('COM_WISHBOXRETAILCRMMODULBANK_STATUS_ERROR');

if ($status !== null)
{
	if (!$status->installed)
	{
		$statusClass = 'alert-warning';
		$statusText = Text::_('COM_WISHBOXRETAILCRMMODULBANK_STATUS_NOT_INSTALLED');
	}
	elseif ($status->updateRequired)
	{
		$statusClass = 'alert-warning';
		$statusText = Text::_('COM_WISHBOXRETAILCRMMODULBANK_STATUS_UPDATE_REQUIRED');
	}
	else
	{
		$statusClass = 'alert-success';
		$statusText = Text::_('COM_WISHBOXRETAILCRMMODULBANK_STATUS_INSTALLED');
	}
}
?>
<div class="container-fluid">
	<div class="row">
		<div class="col-lg-8">
			<div class="card mb-3">
				<div class="card-body">
					<div class="d-flex align-items-center gap-3 mb-3">
						<img
							src="../media/com_wishboxretailcrmmodulbank/images/modulbank.svg"
							alt="Ozon"
							width="96"
							height="48"
						>
						<div>
							<h2 class="h4 mb-1"><?php echo Text::_('COM_WISHBOXRETAILCRMMODULBANK_MODULE_NAME'); ?></h2>
							<div class="text-muted">wishboxozonshipping</div>
						</div>
					</div>

					<div class="alert <?php echo $statusClass; ?>" role="status">
						<strong><?php echo Text::_('COM_WISHBOXRETAILCRMMODULBANK_STATUS_LABEL'); ?>:</strong>
						<?php echo $statusText; ?>
					</div>

					<?php if ($this->errorMessage !== null) : ?>
						<p class="text-danger mb-3">
							<?php echo htmlspecialchars($this->errorMessage, ENT_QUOTES, 'UTF-8'); ?>
						</p>
					<?php endif; ?>

					<?php if ($status !== null && $status->differences !== []) : ?>
						<p class="mb-2"><?php echo Text::_('COM_WISHBOXRETAILCRMMODULBANK_DIFFERENCES_LABEL'); ?></p>
						<ul>
							<?php foreach ($status->differences as $difference) : ?>
								<li><code><?php echo htmlspecialchars($difference, ENT_QUOTES, 'UTF-8'); ?></code></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>

					<?php if ($status !== null && (!$status->installed || $status->updateRequired)) : ?>
						<form
							action="<?php echo Route::_('index.php?option=com_wishboxretailcrmmodulbank&task=integration.update'); ?>"
							method="post"
						>
							<button type="submit" class="btn btn-primary">
								<?php echo Text::_($status !== null && $status->installed
									? 'COM_WISHBOXRETAILCRMMODULBANK_UPDATE_BUTTON'
									: 'COM_WISHBOXRETAILCRMMODULBANK_INSTALL_BUTTON'); ?>
							</button>
							<?php echo HTMLHelper::_('form.token'); ?>
						</form>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>
