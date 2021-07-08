<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Actions;

use Craft;
use ostark\Yii2ArtisanBridge\base\Action;
use yii\console\ExitCode;

class PathAction extends Action
{

    public $verbose = false;

    public $interactive = true;

    /**
     * Return the paths for this environment as JSON
     *
     * @param string|null $stage Name of the stage config
     */
    public function run(?string $stage = null): int
    {
        $paths = [
            'basePath' => Craft::getAlias('@root'),
            'storagePath' => Craft::getAlias('@storage'),
        ];

        $this->output->write(json_encode($paths, JSON_PRETTY_PRINT) . "\r\n");
        return ExitCode::OK;
    }
}
