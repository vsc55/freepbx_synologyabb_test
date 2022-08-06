<?php
    if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
    $synoClass = \FreePBX::create()->Synologyabb;
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
                    echo $synoClass->showPage("main");
                ?>
                </div>
			</div>
		</div>
	</div>
</div>