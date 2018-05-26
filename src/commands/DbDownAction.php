<?php

namespace fortrabbit\Copy\commands;

use \Craft;
use ostark\Yii2ArtisanBridge\base\Action as BaseAction;
use fortrabbit\Copy\Plugin;
use yii\console\ExitCode;


/**
 * Class DbDownAction
 *
 * @package fortrabbit\DeployTools\commands
 */
class DbDownAction extends BaseAction
{
    /**
     * Upload database
     *
     * @return bool
     * @throws \craft\errors\ShellCommandException
     * @throws \fortrabbit\Copy\exceptions\CraftNotInstalledException
     * @throws \fortrabbit\Copy\exceptions\PluginNotInstalledException
     * @throws \fortrabbit\Copy\exceptions\RemoteException
     * @throws \yii\base\Exception
     * @throws \yii\console\Exception
     */
    public function run()
    {
        $plugin = Plugin::getInstance();
        $path   = './storage/';
        $remoteFile  = $path . 'craft-copy-dump-recent.sql';
        $localBackup = $path . 'craft-copy-dump-local.sql';
        $steps       = 4;
        $messages    = [];

        if (!$this->confirm("Do you really want to sync your remote DB with the local?", true)) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $bar = $this->output->createProgressBar($steps);

        // Custom format
        $bar->setFormat('%message%' . PHP_EOL . '%bar% %percent:3s% %' . PHP_EOL . 'time:  %elapsed:6s%/%estimated:-6s%' . PHP_EOL . PHP_EOL);
        $bar->setBarCharacter('<info>' . $bar->getBarCharacter() . '</info>');
        $bar->setBarWidth(70);


        // Step 1: Create dump of the current database
        $bar->setMessage($messages[] = "Creating dump on remote ({$remoteFile})");
        if ($plugin->ssh->exec("php craft copy/db/to-file {$remoteFile} --interactive=0")) {
            $bar->advance();
        }

        // Step 2: Download that dump from remote
        $bar->setMessage($messages[] = "Downloading dump from remote {$remoteFile}");
        if ($plugin->ssh->download($remoteFile, $remoteFile, true)) {
            $bar->advance();
        }

        // Step 3: Backup the local database before importing the downloaded dump
        $bar->setMessage($messages[] = "Creating backup of local DV ({$localBackup})");

        if ($plugin->dump->export($localBackup)) {
            $bar->advance();
        }

        // Step 4: Import
        $bar->setMessage($messages[] = "Importing dump");
        if ($plugin->dump->import($remoteFile)) {
            $bar->advance();
            $bar->setMessage("Dump imported");
        }

        $bar->finish();

        $this->section('Performed steps:');
        $this->listing($messages);

        $this->section('Rollback?');
        $this->line("ssh {$plugin->ssh->remote} 'php craft copy/db/from-file {$localBackup}'" . PHP_EOL);

        return 0;
    }
}
