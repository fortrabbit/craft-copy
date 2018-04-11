<?php namespace fortrabbit\Copy\commands;

use \Craft;
use fortrabbit\Copy\exceptions\RemoteException;
use fortrabbit\Copy\Plugin;
use fortrabbit\Copy\services\ConsoleOutputHelper;
use yii\console\Exception;
use yii\helpers\Console;
use ZipArchive;


/**
 * Class DbDownAction
 *
 * @package fortrabbit\DeployTools\commands
 */
class DbUpAction extends ConsoleBaseAction
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
        $localFile    = $remoteFile = './storage/copy-' . date('Ymd-his') . '.sql';
        $remoteBackup = './storage/copy-recent.sql';
        $steps = 4;

        // Step 0:
        $this->remotePreCheck($plugin);

        $this->isForcedOrConfirmed("Do you really want to sync your local DB with the remote?");

        Console::startProgress(0, $steps);

        // Step 1: Create dump of the current database
        if ($plugin->dump->export($localFile)) {
            $this->info('Local dump created');
            Console::updateProgress(1, $steps);

        }

        // Step 2: Upload that dump to remote
        if ($plugin->ssh->upload($localFile, $remoteFile, true)) {
            $this->info("Dump uploaded $localFile > $remoteFile");
            Console::updateProgress(2, $steps);

        }

        // Step 3: Backup the remote database before importing the uploaded dump
        if ($plugin->ssh->exec("php craft copy/db/to-file {$remoteBackup} --force")) {
            $this->info("DB Backup created on remote ({$remoteBackup})");
            Console::updateProgress(3, $steps);

        }

        // Step 4: Import on remote
        if ($plugin->ssh->exec("php craft copy/db/from-file {$remoteFile} --force")) {
            $this->info('Dump imported');
            Console::updateProgress(4, $steps);

        }

        Console::endProgress();

        return 0;
    }
}
