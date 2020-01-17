<?php

namespace fortrabbit\Copy\Models;

use craft\base\Model;
use craft\helpers\StringHelper;

/**
 * Class that represents the yml config
 * see src/fortrabbit.example-config.yml
 *
 */
class DeployConfig extends Model
{
    public const DEPREACTED_PROPERTIES = ['sshPath'];

    /**
     * @var string Name of App
     */
    public $app;

    /**
     * @var string SSH endpoint
     */
    public $sshUrl;

    /**
     * @var string Git remote/branch
     */
    public $gitRemote;

    /**
     * @var string Relative path to assets
     */
    public $assetPath = 'web/assets';

    /**
     * @var array Scripts that run before commands locally
     */
    public $before = [
        'code/up' => [
            'echo  "Script example: " $(git rev-parse HEAD)'
        ]
    ];

    /**
     * @var array Scripts that run after commands locally
     */
    public $after = [
        'code/down' => [
            'php craft migrate/all'
        ]
    ];

    /**
     * @var string Name of deploy config (base of the file name)
     */
    protected $name = '';

    /**
     * DeployConfig constructor.
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        foreach ($config as $key => $value) {
            unset($config[$key]);
            $prop = StringHelper::toCamelCase($key);
            if (in_array($prop, self::DEPREACTED_PROPERTIES)) {
                continue;
            }
            $config[$prop] = $value;
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

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }
}
