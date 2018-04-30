<?php

namespace fortrabbit\Copy\commands;

use fortrabbit\Copy\helpers\ConsoleOutputHelper;
use fortrabbit\Copy\Plugin;
use ostark\Yii2ArtisanBridge\base\Action;
use yii\console\ExitCode;

/**
 * Class AssetsUpAction
 *
 * @package fortrabbit\Copy\commands
 */
class AssetsUpAction extends Action
{

    public $dryRun = false;

    public $verbose = false;

    use ConsoleOutputHelper;

    /**
     * Upload Assets
     *
     * @return bool
     */
    public function run()
    {
        $plugin = Plugin::getInstance();
        $dir    = './web/assets/';

        // Info
        $this->rsyncInfo($dir);

        // Ask
        if (!$this->confirm("Do you really want to sync your local assets?")) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // Configure rsync
        $plugin->rsync->setOption('dryRun', $this->dryRun);
        $plugin->rsync->setOption('remoteOrigin', false);

        // Type cmd
        if ($this->verbose) {
            $this->output->type($plugin->rsync->getCommand($dir), "fg=white", 50);
        }

        // Execute
        $this->section(($this->dryRun) ? 'Rsync dry-run' : 'Rsync started');
        $plugin->rsync->sync($dir);
        $this->section(PHP_EOL . 'done');

        return true;
    }
}
