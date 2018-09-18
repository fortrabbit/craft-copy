<?php

namespace fortrabbit\Copy\commands;

use fortrabbit\Copy\exceptions\DeployConfigNotFoundException;
use fortrabbit\Copy\Plugin;
use ostark\Yii2ArtisanBridge\base\Action;

abstract class EnvironmentAwareBaseAction extends Action
{

    /**
     * @var string Name of the App (to apply multi staging configs)
     */
    public $app = null;

    /**
     * @var string Name of the Environment (to apply multi staging configs)
     */
    public $env = null;

    /**
     * @var null|\fortrabbit\Copy\models\DeployConfig
     */
    protected $config = null;

    public function beforeRun()
    {
        $this->env = is_string($this->env)
            ? $this->env
            : getenv(Plugin::ENV_DEPLOY_ENVIRONMENT);

        try {
            Plugin::getInstance()->config->setDeployEnviroment($this->env);
            $this->config = Plugin::getInstance()->config->get();
        } catch (DeployConfigNotFoundException $exception) {
            $this->errorBlock(["Unable to find deploy config for '{$this->env}' environment."]);
            return false;
        }

        return true;
    }



}
