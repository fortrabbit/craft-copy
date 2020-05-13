<?php

namespace fortrabbit\Copy\Actions;

use fortrabbit\Copy\Helpers\ConsoleOutputHelper;
use fortrabbit\Copy\Helpers\PathHelper;
use yii\console\ExitCode;

class AllDownAction extends ConfigAwareBaseAction
{
    use ConsoleOutputHelper;
    use PathHelper;

    public $verbose = false;

    public $interactive = true;


    /**
     * Copy everything down
     *
     * @param string|null $config Name of the deploy config
     *
     * @return int
     */
    public function run(string $config = null): int
    {
        // Ask
        if (!$this->confirm("Do you want to copy all <underline>assets</underline>, the <underline>code</underline> and the <underline>db</underline> down?", true)) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (\Craft::$app->runAction('copy/db/down', ['interactive' => true]) !== 0) {
            $this->errorBlock('Failed to copy the database');
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (\Craft::$app->runAction('copy/assets/down', ['interactive' => true]) !== 0) {
            $this->errorBlock('Failed to copy the assets');
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (\Craft::$app->runAction('copy/code/down', ['interactive' => true]) !== 0) {
            $this->errorBlock('Failed to copy the code');
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }
}

