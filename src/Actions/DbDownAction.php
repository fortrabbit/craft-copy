<?php

namespace fortrabbit\Copy\Actions;

use fortrabbit\Copy\Plugin;
use yii\console\ExitCode;

class DbDownAction extends StageAwareBaseAction
{
    /**
     * Download database
     *
     * @param string|null $stage Name of the stage config
     *
     * @return int
     *
     *
     * @throws \craft\errors\FileException
     * @throws \craft\errors\ShellCommandException
     * @throws \fortrabbit\Copy\Exceptions\CraftNotInstalledException
     * @throws \fortrabbit\Copy\Exceptions\PluginNotInstalledException
     * @throws \fortrabbit\Copy\Exceptions\RemoteException
     * @throws \yii\base\Exception
     */
    public function run(string $stage = null)
    {
        $plugin       = Plugin::getInstance();
        $path         = './storage/';
        $transferFile = $path . 'craft-copy-transfer.sql';
        $backupFile   = $path . 'craft-copy-recent.sql';
        $steps        = 4;
        $messages     = [];

        $this->head(
            "Export remote DB, download and import locally.",
            "<comment>{$this->stage}</comment> {$this->stage->app}.frb.io",
            $this->interactive ? true : false
        );

        if (!$this->confirm("Are you sure?", true)) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // Run 'before' commands and stop on error
        if (!$this->runBeforeDeployCommands()) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $bar = $this->output->createProgressBar($steps);

        // Custom format

        $lines = [
            '%message%',
            '%bar% %percent:3s% %',
            'time:  %elapsed:6s%/%estimated:-6s%'
        ];
        $bar->setFormat(implode(PHP_EOL, $lines) . PHP_EOL . PHP_EOL);
        $bar->setBarCharacter('<info>' . $bar->getBarCharacter() . '</info>');
        $bar->setBarWidth(70);


        // Step 1: Create dump of the current database
        $bar->setMessage($messages[] = "Creating dump on remote ({$transferFile})");
        if ($plugin->ssh->exec("php craft copy/db/to-file {$transferFile} --interactive=0")) {
            $bar->advance();
        }

        // Step 2: Download that dump from remote
        $bar->setMessage($messages[] = "Downloading dump from remote {$transferFile}");
        if ($plugin->ssh->download($transferFile, $transferFile)) {
            $bar->advance();
        }

        // Step 3: Backup the local database before importing the downloaded dump
        $bar->setMessage($messages[] = "Creating backup of local DB ({$backupFile})");

        if ($plugin->database->export($backupFile)) {
            $bar->advance();
        }

        // Step 4: Import
        $bar->setMessage($messages[] = "Importing dump");
        if ($plugin->database->import($transferFile)) {
            $bar->advance();
            $bar->setMessage("Database imported");
        }

        $bar->finish();

        $this->section('Performed steps:');
        $this->listing($messages);

        $this->section('Rollback?');
        $this->line("php craft copy/db/from-file {$backupFile}" . PHP_EOL);

        return ExitCode::OK;
    }
}
