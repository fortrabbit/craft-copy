<?php

namespace fortrabbit\Copy\commands;

use ostark\Yii2ArtisanBridge\base\Action;
use fortrabbit\Copy\Plugin;
use yii\console\ExitCode;

/**
 * Class DbImportAction
 *
 * @package fortrabbit\DeployTools\commands
 */
class DbImportAction extends Action
{

    /**
     * Import database
     *
     * @param string|null $file Import a sql dump
     *
     * @return bool
     */
    public function run(string $file = null)
    {
        if (!$this->pleaseConfirm("Do you really want to overwrite your DB with the dump?")) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->info("Import DB Dump from '{$file}'");

        if ($file = Plugin::getInstance()->dump->import($file)) {
            $this->info("OK");
            return 0;
        }
    }
}
