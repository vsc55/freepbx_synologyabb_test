<?php
/**
 * 
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * @copyright 2022 Javier Pastor Garcia
 * 
 */

namespace FreePBX\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Synologyabb extends Command
{    
	protected function configure()
    {
		$this->setName('synologyabb')
			->setDescription(_('Synology Active Backup for Business functions'))
			->addOption('force', 'f', InputOption::VALUE_NONE, _('Force Update Status Info'))
			->addOption('help', 'h', InputOption::VALUE_NONE, _('Show help'))
            ->addArgument('cmd', InputArgument::REQUIRED, _('Command to run (see --help)'))
			->setHelp($this->showHelp());
	}

	protected function execute(InputInterface $input, OutputInterface $output)
    {
        $force  = $input->getOption('force') ? true : false;
		$cmd    = $input->getArgument('cmd');
		switch ($cmd)
        {
            case "check":
                $this->check($output, $force);
                break;
		
            case "version":
                $this->showVersion($output);
                break;

		    default:
                $output->writeln("<error>".sprintf(_("Command [%s] not found!"), $cmd)."</error>");
                $output->writeln("");
			    $output->writeln($this->showHelp());
		}
	}

    private function showHelp()
    {
        $syno = $this->getSynoClass();
		$help = "Valid Commands:\n";
		$commands = array(
            "check" => _("Check status backup"),
            "version" => _("Show version Agent Installed"),
		);
		foreach ($commands as $o => $t) 
        {
			$help .= "<info>$o</info> : <comment>$t</comment>\n";
		}

		$help .= "\n";
		$help .= _("For example:")."\n\n";
		$help .= "# <comment>fwconsole synologyabb version</comment>\n";
        $help .= "<info>".sprintf(_('Agent Version: %s'), $syno->ABBCliVersionMin)."</info>\n";
		return $help;
	}

    private function getSynoClass()
    {
        return \FreePBX::Synologyabb();
    }

    private function showVersion($output)
    {
        $syno = $this->getSynoClass();
        $ver = $syno->getAgentVersion();
        $output->writeln( "<info>".sprintf(_('Agent Version: %s'), $ver)."</info>");
    }

    private function check($output, $force = false)
    {
        $syno   = $this->getSynoClass();
        $output->writeln(_("Getting Backup Status..."));

        $status = $syno->getAgentStatus(true, $force, false);
        $error_code = $status['error'];
        if (is_array($error_code)) {
            $error_code = $error_code['code'];
        }

        if ( $error_code !== $syno::ERROR_ALL_GOOD)
        {
            $output->writeln("<error>".sprintf(_("Error [%s]: %s"), $status['error']['code'], $status['error']['msg'])."</error>");
        }
        else
        {
            $status_code = $status['info_status']['code'];
            switch($status_code)
            {
                case $syno::STATUS_IDLE_COMPLETED:
                    $last_backup_ok_date = $status['lastbackup_date']->format("Y/m/d");
                    $output->writeln("<info>".sprintf(_("Last backup completed successfully - %s"), $last_backup_ok_date)."</info>");
                    break;
                
                case $syno::STATUS_BACKUP_RUN:
                    $output->writeln("<info>".sprintf("%s - %s", $status['info_status']['msg'], $status['info_status']['progress']['all'])."</info>");
                    break;

                case $syno::STATUS_IDLE:
                case $syno::STATUS_IDLE_CANCEL:
                case $syno::STATUS_IDLE_FAILED:
                    $output->writeln("<fg=black;bg=yellow>" . sprintf(_("Warning [%s]: %s"), $status['info_status']['code'], $status['info_status']['msg']). "</>");
                    break;

                default:
                    $output->writeln("<error>".sprintf(_("Error [%s]: %s"), $status['info_status']['code'], $status['info_status']['msg'])."</error>");
            }
        }
        return;
    }

}