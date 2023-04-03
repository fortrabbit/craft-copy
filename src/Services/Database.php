<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Services;

use Craft;
use craft\base\Component;
use craft\db\Connection;
use craft\events\BackupEvent;
use craft\helpers\FileHelper;

/**
 * Database Service
 */
class Database extends Component
{
    /**
     * @var \craft\db\Connection
     */
    public $db;

    /**
     * @throws \craft\errors\ShellCommandException
     * @throws \yii\base\Exception
     */
    public function export(?string $file = null): string
    {
        $file = $this->prepareFile($file);

        $this->alterCraftDefaultBackupCommand($file);

        $this->db->backupTo($file);

        return $file;
    }

    /**
     * @throws \craft\errors\FileException
     * @throws \craft\errors\ShellCommandException
     * @throws \yii\base\Exception
     */
    public function import(?string $file = null): string
    {
        $file = $this->prepareFile($file);

        $this->db->restore($file);

        return $file;
    }

    protected function prepareFile(string $file): string
    {
        $file = FileHelper::normalizePath($file);
        $dir = dirname($file);

        if ( ! is_dir($dir)) {
            FileHelper::createDirectory($dir);
        }

        return $file;
    }


    protected function alterCraftDefaultBackupCommand(string $file): void
    {
        // Fire a 'beforeCreateBackup' event
        $event = new BackupEvent([
                                     'file' => $file,
                                     'ignoreTables' => $this->db->getIgnoredBackupTables(),
                                 ]);
        $this->trigger(Connection::EVENT_BEFORE_CREATE_BACKUP, $event);

        // Determine the command that should be executed
        $backupCommand = Craft::$app->getConfig()->getGeneral()->backupCommand;

        if ($backupCommand === null) {
            $backupCommand = $this->db->getSchema()->getDefaultBackupCommand($event->ignoreTables);
        }

        // The actual overwrite to allow .my.cnf again
        // It basically reverts this change:
        // https://github.com/craftcms/cms/commit/c1068dd56974172a98213b616461266711aef86a
        $backupCommand = str_replace(
            '--defaults-file',
            '--defaults-extra-file',
            $backupCommand
        );

        // Disable single-transaction for now
        $backupCommand = str_replace(
            ' --single-transaction',
            '',
            $backupCommand
        );

        // Disable ssl-mode for now
        $backupCommand = str_replace(
            '--no-tablespaces',
            '--no-tablespaces --ssl-mode=DISABLED',
            $backupCommand
        );



        Craft::$app->getConfig()->getGeneral()->backupCommand = $backupCommand;
    }
}
