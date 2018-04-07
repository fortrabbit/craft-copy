<?php namespace fortrabbit\Copy;

use Craft;
use craft\base\Plugin as BasePlugin;
use fortrabbit\Copy\commands\SetupAction;
use yii\console\Application as ConsoleApplication;

use fortrabbit\Copy\services\Ssh as SshService;
use fortrabbit\Copy\services\Dump as DumpService;
use fortrabbit\Copy\services\Rsync as RsyncService;

/**
 * Class Plugin
 *
 * @package fortrabbit\Copy
 *
 * @property  SshService   $ssh
 * @property  DumpService  $dump
 * @property  RsyncService $rsync
 */
class Plugin extends BasePlugin
{
    /**
     * Initialize Plugins
     */
    public function init()
    {
        parent::init();

        if (Craft::$app instanceof ConsoleApplication) {

            // Register console commands
            Craft::$app->controllerMap['copy'] = Commands::class;

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
