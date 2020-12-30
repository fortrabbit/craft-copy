<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Actions;

use fortrabbit\Copy\Helpers\ConsoleOutputHelper;
use fortrabbit\Copy\Helpers\PathHelper;
use yii\console\ExitCode;

class FolderUpAction extends StageAwareBaseAction
{
    use ConsoleOutputHelper;
    use PathHelper;

    public $dryRun = false;

    public $verbose = false;

    /**
     * Upload a folder
     *
     * @param string|null $stage Name of the stage config
     * @param string|null $folder Directory, relative to the project root, defaults to web/assets
     *
     * @return int
     */
    public function run(?string $stage = null, ?string $folder = null)
    {
        $this->head(
            'Copy folder up.',
            $this->getContextHeadline($this->stage)
        );

        $folder = $this->prepareForRsync($folder ?: 'web/assets');

        // Info
        $this->rsyncInfo($folder, $this->plugin->rsync->remoteUrl);

        if (! is_dir($folder)) {
            $this->errorBlock("$folder does not exist");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // Ask
        if (! $this->confirm('Are you sure?', true)) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // Configure rsync
        $this->plugin->rsync->setOption('dryRun', $this->dryRun);
        $this->plugin->rsync->setOption('remoteOrigin', false);

        // Run 'before' commands and stop on error
        if (! $this->runBeforeDeployCommands()) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // Execute
        $this->section($this->dryRun ? 'Rsync dry-run' : 'Rsync started');
        $this->plugin->rsync->sync($folder);
        $this->section(PHP_EOL . 'done');

        return ExitCode::OK;
    }
}
