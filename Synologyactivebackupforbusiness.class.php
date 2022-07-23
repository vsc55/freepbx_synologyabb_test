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
		'html' => '',
		'error' => ''
    );

	const STATUS_NULL			= -1;		// No se ha definido ningun estado.
	const STATUS_IDLE 			= 110;		// (Idle) No se ha echo ninguna copia aun.
	const STATUS_IDLE_COMPLETED = 120;		// (Idle - Completed)
	const STATUS_IDLE_CANCEL	= 130;		// (Idle - Canceled)
	const STATUS_IDLE_FAILED	= 140;		// (Idel - Failed)
	
	const STATUS_BACKUP_RUN		= 300;		// (Backing up... - 8.31 MB / 9.57 MB (576.00 KB/s)) Backup en curso
	const STATUS_NO_CONNECTION 	= 400;		// (No connection found) No conectado con el servidor

	const STATUS_ERR_DEV_REMOVED = 510; 	// (Error  - The current device has been removed from the server. Please contact your administrator for further assistance.) Equipo eliminado del servidor.

	const STATUS_UNKNOWN 		= 99990;	//99990 - status desconocido
	const STATUS_IDLE_UNKNOWN	= 99991;	//99991 - status Idel desconocido
	const STATUS_ERR_UNKNOWN	= 99992;	//99992 - status Error desconocido



	const ERROR_UNKNOWN 	= -2;
	const ERROR_NOT_DEFINED = -1;
	const ERROR_ALL_GOOD 	= 0;

	const ERROR_AGENT_NOT_INSTALLED 		= 501;
	const ERROR_AGENT_NOT_RETURN_INFO 		= 502;
	const ERROR_AGENT_ENDED_IN_ERROR 		= 503;
	const ERROR_AGENT_RETURN_UNCONTROLLED 	= 504;

	const ERROR_AGENT_ALREADY_CONNECTED 	= 520;	// (Already connected)
	const ERROR_AGENT_NOT_ALREADY_CONNECTED = 521;	// (Not Already connected)

	const ERROR_AGENT_SERVER_CHECK 		= 550;

	const ERROR_AGENT_SERVER_AUTH_FAILED 			= 611;
	const ERROR_AGENT_SERVER_AUTH_FAILED_USER_PASS 	= 612;
	const ERROR_AGENT_SERVER_AUTH_FAILED_BAN_IP 	= 613;

	const ERROR_MISSING_ARGS = 650;

	const ERROR_HOOK_FILE_NOT_EXIST = 710;
	const ERROR_HOOK_FILE_EMTRY 	= 715;
	const ERROR_HOOK_FILE_TOEKN 	= 720;
	const ERROR_HOOK_RUN_TIMEOUT	= 725;

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

		$this->ABBCliVersionMin = "2.2.0-2070"; // Minimum version supported
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

	public function getHookFilename($hookname, $hooktoken) {
		$return = $this->getAstTmpDir() . "/synology-cli";
		if (! empty($hookname))
		{
			$return .= "-" . $hookname;
		}
		if (! empty($hooktoken))
		{
			$return .= "-" . $hooktoken;
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

	private function runHookCheck($hook_file, $hook_run, $hook_params = array(), $decode = true, $timeout = 30) {
		$error_code = self::ERROR_NOT_DEFINED;
		$hook_info 	= null;
		$hook_token = uniqid('hook');
		$file 		= $this->getHookFilename($hook_file, $hook_token);

		$hook_params['hook_file'] 	= $hook_file;
		$hook_params['hook_token'] 	= $hook_token;

		$this->runHook($hook_run, $hook_params);

		// We wait 30 seconds to see if the file with the data is created
		$maxloops = $timeout * 2;
		$hookTimeOut = true;
		while ($maxloops--)
		{
			if (file_exists($file))
			{
				$decode_info = $this->readFileHook($file, true);

				if ( !empty($decode_info) && isset($decode_info['hook']['token']) && $hook_token == $decode_info['hook']['token'] && $decode_info['hook']['status'] == "END")
				{
					$hookTimeOut = false;
					break;
				}
			}
			usleep(500000);
		}
		if ($hookTimeOut)
		{
			$error_code = self::ERROR_HOOK_RUN_TIMEOUT;
		}
		else
		{
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
					if ($hook_token != $hook_info['hook']['token'])
					{
						$error_code = self::ERROR_HOOK_FILE_TOEKN;
					}
					else
					{
						// $error_code = self::ERROR_ALL_GOOD;
						$error_code = ($hook_info['error']['code'] === self::ERROR_ALL_GOOD ? self::ERROR_ALL_GOOD : $hook_info['error']['code']);
						if (! $decode)
						{
							$hook_info = $linesfilehook;
						}
					}
				}
			}
		}

		return array(
			'hook_file' 	=> $hook_file,
			'hook_run' 		=> $hook_run,
			'hook_token'	=> $hook_token,
			'hook_data'		=> $hook_info,
			'hook_timeout' 	=> $timeout,
			'file' 			=> $file,
			'decode'		=> $decode,
			'error' 		=> $error_code,
		);
	}

	public function writeFileHook($file, $data, $encode = true) {
		if (trim($file) == false) {
			return false;
		}
		file_put_contents($file, ($encode == true ? json_encode($data) : $data));
		chown($file, 'asterisk');
		return true;
	}

	public function readFileHook($file, $decode = true) {
		$return = "";
		if (trim($file) != false)
		{
			$return = file_get_contents($file);
			if ($decode == true)
			{
				$return = @json_decode($return, true);
			}
		}
		return $return;
	}

	public function install() {
		outn(_("Upgrading configs.."));
		$set = array();
		$set['value'] = '/usr/bin/abb-cli';
		$set['defaultval'] =& $set['value'];
		$set['readonly'] = 0;
		$set['hidden'] = 0;
		$set['level'] = 0;
		$set['module'] = $this->module_name; //'synologyactivebackupforbusiness';  //disabled as it generates error, Fix FREEPBX-22756
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

	public function dashboardService() {
		$status = array(
			'title' => _('ABB Status Backup'),
			'order' => 3,
		);

		$data = $this->getAgentStatus(true);

		$status_code 	 = $data['info_status']['code'];
		$status_msg 	 = $data['info_status']['msg'];

		// Generate a tooltip
		$status_msg_html = htmlentities(\ForceUTF8\Encoding::fixUTF8($status_msg), ENT_QUOTES,"UTF-8");

		$AlertGlyphicon = array(
			'type' => '',
			"tooltip" => $status_msg_html,
			"glyph-class" => ''
		);

		switch($status_code)
		{
			case self::STATUS_IDLE_COMPLETED:
				$AlertGlyphicon['type'] = "ok";
				$AlertGlyphicon['glyph-class'] = "glyphicon-floppy-saved text-success";
				break;
			case self::STATUS_BACKUP_RUN:
				$status_msg .= " - " . $data['info_status']['progress']['all'];
				$AlertGlyphicon['tooltip'] = htmlentities(\ForceUTF8\Encoding::fixUTF8($status_msg), ENT_QUOTES,"UTF-8");

				$AlertGlyphicon['type'] = "info";
				$AlertGlyphicon['glyph-class'] = "glyphicon-export text-info";
				$status = array_merge($status, $AlertGlyphicon);
				// glyphicon-floppy-open
				break;
			case self::STATUS_IDLE:				// (Idle) no copy has been made yet.
			case self::STATUS_IDLE_CANCEL:
			case self::STATUS_IDLE_FAILED:
				$AlertGlyphicon = $this->Dashboard()->genStatusIcon('warning', $status_msg);
				break;
			default:
				$AlertGlyphicon = $this->Dashboard()->genStatusIcon('error', $status_msg);
				break;
		}


		$status = array_merge($status, $AlertGlyphicon);
		return array($status);
	}


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
			case "setagentcreateconnection":
			case "setagentreconect":
			case "setagentlogout":
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
			case 'setagentcreateconnection':
				$agent_server 	= $this->getReq("ABBServer", "");
				$agent_username = $this->getReq("ABBUser", "");
				$agent_password = $this->getReq("ABBPassword", "");
				$return_status = $this->setAgentConnection($agent_server, $agent_username, $agent_password);

				$data_return = array("status" => true, "data" => $return_status);
				break;

			case 'setagentreconnect':
				$data_return = array("status" => true, "data" => $this->setAgentReConnect());
				break;

			case 'setagentlogout':
				$agent_username = $this->getReq("ABBUser", "");
				$agent_password = $this->getReq("ABBPassword", "");

				$data_return = array("status" => true, "data" => $this->setAgentLogOut($agent_username, $agent_password));
				break;

			default:
				$data_return = array("status" => false, "message" => _("Command not found!"), "command" => $command);
		}
		return $data_return;
	}
	
	private function parseUnitConvert($data)
	{
		return ((int) filter_var($data, FILTER_SANITIZE_NUMBER_INT) == 0 ? 0 : $data);
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
		$hook_data  = $hook['hook_data']['data'];
		$error_code = $hook['error'];
		$return 	= $this->getAgentStatusDefault();
		$t_html 	= array(
			'force' => false,
			'body' => null
		);

		$error_code_array = null;
		$status_code = self::STATUS_NULL;

		if ($error_code === self::ERROR_ALL_GOOD)
		{	
			$t_info = array();

			$hook_data['lastbackup_date'] = \DateTime::createFromFormat('Y-m-d H:i', $hook_data['lastbackup']);
			$hook_data['nextbackup_date'] = \DateTime::createFromFormat('Y-m-d H:i', $hook_data['nextbackup']);

			$t_status_info = preg_split('/[-]+/', $hook_data['server_status']);
			$t_status_info = array_map('trim', $t_status_info);	//Trim All Elements Array

			$t_status_info_type = trim($t_status_info[0], chr(194) . chr(160));
			$t_status_info_msg 	= @$t_status_info[1];

			switch (strtolower($t_status_info_type))
			{
				case strtolower("Idle"):
					if ($t_status_info_msg == "")
					{
						//MSG: Idle
						$status_code = self::STATUS_IDLE;	
					}
					else
					{
						//set generic unknown error
						$status_code = self::STATUS_IDLE_UNKNOWN;

						$t_list_idle = array(
							//MSG: Idle - Completed
							self::STATUS_IDLE_COMPLETED => strtolower("Completed"),
	
							//MSG: Idle - Canceled
							self::STATUS_IDLE_CANCEL => strtolower("Canceled"),

							//MSG: Idle - Failed
							self::STATUS_IDLE_FAILED => strtolower("Failed"),
						);

						//We check if it is any of the errors that we have controlled
						foreach ($t_list_idle as $key => $val)
						{
							if ( strpos(strtolower($t_status_info_msg), $val) !== false )
							{
								$status_code = $key;
								break;
							}
						}
						unset($t_list_idle);
					}
					break;

				case strtolower("Error"):
					$t_list_errors = array(
                        //MSG: Error  - The current device has been removed from the server. Please contact your administrator for further assistance.
                        self::STATUS_ERR_DEV_REMOVED => strtolower("The current device has been removed from the server"),
                    );

                    //set generic unknown error
                    $status_code = self::STATUS_ERR_UNKNOWN;
                    
                    //We check if it is any of the errors that we have controlled
                    foreach ($t_list_errors as $key => $val)
                    {
                        if ( strpos(strtolower($t_status_info_msg), $val) !== false )
                        {
                            $status_code = $key;
                            break;
                        }
                    }
					unset($t_list_errors);
					break;

				case strtolower("Backing up..."):		// Backing up... - 8.31 MB / 9.57 MB (576.00 KB/s)
					$status_code =  self::STATUS_BACKUP_RUN;

					$t_status_info['progress'] = preg_split('/[\(\)]+/', $t_status_info_msg);
					$t_status_info['progress'] = array_map('trim', $t_status_info['progress']);

					$t_status_info['progressdata'] = preg_split('/[\/]+/', $t_status_info['progress'][0]);
					$t_status_info['progressdata'] = array_map('trim', $t_status_info['progressdata']);
					
					$t_info['progress'] = array(
						'all' 	=> $t_status_info_msg,
						'send' 	=> \ByteUnits\parse($this->parseUnitConvert($t_status_info['progressdata'][0]))->numberOfBytes(),
						'total' => \ByteUnits\parse($this->parseUnitConvert($t_status_info['progressdata'][1]))->numberOfBytes(),
						'speed' => $t_status_info['progress'][1],
					);
					break;

				case strtolower("No connection found"):	// No connection found
					$status_code =  self::STATUS_NO_CONNECTION;
					break;

				default:
					$status_code = self::STATUS_UNKNOWN;
					break;
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

			switch (strtolower($t_status_info_type))
			{
				case strtolower("Idle"):
					$t_html = array(
						'force' => false,
						'body' 	=> $this->showPage("main.body.info", array( 'info' => $hook_data )),
					);
					break;
				case strtolower("Backing up..."):
					$t_html = array(
						'force' => true,
						'body' 	=> $this->showPage("main.body.info", array( 'info' => $hook_data )),
					);
					break;

				case strtolower("No connection found"):
					$t_html = array(
						'force' => false,
						'body' => $this->showPage("main.body.login"),
					);
					break;

				case strtolower("Error"):
				default:
					$t_html = array(
						'force' => false,
						'body' 	=> $this->showPage("main.body.error", array( 'error_info' => $status_code )),
					);
			}

			$return = $hook_data;
		}

		$error_code_array = $this->getErrorMsgByErrorCode($error_code, true);
		if ($error_code != self::ERROR_ALL_GOOD)
		{
			$t_html = array(
				'force' => false,
				'body' 	=> $this->showPage("main.body.error", array( 'error_info' => $error_code_array )),
			);
		}

		if (! is_null($t_html['body']))	{ $return['html'] 	= $t_html; }
		if ($return_error) 				{ $return['error'] 	= $error_code_array; }

		return $return;
	}


	public function getAgentVersion($return_array = false, $return_error = true) {
		
		$hook 		= $this->runHookCheck("version", "get-cli-version");
		$hook_data  = $hook['hook_data']['data'];
		$error_code = $hook['error'];
		$return 	= "0.0.0-0";

		if ($error_code === self::ERROR_ALL_GOOD)
		{
			$app_ver = $hook_data['version'];
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
		$return 	 = array();
		$hook_params = array(
			"server" 	=> $server,
			"username" 	=> $user,
			"password" 	=> $pass,
		);
		$hook_params = array_map('trim', $hook_params);

		$hook 		= $this->runHookCheck("createconnection", "set-cli-create-connection", $hook_params);
		$hook_data  = $hook['hook_data']['data'];
		$error_code = $hook['error'];

		//DEBUG!!!!!!
		// $return = $hook;

		if ($error_code === self::ERROR_ALL_GOOD)
		{
			// $hook_info = $hook['hook_data'];
		}

		$return['error'] = $this->getErrorMsgByErrorCode($error_code, true);
		return $return;
	}

	public function setAgentReConnect()
	{
		$return = array();
		$hook 	= $this->runHookCheck("reconnect", "set-cli-reconnect");

		//DEBUG!!!!!!
		//$return = $hook;

		$return['error'] = $this->getErrorMsgByErrorCode($hook['error'], true);
		return $return;
	}

	public function setAgentLogOut($user, $pass)
	{
		$return 	 = array();
		$hook_params = array(
			"username" 	=> $user,
			"password" 	=> $pass,
		);
		$hook_params = array_map('trim', $hook_params);

		$hook 		= $this->runHookCheck("logout", "set-cli-logout", $hook_params);
		$hook_data  = $hook['hook_data']['data'];
		$error_code = $hook['error'];

		//DEBUG!!!!!!
		//$return = $hook;

		if ($error_code === self::ERROR_ALL_GOOD)
		{
			// $hook_info = $hook['hook_data'];
		}

		$return['error'] = $this->getErrorMsgByErrorCode($error_code, true);
		return $return;
	}




	public function getStatusMsgByCode($status_code, $return_array = false) {
		$msg = "";
		switch($status_code)
		{
			case self::STATUS_BACKUP_RUN:
				$msg = _("Backup in Progress...");
				break;

			case self::STATUS_IDLE_CANCEL:
				$msg = _("Canceled");
				break;

			case self::STATUS_IDLE_COMPLETED:
				$msg = _("Completed");
				break;

			case self::STATUS_IDLE:
				$msg = _("Pending First Copy");
				break;
			
			case self::STATUS_IDLE_FAILED:
				$msg = _("Failed");
				break;

			case self::STATUS_NO_CONNECTION:
				$msg = _("No Connection");
				break;

			case self::STATUS_ERR_DEV_REMOVED:
				$msg = _("Device Removed From Server");
				break;

			

			case self::STATUS_UNKNOWN:
			case self::STATUS_IDLE_UNKNOWN:
			case self::STATUS_ERR_UNKNOWN:
				$msg = _("Status Unknown");
				break;

			default:
				$msg = sprintf(_("The status code (%s) is not controlled!"), $status_code);
		}

		return ($return_array ? array( 'code' => $status_code, 'msg' => $msg ) : $msg);
	}

	public function getErrorMsgByErrorCode($error_code, $return_array = false, $msg_alternative = null) {
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

			case self::ERROR_AGENT_SERVER_CHECK: //505
				$msg = _("The server is not available!");
				break;

			case self::ERROR_AGENT_ALREADY_CONNECTED: //520
				$msg = _("Synology Agnet Already connected!");
				break;
			
			case self::ERROR_AGENT_NOT_ALREADY_CONNECTED: //521
				$msg = _("Synology Agent Not Already Connected!");
				break;

			case self::ERROR_AGENT_SERVER_AUTH_FAILED: //511
				$msg = _("The server returned an authentication failed error!");
				break;


			case self::ERROR_AGENT_SERVER_AUTH_FAILED_USER_PASS: //512
				$msg = _("The username or password you entered is incorrect!");
				break;

			case self::ERROR_AGENT_SERVER_AUTH_FAILED_BAN_IP: //513
				$msg = _("This IP address has been blocked because it has reached the maximum number of failed login attempts allowed within a specific time period!");
				break;

			case self::ERROR_HOOK_FILE_NOT_EXIST: //610
				$msg = _("The file that returns the hook information does not exist!");
				break;

			case self::ERROR_HOOK_FILE_EMTRY: //615
				$msg = _("Hook file is empty!");
				break;

			case self::ERROR_HOOK_FILE_TOEKN: //620
				$msg = _("Hook token is invalid!");
				break;
			
			case self::ERROR_HOOK_RUN_TIMEOUT:
				$msg = _("Hook run exccesd tiemout!");
				break;

			case self::ERROR_MISSING_ARGS:
				$msg = _("Missing Arguments!");
				break;

			case self::ERROR_UNKNOWN: //-2
			default:
				$msg =  sprintf(_("Unknown error (%s)!"), $error_code);
				$error_code = self::ERROR_UNKNOWN;
				break;
		}
		if (! is_null($msg_alternative)) { $msg = $msg_alternative; }

		return ($return_array ? array( 'code' => $error_code, 'msg' => $msg ) : $msg);
	}

	

	public function checkServer($host, $port = null, $wait = 1) {
		if ( is_null($port) ) { $port = self::DEFAULT_PORT; }
    	$fp = @fsockopen($host, $port, $errCode, $errStr, $wait);
		if ($fp)
		{
			fclose($fp);
			$return_date = true;
		}
		else
		{
			$return_date = array('code' => $errCode, 'msg' => $errStr);
		}
		return $return_date;
	}

}