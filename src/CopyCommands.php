<?php

namespace fortrabbit\Copy;

use fortrabbit\Copy\commands\AssetsDownAction;
use fortrabbit\Copy\commands\AssetsUpAction;
use fortrabbit\Copy\commands\DbDownAction;
use fortrabbit\Copy\commands\DbExportAction;
use fortrabbit\Copy\commands\DbImportAction;
use fortrabbit\Copy\commands\DbUpAction;
use fortrabbit\Copy\commands\SetupAction;
use yii\console\Controller as BaseConsoleController;

/**
 *
 * copy/setup
 * copy/db/up
 * copy/db/down
 * copy/db/to-file
 * copy/db/from-file
 * copy/assets/up (folder || ALL)
 * copy/assets/down (folder || ALL)
 *
 */

/**
 * Copy -  move Craft effortlessly
 */
class CopyCommands extends BaseConsoleController
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
            'setup'        => SetupAction::class,
            'db/down'      => DbDownAction::class,
            'db/up'        => DbUpAction::class,
            'db/from-file' => DbImportAction::class,
            'db/to-file'   => DbExportAction::class,
            'assets/down'  => AssetsDownAction::class,
            'assets/up'    => AssetsUpAction::class,
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
