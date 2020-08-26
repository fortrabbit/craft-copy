<?php

namespace fortrabbit\Copy\Services;

use craft\helpers\FileHelper;
use fortrabbit\Copy\Exceptions\StageConfigNotFoundException;
use fortrabbit\Copy\Models\StageConfig;
use Symfony\Component\Yaml\Yaml;
use fortrabbit\Copy\Models\StageConfig as StageConfigModel;

/**
 * StageConfig Service
 */
class StageConfigAccess
{
    public const FILE_NAME_TEMPLATE = 'fortrabbit.{name}.yaml';
    public const CONFIG_SUBFOLDER = 'craft-copy';

    /**
     * @var string $name Default stage
     */
    protected $name = 'production';

    /**
     * @var \fortrabbit\Copy\Models\StageConfig | null $stage
     */
    protected $stage;

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        if ($this->name !== $name) {
            // reset config
            // if the config name has changed
            $this->stage = null;
            $this->name = $name;
        }
    }

    /**
     * @return \fortrabbit\Copy\Models\StageConfig
     * @throws \fortrabbit\Copy\Exceptions\StageConfigNotFoundException
     */
    public function get(): StageConfigModel
    {
        if ($this->stage instanceof StageConfigModel) {
            return $this->stage;
        }

        $this->stage = $this->getConfigDataFromFile();

        return $this->stage;
    }

    /**
     * @return \fortrabbit\Copy\Models\StageConfig
     * @throws \fortrabbit\Copy\Exceptions\StageConfigNotFoundException
     */
    protected function getConfigDataFromFile(): StageConfigModel
    {
        $fullPath = $this->getFullPathToConfig();

        if (!file_exists($fullPath)) {
            throw new StageConfigNotFoundException();
        }

        $data = Yaml::parse(file_get_contents($fullPath));
        $model = new StageConfigModel($data);
        $model->setName($this->name);

        return $model;
    }

    /**
     * @return string
     * @throws \yii\base\Exception
     */
    public function getFullPathToConfig(): string
    {
        $file = $this->getConfigFileName();

        return implode(DIRECTORY_SEPARATOR, [$this->getConfigPath(), $file]);
    }

    /**
     * @return string
     * @throws \yii\base\Exception
     */
    public function getConfigFileName(): string
    {
        return str_replace('{name}', $this->name, self::FILE_NAME_TEMPLATE);
    }

    public function getConfigPath(): string
    {
        return implode(DIRECTORY_SEPARATOR, [\Craft::$app->getPath()->getConfigPath(), self::CONFIG_SUBFOLDER]);
    }

    /**
     * Iterates over all config files and extracts the middle names
     *
     * @return array
     * @throws \yii\base\Exception
     */
    public function getConfigOptions(): array
    {
        $globPattern = str_replace('{name}', '*', self::FILE_NAME_TEMPLATE);
        $prefix = 'fortrabbit.';
        $suffix = '.yaml';

        // get config files
        $files = glob($this->getConfigPath() . DIRECTORY_SEPARATOR . $globPattern);

        // extract the prefix of the existing config files
        return array_map(
            function ($path) use ($suffix, $prefix) {
                return str_replace($prefix, '', basename($path, $suffix));
            },
            $files
        );
    }

    /**
     * @param StageConfigModel $config
     *
     * @return bool
     * @throws \yii\base\Exception
     */
    public function persist(StageConfigModel $config): bool
    {
        $this->stage = $config;
        $this->stage->setName($this->name);

        $yaml = Yaml::dump($this->stage->toArray(), Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);
        $fullPath = $this->getFullPathToConfig();

        // Assure directory
        FileHelper::createDirectory($this->getConfigPath());

        if (file_put_contents($fullPath, $yaml)) {
            return true;
        }

        return false;
    }
}
