<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Models;

use craft\base\Model;
use craft\helpers\StringHelper;

/**
 * Class that represents the yml config
 * see src/fortrabbit.example.yml
 */
class StageConfig extends Model
{
    public const DEPREACTED_PROPERTIES = ['sshPath', 'assetPath'];

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
     * @var string Absolute path to Craft base directory on the remote
     */
    public $basePath;

    /**
     * @var string Absolute path to Craft storage directory on the remote
     */
    public $storagePath;

    /**
     * @var array Scripts that run before commands locally
     */
    public $before = [
        'code/up' => [
            // noting defined by default
        ],
    ];

    /**
     * @var array Scripts that run after commands locally
     */
    public $after = [
        'code/down' => [
            'php craft migrate/all',
            'php craft project-config/apply',
        ],
    ];

    /**
     * @var string Name of deploy config (base of the file name)
     */
    protected $name = '';

    public function __construct(array $config = [])
    {
        foreach ($config as $key => $value) {
            unset($config[$key]);
            $prop = StringHelper::toCamelCase($key);
            if (in_array($prop, self::DEPREACTED_PROPERTIES, true)) {
                continue;
            }
            $config[$prop] = $value;
        }

        parent::__construct($config);
    }

    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * Converts the model into an array
     * with SnakeCase keys
     */
    public function toArray(
        array $fields = [],
        array $expand = [],
        $recursive = true
    ): array {
        $array = parent::toArray($fields, $expand, $recursive);

        foreach ($array as $key => $value) {
            unset($array[$key]);
            $array[StringHelper::toSnakeCase($key)] = $value;
        }

        return $array;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
