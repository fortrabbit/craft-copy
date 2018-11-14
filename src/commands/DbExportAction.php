<?php

namespace fortrabbit\Copy\commands;

use fortrabbit\Copy\Plugin;
use yii\console\ExitCode;
use ostark\Yii2ArtisanBridge\base\Action;


/**
 * Class DbExportAction
 *
 * @package fortrabbit\DeployTools\commands
 */
class DbExportAction extends Action
{

    /**
     * Export database
     *
     * @param string|null $file Filename of the sql dump
     *
     * @return int
     * @throws \craft\errors\ShellCommandException
     * @throws \yii\base\Exception
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
