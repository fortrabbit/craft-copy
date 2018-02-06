<?php
/**
 * sync plugin for Craft CMS 3.x
 *
 * ss
 *
 * @link      http://www.fortrabbit.com
 * @copyright Copyright (c) 2018 Oliver Stark
 */

namespace fortrabbit\Sync\services;

use craft\base\Component;
use craft\helpers\FileHelper;

/**
 * Dump Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Oliver Stark
 * @package   Sync
 * @since     1.0.0
 */
class Dump extends Component
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
     * @param $file
     *
     * @return string
     */
    protected function prepareFile($file) {

        $file = ($file)
            ? $file
            : '/tmp/sync/db-' . date('Ymd-His') . '.sql';

        $file = FileHelper::normalizePath($file);
        $dir  = dirname($file);

        if (!is_dir($dir)) {
            FileHelper::createDirectory($dir);
        }

        return $file;

    }
}
