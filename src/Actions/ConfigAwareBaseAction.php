<?php

namespace fortrabbit\Copy\Actions;

use fortrabbit\Copy\Exceptions\DeployConfigNotFoundException;
use fortrabbit\Copy\Helpers\ConfigHelper;
use fortrabbit\Copy\Plugin;
use ostark\Yii2ArtisanBridge\base\Action;
use ostark\Yii2ArtisanBridge\base\Commands;
use yii\base\ActionEvent;
use yii\base\Event;
use yii\console\Controller;
use yii\console\ExitCode;

abstract class ConfigAwareBaseAction extends Action
{
    use ConfigHelper;

    /**
     * @var string Name of the Environment (to apply multi staging configs)
     */
    public $env = null;

    /**
     * @var null|\fortrabbit\Copy\Models\DeployConfig
     */
    protected $config = null;

    /**
     * @var \fortrabbit\Copy\Plugin
     */
    protected $plugin;


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
            $this->errorBlock('The plugin is not configured yet. Make sure to run this setup command first:');
            $this->cmdBlock("php craft copy/setup");

            return false;
        }

        // Get config name
        // Either the first arg of the command or from Env var
        if (!$configName = $this->getConfigName()) {
            return false;
        };

        // Let the user choose
        if ("?" === $configName) {
            $options = $this->plugin->config->getConfigOptions();
            $options = array_combine($options, $options);

            $configName = $this->choice(
                "Select a config",
                $options,
                $configName
            );
        }


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
        Event::on(
            Controller::class,
            Controller::EVENT_AFTER_ACTION,
            function (ActionEvent $event) {
                if ($event->result == ExitCode::OK) {
                    $this->runAfterDeployCommands();
                }
            }
        );

        return true;
    }
}
