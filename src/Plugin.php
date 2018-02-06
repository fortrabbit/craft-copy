<?php namespace fortrabbit\Sync;

use Craft;
use craft\base\Plugin as BasePlugin;
use fortrabbit\Sync\commands\SetupAction;
use yii\console\Application as ConsoleApplication;

use fortrabbit\Sync\services\Ssh as SshService;
use fortrabbit\Sync\services\Dump as DumpService;
use fortrabbit\Sync\services\Rsync as RsyncService;

/**
 * Class Plugin
 *
 * @package fortrabbit\Sync
 *
 * @property  SshService   $ssh
 * @property  DumpService  $dump
 * @property  RsyncService $rsync
 */
class Plugin extends BasePlugin
{
    public $version = '0.2.0';

    /**
     * Initialize Plugins
     */
    public function init()
    {
        parent::init();

        if (Craft::$app instanceof ConsoleApplication) {

            // Register console commands
            Craft::$app->controllerMap['sync'] = SyncCommands::class;

            // Register services

            $this->setComponents([
                'ssh'   => SshService::class,
                'dump'  => DumpService::class,
                'rsync' => RsyncService::class
            ]);

            // Inject $remote and $db Connection
            if (getenv(SetupAction::ENV_NAME_SSH_REMOTE)) {
                $this->ssh->remote = getenv(SetupAction::ENV_NAME_SSH_REMOTE);
                $this->dump->db    = \Craft::$app->getDb();
            }

        }


    }


}
