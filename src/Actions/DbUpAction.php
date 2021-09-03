<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Actions;

use fortrabbit\Copy\Exceptions\CraftNotInstalledException;
use fortrabbit\Copy\Exceptions\PluginNotInstalledException;
use fortrabbit\Copy\Exceptions\RemoteException;
use fortrabbit\Copy\Plugin;
use yii\console\ExitCode;

class DbUpAction extends StageAwareBaseAction
{
    /**
     * @var bool Force questions to 'yes'
     */
    public $force = false;

    /**
     * Upload database
     *
     * @param string|null $stage Name of the stage config
     *
     * @return int
     *
     * @throws \craft\errors\ShellCommandException
     * @throws \fortrabbit\Copy\Exceptions\CraftNotInstalledException
     * @throws \fortrabbit\Copy\Exceptions\PluginNotInstalledException
     * @throws \fortrabbit\Copy\Exceptions\RemoteException
     * @throws \yii\base\Exception
     */
    public function run(?string $stage = null)
    {
        $plugin = Plugin::getInstance();

        $filename = 'craft-copy-transfer.sql';

        $transferFile = $this->getLocalStoragePath($filename);
        $transferTarget = $this->getRemoteStoragePath($filename);
        $backupFile = $this->getRemoteStoragePath('craft-copy-recent.sql');

        $steps = $this->force ? 3 : 4;
        $messages = [];

        $this->head(
            'We are about to export the local database and import it to the fortrabbit App.',
            $this->getContextHeadline($this->stage),
            $this->force ? false : true
        );

        // Always ask (default no), but skip question in non-interactive mode
        if (! $this->confirm('Are you sure?', $this->force ? true : false)) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // Run 'before' commands and stop on error
        if (! $this->runBeforeDeployCommands()) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $bar = $this->createProgressBar($steps);

        // Step 1: Create dump of the current database
        $bar->setMessage($messages[] = 'Creating a local database dump');
        if ($plugin->database->export($transferFile)) {
            $bar->advance();
        }

        // Step 2: Upload that dump to remote
        $bar->setMessage($messages[] = "Uploading local dump to fortrabbit App - {$transferFile}");

        if ($plugin->ssh->upload($transferFile, $transferTarget)) {
            $bar->advance();
        }

        if ($this->force) {
            // Import on remote (does not require craft or copy on remote)
            $bar->setMessage($messages[] = 'Importing dump on fortrabbit App');

            try {
                $plugin->ssh->exec(
                    "php vendor/bin/craft-copy-import-db.php {$transferTarget} --force"
                );
                $bar->advance();
                $bar->setMessage('Database imported');
            } catch (RemoteException $e) {
                $this->errorBlock(
                    [
                        'Unable to import database. Deploy code first using this command:',
                        'php craft copy/code/up',
                    ]
                );
                return ExitCode::UNSPECIFIED_ERROR;
            }
        } else {
            // Step 3: Backup the remote database before importing the uploaded dump
            $bar->setMessage(
                $messages[] = "Creating a database dump on fortrabbit App ({$backupFile})"
            );

            try {
                $plugin->ssh->exec("php craft copy/db/to-file {$backupFile} --interactive=0");
                $bar->advance();
            } catch (CraftNotInstalledException $e) {
                $this->errorBlock(
                    [
                        'Unable to import database. Deploy code first using this command:',
                        'php craft copy/code/up',
                    ]
                );
                return ExitCode::UNSPECIFIED_ERROR;
            } catch (PluginNotInstalledException $e) {
                $this->errorBlock(
                    [
                        'The plugin seems not to be installed on fortrabbit. Deploy code first using this command:',
                        'php craft copy/code/up',
                    ]
                );
                return ExitCode::UNSPECIFIED_ERROR;
            } catch (RemoteException $e) {
                $this->errorBlock(
                    [
                        'An error occurred while creating the backup on the fortrabbit App',
                    ]
                );
                return ExitCode::UNSPECIFIED_ERROR;
            }

            // Step 4: Import on remote
            $bar->setMessage($messages[] = 'Importing dump on fortrabbit App');
            if ($plugin->ssh->exec("php craft copy/db/from-file {$transferTarget} --interactive=0")) {
                $bar->advance();
                $bar->setMessage('Database imported');
            }
        }

        $bar->finish();

        $this->section('Performed steps:');
        $this->listing($messages);

        if (! $this->force) {
            $this->section('Rollback?');
            $this->line(
                "ssh {$plugin->ssh->remote} 'php craft copy/db/from-file {$backupFile}'" . PHP_EOL
            );
        }

        return ExitCode::OK;
    }
}
