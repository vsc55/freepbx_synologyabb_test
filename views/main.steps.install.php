# Synology Active Backup for Business Agent README and Installation Guide<br>
<br>
Copyright (c) 2020 Synology Inc. All rights reserved.<br>
<br>
## Introduction<br>
<br>
The installation file contains Linux snapshot driver and the Linux backup service. <br>
<br>
## System requirement<br>
<br>
### Linux distributions:<br>
* CentOS: 6.10, 7.8, 8.1<br>
* RHEL: 6.10, 7.8, 8.1<br>
* Fedora: 30, 31, 32<br>
<br>
### Required components on the target device:<br>
<br>
* if you are on CentOS8, `kernel-headers-$(uname -r)` is needed, otherwise `kernel-devel-$(uname -r)`<br>
* make 4.1 version or above<br>
* libaio 0.3.110 version or above<br>
* dkms 2.2.0.3 version or above<br>
* gcc 4.8.2 version or above<br>
<br>
For Linux servers without internet connection, you will need to download the required components and install them before running the installation script. <br>
<br>
For Linux server with internet connection, the required components will be installed automatically by running the installation script. <br>
<br>
## Install Active Backup for Business Linux Agent<br>
<br>
Enter command line to install Linux snapshot driver and Linux backup service. Administrator role is required to execute the command.<br>
<br>
Please switch the role to root before execution.<br>
<br>
`> sudo ./install.run `<br>
<br>
## Installation complete<br>
<br>
After the installation is complete, please type `abb-cli -c` to connect to Synology NAS and create the backup task.<br>
To learn more commands about Active Backup for Business Linux Agent, please type `abb-cli -h`. <br>
<br>
## Uninstall the agent<br>
<br>
Type "yum remove synology-active-backup-business-linux-service" to uninstall the backup service.<br>
Type "yum remove synosnap" to uninstall the driver.<br>