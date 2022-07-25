<?php
    $progress_backup = null;
    if (isset($info['info_status']['progress']))
    {
        if ($info['info_status']['progress']['total'] != 0)
        {
            $progress_backup = array(
                'progress' => round((100 / $info['info_status']['progress']['total']) * $info['info_status']['progress']['send']),
                'all'   => $info['info_status']['progress']['all'],
            );
        }
    }

    $ico_status_backup = "status_clock.png";
    if ($status_type == 'error')
    {
        $ico_status_backup = "status_error.png";
    }
    else
    {
        switch($status['code'])
        {
            case 110: // (Idle) No se ha echo ninguna copia aun.
            case 130:// (Idle - Canceled)
            case 140:// (Idel - Failed)
                $ico_status_backup = "status_warn.png";
                break;

            case 120:// (Idle - Completed)
                $ico_status_backup = "status_ok.png";
                break;
            
            case 300:// (Backing up... - 8.31 MB / 9.57 MB (576.00 KB/s)) Backup en curso
                $ico_status_backup = "status_update.png";
                break;
        }
    }
			
	// const STATUS_NO_CONNECTION 	= 400;		// (No connection found) No conectado con el servidor
	// const STATUS_ERR_DEV_REMOVED = 510; 	// (Error  - The current device has been removed from the server. Please contact your administrator for further assistance.) Equipo eliminado del servidor.

	// const STATUS_UNKNOWN 		= 99990;	//99990 - status desconocido
	// const STATUS_IDLE_UNKNOWN	= 99991;	//99991 - status Idel desconocido
	// const STATUS_ERR_UNKNOWN	= 99992;	//99992 - status Error desconocido
    
?>

<div class="panel panel-primary abb-panel-main">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo _("Status Backup") ?></h3>
    </div>
    <div class="panel-body">
        <div class="element-container">
            <div class="row">
                <div class="col-md-6">

                    <div class="media">
                        <div class="media-body">
                            <div class="page-header">
                                <h2><?php echo _('Connection Info:')?></h2>
                            </div>
                            <div class="element-container">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="row">
                                            <div class="form-group">
                                                <div class="col-md-3">
                                                    <label class="control-label" for="ABB_Info_Serv"><?php echo _('Server Address:')?></label>
                                                </div>
                                                <div class="col-md-9"><?php echo $info['server']; ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="row">
                                            <div class="form-group">
                                                <div class="col-md-3">
                                                    <label class="control-label" for="ABB_Info_Username"><?php echo _('Username:')?></label>
                                                </div>
                                                <div class="col-md-9"><?php echo $info['user']; ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="row">
                                            <div class="col-md-3"></div>
                                            <div class="col-md-9">
                                                <button type="button" id="btn-server-logout" class="btn btn-danger btn-sm btn-block"><?php echo _("Logout from Server") ?></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="media-right">
                            <a href="<?php echo $info['portal']?>"  target="_blank">
                                <img class="media-object" src="/admin/assets/synologyactivebackupforbusiness/images/abb_ico_64.png" alt="<?php echo _('Portal Recovery')?>" title="<?php echo _('Portal Recovery')?>">
                            </a>
                        </div>
                    </div>

                </div>
                <div class="col-md-6">

                    <div class="media">
                        <div class="media-body">
                            <div class="page-header">
                                <h2><?php echo sprintf(_('Backup Info (%s):'), $info['info_status']['msg']); ?></h2>
                            </div>
                            <div class="element-container">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="row">
                                            <div class="form-group">
                                                <div class="col-md-3">
                                                    <label class="control-label" for="ABB_Info_lastbackup"><?php echo _('Last Backup Time:')?></label>
                                                </div>
                                                <div class="col-md-9"><?php echo $info['lastbackup']; ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="row">
                                            <div class="form-group">
                                                <div class="col-md-3">
                                                    <label class="control-label" for="ABB_Info_nextbackup"><?php echo _('Next Backup Time:')?></label>
                                                </div>
                                                <div class="col-md-9"><?php echo $info['nextbackup']; ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="row">
                                            <div class="col-md-3"></div>
                                            <div class="col-md-9">
                                                <button type="button" id="btn-force-refresh" class="btn btn-default btn-sm btn-block"><?php echo _("Force refresh") ?></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="media-right">
                            <img class="media-object" src="/admin/assets/synologyactivebackupforbusiness/images/<?php echo $ico_status_backup?>" alt="<?php echo $info['info_status']['msg']; ?>" title="<?php echo $info['info_status']['msg']; ?>">
                        </div>
                    </div>

                </div>
            </div>
            <?php if (is_null($progress_backup)): ?>
                <div class="row">
                <div class="col-md-12">

                    <div class="media">
                        <div class="media-body">
                            <div class="page-header">
                                <h3><?php echo _('Service Status:')?></h3>
                            </div>
                            <div class="element-container">
                                <div class="row">
                                    <div class="col-md-12"><?php echo $info['server_status']; ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="media-right">
                            <img class="media-object" src="/admin/assets/synologyactivebackupforbusiness/images/ico_info.png">
                        </div>
                    </div>

                </div>
            </div>
            <?php else: ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="jumbotron" id="box_copy_progress">
                        <div class="container">
                            <h1><?php echo $info['info_status']['msg']; ?></h1>
                            <p><?php echo $progress_backup['all'] ?></p>
                            <p>
                                <div class="progress">
                                    <div class="progress-bar progress-bar-success progress-bar-striped active" role="progressbar" aria-valuenow="<?php echo $progress_backup['progress'] ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $progress_backup['progress'] ?>%;">
                                    <?php echo $progress_backup['progress'] ?>%
                                    </div>
                                </div>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    $("#btn-server-logout").click( function()
	{
        fpbxConfirm("<?php echo _("Are you sure you want to disconnect this device from the backup server?"); ?>",
			"<?php echo _("YES"); ?>", "<?php echo _("NO")?>",
			function()
			{
				alert("Pending crate dialog!");
			}
		);
    });

    $("#btn-force-refresh").click( function()
	{
        timerStop();
        boxLoading(true);
        fpbxToast("<?php echo _("Started data update process..."); ?>", '', 'info');
        var post_data = {
            module	: 'synologyactivebackupforbusiness',
            command	: 'setagentreconnect',
        };

        $.post(window.FreePBX.ajaxurl, post_data, function()
        {
            window.setTimeout(loadStatusForceRefresh, 500);
        });
    });
</script>