<?php

namespace fortrabbit\Copy\commands;

use ostark\Yii2ArtisanBridge\base\Action;
use fortrabbit\Copy\Plugin;


/**
 * Class DbExportAction
 *
 * @package fortrabbit\DeployTools\commands
 */
class DbExportAction extends Action
{

    var $name = 'bar';


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
            $this->info("OK");
            return 0;
        }

    }
}
