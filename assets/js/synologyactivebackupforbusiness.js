var timerRefresInfo;
var timerRefresInterval;
var lastStatusCode;

$(document).ready(function()
{
	$(window).resize(function() { box_resize(); });

	// Detect when the global banner closes and run resize.
	// Mitigation of "offset().top" change detection problem.
	$("#page_body > .global-message-banner").on("remove", function () { box_resize(); });


	timerRefresInterval = 5000;
	box_resize();
    loadStatus();
});


function box_resize()
{
	var box_area = $("#synologyabb-panel");
	var new_size = ($(window).height() - $('#footer').height() - $('div.panel-heading', box_area).outerHeight(true) - $('div.panel-footer', box_area).outerHeight(true)  - $('div.panel-body', box_area).offset().top);
	$('div.panel-body', box_area).css({
		'min-height': new_size,
		'max-height': new_size
	});
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
			if (status.error_code != 0)
			{
				$('div.panel-body', box_area).hide();
				$('div.panel-footer', box_area).hide();

				$('div.panel-body', box_area).html("");
				$('div.panel-footer', box_area).html("");

				$('div.panel', box_area).removeClass("panel-danger panel-success panel-warning").addClass( "panel-danger" );
				$('div.panel-heading, div.panel-title', box_area).html("<b>Error: " + status.error_msg + "</b>");
			
				lastStatusCode = undefined;
			}
			else
			{
				var status_code = status.info_status.status_code;
				//1 Completed
				//2 Cancel
				//3 Backup en curso
				//4 No conectado
				//99990 - status desconocido
				//99991 - status Idel desconocido

				switch (status_code)
				{
					case 1:
					case 2:
					case 3:
						$('div.panel', box_area).removeClass("panel-danger panel-success panel-warning").addClass( "panel-success" );

						$('div.panel-heading, div.panel-title', box_area).html("<b>Server:</b> " + status.server);

						$('div.panel-body', box_area).html("").show();
						$('div.panel-footer', box_area).html("").show();
						break;

					case 4:
						$('div.panel', box_area).removeClass("panel-danger panel-success panel-warning").addClass( "panel-warning" );
						$('div.panel-heading, div.panel-title', box_area).html("<b>Warning Status: " + status.server_status + "</b>");

						if (lastStatusCode != status_code || status.html.force == true)
						{
							$('div.panel-body', box_area).html(status.html.body);
						}
						$('div.panel-body', box_area).show();
						
						$('div.panel-footer', box_area).html("<b>Agent Version: " + status.agent_version + "</b>").show();
						break;

					default:
						$('div.panel', box_area).removeClass("panel-danger panel-success panel-warning").addClass( "panel-warning" );
						$('div.panel-heading, div.panel-title', box_area).html("<b>Warning Status: " + status.server_status + "</b>");
						
						$('div.panel-body', box_area).html("").hide();

						$('div.panel-footer', box_area).html("<b>Agent Version: " + status.agent_version + "</b>").show();
						break;
				}
				lastStatusCode = status_code;
			}
			

            // $("#info_server").text(status.server);
            // $("#info_user").text(status.user);
            // $("#info_lastbackup").text(status.lastbackup);
            // $("#info_nextbackup").text(status.nextbackup);
            // $("#info_status").text(status.server_status);
            // $("#info_portal").attr("href", status.portal);	
		}
		box_resize();
        timerRefresInfo = setTimeout(loadStatus, timerRefresInterval);
	});
}

function timerStop()
{
    clearTimeout(timerRefresInfo);
} 
