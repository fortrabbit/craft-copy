<?php namespace fortrabbit\Sync\commands;

use \Craft;
use fortrabbit\Sync\Plugin;
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
     * Dump
     *
     * @return bool
     * @throws \craft\errors\ActionCancelledException
     */
    public function run()
    {
        $this->isForcedOrConfirmed("Do you really want to sync your local DB with the remote?");

        $plugin       = Plugin::getInstance();
        $localFile    = $remoteFile = '/tmp/db/' . date('Ymd-his') . '.sql';
        $remoteBackup = '/tmp/db/recent.sql';

        // Step 1: Create dump of the current database
        if ($plugin->dump->export($localFile)) {
            $this->info('Local dump created');
        }

        // Step 2: Upload that dump to remote
        if ($plugin->ssh->upload($localFile, $remoteFile, true)) {
            $this->info("Dump uploaded $localFile > $remoteFile");
        }

        // Step 3: Backup the remote database before importing the uploaded dump
        if ($plugin->ssh->exec("php craft sync/db/export {$remoteBackup} --force")) {
            $this->info("DB Backup created on remote ({$remoteBackup})");
        }

        // Step 4: Import on remote
        if ($plugin->ssh->exec("php craft sync/db/import {$remoteFile} --force")) {
            $this->info('Dump imported');
        }

        /*
                $zipPath = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . 'latest-backup.zip';

                if (is_file($zipPath)) {
                    try {
                        FileHelper::removeFile($zipPath);
                    } catch (ErrorException $e) {
                        Craft::warning("Unable to delete the file \"{$zipPath}\": " . $e->getMessage(), __METHOD__);
                    }
                }

                $zip = new ZipArchive();

                if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
                    throw new Exception('Cannot create zip at ' . $zipPath);
                }

                $filename = pathinfo($backupPath, PATHINFO_BASENAME);
                $zip->addFile($backupPath, $filename);
                $zip->close();

        */

        return true;
    }
}
