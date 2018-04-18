<?php

namespace fortrabbit\Copy\commands;

use ostark\Yii2ArtisanBridge\base\Action;
use yii\console\ExitCode;


/**
 * Class AssetsDownAction
 *
 * @package fortrabbit\Copy\commands
 */
class AssetsDownAction extends Action
{

    /**
     * Download Assets
     *
     * @param string|null $app
     *
     * @return bool
     */
    public function run(string $app = null)
    {
        // Ask if not forced
        if (!$this->pleaseConfirm("Do you really want to sync upload your local assets? to ...")) {
            return ExitCode::UNSPECIFIED_ERROR;
        }


        die('SOME CALLED ME!!');
        return true;
    }
}
