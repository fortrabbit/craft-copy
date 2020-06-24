<?php

namespace fortrabbit\Copy\Services;

use craft\base\Component;
use craft\helpers\FileHelper;

/**
 * Database Service
 *
 * @author    Oliver Stark
 * @package   Copy
 * @since     1.0.0
 */
class Database extends Component
{
    /**
     * @var \craft\db\Connection
     */
    public $db;

    /**
     * @param string|null $file
     *
     * @return null|string
     * @throws \craft\errors\ShellCommandException
     * @throws \yii\base\Exception
     */
    public function export(string $file = null)
    {
        $file = $this->prepareFile($file);

        $this->db->backupTo($file);

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

        if (!is_dir($dir)) {
            FileHelper::createDirectory($dir);
        }

        return $file;
    }

    /**
     * @param string $file
     *
     * @return string|null $file /path/to/file.sql
     *
     * @throws \craft\errors\FileException
     * @throws \craft\errors\ShellCommandException
     * @throws \yii\base\Exception
     */
    public function import(string $file = null)
    {
        $file = $this->prepareFile($file);

        $this->db->restore($file);

        return $file;
    }
}
