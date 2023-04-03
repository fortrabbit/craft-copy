<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Actions;

use Craft;
use craft\errors\ShellCommandException;
use fortrabbit\Copy\Helpers\MysqlConfigFile;
use fortrabbit\Copy\Plugin;
use ostark\Yii2ArtisanBridge\base\Action;
use yii\base\Exception;
use yii\console\ExitCode;

class DbExportAction extends Action
{
    use MysqlConfigFile;

    /**
     * Export database from file
     *
     * @param string|null $file Filename of the sql dump
     */
    public function run(?string $file = null): int
    {
        $plugin = Plugin::getInstance();
        $this->assureMyCnf();
        $this->info("Creating DB Dump in '{$file}'");

        try {
            $plugin->database->export($file);
            $this->info('OK');

            return ExitCode::OK;
        } catch (ShellCommandException $shellCommandException) {
            $this->errorBlock(['Mysql Import error', $shellCommandException->getMessage()]);

            return ExitCode::UNSPECIFIED_ERROR;
        } catch (Exception $exception) {
            $this->errorBlock([$exception->getMessage()]);

            return ExitCode::UNSPECIFIED_ERROR;
        }
    }


}
