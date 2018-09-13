<?php

namespace fortrabbit\Copy;

use Craft;
use craft\base\Plugin as BasePlugin;
use fortrabbit\Copy\commands\AssetsDownAction;
use fortrabbit\Copy\commands\AssetsUpAction;
use fortrabbit\Copy\commands\BaseAction;
use fortrabbit\Copy\commands\CodeDownAction;
use fortrabbit\Copy\commands\CodeUpAction;
use fortrabbit\Copy\commands\DbDownAction;
use fortrabbit\Copy\commands\DbExportAction;
use fortrabbit\Copy\commands\DbImportAction;
use fortrabbit\Copy\commands\DbUpAction;
use fortrabbit\Copy\commands\InfoAction;
use fortrabbit\Copy\commands\SetupAction;
use fortrabbit\Copy\models\Settings;
use fortrabbit\Copy\models\StageConfig;
use fortrabbit\Copy\services\Git;
use fortrabbit\Copy\services\Rsync;
use ostark\Yii2ArtisanBridge\base\Commands;

use yii\base\ActionEvent;
use yii\console\Application as ConsoleApplication;

use fortrabbit\Copy\services\Ssh as SshService;
use fortrabbit\Copy\services\Dump as DumpService;
use fortrabbit\Copy\services\Rsync as RsyncService;
use fortrabbit\Copy\services\Git as GitService;

/**
 * Class Plugin
 *
 * @package fortrabbit\Copy
 *
 * @property  SshService   $ssh
 * @property  DumpService  $dump
 * @property  RsyncService $rsync
 * @property  GitService   $git
 *
 * @method    models\Settings getSettings()
 *
 */
class Plugin extends BasePlugin
{
    const ENV_NAME_APP = "APP_NAME";
    const ENV_DEFAULT_DEPLOY_ENVIRONMENT = "DEFAULT_DEPLOY_ENVIRONMENT";
    const ENV_NAME_SSH_REMOTE = "APP_SSH_REMOTE";
    const PLUGIN_ROOT_PATH = __DIR__;
    const REGIONS = [
        'us1' => 'US (AWS US-EAST-1 / Virginia)',
        'eu2' => 'EU (AWS EU-WEST-1 / Ireland)'
    ];

    const DASHBOARD_URL = "https://dashboard.fortrabbit.com";

    /**
     * Initialize Plugin
     */
    public function init()
    {
        parent::init();

        if (Craft::$app instanceof ConsoleApplication) {

            // Register console commands
            Commands::register(
                'copy',
                [
                    'assets/up'    => AssetsUpAction::class,
                    'assets/down'  => AssetsDownAction::class,
                    'code/up'      => CodeUpAction::class,
                    'code/down'    => CodeDownAction::class,
                    'db/up'        => DbUpAction::class,
                    'db/down'      => DbDownAction::class,
                    'db/to-file'   => DbExportAction::class,
                    'db/from-file' => DbImportAction::class,
                    'setup'        => SetupAction::class,
                    'info'         => InfoAction::class
                ],
                [
                    'v' => 'verbose',
                    'd' => 'directory',
                    'n' => 'dryRun',
                    'a' => 'app',
                    'e' => 'env'
                ]
            );

            Commands::setDefaultAction('copy', 'info');

            // Register services
            $this->setComponents([
                'ssh'   => SshService::class,
                'dump'  => DumpService::class,
                'rsync' => function () {
                    return Rsync::remoteFactory(getenv(self::ENV_NAME_SSH_REMOTE));
                },
                'git'   => function () {
                    return Git::fromDirectory(\Craft::getAlias('@root') ?: CRAFT_BASE_PATH);
                }
            ]);

            // Inject $db Connection
            $this->dump->db = \Craft::$app->getDb();

            // Inject $remote
            if (getenv(self::ENV_NAME_SSH_REMOTE)) {
                $this->ssh->remote = getenv(self::ENV_NAME_SSH_REMOTE);
            }

            // Globally apply stage config if --app=name is given
            \yii\base\Event::on(
                Commands::class,
                Commands::EVENT_BEFORE_ACTION,
                function (ActionEvent $event) {
                    if ($event->action instanceof BaseAction) {
                        if (is_string($event->action->app)) {
                            $stage = $this->getSettings()->getStageConfig($event->action->app);
                            $this->applyStageConfig($stage);
                        }
                    }
                }
            );
        }
    }


    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return \craft\base\Model|null
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * @param \fortrabbit\Copy\models\StageConfig $stageConfig
     */
    protected function applyStageConfig(StageConfig $stageConfig)
    {
        if ($stageConfig->sshRemoteUrl) {
            $this->ssh->remote = $stageConfig->sshRemoteUrl;
            $this->set('rsync', function () use ($stageConfig) {
                return Rsync::remoteFactory($stageConfig->sshRemoteUrl);
            });
        }
    }
}
