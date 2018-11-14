<?php

namespace fortrabbit\Copy\commands;

use fortrabbit\Copy\exceptions\DeployConfigNotFoundException;
use fortrabbit\Copy\helpers\ConfigHelper;
use fortrabbit\Copy\Plugin;
use ostark\Yii2ArtisanBridge\base\Action;
use ostark\Yii2ArtisanBridge\base\Commands;

abstract class ConfigAwareBaseAction extends Action
{

    /**
     * @var string Name of the Environment (to apply multi staging configs)
     */
    public $env = null;

    /**
     * @var null|\fortrabbit\Copy\models\DeployConfig
     */
    protected $config = null;

    /**
     * @var \fortrabbit\Copy\Plugin
     */
    protected $plugin;


    use ConfigHelper;

    public function __construct(string $id, Commands $controller, array $config = [])
    {
        parent::__construct($id, $controller, $config);

        $this->plugin = Plugin::getInstance();
    }

    /**
     * @return bool
     * @throws \yii\base\Exception
     */
    public function beforeRun()
    {
        if ($this->hasDeprecatedEnvOption()) {
            return false;
        };

        // No deploy config files found?
        if (count($this->plugin->config->getConfigOptions()) === 0) {
            $this->errorBlock("The plugin is not configured yet.");
            $this->line('Run the setup command first:' . PHP_EOL);
            $this->output->type('php craft copy/setup' . PHP_EOL, 'fg=white', 20);

            return false;
        }

        // Get config name
        // Either the first arg of the command or from Env var
        if (!$configName = $this->getConfigName()) {
            return false;
        };


        try {
            $this->plugin->config->setName($configName);
            $this->config = Plugin::getInstance()->config->get();
        } catch (DeployConfigNotFoundException $exception) {

            $configFile = $this->plugin->config->getFullPathToConfig();
            $this->errorBlock(["Unable to find deploy config file '{$configFile}'"]);

            return false;
        }

        return true;
    }

    public function afterRun()
    {
        if (!$this->runAfterDeployCommands()) {
            return false;
        }
        return true;
    }

}
