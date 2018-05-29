<?php

namespace fortrabbit\Copy\commands;

use craft\helpers\FileHelper;
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
     * @param string $file Import a sql dump
     *
     * @return bool
     */
    public function run(string $file)
    {
        if (!file_exists($file)) {
            $this->errorBlock("File '{$file}' does not exist.");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (!$this->confirm("Do you really want to overwrite your DB with the dump?", true)) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->info("Import DB Dump from '{$file}'");

        if ($file = Plugin::getInstance()->dump->import($file)) {

            $this->successBlock("Dump imported");

            if (!$this->confirm("Do you really want to remove the {$file} file?", true)) {
                return ExitCode::OK;
            }

            if (FileHelper::unlink($file)) {
                $this->successBlock("Dump removed");
            }

            return ExitCode::OK;
        }
    }
}
