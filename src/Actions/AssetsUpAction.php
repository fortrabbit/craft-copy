<?php

namespace fortrabbit\Copy\Actions;

use fortrabbit\Copy\Helpers\ConsoleOutputHelper;
use fortrabbit\Copy\Helpers\PathHelper;
use fortrabbit\Copy\Plugin;
use yii\console\ExitCode;

/**
 * Class AssetsUpAction
 *
 * @package fortrabbit\Copy\Actions
 */
class AssetsUpAction extends ConfigAwareBaseAction
{
    use ConsoleOutputHelper;
    use PathHelper;

    public $dryRun = false;

    public $verbose = false;


    /**
     * Upload Assets
     *
     * @param string|null $config Name of the deploy config
     * @param string|null $dir    Directory, relative to the project root, defaults to web/assets
     *
     * @return int
     */
    public function run(string $config = null, string $dir = null)
    {
        $plugin = Plugin::getInstance();
        $dir    = $this->prepareForRsync($dir ?: $this->config->assetPath);

        $this->section('Copy assets up');

        // Info
        $this->rsyncInfo($dir, $plugin->rsync->remoteUrl);

        // Ask
        if (!$this->confirm("Are you sure?", true)) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // Configure rsync
        $plugin->rsync->setOption('dryRun', $this->dryRun);
        $plugin->rsync->setOption('remoteOrigin', false);

        // Type cmd
        if ($this->verbose) {
            $this->cmdBlock($plugin->rsync->getCommand($dir));
        }

        // Run 'before' commands and stop on error
        if (!$this->runBeforeDeployCommands()) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // Execute
        $this->section(($this->dryRun) ? 'Rsync dry-run' : 'Rsync started');
        $plugin->rsync->sync($dir);
        $this->section(PHP_EOL . 'done');

        return ExitCode::OK;
    }
}
