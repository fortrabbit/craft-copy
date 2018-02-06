<?php

namespace fortrabbit\Sync;

use fortrabbit\Sync\commands\AssetsDownAction;
use fortrabbit\Sync\commands\DbDownAction;
use fortrabbit\Sync\commands\DbExportAction;
use fortrabbit\Sync\commands\DbImportAction;
use fortrabbit\Sync\commands\DbUpAction;
use fortrabbit\Sync\commands\SetupAction;
use yii\console\Controller as BaseConsoleController;

/**
 *
 * sync/setup
 * sync/db/up (foo.sql)
 * sync/db/down (foo.sql)
 * sync/assets/up (folder || ALL)
 * sync/assets/down (folder || ALL)
 *
 */

/**
 * fortrabbit Sync - a tool belt for easy deployment
 */
class SyncCommands extends BaseConsoleController
{

    public $defaultAction = 'setup';


    // Options

    public $app;

    public $region;

    /**
     * Force execution
     *
     * @var bool
     */
    public $force = false;


    public function actions()
    {
        return [
            'setup'       => SetupAction::class,
            'db/down'     => DbDownAction::class,
            'db/up'       => DbUpAction::class,
            'db/import'   => DbImportAction::class,
            'db/export'   => DbExportAction::class,
            'assets/down' => AssetsDownAction::class,
            'assets/up'   => AssetsDownAction::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        $globalOptions = ['app', 'region', 'force', 'help'];
        $actionClass   = $this->actions()[$actionID];
        $actionOptions = $actionClass::OPTIONS;

        return array_merge($globalOptions, $actionOptions);

    }


}
