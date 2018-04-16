<?php namespace fortrabbit\Copy;

use Craft;
use craft\base\Plugin as BasePlugin;
use fortrabbit\Copy\ArtisanConsoleBridge\base\Commands;
use fortrabbit\Copy\commands\AssetsDownAction;
use fortrabbit\Copy\commands\AssetsUpAction;
use fortrabbit\Copy\commands\DbDownAction;
use fortrabbit\Copy\commands\DbExportAction;
use fortrabbit\Copy\commands\DbImportAction;
use fortrabbit\Copy\commands\DbUpAction;
use fortrabbit\Copy\commands\SetupAction;
use fortrabbit\Copy\ArtisanConsoleBridge\ArtisanConsoleBehavior;
use yii\base\ActionEvent;
use yii\base\Event;
use yii\console\Application as ConsoleApplication;

use fortrabbit\Copy\services\Ssh as SshService;
use fortrabbit\Copy\services\Dump as DumpService;
use fortrabbit\Copy\services\Rsync as RsyncService;
use yii\console\Controller;

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
            //Craft::$app->controllerMap['copy'] = Commands::class;

            Commands::registerCommands('copy', [
                'assets/up'    => AssetsUpAction::class,
                'assets/down'  => AssetsDownAction::class,
                'db/up'        => DbUpAction::class,
                'db/down'      => DbDownAction::class,
                'db/to-file'   => DbExportAction::class,
                'db/from-file' => DbImportAction::class,
                'setup'        => SetupAction::class
            ], 'db/from-file');
            Commands::registerOptions('copy', [
                'v' => 'verbose',
                'n' => 'name',
                'option-without-alias'
            ]);


            // Register services

            $this->setComponents([
                'ssh'   => SshService::class,
                'dump'  => DumpService::class,
                'rsync' => RsyncService::class
            ]);

            // Inject $db Connection
            $this->dump->db = \Craft::$app->getDb();

            // Inject $remote
            if (getenv(SetupAction::ENV_NAME_SSH_REMOTE)) {
                $this->ssh->remote = getenv(SetupAction::ENV_NAME_SSH_REMOTE);
            }

        }


    }


}
