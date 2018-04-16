<?php

namespace fortrabbit\Copy\commands;

use fortrabbit\Copy\ArtisanConsoleBridge\base\Action;
use yii\console\ExitCode;

/**
 * Class AssetsUpAction
 *
 * @package fortrabbit\Copy\commands
 */
class AssetsUpAction extends Action
{

    /**
     * Upload Assets
     *
     * @param string|null $app
     *
     * @return bool
     */
    public function run(string $app = null)
    {
        $this->cautionBlock('Foo Bar ...');
        $this->title('Foo dsf sdf sfs');
        $this->successBlock('Foo Bar ...');

        $this->section('some section');
        $this->listing(['dog','cat','elephant']);

        $this->choice('Really', ['yes','no','maybe']);



        // Ask if not forced
        if (!$this->pleaseConfirm("Do you really want to sync upload your local assets? to ...")) {
            return ExitCode::UNSPECIFIED_ERROR;

        }


        die('SOME CALLED ME!!');
        return true;
    }
}
