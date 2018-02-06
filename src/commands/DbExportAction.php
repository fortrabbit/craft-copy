<?php namespace fortrabbit\Sync\commands;

use fortrabbit\Sync\Plugin;


/**
 * Class DbExportAction
 *
 * @package fortrabbit\DeployTools\commands
 */
class DbExportAction extends ConsoleBaseAction
{

    /**
     * @param string|null $file Create a sql dump
     *
     * @return bool
     * @throws \yii\console\Exception
     */
    public function run(string $file = null)
    {
        if ($file = Plugin::getInstance()->dump->export($file)) {
            $this->info("DB Dump created in '{$file}'");
            return true;
        }

    }
}
