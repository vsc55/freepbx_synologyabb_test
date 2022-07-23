<?php
    if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
    $synoClass = \FreePBX::Synologyactivebackupforbusiness();
?>
<h1><?php echo _("Synology Active Backup for Business"); ?></h1>
<div class="container-fluid">
	<div class="row">
		<div class="col-sm-12">
			<div class="fpbx-container">
                <?php
                if (! $synoClass->isAgentInstalled())
                {
                    echo sprintf('<div class="alert alert-warning" role="alert">%s</div>', _("It has been detected that the active backup for business agent is not installed, please follow the steps below to install it."));
                    echo $synoClass->showPage("main.steps.install");
                }
                else
                {
                    echo $synoClass->showPage("main");
                }
                ?>
			</div>
		</div>
	</div>
</div>