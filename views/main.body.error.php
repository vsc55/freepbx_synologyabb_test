<?php
    $error_code = empty($error_info['code']) ? '?' : $error_info['code'];
    $error_msg  = empty($error_info['msg'])  ? _('Unknown Error!') : $error_info['msg'];
?>
<div class="panel panel-danger" style="display: none;">
    <div class="panel-heading">
        <h3 class="panel-title"><b><?php echo sprintf( _('Error (%s): %s'), $error_code, $error_msg); ?></b></h3>
    </div>
    <div class="panel-body">
        <center>
            <img src='modules/synologyabb/assets/images/icons-error.png'>
        </center>
    </div>
    <!-- <div class="panel-footer" style="display: none;"></div> -->
</div>