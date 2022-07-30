<?php
    if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
    $synoClass = \FreePBX::Synologyabb();
?>
<h1><?php echo _("Synology Active Backup for Business"); ?></h1>
<div id="box_loading" class="loader-background">
    <div class="background-opacity">
        <h1><?php echo _("Please Wait..."); ?></h1>
    </div>
    <div class="loader"></div>
</div>
<div class="container-fluid">
	<div class="row">
		<div class="col-sm-12">
			<div class="fpbx-container" id="container-box_main">
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
</div>