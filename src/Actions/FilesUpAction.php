<?php

namespace fortrabbit\Copy\Actions;

use fortrabbit\Copy\Helpers\ConsoleOutputHelper;
use fortrabbit\Copy\Helpers\PathHelper;
use yii\console\ExitCode;

class FilesUpAction extends ConfigAwareBaseAction
{
    use ConsoleOutputHelper;
    use PathHelper;

    public $dryRun = false;
    public $verbose = false;

    /**
     * Upload Files
     *
     * @param string|null $config Name of the deploy config
     * @param string|null $dir Directory, relative to the project root, defaults to web/assets
     *
     * @return int
     */
    public function run(string $config = null, string $dir = 'web/assets')
    {
        $dir = $this->prepareForRsync($dir);

        $this->section('Copy files up');

        // Info
        $this->rsyncInfo($dir, $this->plugin->rsync->remoteUrl);

        // Ask
        if (!$this->confirm("Are you sure?", true)) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // Configure rsync
        $this->plugin->rsync->setOption('dryRun', $this->dryRun);
        $this->plugin->rsync->setOption('remoteOrigin', false);

        // Type cmd
        if ($this->verbose) {
            $this->cmdBlock($this->plugin->rsync->getCommand($dir));
        }

        // Run 'before' commands and stop on error
        if (!$this->runBeforeDeployCommands()) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // Execute
        $this->section(($this->dryRun) ? 'Rsync dry-run' : 'Rsync started');
        $this->plugin->rsync->sync($dir);
        $this->section(PHP_EOL . 'done');

        return ExitCode::OK;
    }
}
