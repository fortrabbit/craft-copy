<?php

namespace fortrabbit\Copy\commands;

use fortrabbit\Copy\Plugin;
use yii\console\ExitCode;

/**
 * Class DbExportAction
 *
 * @package fortrabbit\DeployTools\commands
 */
class DbExportAction extends EnvironmentAwareBaseAction
{

    /**
     * Export database
     *
     * @param string|null $file Create a sql dump
     *
     * @return int
     * @throws \yii\console\Exception
     */
    public function run(string $file = null)
    {
        $plugin = Plugin::getInstance();
        $this->info("Creating DB Dump in '{$file}'");

        if ($file = $plugin->dump->export($file)) {
            $this->info("OK");
            return ExitCode::OK;
        }
    }
}
