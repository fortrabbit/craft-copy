<?php namespace fortrabbit\Copy\commands;

use \Craft;
use fortrabbit\Copy\exceptions\RemoteException;
use fortrabbit\Copy\Plugin;
use fortrabbit\Copy\services\ConsoleOutputHelper;
use yii\console\Exception;
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
     * @throws \craft\errors\ActionCancelledException
     */
    public function run()
    {
        $plugin       = Plugin::getInstance();
        $localFile    = $remoteFile = '/tmp/db/' . date('Ymd-his') . '.sql';
        $remoteBackup = '/tmp/db/recent.sql';

        // Step 0:
        $this->remotePreCheck($plugin);

        $this->isForcedOrConfirmed("Do you really want to sync your local DB with the remote?");

        // Step 1: Create dump of the current database
        if ($plugin->dump->export($localFile)) {
            $this->info('Local dump created');
        }

        // Step 2: Upload that dump to remote
        if ($plugin->ssh->upload($localFile, $remoteFile, true)) {
            $this->info("Dump uploaded $localFile > $remoteFile");
        }

        // Step 3: Backup the remote database before importing the uploaded dump
        if ($plugin->ssh->exec("php craft copy/db/to-file {$remoteBackup} --force")) {
            $this->info("DB Backup created on remote ({$remoteBackup})");
        }

        // Step 4: Import on remote
        if ($plugin->ssh->exec("php craft copy/db/from-file {$remoteFile} --force")) {
            $this->info('Dump imported');
        }

        return true;
    }
}
