<button type="button" class="btn btn-info btn-block btn-lg" data-toggle="modal" data-target="#dialogManualInstall">
    <i class="fa fa-info-circle" aria-hidden="true"></i> <?php echo _("Process Manual Installation") ?>
</button>

<div class="modal fade" id="dialogManualInstall" tabindex="-1" role="dialog" aria-labelledby="dialogManualInstallTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h5 class="modal-title" id="dialogManualInstallTitle"><?php echo _("Synology Active Backup for Business Agent Installation Guide") ?></h5>
            </div>
            <div class="modal-body">

                <h3><?php echo _("Introduction") ?></h3>
                <p><?php echo _("The installation file contains Linux snapshot driver and the Linux backup service.") ?></p>
                <br>

                <h3><?php echo _("System requirement") ?></h3>
                <h4><?php echo _("Linux distributions:") ?></h4>
                <ul>
                    <li>CentOS: 6.10, 7.8, 8.1 (rpm)</li>
                    <li>RHEL: 6.10, 7.8, 8.1 (rpm)</li>
                    <li>Fedora: 30, 31, 32 (rpm)</li>
                    <li>Ubuntu: 16.04, 18.04, 20.04 (deb)</li>
                    <li>Debian: 8.0 to 10 (deb)</li>
                </ul>
                <br>
                <h4><?php echo _("Required components on the target device:") ?></h4>
                <ul>
                    <li><?php echo _('If you are on CentOS8, "kernel-headers-$(uname -r)" is needed, otherwise "kernel-devel-$(uname -r)"') ?></li>
                    <li>make 4.1 <?php echo _("version or above") ?></li>
                    <li>libaio 0.3.110 <?php echo _("version or above") ?></li>
                    <li>dkms 2.2.0.3 <?php echo _("version or above") ?></li>
                    <li>gcc 4.8.2 <?php echo _("version or above") ?></li>
                </ul>
                <p><?php echo _("For Linux servers without internet connection, you will need to download the required components and install them before running the installation script.") ?></p>
                <p><?php echo _("For Linux server with internet connection, the required components will be installed automatically by running the installation script.") ?></p>
                <br>

                <hr><br>

                <h3><?php echo _("Install Active Backup for Business Linux Agent") ?></h3>
                <p><?php echo _('Download the agent from <a href="https://archive.synology.com/download/Utility/ActiveBackupBusinessAgent" target="_blank">here</a>') ?></p>
                <p><?php echo _("Enter command line to install Linux snapshot driver and Linux backup service. Administrator role is required to execute the command.") ?></p>
                <p><?php echo _("Please switch the role to root before execution.") ?></p>
                <p>`> sudo ./install.run `</p>
                <br>

                <h3><?php echo _("Installation complete") ?></h3>
                <p><?php echo _("After the installation is complete, please type `abb-cli -c` to connect to Synology NAS and create the backup task.") ?></p>
                <p><?php echo _("To learn more commands about Active Backup for Business Linux Agent, please type `abb-cli -h`.") ?></p>
                <br>

                <hr><br>

                <h3><?php echo _("Uninstall the agent") ?></h3>
                <p><?php echo _('Type "yum remove synology-active-backup-business-linux-service" to uninstall the backup service.') ?></p>
                <p><?php echo _('Type "yum remove synosnap" to uninstall the driver.') ?></p>
                <br>

                <hr><br>

                <h3><?php echo _("F.A.Q.") ?></h3>

                <h4><b><?php echo _("Problem:") ?></b></h4>
                <p><i><?php echo _("In the installation process the compilation of the kernel module fails, the following error is seen: In kernel headers for this kernel does not seem to be installed.") ?></i></p>
                <br>
                <h4><b><?php echo _("Solution:") ?></b></h4>
                <p><?php echo _("This error has been presented to me in a FreePBX distribution (Sangoma Linux release 7.8.2003 (Core)) and this is the solution that I have found.") ?></p>
                <br>

                <h5><?php echo _("Step 1") ?></h5>
                <?php echo _("The first thing is to check that we have kernel-devel installed with:") ?><br>
                &emsp;&emsp;<i><b># yum install kernel-devel</b></i><br>
                <br>
                <?php echo _("Another way to check if it is installed is to look at the directory where the kernels are installed, it should look something like this:") ?><br>
                &emsp;&emsp;<i><b># ls -la /usr/src/kernels/</b></i><br>
                &emsp;&emsp;<i><b>total 4</b></i><br>
                &emsp;&emsp;<i><b>drwxrwxrwx.  3 root root   41 Aug 19  2021 .</b></i><br>
                &emsp;&emsp;<i><b>drwxrwxrwx.  8 root root  157 Jul 22 17:35 ..</b></i><br>
                &emsp;&emsp;<i><b>drwxr-xr-x  22 root root 4096 Jul 22 17:37 3.10.0-1127.19.1.el7.x86_64</b></i><br>
                <br>

                <h5><?php echo _("Step 2") ?></h5>
                <?php echo _('If all this is correct we will check if the symbolic link of <b><i>"/lib/modules/{version}/build"</i></b> exists.') ?><br>
                <?php echo _("On my system that file did not exist and because of this the kernel module that has to be compiled during the installation process shows the error message.") ?><br>
                <?php echo _("We create the symbolic link:") ?><br>
                &emsp;&emsp;</i><b># cd /lib/modules/$(uname -r)/</b></i><br>
                &emsp;&emsp;</i><b># ln -s /usr/src/kernels/$(uname -r)/ build</b></i><br>
                <br>

                <h5><?php echo _("Step 3") ?></h5>
                <?php echo _("Since the module build process failed, trying to reinstall the update will still cause build issues.") ?><br>
                <?php echo _('To solve this, before starting the update process we must delete <b><i>"/usr/src/synosnap-{version}/"</i></b>, or do a <b><i>"make clean"</i></b> inside the directory <b><i>"/usr/src/synosnap-{version}/"</i></b>') ?>.<br>
                <h5>&emsp;&emsp;<?php echo _("Opcion 1:") ?></h5>
                &emsp;&emsp;&emsp;&emsp;</i><b># rm -fr /usr/src/synosnap-*</b></i><br>
                <br>

                <h5>&emsp;&emsp;<?php echo _("Opcion 2:") ?></h5>
                &emsp;&emsp;&emsp;&emsp;</i><b># cd /usr/src/synosnap-{version}/</b></i><br>
                &emsp;&emsp;&emsp;&emsp;</i><b># make clean</b></i><br>
                <br>

                <h5><?php echo _("Step 4") ?></h5>
                <?php echo _("Once we have created this, we start the installation/update process again.") ?><br>
                <br>

                <hr>
                <br>
                <p><b><?php echo _('More info click <a href="https://kb.synology.com/en-global/DSM/help/ActiveBackupBusinessAgent/activebackupbusinessagent?version=7" target="_blank">here</a>') ?></b></p>

            </div>
        </div>
    </div>
</div>
