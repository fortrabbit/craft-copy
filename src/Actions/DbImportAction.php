<?php

namespace fortrabbit\Copy\Actions;

use craft\errors\ShellCommandException;
use craft\helpers\FileHelper;
use fortrabbit\Copy\Plugin;
use ostark\Yii2ArtisanBridge\base\Action;
use yii\base\Exception;
use yii\console\ExitCode;

/**
 * Class DbImportAction
 *
 * @package fortrabbit\Copy\Commands
 */
class DbImportAction extends Action
{
    /**
     * Import database
     *
     * @param string $file Import a sql dump
     *
     * @return int
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

        $this->info("Importing DB Dump from '{$file}'");

        try {
            $file = Plugin::getInstance()->dump->import($file);
            $this->successBlock("Dump imported");

            if (!$this->confirm("Do you really want to remove the {$file} file?", true)) {
                return ExitCode::OK;
            }

            if (FileHelper::unlink($file)) {
                $this->successBlock("Dump removed");
            }

            return ExitCode::OK;
        } catch (ShellCommandException $exception) {
            $this->errorBlock(['Mysql Import error', $exception->getMessage()]);
            return ExitCode::UNSPECIFIED_ERROR;
        } catch (Exception $exception) {
            $this->errorBlock([$exception->getMessage()]);
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
}
