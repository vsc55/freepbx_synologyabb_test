<?php
/**
 * 
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * @copyright 2021 Javier Pastor Garcia
 * 
 */
namespace FreePBX\modules;
include __DIR__."/vendor/autoload.php";

class Synologyactivebackupforbusiness extends \FreePBX_Helpers implements \BMO {
	
	public static $default_agent_status_data = array(
        'server' => '',
        'user' => '',
        'lastbackup' => '',
        'nextbackup' => '',
        'server_status' => '',
        'portal' => '',
		'error_code' => -1,
		'error_msg' => '',
		'exec_ret' => -1,
    );

	const STATUS_COMPLETED 		= 100;		//1 Completed 		(Idle - Completed)
	const STATUS_CANCEL			= 150;		//2 Cancel 			(Idle - Canceled)
	const STATUS_BACKUP_RUN		= 300;		//3 Backup en curso (Backing up... - 8.31 MB / 9.57 MB (576.00 KB/s))
	const STATUS_NO_CONNECTION 	= 400;		//4 No conectado 	(No connection found)
	const STATUS_UNKNOWN 		= 99990;	//99990 - status desconocido
	const STATUS_UNKNOWN_IDEL	= 99991;	//99991 - status Idel desconocido
	
	

	public function __construct($freepbx = null) {
		if ($freepbx == null) {
			throw new \Exception("Not given a FreePBX Object");
		}
		$this->FreePBX 	= $freepbx;
		$this->db 		= $freepbx->Database;
		$this->config 	= $freepbx->Config;
		$this->logger 	= $freepbx->Logger()->getDriver('freepbx');
		
		$this->module_name 	=  join('', array_slice(explode('\\', get_class()), -1));
		
		$this->astspooldir 	= $this->config->get("ASTSPOOLDIR");
		$this->asttmpdir 	= $this->getAstSpoolDir() . "/tmp";

		$this->ABBCliVersionMin = "2.2.0-2070";
	}

	public function chownFreepbx() {
		$files = array(
			array('type' => 'execdir', 'path' => __DIR__."/hooks", 'perms' => 0755)
		);
		return $files;
	}

	public function getAstSpoolDir() {
		return $this->astspooldir; 
	}

	public function getAstTmpDir() {
		return $this->asttmpdir;
	}

	public function getABBCliPath() {
		return $this->config->get('SYNOLOGYABFBABBCLI');
	}

	public function getHookFilename($hookname) {
		$return = $this->getAstTmpDir() . "/synology-cli";
		if (! empty($hookname))
		{
			$return .= "-" . $hookname;
		}
		$return .= ".hook";
		return $return;
	}

	public function runHook($hookname, $params = false) {
		// Runs a new style Syadmin hook
		if (!file_exists("/etc/incron.d/sysadmin")) {
			throw new \Exception("Sysadmin RPM not up to date, or not a known OS.");
		}

		$basedir = $this->getAstSpoolDir()."/incron";
		if (!is_dir($basedir)) {
			throw new \Exception("$basedir is not a directory");
		}
		
		// Does our hook actually exist?
		if (!file_exists(__DIR__."/hooks/$hookname")) {
			throw new \Exception("Hook $hookname doesn't exist");
		}
		// So this is the hook I want to run
		
		$filename = "$basedir/". strtolower($this->module_name) .$hookname;

		// If we have a modern sysadmin_rpm, we can put the params
		// INSIDE the hook file, rather than as part of the filename
		if (file_exists("/etc/sysadmin_contents_max")) {
			$fh = fopen("/etc/sysadmin_contents_max", "r");
			if ($fh) {
				$max = (int) fgets($fh);
				fclose($fh);
			}
		} else {
			$max = false;
		}

		if ($max > 65535 || $max < 128) {
			$max = false;
		}

		// Do I have any params?
		$contents = "";
		if ($params) {
			// Oh. I do. If it's an array, json encode and base64
			if (is_array($params)) {
				$b = base64_encode(gzcompress(json_encode($params)));
				// Note we derp the base64, changing / to _, because this may be used as a filepath.
				if ($max) {
					if (strlen($b) > $max) {
						throw new \Exception("Contents too big for current sysadmin-rpm. This is possibly a bug!");
					}
					$contents = $b;
					$filename .= ".CONTENTS";
				} else {
					$filename .= ".".str_replace('/', '_', $b);
					if (strlen($filename) > 200) {
						throw new \Exception("Too much data, and old sysadmin rpm. Please run 'yum update'");
					}
				}
			} elseif (is_object($params)) {
				throw new \Exception("Can't pass objects to hooks");
			} else {
				// Cast it to a string if it's anything else, and then make sure
				// it doesn't have any spaces.
				$filename .= ".".preg_replace("/[[:blank:]]+/", "", (string) $params);
			}
		}

		$fh = fopen($filename, "w+");
		if ($fh === false) {
			// WTF, unable to create file?
			throw new \Exception("Unable to create hook trigger '$filename'");
		}

		// Put our contents there, if there are any.
		fwrite($fh, $contents);

		// As soon as we close it, incron does its thing.
		fclose($fh);

		// Wait for up to 10 seconds and make sure it's been deleted.
		$maxloops = 20;
		$deleted = false;
		while ($maxloops--) {
			if (!file_exists($filename)) {
				$deleted = true;
				break;
			}
			usleep(500000);
		}

		if (!$deleted) {
			throw new \Exception("Hook file '$filename' was not picked up by Incron after 10 seconds. Is it not running?");
		}
		return true;
	}


