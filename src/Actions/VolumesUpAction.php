<?php

namespace fortrabbit\Copy\Actions;

use fortrabbit\Copy\Exceptions\VolumeNotFound;
use fortrabbit\Copy\Helpers\ConsoleOutputHelper;
use fortrabbit\Copy\Helpers\PathHelper;
use fortrabbit\Copy\Services\LocalVolume;
use fortrabbit\Copy\Services\Volumes;
use ostark\Yii2ArtisanBridge\base\Commands;
use yii\console\ExitCode;

/**
 * Class VolumesUpAction
 *
 * @package fortrabbit\Copy\Actions
 */
class VolumesUpAction extends ConfigAwareBaseAction
{
    use ConsoleOutputHelper;
    use PathHelper;

    public $dryRun = false;

    public $verbose = false;

    /**
     * @var LocalVolume
     */
    protected $localVolume;

    public function __construct(
        string $id,
        Commands $controller,
        LocalVolume $localVolume,
        array $config = []
    ) {
        $this->localVolume = $localVolume;
        parent::__construct($id, $controller, $config);
    }

    /**
     * Upload Volumes
     *
     * @param string|null $config Name of the deploy config. Use '?' to choose.
     * @param array|null $volumeHandles Limit the command to specific volumes
     *
     * @return int
     * @throws VolumeNotFound
     */
    public function run(string $config = null, array $volumeHandles = null)
    {
        // Run 'before' commands and stop on error
        if (!$this->runBeforeDeployCommands()) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $volumes = $this->localVolume->filterByHandle($volumeHandles);

        $this->section("Copy volumes up");

        foreach ($volumes as $volume) {

            $path = $this->prepareForRsync($volume->path);

            // Info
            $this->rsyncInfo(
                $path,
                $this->plugin->rsync->remoteUrl,
                $volume->handle
            );

            // Ask
            if (!$this->confirm("Are you sure?", true)) {
                return ExitCode::UNSPECIFIED_ERROR;
            }

            // Configure rsync
            $this->plugin->rsync->setOption('dryRun', $this->dryRun);
            $this->plugin->rsync->setOption('remoteOrigin', false);

            // Type cmd
            if ($this->verbose) {
                $this->cmdBlock($this->plugin->rsync->getCommand($path));
            }

            // Execute
            $this->section(($this->dryRun) ? 'Rsync dry-run' : 'Rsync started');
            $this->plugin->rsync->sync($path);
            $this->section(PHP_EOL . 'done');
        }

        return ExitCode::OK;
    }

}
