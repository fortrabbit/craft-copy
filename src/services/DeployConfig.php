<?php
/**
 * Copy plugin for Craft CMS 3.x
 **
 *
 * @link      https://www.fortrabbit.com/
 * @copyright Copyright (c) 2018 Oliver Stark
 */

namespace fortrabbit\Copy\services;


use Symfony\Component\Yaml\Exception\ParseException as YamlParseException;
use Symfony\Component\Yaml\Yaml;
use fortrabbit\Copy\models\DeployConfig as DeployConfigModel;

class DeployConfig
{

    const FILE_NAME = '{env}.copy.yaml';

    const DEFAULT_DEPLOY_ENVIRONMENT = 'production';

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

    public function get(): DeployConfigModel
    {
        if ($this->config instanceof DeployConfigModel) {
            return $this->config;
        }

        try {
            $this->config = $this->getConfigDataFromFile();;
        } catch (\InvalidArgumentException $e) {
            // file does not exist
            $this->config = $this->writeConfigDataToFile();
        } catch (YamlParseException $e) {
            // invalid yaml
        }

        return $this->config;
    }


    protected function getFullPathToConfig()
    {
        $file = str_replace('{env}', $this->env, self::FILE_NAME);

        return \Craft::$app->getPath()->getConfigPath() . DIRECTORY_SEPARATOR . $file;
    }

    /**
     * @return \fortrabbit\Copy\models\DeployConfig
     */
    protected function getConfigDataFromFile(): DeployConfigModel
    {
        $fullPath = $this->getFullPathToConfig();
        if (!file_exists($fullPath)) {
            throw new \InvalidArgumentException("File '$fullPath' does not exist");
        }

        $data = Yaml::parse(file_get_contents($fullPath));

        return new \fortrabbit\Copy\models\DeployConfig($data);

    }

    /**
     * @return bool
     */
    protected function persist(): bool
    {
        if (!$this->config instanceof DeployConfigModel) {
            return false;
        }

        $yaml     = Yaml::dump($this->config->toArray());
        $fullPath = $this->getFullPathToConfig();

        if (file_put_contents($fullPath, $yaml)) {
            return true;
        }

        return false;

    }

}
