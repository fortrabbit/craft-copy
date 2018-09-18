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

    const FILE_NAME_TEMPLATE = '{env}.copy.yaml';

    protected $env = 'production';

    /**
     * @var \fortrabbit\Copy\services\DeployConfig $config
     */
    protected $config;

    /**
     * @param string $env
     */
    public function setDeployEnviroment(string $env)
    {
        if ($this->env !== $env) {
            // reset config if the env has changed
            $this->config = null;
            $this->env    = $env;
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
     * @return string
     * @throws \yii\base\Exception
     */
    protected function getFullPathToConfig()
    {
        $file = str_replace('{env}', $this->env, self::FILE_NAME_TEMPLATE);

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

        return new DeployConfigModel($data);

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

}
