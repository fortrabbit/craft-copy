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
     * @return string|null
     * @throws \craft\errors\ShellCommandException
     * @throws \yii\base\Exception
     */
    public function export(?string $file = null)
    {
        $file = $this->prepareFile($file);

        $this->db->backupTo($file);

        return $file;
    }

    /**
     * @param string $file
     *
     * @return string|null /path/to/file.sql
     *
     * @throws \craft\errors\FileException
     * @throws \craft\errors\ShellCommandException
     * @throws \yii\base\Exception
     */
    public function import(?string $file = null)
    {
        $file = $this->prepareFile($file);

        $this->db->restore($file);

        return $file;
    }

    /**
     * @param string $file
     *
     * @return string
     */
    protected function prepareFile($file)
    {
        $file = FileHelper::normalizePath($file);
        $dir = dirname($file);

        if (! is_dir($dir)) {
            FileHelper::createDirectory($dir);
        }

        return $file;
    }
}
