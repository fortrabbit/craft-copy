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
class DbUpAction extends BaseAction
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
        $plugin       = Plugin::getInstance();
        $path         = './storage/';
        $localFile    = $remoteFile = $path . 'craft-copy-dump-' . date('Ymd-his') . '.sql';
        $remoteBackup = $path . 'craft-copy-dump-recent.sql';
        $steps        = 4;
        $messages     = [];
        // Step 0:
        //$this->remotePreCheck($plugin);

        if (!$this->confirm("Do you really want to sync your local DB with the remote?")) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $bar = $this->output->createProgressBar($steps);

        // Custom format
        $bar->setFormat('%message%' . PHP_EOL . '%bar% %percent:3s% %' . PHP_EOL . 'time:  %elapsed:6s%/%estimated:-6s%' . PHP_EOL . PHP_EOL);
        $bar->setBarCharacter('<info>' . $bar->getBarCharacter() . '</info>');
        $bar->setBarWidth(70);


        // Step 1: Create dump of the current database
        $bar->setMessage($messages[] = "Creating local dump");
        if ($plugin->dump->export($localFile)) {
            $bar->advance();
        }

        // Step 2: Upload that dump to remote
        $bar->setMessage($messages[] = "Uploading dump to remote {$remoteFile}");
        if ($plugin->ssh->upload($localFile, $remoteFile, true)) {
            $bar->advance();
        }

        // Step 3: Backup the remote database before importing the uploaded dump
        $bar->setMessage($messages[] = "Creating DB Backup on remote ({$remoteBackup})");
        if ($plugin->ssh->exec("php craft copy/db/to-file {$remoteBackup} --interactive=0")) {
            $bar->advance();
        }

        // Step 4: Import on remote
        $bar->setMessage($messages[] = "Importing dump on remote");
        if ($plugin->ssh->exec("php craft copy/db/from-file {$remoteFile} --interactive=0")) {
            $bar->advance();
            $bar->setMessage("Dump imported");
        }

        $bar->finish();

        $this->section('Performed steps:');
        $this->listing($messages);

        $this->section('Rollback?');
        $this->line("ssh {$plugin->ssh->remote} 'php craft copy/db/from-file {$remoteBackup}'" . PHP_EOL);

        return 0;
    }
}
