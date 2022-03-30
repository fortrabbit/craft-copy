<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Actions;

use fortrabbit\Copy\Exceptions\PluginNotInstalledException;
use fortrabbit\Copy\Plugin;
use yii\console\ExitCode;

class DbDownAction extends StageAwareBaseAction
{
    /**
     * Download database
     *
     * @param string|null $stage Name of the stage config
     *
     *
     * @throws \craft\errors\FileException
     * @throws \craft\errors\ShellCommandException
     * @throws \fortrabbit\Copy\Exceptions\CraftNotInstalledException
     * @throws \fortrabbit\Copy\Exceptions\PluginNotInstalledException
     * @throws \fortrabbit\Copy\Exceptions\RemoteException
     * @throws \yii\base\Exception
     */
    public function run(?string $stage = null): int
    {
        $plugin = Plugin::getInstance();
        $path = './storage/';
        $transferFile = $path . 'craft-copy-transfer.sql';
        $backupFile = $path . 'craft-copy-recent.sql';
        $steps = 4;
        $messages = [];

        $this->head(
            'Export DB from fortrabbit, download and import locally.',
            $this->getContextHeadline($this->stage),
            $this->interactive
        );

        if (! $this->confirm('Are you sure?', true)) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // Run 'before' commands and stop on error
        if (! $this->runBeforeDeployCommands()) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $bar = $this->createProgressBar($steps);

        // Step 1: Create dump of the current database
        $bar->setMessage($messages[] = "Creating dump on fortrabbit App ({$transferFile})");

        try {
            $plugin->ssh->exec("php craft copy/db/to-file {$transferFile} --interactive=0");
            $bar->advance();
        } catch (PluginNotInstalledException) {
            $this->errorBlock('Make sure to deploy the plugin first.');
        }

        // Step 2: Download that dump from remote
        $bar->setMessage($messages[] = "Downloading dump from fortrabbit App {$transferFile}");
        if ($plugin->ssh->download($transferFile, $transferFile)) {
            $bar->advance();
        }

        // Step 3: Backup the local database before importing the downloaded dump
        $bar->setMessage($messages[] = "Creating backup of local DB ({$backupFile})");

        if ($plugin->database->export($backupFile)) {
            $bar->advance();
        }

        // Step 4: Import
        $bar->setMessage($messages[] = 'Importing dump');
        if ($plugin->database->import($transferFile)) {
            $bar->advance();
            $bar->setMessage('Database imported');
        }

        $bar->finish();

        $this->section('Performed steps:');
        $this->listing($messages);

        $this->section('Rollback?');
        $this->line("php craft copy/db/from-file {$backupFile}" . PHP_EOL);

        return ExitCode::OK;
    }
}
