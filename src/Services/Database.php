<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Services;

use craft\base\Component;
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

        if (! is_dir($dir)) {
            FileHelper::createDirectory($dir);
        }

        return $file;
    }
}
