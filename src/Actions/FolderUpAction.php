<?php

namespace fortrabbit\Copy\Actions;

use fortrabbit\Copy\Helpers\ConsoleOutputHelper;
use fortrabbit\Copy\Helpers\PathHelper;
use yii\console\ExitCode;

class FolderUpAction extends ConfigAwareBaseAction
{
    use ConsoleOutputHelper;
    use PathHelper;

    public $dryRun = false;
    public $verbose = false;

    /**
     * Upload Folder
     *
     * @param string|null $config Name of the deploy config
     * @param string|null $folder Directory, relative to the project root, defaults to web/assets
     *
     * @return int
     */
    public function run(string $config = null, string $folder = 'web/assets')
    {
        $folder = $this->prepareForRsync($folder);

        $this->section('Copy folder up');

        // Info
        $this->rsyncInfo($folder, $this->plugin->rsync->remoteUrl);

        if (!is_dir($folder)) {
            $this->errorBlock("$folder does not exist");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // Ask
        if (!$this->confirm("Are you sure?", true)) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // Configure rsync
        $this->plugin->rsync->setOption('dryRun', $this->dryRun);
        $this->plugin->rsync->setOption('remoteOrigin', false);

        // Run 'before' commands and stop on error
        if (!$this->runBeforeDeployCommands()) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // Execute
        $this->section(($this->dryRun) ? 'Rsync dry-run' : 'Rsync started');
        $this->plugin->rsync->sync($folder);
        $this->section(PHP_EOL . 'done');

        return ExitCode::OK;
    }
}
