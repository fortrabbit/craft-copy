<?php

namespace fortrabbit\Copy\commands;

use ostark\Yii2ArtisanBridge\base\Action;
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
        $this->title("Hello {$app}");

        $answer = $this->choice("What's your favorite animal?", ['Dog','Cat','Elephant']);

        if ($answer === 'Elephant') {
            $this->successBlock("Yes, '$answer' is correct.");
            return ExitCode::OK;
        } else {
            $this->errorBlock("No, '$answer' is the wrong.");
            return ExitCode::UNSPECIFIED_ERROR;
        }


        // Ask if not forced
        if (!$this->pleaseConfirm("Do you really want to sync upload your local assets? to ...")) {
            return ExitCode::UNSPECIFIED_ERROR;

        }


        die('SOME CALLED ME!!');
        return true;
    }
}
