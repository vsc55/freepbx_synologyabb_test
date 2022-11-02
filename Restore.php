<?php
namespace FreePBX\modules\Synologyabb;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase
{
	public function runRestore()
    {
		$configs = $this->getConfigs();
        if(!empty($configs['settings']) && is_array($configs['settings'])) {
			$this->importAdvancedSettings($configs['settings']);
		}
	}

	public function processLegacy($pdo, $data, $tables, $unknownTables)
    {
		$this->restoreLegacyDatabase($pdo);
	}
}