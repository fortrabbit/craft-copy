<?php
/**
 * Copy plugin for Craft CMS 3.x
 **
 *
 * @link      https://www.fortrabbit.com/
 * @copyright Copyright (c) 2018 Oliver Stark
 */

namespace fortrabbit\Copy\services;

use craft\base\Component;
use craft\helpers\FileHelper;

/**
 * Dump Service
 *
 * @author    Oliver Stark
 * @package   Copy
 * @since     1.0.0
 */
class Dump extends Component
{
    /**
     * @var \craft\db\Connection
     */
    public $db;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

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


    /**
     * @param string $file
     *
     * @return string
     */
    protected function prepareFile($file)
    {
        $file = FileHelper::normalizePath($file);
        $dir  = dirname($file);

        if (!is_dir($dir)) {
            FileHelper::createDirectory($dir);
        }

        return $file;

    }
}