	public function writeFileHook($file, $data) {
		if (trim($file) == false) {
			return false;
		}
		file_put_contents($file, json_encode($data));
		chown($file, 'asterisk');
		return true;
	}

	public function install() {
		outn(_("Upgrading configs.."));
		$set = array();
		$set['value'] = '/usr/bin/abb-cli';
		$set['defaultval'] =& $set['value'];
		$set['readonly'] = 0;
		$set['hidden'] = 0;
		$set['level'] = 0;
		// $set['module'] = 'synologyactivebackupforbusiness';  //disabled as it generates error, Fix FREEPBX-22756
		$set['category'] = 'Synology Active Backup for Business';
		$set['emptyok'] = 1;
		$set['name'] = 'Path for abb-cli';
		$set['description'] = 'The default path to abb-cli. overwrite as needed.';
		$set['type'] = CONF_TYPE_TEXT;
		$this->config->define_conf_setting('SYNOLOGYABFBABBCLI', $set, true);
		out(_("Done!"));
	}
	public function uninstall() {}
	
	public function backup() {}
	public function restore($backup) {}
	
	
	public function doConfigPageInit($page) {}
	
	public function getActionBar($request) {}
	
	public function getRightNav($request) {}
	
	public function showPage($page, $params = array()) {
		$page = trim($page);
		$page_show = '';
		$data = array(
			"syno" 		=> $this,
			'request'	=> $_REQUEST,
			'page'		=> $page
		);
		$data = array_merge($data, $params);
		
		switch ($page)
		{
			case "":
				$page_show = 'main';
				break;

			default:
				$page_show = $page;
		}

		if (! empty($page_show))
		{
			//clean up possible things that don't have to be here
			$filename = strtolower(str_ireplace(array('..','\\','/'), "", $page_show));

			$page_path = sprintf(_("%s/views/%s.php"), __DIR__, $filename);
			if (! file_exists($page_path))
			{
				$page_show = '';
			}
			else
			{
				$data_return = load_view($page_path, $data);
			}
		}
		
		if (empty($page_show))
		{
			$data_return = sprintf(_("Page Not Found (%s)!!!!"), $page);
		}
		return $data_return;
	}

	public function ajaxRequest($req, &$setting) {
		// ** Allow remote consultation with Postman **
		// ********************************************
		$setting['authenticate'] = false;
		$setting['allowremote'] = true;
		return true;
		// ********************************************
		switch($req)
		{
			case "getagentversion":
			case "getagentstatus":
				return true;
				break;

			default:
				return false;
		}
		return false;
	}

	public function ajaxHandler() {
		$command = $this->getReq("command", "");
		switch ($command)
		{
			case 'getagentversion':
				return array("status" => true, "data" => $this->getAgentVersion());
				break;

			case 'getagentstatus':
					return array("status" => true, "data" => $this->getAgentStatus());
					break;

			default:
				return array("status" => false, "message" => _("Command not found!"), "command" => $command);
		}
	}
	

	public function isAgentInstalled() {
		return file_exists($this->getABBCliPath());
	}

	public function isAgentVersionOk() {
		$version_minimal = $this->ABBCliVersionMin;
		$version_installed = $this->getAgentVersion();
		return version_compare($version_minimal, $version_installed, '<=');
	}

	public function getAgentStatusDefault() {
		return self::$default_agent_status_data;
	}


