<?php

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
     * @var array Scripts that run before commands locally
     */
    public $before = [
        'code/up' => [
            "# insert your npm build commands here, e.g ",
            "# npm run prod",
            "# and sync your build folder to the fortabbit App",
            "# php craft copy/folder/up {stage} web/build/prod --interactive=0"
        ]
    ];

    /**
     * @var array Scripts that run after commands locally
     */
    public $after = [
        'code/down' => [
            'php craft migrate/all',
            'php craft project-config/apply'
        ]
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

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
