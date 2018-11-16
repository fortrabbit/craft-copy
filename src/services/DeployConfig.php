<?php
/**
 * Copy plugin for Craft CMS 3.x
 **
 *
 * @link      https://www.fortrabbit.com/
 * @copyright Copyright (c) 2018 Oliver Stark
 */

namespace fortrabbit\Copy\services;

use fortrabbit\Copy\exceptions\DeployConfigNotFoundException;
use Symfony\Component\Yaml\Yaml;
use fortrabbit\Copy\models\DeployConfig as DeployConfigModel;

class DeployConfig
{
    const FILE_NAME_TEMPLATE = '{name}.copy.yaml';

    protected $name = 'production';

    /**
     * @var \fortrabbit\Copy\models\DeployConfig | null $config
     */
    protected $config;

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        if ($this->name !== $name) {
            // reset config
            // if the config name has changed
            $this->config = null;
            $this->name   = $name;
        }
    }

    /**
     * @return \fortrabbit\Copy\models\DeployConfig
     * @throws \fortrabbit\Copy\exceptions\DeployConfigNotFoundException
     */
    public function get(): DeployConfigModel
    {
        if ($this->config instanceof DeployConfigModel) {
            return $this->config;
        }

        $this->config = $this->getConfigDataFromFile();

        return $this->config;
    }

    /**
     * @return array
     * @throws \yii\base\Exception
     */
    public function getConfigOptions(): array
    {
        $globPattern = str_replace('{name}', '*', self::FILE_NAME_TEMPLATE);
        $suffix      = str_replace('{name}', '', self::FILE_NAME_TEMPLATE);

        // get config files
        $files = glob(\Craft::$app->getPath()->getConfigPath() . DIRECTORY_SEPARATOR . $globPattern);

        // extract the prefix of the existing config files
        return array_map(function ($path) use ($suffix) {
            return basename($path, $suffix);
        }, $files);
    }

    /**
     * @param \fortrabbit\Copy\models\DeployConfig $config
     *
     * @return bool
     * @throws \yii\base\Exception
     */
    public function persist(DeployConfigModel $config): bool
    {
        $this->config = $config;
        $yaml         = Yaml::dump($this->config->toArray());
        $fullPath     = $this->getFullPathToConfig();

        if (file_put_contents($fullPath, $yaml)) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     * @throws \yii\base\Exception
     */
    public function getFullPathToConfig()
    {
        $file = str_replace('{name}', $this->name, self::FILE_NAME_TEMPLATE);

        return \Craft::$app->getPath()->getConfigPath() . DIRECTORY_SEPARATOR . $file;
    }

    /**
     * @return \fortrabbit\Copy\models\DeployConfig
     * @throws \fortrabbit\Copy\exceptions\DeployConfigNotFoundException
     */
    protected function getConfigDataFromFile(): DeployConfigModel
    {
        $fullPath = $this->getFullPathToConfig();

        if (!file_exists($fullPath)) {
            throw new DeployConfigNotFoundException();
        }

        $data = Yaml::parse(file_get_contents($fullPath));
        $model = new DeployConfigModel($data);
        $model->setName($this->name);

        return $model;
    }
}
