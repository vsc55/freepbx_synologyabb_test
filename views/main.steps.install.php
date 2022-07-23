<h2>Synology Active Backup for Business Agent README and Installation Guide</h2>
<p>Copyright (c) 2020 Synology Inc. All rights reserved.</p>
<br>

<hr><br>

<h3>Introduction</h3>
<p>The installation file contains Linux snapshot driver and the Linux backup service.</p>
<br>

<h3>System requirement</h3>
<h4>Linux distributions:</h4>
<ul>
    <li>CentOS: 6.10, 7.8, 8.1 (rpm)</li>
    <li>RHEL: 6.10, 7.8, 8.1 (rpm)</li>
    <li>Fedora: 30, 31, 32 (rpm)</li>
    <li>Ubuntu: 16.04, 18.04, 20.04 (deb)</li>
    <li>Debian: 8.0 to 10 (deb)</li>
</ul>
<br>
<h4>Required components on the target device:</h4>
<ul>
    <li>if you are on CentOS8, `kernel-headers-$(uname -r)` is needed, otherwise `kernel-devel-$(uname -r)`</li>
    <li>make 4.1 version or above</li>
    <li>libaio 0.3.110 version or above</li>
    <li>dkms 2.2.0.3 version or above</li>
    <li>gcc 4.8.2 version or above</li>
</ul>
<p>For Linux servers without internet connection, you will need to download the required components and install them before running the installation script.</p>
<p>For Linux server with internet connection, the required components will be installed automatically by running the installation script.</p>
<br>

<hr><br>

<h3>Install Active Backup for Business Linux Agent</h3>
<p>Download the agent from <a href="https://archive.synology.com/download/Utility/ActiveBackupBusinessAgent" target="_blank">here</a></p>
<p>Enter command line to install Linux snapshot driver and Linux backup service. Administrator role is required to execute the command.</p>
<p>Please switch the role to root before execution.</p>
<p>`> sudo ./install.run `</p>
<br>

<h3>Installation complete</h3>
<p>After the installation is complete, please type `abb-cli -c` to connect to Synology NAS and create the backup task.</p>
<p>To learn more commands about Active Backup for Business Linux Agent, please type `abb-cli -h`.</p>
<br>

<hr><br>

<h3>Uninstall the agent</h3>
<p>Type "yum remove synology-active-backup-business-linux-service" to uninstall the backup service.</p>
<p>Type "yum remove synosnap" to uninstall the driver.</p>
<br>

<hr><br>

<h3>F.A.Q.</h3>

<p><b>Problem:</b><br>
<i>In the installation process the compilation of the kernel module fails, the following error is seen: In kernel headers for this kernel does not seem to be installed.</i></p>

<p><b>Solution:</b><br>
This error has been presented to me in a FreePBX distribution (Sangoma Linux release 7.8.2003 (Core)) and this is the solution that I have found.<br>
<br>
The first thing is to check that we have kernel-devel installed with:<br>
<i><b># yum install kernel-devel</b></i><br>
Another way to compare if it is installed is by looking at the directory where the kernels are installed, something like this should come out:<br>
<i><b># ls -la /usr/src/kernels/<br>
total 4<br>
drwxrwxrwx.  3 root root   41 Aug 19  2021 .<br>
drwxrwxrwx.  8 root root  157 Jul 22 17:35 ..<br>
drwxr-xr-x  22 root root 4096 Jul 22 17:37 3.10.0-1127.19.1.el7.x86_64</b></i><br>
<br>
If all this is correct we will check if the symbolic link of "/lib/modules/{version}/build" exists.<br>
On my system that file did not exist and because of this the kernel module that has to be compiled during the installation process shows the error message.<br>
We create the symbolic link:<br>
</i><b># cd /usr/src/kernels/$(uname -r)/<br>
# ln -s /usr/src/kernels/$(uname -r)/ build</b></i><br>
<br>
Once we have created this, we start the installation/update process again.</p>


<hr><br>

<p><b>More info click <a href="https://kb.synology.com/en-global/DSM/help/ActiveBackupBusinessAgent/activebackupbusinessagent?version=7" target="_blank">here</a></b></p>