<?php
    namespace FreePBX\modules\Synologyactivebackupforbusiness;

    class ParseArgs
    {
        private $args       = array();
        private $settings   = array();

        private $hook_file      = "";
        private $syslog_prefix = "sysadmin-hook";

        public function __construct($args = array(), $file = "", $noCheckSettings = false)
        {
            $this->args = $args;
            $this->hook_file = $file;
            $this->checkArgs();
            $this->readSettings();
            if ($noCheckSettings == false)
            {
                $this->checkSettings();
            }
        }

        private function checkArgs ()
        {
            if (empty($this->args[1]))
            {
                $msg_err = "Needs a param";
                $this->sendSyslog($msg_err);
                throw new \Exception($msg_err);
            }
        }

        private function readSettings()
        {
            // Underp the base64 that the param is using.
            $b = str_replace('_', '/', $this->args[1]);
            $this->settings = @json_decode(gzuncompress(@base64_decode($b)), true);

            if (!is_array($this->settings))
            {
                $msg_err = "Invalid param";
                $this->sendSyslog($msg_err);
                throw new \Exception($msg_err);
            }
        }

        public function sendSyslog($msg, $msg_type = LOG_ERR)
        {
            openlog($this->syslog_prefix, LOG_PID | LOG_PERROR, LOG_CRON);
            syslog($msg_type, sprintf("%s > %s",  $this->hook_file , $msg));
            closelog();
        }
            
        public function checkSettings($setting_custom = array(), $gen_exception = true)
        {
            $setting_default = array('hook_token' => true, 'hook_file' => true);
            $setting_needs = array_merge($setting_default, $setting_custom);

            $msg_err = null;
            foreach ($setting_needs as $key => $val)
            {
                if (! array_key_exists($key, $this->settings) )
                {
                    $msg_err = sprintf( "Necessary param [%s] is missing!", $key);
                    break;
                }
                elseif ( $val == true && empty($this->settings[$key]) )
                {
                    $msg_err = sprintf("Param is empty [%s]", $key);
                    break;
                }
            }

            if (! is_null($msg_err))
            {
                $this->sendSyslog($msg_err);
                if ($gen_exception)
                {
                    throw new \Exception($msg_err);
                }
                return false;
            }
            return true;
        }

        public function getSetting($key, $default = "")
        {
            return (array_key_exists($key, $this->settings) ? $this->settings[$key] : $default);
        }

        public function getSettingAll()
        {
            return $this->settings;
        }

    }