<?php

namespace fortrabbit\Copy\Actions;

use fortrabbit\Copy\Exceptions\StageConfigNotFoundException;
use fortrabbit\Copy\Helpers\ConfigHelper;
use fortrabbit\Copy\Helpers\DeprecationHelper;
use fortrabbit\Copy\Plugin;
use fortrabbit\Copy\Services\DeprecatedConfigFixer;
use ostark\Yii2ArtisanBridge\base\Action;
use ostark\Yii2ArtisanBridge\base\Commands;
use yii\base\ActionEvent;
use yii\base\Event;
use yii\console\Controller;
use yii\console\ExitCode;

abstract class StageAwareBaseAction extends Action
{
    use ConfigHelper;

    /**
     * @var string Name of the Environment (to apply multi staging configs)
     */
    public $env = null;

    /**
     * @var null|\fortrabbit\Copy\Models\StageConfig
     */
    protected $stage = null;

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
        if (DeprecatedConfigFixer::hasDeprecatedConfig()) {
            $fixer = new DeprecatedConfigFixer($this, $this->plugin->stage);
            $fixer->showWarning();
            $fixer->askAndRun();
            return false;
        };

        // No stage config files found?
        if (count($this->plugin->stage->getConfigOptions()) === 0) {
            $this->errorBlock('The plugin is not configured yet. Make sure to run this setup command first:');
            $this->cmdBlock("php craft copy/setup");

            return false;
        }

        // Get config name
        // Either the first arg of the command or from Env var
        if (!$stageName = $this->getStageName()) {
            return false;
        };

        // Let the user choose
        if ("?" === $stageName) {
            $options = $this->plugin->stage->getConfigOptions();
            $options = array_combine($options, $options);

            $stageName = $this->choice(
                "Select a stage",
                $options,
                $stageName
            );
        }


        try {
            $this->plugin->stage->setName($stageName);
            $this->stage = Plugin::getInstance()->stage->get();
        } catch (StageConfigNotFoundException $exception) {
            $configFile = $this->plugin->stage->getFullPathToConfig();
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
