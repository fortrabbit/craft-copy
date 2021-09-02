<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Actions;

use fortrabbit\Copy\Exceptions\StageConfigNotFoundException;
use fortrabbit\Copy\Helpers\DeployHooksHelper;
use fortrabbit\Copy\Models\StageConfig;
use fortrabbit\Copy\Plugin;
use fortrabbit\Copy\Services\DeprecatedConfigFixer;
use InvalidArgumentException;
use ostark\Yii2ArtisanBridge\base\Action;
use ostark\Yii2ArtisanBridge\base\Commands;
use ReflectionClass;
use Yii;
use yii\base\ActionEvent;
use yii\base\Event;
use yii\console\Controller;
use yii\console\ExitCode;

abstract class StageAwareBaseAction extends Action
{
    use DeployHooksHelper;

    /**
     * @var string Name of the Environment (to apply multi staging configs)
     */
    public $env = null;

    /**
     * @var \fortrabbit\Copy\Models\StageConfig|null
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
        }

        if ($this->isFortrabbitEnv()) {
            $this->errorBlock(
                "It looks like you are running this command in a fortrabbit app container. That won't work. Instead, you need to run Craft Copy commands from your local development environment."
            );
            return false;
        }

        // No stage config files found?
        if (count($this->plugin->stage->getConfigOptions()) === 0) {
            $this->errorBlock(
                'The plugin is not configured yet. Make sure to run this setup command first:'
            );
            $this->cmdBlock('php craft copy/setup');

            return false;
        }

        // Get config name
        // Either the first argument of the command or from Env var
        if (! $stageName = $this->getStageName()) {
            return false;
        }

        // Let the user choose
        if ($stageName === '?') {
            $options = $this->plugin->stage->getConfigOptions();
            $options = array_combine($options, $options);

            $stageName = $this->choice(
                'Select a stage',
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
            function (ActionEvent $event): void {
                if ($event->result === ExitCode::OK) {
                    $this->runAfterDeployCommands();
                }
            }
        );

        return true;
    }

    /**
     * Formatted headline
     */
    protected function getContextHeadline(StageConfig $stage): string
    {
        return "App: {$stage->app}.frb.io <comment>({$stage})</comment>";
    }

    /**
     * Extracts the name of the stage from the run command signature
     */
    protected function getStageName(): ?string
    {
        $action = new ReflectionClass(static::class);
        $runMethod = $action->getMethod('run');

        if (count($runMethod->getParameters()) === 0) {
            throw new InvalidArgumentException('function run() has no parameters.');
        }

        if ($runMethod->getParameters()[0]->getName() !== 'stage') {
            throw new InvalidArgumentException('First parameter of run() is not $stage.');
        }

        return Yii::$app->requestedParams[0]
            ?? getenv(Plugin::ENV_DEFAULT_STAGE)
                ?: 'production';
    }

    protected function isFortrabbitEnv(): bool
    {
        return getenv('APP_SECRETS') == '/etc/secrets.json';
    }
}
