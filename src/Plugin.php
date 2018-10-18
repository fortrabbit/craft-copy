<?php

namespace fortrabbit\Copy;

use Craft;
use craft\base\Plugin as BasePlugin;
use fortrabbit\Copy\commands\AssetsDownAction;
use fortrabbit\Copy\commands\AssetsUpAction;
use fortrabbit\Copy\commands\CodeDownAction;
use fortrabbit\Copy\commands\CodeUpAction;
use fortrabbit\Copy\commands\DbDownAction;
use fortrabbit\Copy\commands\DbExportAction;
use fortrabbit\Copy\commands\DbImportAction;
use fortrabbit\Copy\commands\DbUpAction;
use fortrabbit\Copy\commands\InfoAction;
use fortrabbit\Copy\commands\SetupAction;
use fortrabbit\Copy\models\Settings;
use fortrabbit\Copy\services\DeployConfig;
use fortrabbit\Copy\services\Git;
use fortrabbit\Copy\services\Rsync;

use ostark\Yii2ArtisanBridge\ActionGroup;
use ostark\Yii2ArtisanBridge\Bridge;

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
 * @property  DeployConfig $config
 *
 * @method    models\Settings getSettings()
 *
 */
class Plugin extends BasePlugin
{
    const ENV_NAME_APP = "APP_NAME";
    const ENV_DEPLOY_ENVIRONMENT = "DEPLOY_ENVIRONMENT";
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

            $group = (new ActionGroup('copy', 'Copy Craft between environments.'))
                ->setActions([
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
                ])
                ->setDefaultAction('info')
                ->setOptions([
                        'v' => 'verbose',
                        'd' => 'directory',
                        'n' => 'dryRun',
                        'a' => 'app',
                        'e' => 'env'
                    ]
                );

            // Register console commands
            Bridge::registerGroup($group);

            // Register services
            $this->setComponents([
                'config' => DeployConfig::class,
                'dump'   => function () {
                    return new DumpService(['db' => Craft::$app->getDb()]);
                },
                'git'    => function () {
                    return GitService::fromDirectory(\Craft::getAlias('@root') ?: CRAFT_BASE_PATH);
                },
                'rsync'  => function () {
                    return RsyncService::remoteFactory($this->config->get()->sshUrl);
                },
                'ssh'    => function () {
                    return new SshService(['remote' => $this->config->get()->sshUrl]);
                },
            ]);

            // Inject $db Connection
            //$this->dump->db = \Craft::$app->getDb();

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


}
