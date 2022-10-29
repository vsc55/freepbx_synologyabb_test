<?php
    $error_code = empty($error_info['code']) ? '?' : $error_info['code'];
    $error_msg  = empty($error_info['msg'])  ? _('Unknown Error!') : $error_info['msg'];
?>
<div id="panelError" class="panel panel-danger" style="display: none;">
    <div class="panel-heading">
        <h3 class="panel-title"><b><?php echo sprintf( _('Error (%s): %s'), $error_code, $error_msg); ?></b></h3>
    </div>
    <div class="panel-body">
        <i class="fa fa-exclamation-triangle fa-5x" aria-hidden="true"></i>
    </div>
    <div class="panel-footer">
        <button type="button" class="btn btn-warning cmd-reloadweb btn-block"><?php echo _("Try Again")?> </button>
    </div>
</div>