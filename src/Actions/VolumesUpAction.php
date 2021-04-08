<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Actions;

use fortrabbit\Copy\Exceptions\VolumeNotFound;
use fortrabbit\Copy\Helpers\ConsoleOutputHelper;
use fortrabbit\Copy\Helpers\PathHelper;
use fortrabbit\Copy\Services\LocalVolume;
use ostark\Yii2ArtisanBridge\base\Commands;
use yii\console\ExitCode;

class VolumesUpAction extends StageAwareBaseAction
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
     * Upload assets in Volumes
     *
     * @param string|null $stage Name of the stage config. Use '?' to choose.
     * @param array|null $volumeHandles Limit the command to specific volumes
     *
     * @return int
     * @throws VolumeNotFound
     */
    public function run(?string $stage = null, ?array $volumeHandles = null)
    {
        $this->head(
            'Copy volumes up.',
            $this->getContextHeadline($this->stage)
        );

        // Run 'before' commands and stop on error
        if (! $this->runBeforeDeployCommands()) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        try {
            $volumes = $this->localVolume->filterByHandle($volumeHandles);
            $lastVolume = end($volumes);
        } catch (VolumeNotFound $exception) {
            $this->line('No local volumes found.' . PHP_EOL);
            return ExitCode::OK;
        }


        foreach ($volumes as $volume) {
            $path = $this->prepareForRsync($volume->path);

            // Info
            $this->rsyncInfo(
                $path,
                $this->plugin->rsync->remoteUrl,
                $volume->handle
            );

            if (! is_dir($path)) {
                $this->errorBlock("$path does not exist");
                continue;
            }

            // Ask
            if (! $this->confirm('Are you sure?', true)) {
                return ExitCode::UNSPECIFIED_ERROR;
            }

            // Configure rsync
            $this->plugin->rsync->setOption('dryRun', $this->dryRun);
            $this->plugin->rsync->setOption('remoteOrigin', false);

            // Execute
            $this->section($this->dryRun ? 'Rsync dry-run' : 'Rsync started');
            $this->plugin->rsync->sync($path);
            $this->line(PHP_EOL);
            $this->line(
                $volume === $lastVolume ? 'All done.' : "{$volume->name} done, next volume:"
            );
            $this->line(PHP_EOL);
        }

        return ExitCode::OK;
    }
}
