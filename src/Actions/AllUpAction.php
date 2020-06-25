<?php

namespace fortrabbit\Copy\Actions;

use fortrabbit\Copy\Helpers\ConsoleOutputHelper;
use fortrabbit\Copy\Helpers\PathHelper;
use yii\console\ExitCode;

class AllUpAction extends ConfigAwareBaseAction
{
    use ConsoleOutputHelper;
    use PathHelper;

    public $verbose = false;

    public $interactive = true;


    /**
     * Copy everything up
     *
     * @param string|null $config Name of the deploy config
     *
     * @return int
     */
    public function run(string $config = null): int
    {
        // Ask
        if (!$this->confirm("Do you want to copy all volumes, the code and the database up?", true)) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (\Craft::$app->runAction('copy/db/up', ['interactive' => true, 'force' => true]) !== 0) {
            $this->errorBlock('Failed to copy the database');
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (\Craft::$app->runAction('copy/volumes/up', ['interactive' => true]) !== 0) {
            $this->errorBlock('Failed to copy the assets');
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (\Craft::$app->runAction('copy/code/up', ['interactive' => true]) !== 0) {
            $this->errorBlock('Failed to copy the code');
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }
}
