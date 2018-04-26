<?php

namespace fortrabbit\Copy\commands;

use fortrabbit\Copy\Plugin;
use ostark\Yii2ArtisanBridge\base\Action;
use Symfony\Component\Console\Helper\TableSeparator;
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

    /**
     * Upload Assets
     *
     * @param string|null $app
     *
     * @return bool
     */
    public function run(string $app = null)
    {
        $plugin = Plugin::getInstance();
        $dir    = './web/assets/';

        // Info
        $this->table(
            ['Key', 'Value'],
            [
                ['Asset directory', $dir],
                new TableSeparator(),
                ['SSH remote', getenv(Plugin::ENV_NAME_SSH_REMOTE)],
                new TableSeparator(),
                ['Dry run', $this->dryRun ? 'true' : 'false']
            ]
        );

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
