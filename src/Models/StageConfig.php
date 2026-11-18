<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Models;

use craft\base\Model;
use craft\helpers\StringHelper;
use Symfony\Component\Process\Process;

/**
 * Class that represents the yml config
 * see src/fortrabbit.example.yml
 */
class StageConfig extends Model implements \Stringable
{
    /**
     * @var string[]
     */
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
     * @var string|null Path to Craft on the fortrabbit App, relative to the SSH home dir.
     * Defaults to the subfolder of Craft in the local Git repository.
     */
    public $craftPath;

    /**
     * @var string Git remote/branch
     */
    public $gitRemote;

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

    public function init(): void
    {
        parent::init();

        // Code is deployed with Git, so Craft lives in the same
        // subfolder of the repository on the fortrabbit App
        if ($this->craftPath === null) {
            $process = new Process(['git', 'rev-parse', '--show-prefix'], CRAFT_BASE_PATH);
            $process->run();
            $this->craftPath = $process->isSuccessful() ? $process->getOutput() : '';
        }

        $this->craftPath = trim((string) $this->craftPath, "/ \n");
    }

    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * Converts the model into an array
     * with SnakeCase keys
     * @return mixed[]
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
