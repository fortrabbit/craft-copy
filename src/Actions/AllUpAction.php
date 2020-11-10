<?php

namespace fortrabbit\Copy\Actions;

use fortrabbit\Copy\Helpers\ConsoleOutputHelper;
use fortrabbit\Copy\Helpers\PathHelper;
use yii\console\ExitCode;

class AllUpAction extends StageAwareBaseAction
{
    use ConsoleOutputHelper;
    use PathHelper;

    public $verbose = false;

    public $interactive = true;


    /**
     * Copy everything up
     *
     * @param string|null $stage Name of the stage config
     *
     * @return int
     */
    public function run(string $stage = null): int
    {

        if (\Craft::$app->runAction('copy/code/up', [$stage, 'interactive' => true]) !== 0) {
            $this->errorBlock('Failed to copy the code');
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (\Craft::$app->runAction('copy/db/up', [$stage, 'interactive' => true, 'force' => true]) !== 0) {
            $this->errorBlock('Failed to copy the database');
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (\Craft::$app->runAction('copy/volumes/up', [$stage, 'interactive' => true]) !== 0) {
            $this->errorBlock('Failed to copy the assets');
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }
}
