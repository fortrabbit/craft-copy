<?php

namespace fortrabbit\Copy\commands;

use fortrabbit\Copy\helpers\ConsoleOutputHelper;
use fortrabbit\Copy\helpers\PathHelper;
use fortrabbit\Copy\Plugin;
use ostark\Yii2ArtisanBridge\base\Action;
use yii\console\ExitCode;

/**
 * Class AssetsDownAction
 *
 * @package fortrabbit\Copy\commands
 */
class AssetsDownAction extends Action
{
    public $dryRun = false;

    public $verbose = false;

    use ConsoleOutputHelper;
    use PathHelper;

    /**
     * Download Assets
     *
     * @param string $dir Directory, relative to the project root
     *
     * @return int
     */
    public function run($dir = 'web/assets')
    {
        $plugin = Plugin::getInstance();
        $dir    = $this->prepareForRsync($dir);

        $this->section('Copy assets down');

        // Info
        $this->rsyncInfo($dir);

        // Ask
        if (!$this->confirm("Are you sure?", true)) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // Configure rsync
        $plugin->rsync->setOption('dryRun', $this->dryRun);
        $plugin->rsync->setOption('remoteOrigin', true);

        // Type cmd
        if ($this->verbose) {
            $this->output->type($plugin->rsync->getCommand($dir), "fg=white", 50);
        }

        // Execute
        $this->section(($this->dryRun) ? 'Rsync dry-run' : 'Rsync started');
        $plugin->rsync->sync($dir);
        $this->section(PHP_EOL . 'done');

        return ExitCode::OK;
    }
}
