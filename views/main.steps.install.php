<div class='container-fluid'>
    <div class='panel panel-primary' id="box-install-synologyabb">
        <div class='panel-heading'>
	        <div class='panel-title'>
                <?php echo _("Installation Wizard") ?>
            </div>
        </div>
        <div class='panel-body' align="center">

            <h2><?php echo _("The Agent by Synology Active Backup for Business is not Installed!"); ?></h2>
            <div class='row'>
                <div class='col-sm-12'>
                    <img src='modules/synologyabb/assets/images/abb_ico_256.png' class='img-responsive'>
                </div>
            </div>
            <div class='row'>
                <div class='col-sm-2'></div>
                <div class='col-sm-8'>
                    <div class="well well-sm box_seach_version">
                        <div class="label_seach_version">
                            <?php echo _("Searching Latest Version...") ?>
                        </div>
                        <div class="autoinstall_output">
                            <ul class="list-group">
                            </ul>
                        </div>
                        <div class="progress">
                            <div class="progress-bar progress-bar-info progress-bar-striped active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%"></div>
                        </div>
                    </div>
                </div>
                <div class='col-sm-2'></div>
            </div>
        </div>
        <div class='panel-footer clearfix'>
            <button type='button' class='pull-right btn btn-default' id='btn_install_now' disabled><?php echo _("Install Now") ?></button>
        </div>
    </div>
</div>

