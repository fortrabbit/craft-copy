<?php namespace fortrabbit\Copy\commands;

use fortrabbit\Copy\Plugin;
use fortrabbit\Copy\services\ConsoleOutputHelper;


/**
 * Class DbExportAction
 *
 * @package fortrabbit\DeployTools\commands
 */
class DbExportAction extends ConsoleBaseAction
{

    /**
     * Export database
     *
     * @param string|null $file Create a sql dump
     *
     * @return bool
     * @throws \yii\console\Exception
     */
    public function run(string $file = null)
    {
        $plugin = Plugin::getInstance();
        $this->info("Create DB Dump in '{$file}'");

        if ($file = $plugin->dump->export($file)) {
            $this->success("Success");

            return true;
        }

    }
}
