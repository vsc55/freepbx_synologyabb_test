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
		'error' => array(),
		'exec' => array(),
    );

	const STATUS_NULL			= -1;
	const STATUS_COMPLETED 		= 100;		//1 Completed 		(Idle - Completed)
	const STATUS_CANCEL			= 150;		//2 Cancel 			(Idle - Canceled)
	const STATUS_BACKUP_RUN		= 300;		//3 Backup en curso (Backing up... - 8.31 MB / 9.57 MB (576.00 KB/s))
	const STATUS_NO_CONNECTION 	= 400;		//4 No conectado 	(No connection found)
	const STATUS_UNKNOWN 		= 99990;	//99990 - status desconocido
	const STATUS_UNKNOWN_IDEL	= 99991;	//99991 - status Idel desconocido

	const ERROR_UNKNOWN = -2;
	const ERROR_NOT_DEFINED = -1;
	const ERROR_ALL_GOOD = 0;
	const ERROR_AGENT_NOT_INSTALLED = 501;
	const ERROR_AGENT_NOT_RETURN_INFO = 502;
	const ERROR_AGENT_ENDED_IN_ERROR = 503;
	const ERROR_AGENT_RETURN_UNCONTROLLED = 504;
	const ERROR_AGENT_SERVER_CHECK_ERROR = 505;
	const ERROR_HOOK_FILE_NOT_EXIST = 510;
	const ERROR_HOOK_FILE_EMTRY = 515;

	const DEFAULT_PORT = 5510;	// Default port Active Backup for Business Server
	
	

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
		
		$filename = sprintf("%s/%s.%s", "$basedir", strtolower($this->module_name), $hookname);
		if (file_exists($filename)) {
			throw new \Exception("Hook $hookname is already running");
		}

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

	private function runHookCheck($hook_file, $hook_run, $hook_params = array(), $decode = true) {
		$error_code = self::ERROR_NOT_DEFINED;
		$hook_info 	= null;
		$file 		= $this->getHookFilename($hook_file);

		$hook_params['hook_file'] = $hook_file;

		$this->runHook($hook_run, $hook_params);
		if(! file_exists($file))
		{
			$error_code = self::ERROR_HOOK_FILE_NOT_EXIST;
		}
		else
		{
			$linesfilehook = file_get_contents($file);
			unlink($file);

			if (trim($linesfilehook) == false)
			{
				$error_code = self::ERROR_HOOK_FILE_EMTRY;
			}
			else
			{
				$hook_info = @json_decode($linesfilehook, true);
				$error_code = ($hook_info['error']['code'] === self::ERROR_ALL_GOOD ? self::ERROR_ALL_GOOD : $hook_info['error']['code']);
				if (! $decode)
				{
					$hook_info = $linesfilehook;
				}
			}
		}

		return array(
			'hook_file' => $hook_file,
			'hook_run' 	=> $hook_run,
			'hook_data'	=> $hook_info,
			'file' 		=> $file,
			'decode'	=> $decode,
			'error' 	=> $error_code,
		);
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
		$data_return = false;
		switch ($command)
		{
			case 'getagentversion':
				$data_return = array("status" => true, "data" => $this->getAgentVersion(true, false));
				break;

			case 'getagentstatus':
				$status_info = $this->getAgentStatus();
				$status_info['agent_version'] = $this->getAgentVersion(true, false);

				$data_return = array("status" => true, "data" => $status_info);
				break;

			default:
				$data_return = array("status" => false, "message" => _("Command not found!"), "command" => $command);
		}
		return $data_return;
	}
	

	public function isAgentInstalled() {
		return file_exists($this->getABBCliPath());
	}

	public function isAgentVersionOk() {
		$version_minimal = $this->ABBCliVersionMin;
		$version_installed = $this->getAgentVersion(true);
		return version_compare($version_minimal, $version_installed['full'], '<=');
	}

	public function getAgentStatusDefault() {
		return self::$default_agent_status_data;
	}


	public function getAgentStatus($return_error = true) {
		$hook 		= $this->runHookCheck("status", "get-cli-status");
		$error_code = $hook['error'];
		$return 	= $this->getAgentStatusDefault();
		$t_html 	= array('force' => false, 'body' => null);

		if ($error_code === self::ERROR_ALL_GOOD)
		{
			$hook_data = $hook['hook_data'];

			$status_code = self::STATUS_NULL;
			$t_info = array();

			//clean array info debug hook
			unset($hook_data['exec']);

			$hook_data['lastbackup_date'] = \DateTime::createFromFormat('Y-m-d H:i', $hook_data['lastbackup']);
			$hook_data['nextbackup_date'] = \DateTime::createFromFormat('Y-m-d H:i', $hook_data['nextbackup']);

			$t_status_info = preg_split('/[-]+/', $hook_data['server_status']);
			$t_status_info = array_map('trim', $t_status_info);	//Trim All Elements Array
			switch (strtolower($t_status_info[0]))
			{
				case strtolower("Idle"):
					switch (strtolower($t_status_info[1]))
					{
						case strtolower("Completed"):	// Idle - Completed
							$status_code = self::STATUS_COMPLETED;
							break;

						case strtolower("Canceled"):	// Idle - Canceled
							$status_code =  self::STATUS_CANCEL;
							break;

						default:
							$status_code =  self::STATUS_UNKNOWN_IDEL;
					}
					break;

				case strtolower("Backing up..."):		// Backing up... - 8.31 MB / 9.57 MB (576.00 KB/s)
					$status_code =  self::STATUS_BACKUP_RUN;

					$t_status_info['progress'] = preg_split('/[\(\)]+/', $t_status_info[1]);
					$t_status_info['progress'] = array_map('trim', $t_status_info['progress']);

					$t_status_info['progressdata'] = preg_split('/[\/]+/', $t_status_info['progress'][0]);
					$t_status_info['progressdata'] = array_map('trim', $t_status_info['progressdata']);
					
					
					$t_info['progress'] = array(
						'all' 	=> $t_status_info[1],
						'send' 	=> \ByteUnits\parse($t_status_info['progressdata'][0])->numberOfBytes(),
						'total' => \ByteUnits\parse($t_status_info['progressdata'][1])->numberOfBytes(),
						'speed' => $t_status_info['progress'][1],
					);
					break;

				case strtolower("No connection found"):	// No connection found
					$status_code =  self::STATUS_NO_CONNECTION;
					$t_html = array(
						'force' => false,
						'body' => $this->showPage("main.body.login"),
					);
					break;

				default:
					$status_code = self::STATUS_UNKNOWN;
			}

			if (! is_array($status_code))
			{
				$status_code = $this->getStatusMsgByCode($status_code, true);
			}
			$hook_data['info_status'] = array_merge($status_code, $t_info);

			if ($status_code['code'] >= self::STATUS_UNKNOWN )
			{
				$this->logger->warning( sprintf("%s->%s - Code (%s): Status not controlled [%s]!", $this->module_name, __FUNCTION__, $status_code['code'], $hook_data['server_status']));
			}

			$return = $hook_data;
		}

		$error_code_array = $this->getErrorMsgByErrorCode($error_code, true);
		if ($error_code != self::ERROR_ALL_GOOD)
		{
			$t_html = array(
				'force' => false,
				'body' => $this->showPage("main.body.error", array( 'error_info' => $error_code_array )),
			);
		}

		if (! is_null($t_html['body']))	{ $return['html'] 	= $t_html; }
		if ($return_error) 				{ $return['error'] 	= $error_code_array; }

		return $return;
	}


	public function getAgentVersion($return_array = false, $return_error = true) {
		
		$hook 		= $this->runHookCheck("version", "get-cli-version");
		$error_code = $hook['error'];
		$return 	= "0.0.0-0";

		if ($error_code === self::ERROR_ALL_GOOD)
		{
			$app_ver = $hook['hook_data']['app']['version'];
			if (! empty($app_ver))
			{
				$return = $app_ver;
			}
		}

		if ($return_array)
		{
			$app_ver_array = explode(".", str_replace("-", ".",  $return));
			$return = array(
				'major' => $app_ver_array[0],
				'minor' => $app_ver_array[1],
				'patch' => $app_ver_array[2],
				'build' => $app_ver_array[3],
				'full' => $return,
			);
		}

		if ($return_array && $return_error)
		{
			$return['error'] = $this->getErrorMsgByErrorCode($error_code, true);
		}
		return $return;
	}


	public function setAgentConnection($server, $user, $pass) {
		$hook_params = array(
			"server" => $server,
			"user" => $user,
			"pass" => $pass,
		);
		$hook 		= $this->runHookCheck("create_connection", "set-cli-create-connection", $hook_params);
		$error_code = $hook['error'];
		$return 	= array();

		if ($error_code === self::ERROR_ALL_GOOD)
		{
			$hook_info = $hook['hook_data'];
			
		}

		$return['error'] = $this->getErrorMsgByErrorCode($error_code, true);
		return $return;
	}



	public function getStatusMsgByCode($status_code, $return_array = false) {
		$msg = "";
		switch($status_code)
		{
			case self::STATUS_BACKUP_RUN:
				$msg = "BackingUp";
				break;

			case self::STATUS_CANCEL:
				$msg = _("Canceled");
				break;

			case self::STATUS_COMPLETED:
				$msg = _("Completed");
				break;

			case self::STATUS_NO_CONNECTION:
				$msg = _("No Connection");
				break;

			case self::STATUS_UNKNOWN:
			case self::STATUS_UNKNOWN_IDEL:
				$msg = _("Status Unknown");
				break;

			default:
				$msg = sprintf(_("The status code (%s) is not controlled!"), $status_code);
		}

		return ($return_array ? array( 'code' => $status_code, 'msg' => $msg ) : $msg);
	}

	public function getErrorMsgByErrorCode($error_code, $return_array = false) {
		$msg = "";
		switch($error_code)
		{
			case self::ERROR_NOT_DEFINED: //-1
				$msg = _("Nothing has been defined yet.");
				break;

			case self::ERROR_ALL_GOOD: //0
				$msg = _("No mistake, everything ok.");
				break;

			case self::ERROR_AGENT_NOT_INSTALLED: //501
				$msg = _("Synology Agent not Installed!");
				break;

			case self::ERROR_AGENT_NOT_RETURN_INFO: //502
				$msg = _("Synology Agent not return info!");
				break;

			case self::ERROR_AGENT_ENDED_IN_ERROR: //503
				$msg = _("Synology Agent ended in error!");
				break;

			case self::ERROR_AGENT_RETURN_UNCONTROLLED: //504
				$msg = _("Synology Agent returned uncontrolled information!");
				break;

			case self::ERROR_HOOK_FILE_NOT_EXIST: //510
				$msg = _("The file that returns the hook information does not exist!");
				break;

			case self::ERROR_HOOK_FILE_EMTRY: //515
				$msg = _("Hook file is empty!");
				break;

			case self::ERROR_UNKNOWN: //-2
			default:
				$msg =  sprintf(_("Unknown error (%s)!"), $error_code);
				$error_code = self::ERROR_UNKNOWN;
				break;
		}

		return ($return_array ? array( 'code' => $error_code, 'msg' => $msg ) : $msg);
	}

	

	public function checkServer($host, $port = null, $wait = 1) {
		if ( is_null($port) ) { $prot = self::DEFAULT_PORT; }
    	$fp = @fsockopen($host, $port, $errCode, $errStr, $wait);
		if ($fp)
		{
			fclose($fp);
			$return_date = true;
		}
		else
		{
			// echo "ERROR: $errCode - $errStr";
			$return_date = array('code' => $errCode, 'msg' => $errStr);
		}
		return $return_date;
	}


	


}