	public function getAgentStatus() {
		//1 Completed
		//2 Cancel
		//3 Backup en curso
		//4 No conectado
		//99990 - status desconocido
		//99991 - status Idel desconocido


		$return = $this->getAgentStatusDefault();

		$file = $this->getHookFilename("status");
		$this->runHook("get-cli-status");
		if(file_exists($file))
		{
			$linesfilehook = file_get_contents($file);
			// unlink($file);

			if (empty($linesfilehook))
			{
				$return['error_code'] = 511;
				$return['error_msg'] = _("Hook file is emtry!");
			}
			else
			{
				$return = @json_decode($linesfilehook, true);
				$return['lastbackup_date'] = \DateTime::createFromFormat('Y-m-d H:i', $return['lastbackup']);
				$return['nextbackup_date'] = \DateTime::createFromFormat('Y-m-d H:i', $return['nextbackup']);

				$return['html']['body'] = "";
				$return['html']['force'] = false;

				$t_status_info = preg_split('/[-]+/', $return['server_status']);
				$t_status_info = array_map('trim', $t_status_info);	//Trim All Elements Array
				
				switch (strtolower($t_status_info[0]))
				{
					case strtolower("Idle"):
						switch (strtolower($t_status_info[1]))
						{
							case strtolower("Completed"):
								// Idle - Completed
								$return['info_status'] = array ('status_code' => 1, 'status' => _("Completed"));
								break;

							case strtolower("Canceled"):
								//Idle - Canceled
								$return['info_status'] = array ('status_code' => 2, 'status' => _("Canceled"));
								break;

							default:
								$return['info_status'] = array ('status_code' => 99991, 'status' => _("Status Unknown"));
						}
						break;

					case strtolower("Backing up..."):
						// Backing up... - 8.31 MB / 9.57 MB (576.00 KB/s)

						$t_status_info['progress'] = preg_split('/[\(\)]+/', $t_status_info[1]);
						$t_status_info['progress'] = array_map('trim', $t_status_info['progress']);

						$t_status_info['progressdata'] = preg_split('/[\/]+/', $t_status_info['progress'][0]);
						$t_status_info['progressdata'] = array_map('trim', $t_status_info['progressdata']);
						
						$return['info_status'] = array(
							'status'		=> _("BackingUp"),
							'status_code'	=> 3,
							'progress'		=> array(
								'all' 	=> $t_status_info[1],
								'send' 	=> \ByteUnits\parse($t_status_info['progressdata'][0])->numberOfBytes(),
								'total' => \ByteUnits\parse($t_status_info['progressdata'][1])->numberOfBytes(),
								'speed' => $t_status_info['progress'][1],
							)
						);
						break;

					case strtolower("No connection found"):
						$return['info_status'] = array ('status_code' => 4, 'status' => _("NoConnection"));
						$return['html']['body'] = $this->showPage("main.body.login");
						//TODO: Debug!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
						$return['html']['force'] = true;
						break;

					default:
						$return['info_status'] = array ('status_code' => 99990, 'status' => _("Status Unknown"));
				}

				if ($return['info_status']['status_code'] >= 99990 )
				{
					$this->logger->warning( sprintf("Synology ABB / getAgentStatus - Warning Code (%s): Status not controlled [%s]", $return['info_status']['status_code'], $return['server_status']) );
				}

				$return['error_code'] = 0;
			}
		}
		else
		{
			$return['error_code'] = 510;
			$return['error_msg'] = _("The file that returns the hook information does not exist!");
		}

		if ($return['error_code'] != 0)
		{
			$this->logger->error( sprintf("Synology ABB / getAgentStatus - Error Code (%s): %s", $return['error_code'], $return['error_msg']) ) ;
		}
		
		$return['agent_version'] = $this->getAgentVersion();

		return $return;
	}


	public function getAgentVersion($return_array = false) {
		$return = "";
		$error_code = -1;
		
		$file = $this->getHookFilename("version");
		$this->runHook("get-cli-version");
		if(! file_exists($file))
		{
			$error_code = 510;
		}
		else
		{
			$linesfilehook = file_get_contents($file);
			// unlink($file);

			if (trim($linesfilehook) == false)
			{
				$error_code = 515;
			}
			else
			{
				$hook_info = @json_decode($linesfilehook, true);
				if ($hook_info['error']['code'] != 0)
				{
					$error_code = $hook_info['error'];
				}
				else
				{
					$app_info = $hook_info['app'];
					$app_ver = $app_info['version'];
					$app_ver .= (empty($app_ver) ? "0.0.0-0" : "");
					$return = $app_ver;

					if ($return_array)
					{
						$app_ver_array = explode(".", str_replace("-", ".",  $app_ver));
						$return = array(
							'major' => $app_ver_array[0],
							'minor' => $app_ver_array[1],
							'patch' => $app_ver_array[2],
							'build' => $app_ver_array[3],
							'full' => $app_ver,
						);
					}
					$error_code = 0;
				}
			}
		}

		
		if (! is_array($error_code))
		{
			$error_code = $this->getErrorMsgByErrorCode($error_code, true);
		}
		if ($error_code['code'] != 0) 
		{
			$this->logger->error( sprintf("%s->%s - Code (%s): %s", $this->module_name, __FUNCTION__, $error_code['code'], $error_code['msg']));
		}
		return $return;
	}


	public function getErrorMsgByErrorCode($error_code, $return_array = false) {
		$msg = "";
		switch($error_code)
		{
			case -1:
				$msg = _("Nothing has been defined yet.");
				break;

			case 0:
				$msg = _("No mistake, everything ok.");
				break;

			case 501:
				$msg = _("Synology Agent not Installed!");
				break;

			case 502:
				$msg = _("Synology Agent not return info!");
				break;

			case 503:
				$msg = _("Synology Agent ended in error!");
				break;

			case 504:
				$msg = _("Synology Agent returned uncontrolled information!");
				break;

			case 510:
				$msg = _("The file that returns the hook information does not exist!");
				break;

			case 515:
				$msg = _("Hook file is empty!");
				break;

			case -2:
			default:
				$msg =  sprintf(_("Unknown error (%s)!"), $error_code);
				$error_code = -2;
				break;
		}

		return ($return_array ? array( 'code' => $error_code, 'msg' => $msg ) : $msg);
	}


}