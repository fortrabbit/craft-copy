<?php

declare(strict_types=1);

namespace fortrabbit\Copy;

use Craft;
use craft\base\Plugin as BasePlugin;
use craft\db\Connection;
use fortrabbit\Copy\Actions\AllDownAction;
use fortrabbit\Copy\Actions\AllUpAction;
use fortrabbit\Copy\Actions\CodeDownAction;
use fortrabbit\Copy\Actions\CodeUpAction;
use fortrabbit\Copy\Actions\DbDownAction;
use fortrabbit\Copy\Actions\DbExportAction;
use fortrabbit\Copy\Actions\DbImportAction;
use fortrabbit\Copy\Actions\DbUpAction;
use fortrabbit\Copy\Actions\FolderDownAction;
use fortrabbit\Copy\Actions\FolderUpAction;
use fortrabbit\Copy\Actions\InfoAction;
use fortrabbit\Copy\Actions\NitroSetupAction;
use fortrabbit\Copy\Actions\NitroDebugAction;
use fortrabbit\Copy\Actions\SetupAction;
use fortrabbit\Copy\Actions\VolumesDownAction;
use fortrabbit\Copy\Actions\VolumesUpAction;
use fortrabbit\Copy\EventHandlers\CommandOutputFormatHandler;
use fortrabbit\Copy\EventHandlers\IgnoredBackupTablesHandler;
use fortrabbit\Copy\Services\Database as DatabaseService;
use fortrabbit\Copy\Services\Git as GitService;
use fortrabbit\Copy\Services\Rsync as RsyncService;
use fortrabbit\Copy\Services\Ssh as SshService;
use fortrabbit\Copy\Services\StageConfigAccess;
use ostark\Yii2ArtisanBridge\ActionGroup;
use ostark\Yii2ArtisanBridge\base\Commands;
use ostark\Yii2ArtisanBridge\Bridge;
use yii\base\Event;
use yii\console\Application as ConsoleApplication;

/**
 * Craft Copy main plugin class
 *
 * @property SshService $ssh
 * @property DatabaseService $database
 * @property RsyncService $rsync
 * @property GitService $git
 * @property StageConfigAccess $stage
 */
class Plugin extends BasePlugin
{
    /**
     * @var string
     */
    public const DASHBOARD_URL = 'https://dashboard.fortrabbit.com';

    /**
     * @var int
     */
    public const DEPLOY_HOOK_TIMEOUT = 300;

    /**
     * @var string
     */
    public const ENV_DEFAULT_STAGE = 'DEFAULT_STAGE';

    /**
     * @var string
     */
    public const PLUGIN_ROOT_PATH = __DIR__;

    /**
     * @var array<string, string>
     */
    public const REGIONS = [
        'us1' => 'US (AWS US-EAST-1 / Virginia)',
        'eu2' => 'EU (AWS EU-WEST-1 / Ireland)',
    ];

    /**
     * Initialize Plugin
     */
    public function init(): void
    {
        parent::init();

        // Only console matters
        if (! (Craft::$app instanceof ConsoleApplication)) {
            return;
        }

        $this->registerConsoleCommands();

        $this->registerComponents();

        $this->registerEventHandlers();
    }

    private function registerConsoleCommands(): void
    {
        $actions = [
            'all/up' => AllUpAction::class,
            'all/down' => AllDownAction::class,
            'folder/up' => FolderUpAction::class,
            'folder/down' => FolderDownAction::class,
            'volumes/up' => VolumesUpAction::class,
            'volumes/down' => VolumesDownAction::class,
            'code/up' => CodeUpAction::class,
            'code/down' => CodeDownAction::class,
            'db/up' => DbUpAction::class,
            'db/down' => DbDownAction::class,
            'db/to-file' => DbExportAction::class,
            'db/from-file' => DbImportAction::class,
            'setup' => SetupAction::class,
            'nitro/setup' => NitroSetupAction::class,
            'nitro/debug' => NitroDebugAction::class,

        ];

        $options = [
            'v' => 'verbose',
            'd' => 'directory',
            'n' => 'dryRun',
            'a' => 'app',
            'e' => 'env',
            'f' => 'force',
        ];

        $group = (new ActionGroup('copy', 'Copy Craft between environments.'))
            ->setActions($actions)
            ->setDefaultAction('all/up')
            ->setOptions($options);

        // Register console commands
        Bridge::registerGroup($group);
    }

    private function registerComponents(): void
    {
        $this->setComponents(
            [
                'stage' => StageConfigAccess::class,
                'database' => fn() => new DatabaseService([
                    'db' => Craft::$app->getDb(),
                ]),
                'git' => fn() => GitService::fromDirectory(Craft::getAlias('@root') ?: CRAFT_BASE_PATH),
                'rsync' => fn() => RsyncService::remoteFactory($this->stage->get()->sshUrl),
                'ssh' => fn() => new SshService([
                    'remote' => $this->stage->get()->sshUrl,
                ]),
            ]
        );
    }

    private function registerEventHandlers(): void
    {
        Event::on(
            Commands::class,
            Commands::EVENT_BEFORE_ACTION,
            new CommandOutputFormatHandler()
        );
        Event::on(
            Connection::class,
            Connection::EVENT_BEFORE_CREATE_BACKUP,
            new IgnoredBackupTablesHandler()
        );
    }
}
