<div class="panel panel-warning" style="display: none;">
	<div class="panel-heading">
		<h3 class="panel-title">
			<img src='modules/synologyactivebackupforbusiness/assets/images/abb_ico_24.png'>
			<b><?php echo _("Not Connection, pending configuration!"); ?><b>
		</h3>
    </div>
    <div class="panel-body">
		<form method="post" id="formlogin">
			<input type="hidden" id="module" name="module" value="synologyactivebackupforbusiness"> 
			<input type="hidden" id="command" name="command" value="setagentcreateconnection">
			<div class="form-group">
				<label for="ABBServer"><?php echo _("IP/Address Server") ?></label>
				<input type="text" class="form-control" name="ABBServer" id="ABBServer" aria-describedby="serverHelp" placeholder="nas.example.com" required="required">
				<small id="serverHelp" class="form-text text-muted"><?php echo _("Active Backup for Business server IP address.") ?></small>
			</div>
			<div class="form-group">
				<label for="ABBUser"><?php echo _("Username") ?></label>
				<input type="text" class="form-control" name="ABBUser" id="ABBUser" aria-describedby="userHelp" placeholder="<?php echo _("Username") ?>" required="required">
				<small id="userHelp" class="form-text text-muted"><?php echo _("User with which this equipment will be added to the server.") ?></small>
			</div>
			<div class="form-group">
				<label for="ABBPassword"><?php echo _("Password")?></label>
				<input type="password" class="form-control" name="ABBPassword" id="ABBPassword" placeholder="<?php echo _("Password")?>" required="required">
			</div>
		
			<button type="button" id="ABBCreateNow" class="btn btn-success btn-lg btn-block"><?php echo _("Register Device")?></button>
		</form>
	</div>
	<div class="panel-footer panel-version">
		<b><?php echo sprintf( _("Agent Version: %s"), $syno->getAgentVersion() ); ?></b>
	</div>
</div>

<script type="text/javascript">
	$('#formlogin').keypress((e) => {
		if (e.which === 13) {
			$("#ABBCreateNow").trigger("click");
		}
	});
	function validaFormABB()
	{
		if($("#ABBServer").val() == "")
		{
			fpbxToast("<?php echo _("The Server field cannot be empty!"); ?>", '', 'warning');
			$("#ABBServer").focus();
			return false;
		}
		if($("#ABBUser").val() == "")
		{
			fpbxToast("<?php echo _("The Username field cannot be empty!"); ?>", '', 'warning');
			$("#ABBUser").focus();
			return false;
		}
		if($("#ABBPassword").val() == "")
		{
			fpbxToast("<?php echo _("The Password field cannot be empty!"); ?>", '', 'warning');
			$("#ABBPassword").focus();
			return false;
		}
		return true;
	}
	$("#ABBCreateNow").click( function()
	{
		if(validaFormABB())
		{
			timerStop();
			boxLoading(true);
			var form = $("#formlogin");
			var post_data = form.serialize();

			form.find(':input:not(:disabled)').prop('disabled',true);

			$.post(window.FreePBX.ajaxurl, post_data, function(res)
			{
				var data 	= res.data;
				var error 	= data.error;
				if(error.code === 0)
				{
					fpbxToast('<i class="fa fa-check" aria-hidden="true"></i>&nbsp;&nbsp;' + '<?php echo _('Device Successfully Registered!'); ?>', '', 'success');
					window.setTimeout(loadStatusForceRefresh, 1000);
				}
				else
				{
					fpbxToast('<i class="fa fa-exclamation-triangle" aria-hidden="true"></i>&nbsp;&nbsp;' + error.msg, '', 'warning');
					form.find(':input(:disabled)').prop('disabled', false);
					switch(error.code)
					{
						case 550:
							$("#ABBServer").focus();
							break;
						case 612:
							$("#ABBUser").focus();
							break;
					}
					boxLoading(false);
					loadStatus();
				}
			});
		}
	});
</script>