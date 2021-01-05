<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Actions;

use Craft;
use craft\errors\ShellCommandException;
use fortrabbit\Copy\Plugin;
use ostark\Yii2ArtisanBridge\base\Action;
use yii\base\Exception;
use yii\console\ExitCode;

class DbExportAction extends Action
{
    /**
     * Export database from file
     *
     * @param string|null $file Filename of the sql dump
     *
     * @return int
     */
    public function run(?string $file = null)
    {
        $plugin = Plugin::getInstance();
        $this->assureMyCfnForMysqldump();
        $this->info("Creating DB Dump in '{$file}'");

        try {
            $plugin->database->export($file);
            $this->info('OK');
            return ExitCode::OK;
        } catch (ShellCommandException $exception) {
            $this->errorBlock(['Mysql Import error', $exception->getMessage()]);
            return ExitCode::UNSPECIFIED_ERROR;
        } catch (Exception $exception) {
            $this->errorBlock([$exception->getMessage()]);
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    protected function assureMyCfnForMysqldump(): bool
    {
        $mycnfDest = Craft::getAlias('@root') . '/.my.cnf';
        $mycnfSrc = Plugin::PLUGIN_ROOT_PATH . '/.my.cnf.example';

        if (! file_exists($mycnfDest)) {
            return copy($mycnfSrc, $mycnfDest);
        }

        return true;
    }
}
