var timerRefresInfo;

$(document).ready(function()
{
    loadStatus();
});

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
    
            $("#info_server").text(status.server);
            $("#info_user").text(status.user);
            $("#info_lastbackup").text(status.lastbackup);
            $("#info_nextbackup").text(status.nextbackup);
            $("#info_status").text(status.server_status);
            $("#info_portal").attr("href", status.portal);	
		}
        timerRefresInfo = setTimeout(loadStatus, 2000);
	});
}

function timerStop()
{
    clearTimeout(timerRefresInfo);
} 
