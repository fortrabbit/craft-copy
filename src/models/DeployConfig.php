<?php

namespace fortrabbit\Copy\models;

use craft\base\Model;
use craft\helpers\StringHelper;

class DeployConfig extends Model
{
    public $app;
    public $sshUrl;
    public $sshPath;
    public $gitRemote;
    public $before = [];
    public $after = [];

    /**
     * DeployConfig constructor.
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        foreach ($config as $key => $value) {
            unset($config[$key]);
            $config[StringHelper::toCamelCase($key)] = $value;
        }
        parent::__construct($config);
    }

    /**
     * Converts the model into an array
     * with SnakeCase keys
     *
     * @param array $fields
     * @param array $expand
     * @param bool  $recursive
     *
     * @return array
     */
    public function toArray(array $fields = [], array $expand = [], $recursive = true)
    {
        $array = parent::toArray($fields, $expand, $recursive);

        foreach ($array as $key => $value) {
            unset($array[$key]);
            $array[StringHelper::toSnakeCase($key)] = $value;
        }

        return $array;
    }

}
