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
     *
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
        $steps = $this->force ? 3 : 4;
        $messages = [];

        $this->head(
            'We are about to export the local database and import it to the fortrabbit App.',
            $this->getContextHeadline($this->stage),
            $this->force ? false : true
        );

        // Always ask (default no), but skip question in non-interactive mode
        if (! $this->confirm('Are you sure?', $this->force)) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // Run 'before' commands and stop on error
        if (! $this->runBeforeDeployCommands()) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if ($plugin->ssh->exec('ls vendor/bin/craft-copy-import-db.php | wc -l') && trim($plugin->ssh->getOutput()) !== '1') {
            return $this->printAndExit(new PluginNotInstalledException());
        }

        $bar = $this->createProgressBar($steps);

        // Step 1: Create dump of the current database
        $bar->setMessage($messages[] = 'Creating a local database dump');
        if ($plugin->database->export($transferFile)) {
            $bar->advance();
        }

        // Step 2: Upload that dump to remote
        $bar->setMessage($messages[] = "Uploading local dump to fortrabbit App - {$transferFile}");
        if ($plugin->ssh->upload($transferFile, $transferFile)) {
            $bar->advance();
        }

        if ($this->force) {
            // Import on remote (does not require craft or copy on remote)
            $bar->setMessage($messages[] = 'Importing dump on fortrabbit App');

            // Try to create storage path first
            $plugin->ssh->exec("mkdir -p {$path}");

            try {
                $plugin->ssh->exec(
                    "php vendor/bin/craft-copy-import-db.php {$transferFile} --force"
                );
                $bar->advance();
                $bar->setMessage('Database imported');
            } catch (RemoteException $remoteException) {
                return $this->printAndExit($remoteException);
            }
        } else {
            // Step 3: Backup the remote database before importing the uploaded dump
            $bar->setMessage(
                $messages[] = "Creating a database dump on fortrabbit App ({$backupFile})"
            );

            try {
                $plugin->ssh->exec("php craft copy/db/to-file {$backupFile} --interactive=0");
                $bar->advance();
            } catch (RemoteException $remoteException) {
                return $this->printAndExit($remoteException);
            }

            // Step 4: Import on remote
            $bar->setMessage($messages[] = 'Importing dump on fortrabbit App');
            if ($plugin->ssh->exec("php craft copy/db/from-file {$transferFile} --interactive=0")) {
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

    protected function printAndExit(RemoteException $exception): int
    {
        if ($exception instanceof CraftNotInstalledException) {
            $this->errorBlock(
                [
                    'Unable to import database. Deploy code first using this command:',
                    'php craft copy/code/up',
                ]
            );

            return ExitCode::UNSPECIFIED_ERROR;
        }

        if ($exception instanceof PluginNotInstalledException) {
            $this->errorBlock(
                [
                    'The plugin seems not to be installed on fortrabbit. Deploy code first using this command:',
                    'php craft copy/code/up',
                ]
            );

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->errorBlock([$exception->getMessage()]);

        return ExitCode::UNSPECIFIED_ERROR;
    }
}
