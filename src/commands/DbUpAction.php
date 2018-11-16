<?php

namespace fortrabbit\Copy\commands;

use fortrabbit\Copy\Plugin;
use yii\console\ExitCode;

/**
 * Class DbDownAction
 *
 * @package fortrabbit\DeployTools\commands
 */
class DbUpAction extends ConfigAwareBaseAction
{
    /**
     * @var bool Force questions to 'yes'
     */
    public $force = false;

    /**
     * Upload database
     *
     * @param string|null $config Name of the deploy config
     *
     * @return int
     *
     * @throws \craft\errors\ShellCommandException
     * @throws \fortrabbit\Copy\exceptions\CraftNotInstalledException
     * @throws \fortrabbit\Copy\exceptions\PluginNotInstalledException
     * @throws \fortrabbit\Copy\exceptions\RemoteException
     * @throws \yii\base\Exception
     */
    public function run(string $config = null)
    {
        $plugin       = Plugin::getInstance();
        $path         = './storage/';
        $transferFile = $path . 'craft-copy-transfer.sql';
        $backupFile   = $path . 'craft-copy-recent.sql';
        $steps        = 4;
        $messages     = [];

        $this->head(
            "Export local DB and import on remote.",
            "<comment>{$this->config}</comment> {$this->config->app}.frb.io",
            $this->force ? false : true
        );

        // Always ask (default no), but skip question in non-interactive mode
        if (!$this->confirm("Are you sure?", $this->force ? true : false)) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // Run 'before' commands and stop on error
        if (!$this->runBeforeDeployCommands()) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $bar = $this->output->createProgressBar($steps);

        // Custom format
        $bar->setFormat('%message%' . PHP_EOL . '%bar% %percent:3s% %' . PHP_EOL . 'time:  %elapsed:6s%/%estimated:-6s%' . PHP_EOL . PHP_EOL);
        $bar->setBarCharacter('<info>' . $bar->getBarCharacter() . '</info>');
        $bar->setBarWidth(70);

        // Step 1: Create dump of the current database
        $bar->setMessage($messages[] = "Creating local dump");
        if ($plugin->dump->export($transferFile)) {
            $bar->advance();
        }

        // Step 2: Upload that dump to remote
        $bar->setMessage($messages[] = "Uploading dump to remote {$transferFile}");
        if ($plugin->ssh->upload($transferFile, $transferFile)) {
            $bar->advance();
        }

        // Step 3: Backup the remote database before importing the uploaded dump
        $bar->setMessage($messages[] = "Creating DB Backup on remote ({$backupFile})");
        if ($plugin->ssh->exec("php craft copy/db/to-file {$backupFile} --interactive=0")) {
            $bar->advance();
        }

        // Step 4: Import on remote
        $bar->setMessage($messages[] = "Importing dump on remote");
        if ($plugin->ssh->exec("php craft copy/db/from-file {$transferFile} --interactive=0")) {
            $bar->advance();
            $bar->setMessage("Dump imported");
        }

        $bar->finish();

        $this->section('Performed steps:');
        $this->listing($messages);

        $this->section('Rollback?');
        $this->line("ssh {$plugin->ssh->remote} 'php craft copy/db/from-file {$backupFile}'" . PHP_EOL);

        return ExitCode::OK;
    }
}
