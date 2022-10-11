var timerRefresInfo;
var timerRefresInterval;
var timerRefresIntervalRun;
var lastCheckCode;

const SynologyABB_STATUS = {
	STATUS_NULL: -1,
	STATUS_IDLE: 110,
	STATUS_IDLE_COMPLETED: 120,
	STATUS_IDLE_CANCEL: 130,
	STATUS_IDLE_FAILED:140,
	STATUS_BACKUP_RUN: 300,
	STATUS_NO_CONNECTION: 400,
	STATUS_ERR_DEV_REMOVED: 510,
	STATUS_UNKNOWN: 99990,
	STATUS_IDLE_UNKNOWN: 99991,
	STATUS_ERR_UNKNOWN: 99992,
};

const SynologyABB_ERROR = {
	ERROR_UNKNOWN: -2,
	ERROR_NOT_DEFINED: -1,
	ERROR_ALL_GOOD: 0,
	ERROR_AGENT_NOT_INSTALLED: 501,
	ERROR_AGENT_NOT_RETURN_INFO: 502,
	ERROR_AGENT_ENDED_IN_ERROR: 503,
	ERROR_AGENT_RETURN_UNCONTROLLED: 504,
	ERROR_AGENT_ALREADY_CONNECTED: 520,
	ERROR_AGENT_NOT_ALREADY_CONNECTED: 521,
	ERROR_AGENT_SERVER_CHECK: 550,
	ERROR_AGENT_SERVER_AUTH_FAILED: 611,
	ERROR_AGENT_SERVER_AUTH_FAILED_USER_PASS: 612,
	ERROR_AGENT_SERVER_AUTH_FAILED_BAN_IP: 613,
	ERROR_MISSING_ARGS: 650,
	ERROR_HOOK_FILE_NOT_EXIST: 710,
	ERROR_HOOK_FILE_EMTRY: 715,
	ERROR_HOOK_FILE_TOEKN: 720,
	ERROR_HOOK_RUN_TIMEOUT: 725,
};

$(document).ready(function()
{
	$(window).resize(function() { box_resize(); });

	// Detect when the global banner closes and run resize.
	// Mitigation of "offset().top" change detection problem.
	$("#page_body > .global-message-banner").on("remove", function () { box_resize(); });

	timerRefresInterval 	= 5000;
	timerRefresIntervalRun 	= 1000;
	box_resize();
    loadStatus();
});

// Obtain URL parameter in case insensitive
// Source: https://stackoverflow.com/questions/24395838/how-to-obtain-url-parameter-using-jquery-in-case-insensitive-way
function urlParam_CI(parm)
{
	var str = window.location.href;
	var rgx = new RegExp('\\b' + parm + '=.*\\b', 'gi');

	//this gets an array of matches
	var aMatches = str.match(rgx);
	if (aMatches == null) return;
	var parmVal = aMatches[0].substring(parm.length + 1);

	//we shouldnt, but make sure there are not trailing parms
	var idx = parmVal.indexOf('&');
	//alert('amp:' + idx);
	if (idx > -1) parmVal = parmVal.substring(0, idx);
	return parmVal;
}

function box_resize()
{
	return;
	var box_area = $("#synologyabb-panel");
	var new_size = ($(window).height() - $('#footer').height() - $('div.panel-heading', box_area).outerHeight(true) - $('div.panel-footer', box_area).outerHeight(true)  - $('div.panel-body', box_area).offset().top);
	$('div.panel-body', box_area).css({
		'min-height': new_size,
		'max-height': new_size
	});
}


function loadStatusForceRefresh(e)
{
	timerStop();
	lastCheckCode = null;
	loadStatus(e);
}


function loadStatus(e)
{
    timerStop();
	if (e != undefined)
	{
		e.preventDefault();
	}
	var post_data = {
		module	: urlParam_CI('display'),
		command	: 'getagentstatus',
	};
	$.post(window.FreePBX.ajaxurl, post_data, function(data) 
	{
		if(!data.status)
		{
			fpbxToast(data.message, '', 'error');
		}
		else
		{
			if (e != undefined)
			{
				fpbxToast(data.message, '', 'success' );
			}

            var status = data.data;

			var box_area = $("#synologyabb-panel");
			var error_code = status.error.code;
			var status_code = undefined;
			var tRefresInterval = timerRefresInterval;

			if ( 'info_status' in status && 'code' in  status.info_status )
			{
				status_code = status.info_status.code;
			}

			var check_code = error_code !== SynologyABB_ERROR['ERROR_ALL_GOOD'] || typeof status_code === undefined ? "E" + error_code : "S" + status_code;

			if (lastCheckCode !== check_code || status.html.force == true)
			{
				if (lastCheckCode === check_code)
				{
					$(box_area).find('div[class=panel-body]').html($($.parseHTML(status.html.body)).find('div[class=panel-body]').html());
					boxLoading(false);
					
					$('.modal-backdrop').remove(); // Fix: modal-backdrop not remove is modal is open
				}
				else
				{
					$('div:first-child', box_area).hide("fast", function()
					{
						$(box_area).html(status.html.body);
						$('div:first-child', box_area).show("fast");
						boxLoading(false);
						
						$('.modal-backdrop').remove(); // Fix: modal-backdrop not remove is modal is open
					});
				}

				if (error_code === SynologyABB_ERROR['ERROR_ALL_GOOD'])
				{
					switch (status_code)
					{
						case SynologyABB_STATUS['STATUS_IDLE']:
						case SynologyABB_STATUS['STATUS_IDLE_COMPLETED']:
						case SynologyABB_STATUS['STATUS_IDLE_CANCEL']:
						case SynologyABB_STATUS['STATUS_IDLE_FAILED']:
							break;

						case SynologyABB_STATUS['STATUS_NO_CONNECTION']:
						case SynologyABB_STATUS['STATUS_ERR_DEV_REMOVED']:
							break;

						case SynologyABB_STATUS['STATUS_BACKUP_RUN']:
							// When the copy is running the data is read at a shorter interval.
							tRefresInterval = timerRefresIntervalRun;
							break;

						default:
							// const STATUS_NULL			= -1;		// No state has been defined.
							// const STATUS_UNKNOWN 		= 99990;	// 99990 - unknown status
							// const STATUS_UNKNOWN_IDEL	= 99991;	// 99991 - Idel status unknown
							// const STATUS_ERR_UNKNOWN		= 99992;	// 99992 - error status unknown

							break;
					}
					// $('div.panel-version', box_area).html("<b>Agent Version: " + status.agent_version.full + "</b>");
				}
			}
			lastCheckCode = check_code;
		}
		box_resize();
        timerRefresInfo = setTimeout(loadStatus, tRefresInterval);
	});
}

function timerStop()
{
    clearTimeout(timerRefresInfo);
}

function boxLoading(status)
{
	if (status == true)
    {
        $("#box_loading").show();
    }
	else
	{
		$("#box_loading").hide();
	}
}