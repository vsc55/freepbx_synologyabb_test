<?php
namespace FreePBX\modules\Synologyabb;
use FreePBX\modules\Backup as Base;
class Backup Extends Base\BackupBase
{
	public function runBackup($id,$transaction)
    {
		$this->addConfigs([
			'settings' => $this->dumpAdvancedSettings(),
		]);
	}
}