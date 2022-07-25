var timerRefresInfo;
var timerRefresInterval;
var timerRefresIntervalRun;
var lastCheckCode;

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
		module	: 'synologyactivebackupforbusiness',
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
			
			// console.log(data.data);

			var box_area = $("#synologyabb-panel");
			var error_code = status.error.code;
			var status_code = undefined;
			var tRefresInterval = timerRefresInterval;

			if ( 'info_status' in status && 'code' in  status.info_status )
			{
				status_code = status.info_status.code;
			}

			var check_code = error_code !== 0 || typeof status_code === undefined ? "E" + error_code : "S" + status_code;

			if (lastCheckCode !== check_code || status.html.force == true)
			{
				if (lastCheckCode === check_code)
				{
					$(box_area).find('div[class=panel-body]').html($($.parseHTML(status.html.body)).find('div[class=panel-body]').html());
					boxLoading(false);
				}
				else
				{
					$('div:first-child', box_area).hide("fast", function()
					{
						$(box_area).html(status.html.body);
						$('div:first-child', box_area).show("fast");
						boxLoading(false);
					});
				}

				if (error_code === 0)
				{
					// const STATUS_COMPLETED 		= 100;		//1 Completed 		(Idle - Completed)
					// const STATUS_CANCEL			= 150;		//2 Cancel 			(Idle - Canceled)
					// const STATUS_BACKUP_RUN		= 300;		//3 Backup en curso (Backing up... - 8.31 MB / 9.57 MB (576.00 KB/s))
					// const STATUS_NO_CONNECTION 	= 400;		//4 No conectado 	(No connection found)
					// const STATUS_UNKNOWN 		= 99990;	//99990 - status desconocido
					// const STATUS_UNKNOWN_IDEL	= 99991;	//99991 - status Idel desconocido

					switch (status_code)
					{
						case 100:
						case 150:
						case 400:
							break;

						case 300:
							// When the copy is running the data is read at a shorter interval.
							tRefresInterval = timerRefresIntervalRun;
						default:
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
		$("#box_loading").hide()
	}
}