<?php
    if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
    $synoClass = \FreePBX::create()->Synologyabb;
?>
<h1><?php echo _("Synology Active Backup for Business"); ?></h1>

<div id="box_loading">
    <div class="box_loading_msgbox">
        <h1><?php echo _("Please Wait..."); ?></h1>
        <div class="fa-5x box_loading_spin">
            <i class="fa fa-spinner fa-pulse"></i>
        </div>
    </div>
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