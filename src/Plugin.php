<?php

namespace fortrabbit\Copy;

use Craft;
use craft\base\Plugin as BasePlugin;
use craft\db\Connection;
use fortrabbit\Copy\Actions\AllDownAction;
use fortrabbit\Copy\Actions\AllUpAction;
use fortrabbit\Copy\Actions\AssetsDownAction;
use fortrabbit\Copy\Actions\AssetsUpAction;
use fortrabbit\Copy\Actions\CodeDownAction;
use fortrabbit\Copy\Actions\CodeUpAction;
use fortrabbit\Copy\Actions\DbDownAction;
use fortrabbit\Copy\Actions\DbExportAction;
use fortrabbit\Copy\Actions\DbImportAction;
use fortrabbit\Copy\Actions\DbUpAction;
use fortrabbit\Copy\Actions\InfoAction;
use fortrabbit\Copy\Actions\SetupAction;
use fortrabbit\Copy\Actions\VolumesDownAction;
use fortrabbit\Copy\Actions\VolumesUpAction;
use fortrabbit\Copy\EventHandlers\CommandOutputFormatHandler;
use fortrabbit\Copy\EventHandlers\IgnoredBackupTablesHandler;
use fortrabbit\Copy\Services\DeployConfig;
use fortrabbit\Copy\Services\Git;
use fortrabbit\Copy\Services\Rsync;
use ostark\Yii2ArtisanBridge\ActionGroup;
use ostark\Yii2ArtisanBridge\base\Commands;
use ostark\Yii2ArtisanBridge\Bridge;
use yii\base\Event;
use yii\console\Application as ConsoleApplication;
use fortrabbit\Copy\Services\Ssh as SshService;
use fortrabbit\Copy\Services\Database as DatabaseService;
use fortrabbit\Copy\Services\Rsync as RsyncService;
use fortrabbit\Copy\Services\Git as GitService;

/**
 * Class Plugin
 *
 * @package fortrabbit\Copy
 *
 * @property  SshService $ssh
 * @property  DatabaseService $database
 * @property  RsyncService $rsync
 * @property  GitService $git
 * @property  DeployConfig $config
 *
 */
class Plugin extends BasePlugin
{
    public const DASHBOARD_URL = "https://dashboard.fortrabbit.com";
    public const ENV_DEPLOY_ENVIRONMENT = "DEPLOY_ENVIRONMENT";
    public const ENV_DEFAULT_CONFIG = "DEFAULT_CONFIG";
    public const PLUGIN_ROOT_PATH = __DIR__;
    public const REGIONS = [
        'us1' => 'US (AWS US-EAST-1 / Virginia)',
        'eu2' => 'EU (AWS EU-WEST-1 / Ireland)'
    ];

    /**
     * Initialize Plugin
     */
    public function init()
    {
        parent::init();

        // Only console matters
        if (!(Craft::$app instanceof ConsoleApplication)) {
            return;
        }

        // Console commands
        $group = (new ActionGroup('copy', 'Copy Craft between environments.'))
            ->setActions(
                [
                    'all/up' => AllUpAction::class,
                    'all/down' => AllDownAction::class,
                    'assets/up' => AssetsUpAction::class,
                    'assets/down' => AssetsDownAction::class,
                    'volumes/up' => VolumesUpAction::class,
                    'volumes/down' => VolumesDownAction::class,
                    'code/up' => CodeUpAction::class,
                    'code/down' => CodeDownAction::class,
                    'db/up' => DbUpAction::class,
                    'db/down' => DbDownAction::class,
                    'db/to-file' => DbExportAction::class,
                    'db/from-file' => DbImportAction::class,
                    'setup' => SetupAction::class,
                    'info' => InfoAction::class
                ]
            )
            ->setDefaultAction('info')
            ->setOptions(
                [
                    'v' => 'verbose',
                    'd' => 'directory',
                    'n' => 'dryRun',
                    'a' => 'app',
                    'e' => 'env',
                    'f' => 'force'
                ]
            );

        // Register console commands
        Bridge::registerGroup($group);

        // Register Event Handlers
        Event::on(Commands::class, Commands::EVENT_BEFORE_ACTION, new CommandOutputFormatHandler());
        Event::on(Connection::class, Connection::EVENT_BEFORE_CREATE_BACKUP, new IgnoredBackupTablesHandler());

        // Register (singleton) services
        $this->setComponents(
            [
                'config' => DeployConfig::class,
                'database' => function () {
                    return new DatabaseService(['db' => Craft::$app->getDb()]);
                },
                'git' => function () {
                    return GitService::fromDirectory(\Craft::getAlias('@root') ?: CRAFT_BASE_PATH);
                },
                'rsync' => function () {
                    return RsyncService::remoteFactory($this->config->get()->sshUrl);
                },
                'ssh' => function () {
                    return new SshService(['remote' => $this->config->get()->sshUrl]);
                },
            ]
        );
    }
}