<script type="text/javascript">
    var system_allow_auto_install = <?php echo ($allow_auto_install ? "true" : "false"); ?>;
    var timerRefresInfoAutoInstall;
    var AutoScrollListOutput = true;

    $(document).ready(function()
    {
        var box_area        = $("#box-install-synologyabb");
        var box_progress    = $(".progress", box_area);
        var box_footer      = $(".panel-footer", box_area);
        var box_seach_label = $('.label_seach_version', box_area);
        var box_output      = $('.autoinstall_output', box_area);
        var list_output     = $('ul', box_output);

        $( "#btn_install_now" ).click(function()
        {
            runInstallNow();
        });

        list_output.scroll(function()
        {
            let scrollSiezeAll = list_output.prop("scrollTop") + list_output.prop("offsetHeight");
            let scrollPosition = list_output.prop("scrollHeight");
            if (scrollSiezeAll == scrollPosition)
            {
                AutoScrollListOutput = true;
            }
            else
            {
                AutoScrollListOutput = false;
            }
        });

        <?php if($runing_installation): ?>
        runInstallNow();
        <?php else: ?>
        seachAgentVersionOnline();
        <?php endif; ?>
    });

    function runInstallNow()
    {
        if (! system_allow_auto_install)
        {
            fpbxToast('<?php echo _("The system does not support automatic installation!") ?>', '', 'error');
        }
        else
        {
            timerStop();
            timerStopAutoInstall();
            var box_area        = $("#box-install-synologyabb");
            var box_progress    = $(".progress", box_area);
            var box_footer      = $(".panel-footer", box_area);
            var box_seach_label = $('.label_seach_version', box_area);
            var box_output      = $('.autoinstall_output', box_area);
            var list_output     = $('ul', box_output);

            $("#btn_install_now").hide();
            box_footer.find("button").attr("disabled", true);
            box_progress.show();
            box_output.show();
            list_output.empty();

            AutoScrollListOutput = true;

            var label_text  = '<?php echo _("Please wait..."); ?>';
            box_seach_label.html(label_text);
            runInstallNowStatus();
        }
    }

    function runInstallNowStatus()
    {
        timerStop();
        timerStopAutoInstall();
        var box_area        = $("#box-install-synologyabb");
        var box_progress    = $(".progress", box_area);
        var box_footer      = $(".panel-footer", box_area);
        var box_seach_label = $('.label_seach_version', box_area);
        var box_output      = $('.autoinstall_output', box_area);
        var list_output     = $('ul', box_output);

        var post_data = {
            module	: urlParam_CI('display'),
            command	: 'runautoinstall',
        };
        $.post(window.FreePBX.ajaxurl, post_data, function(data)
        {
            if(!data.status)
            {
                fpbxToast(data.message, '', 'error');
                box_seach_label.html(data.message);
            }
            else
            {
                var info = data.data.info;
                var out = data.data.info.out;

                var status_code = info.status;
                var reload_page = false;
                var label_text  = "";

                switch (status_code)
                {
                    case "":
                    case "INIT":
                        label_text += '<?php echo _("Starting Installation..."); ?>';
                        break;
                        
                    case "DOWNLOADING":
                        label_text += '<?php echo _("Downloading..."); ?>';
                        break;

                    case "DOWNLOADOK":
                        label_text += '<?php echo _("Download Completed Successful"); ?>';
                        break;

                    case "EXTRACTING":
                        label_text += '<?php echo _("Extracting...."); ?>';
                        break;

                    case "EXTRACTOK":
                        label_text += '<?php echo _("Extraction Completed Successful"); ?>';
                        break;
                        
                    case "INSTALLING":
                        label_text += '<?php echo _("Installing..."); ?>';
                        break;

                    case "ENDOK":
                        label_text += '<?php echo _("Installation Completed Successfully"); ?>';
                        reload_page = true;
                        break;

                    case "ENDERROR":
                    case "END":
                    default:
                        label_text += info.status;
                        break;
                }

                // Update List Output
                let numNewItems = 0;
                out.forEach(function(line)
                {
                    let nLine = line.line;
                    let sMsg  = line.msg;
                    let isExisteItem = $('.output_line_'+nLine, list_output).length;
                    if (isExisteItem == 0)
                    {
                        let str_danger   = ['error', 'fatal'];
                        let str_success  = ['done', 'ok!', 'ok.', 'all good.'];
                        let set_type_msg = false;

                        numNewItems ++;
                        let css_custom = " output_line_" + nLine;
                        

                        if (sMsg.toLowerCase().indexOf('kernel headers for this kernel does not seem to be installed.') !== -1)
                        {
                            set_type_msg = true;
                            css_custom += " list-group-item-danger";
                            sMsg += '<br><b><?php echo _('Review the FAQ section of the manual installation process for more information on how to fix this problem.'); ?></b>'
                        }
                        if (! set_type_msg)
                        {
                            $.each( str_danger, function( i, val )
                            {
                                if (sMsg.toLowerCase().indexOf(val) !== -1)
                                {
                                    set_type_msg = true;
                                    css_custom += " list-group-item-danger";
                                    return false;
                                }
                            });
                        }
                        if (! set_type_msg)
                        {
                            $.each( str_success, function( i, val )
                            {
                                if (sMsg.toLowerCase().indexOf(val) !== -1)
                                {
                                    set_type_msg = true;
                                    css_custom += " list-group-item-success";
                                    return false;
                                }
                            });
                        }
                        list_output.append('<li class="list-group-item'+ css_custom +'">' + sMsg + '</li>');
                    }
                });

                // AutoScroll
                if (numNewItems > 0)
                {
                    if (AutoScrollListOutput)
                    {
                        let scrollPosition = list_output.prop("scrollHeight");
                        list_output.scrollTop(scrollPosition);
                    }
                }

                box_seach_label.html(label_text);
                if (reload_page) {
                    location.reload();
                }
                else
                {
                    timerRefresInfoAutoInstall = setTimeout(runInstallNowStatus, 500);
                }
            }
        });

    }

    function timerStopAutoInstall()
    {
        clearTimeout(timerRefresInfoAutoInstall);
    }

    function seachAgentVersionOnline()
    {
        timerStop();
        var box_area        = $("#box-install-synologyabb");
        var box_progress    = $(".progress", box_area);
        var box_footer      = $(".panel-footer", box_area);
        var box_seach_label = $('.label_seach_version', box_area);
        var box_output      = $('.autoinstall_output', box_area);
        var list_output     = $('ul', box_output);

        var tRefresInterval = timerRefresInterval;

        box_footer.find("button").attr("disabled", true);
        box_progress.show();
        box_output.hide();

        var post_data = {
            module	: urlParam_CI('display'),
            command	: 'getagentversiononline',
        };
        $.post(window.FreePBX.ajaxurl, post_data, function(data)
        {
            box_progress.hide();
            if(!data.status)
            {
                fpbxToast(data.message, '', 'error');
                box_seach_label.html(data.message);
            }
            else
            {
                var all_ver     = data.data;
                var last_ver    = Object.keys(all_ver)[0];
                var last_url    = all_ver[last_ver];
                var label_text  = "";

                if (! system_allow_auto_install) {
                    label_text += '<p><?php echo _('The system does not support automatic installation!'); ?></p>';
                    $("#btn_install_now").hide();
                }
                else
                {
                    box_footer.find("button").attr("disabled", false);
                }
                label_text += '<p><?php echo _('Latest version detected:') ?> <a href="' + last_url + '" target="_blank">' + last_ver + '</a></p>';
                box_seach_label.html(label_text);
            }
            boxLoading(false);
            timerRefresInfo = setTimeout(loadStatus, tRefresInterval);
        });
    }
</script>

<?php
    echo load_view(__DIR__ . "/main.steps.install.manual.php");
?>