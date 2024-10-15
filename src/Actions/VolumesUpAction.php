<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Actions;

use fortrabbit\Copy\Exceptions\VolumeNotFound;
use fortrabbit\Copy\Helpers\ConsoleOutputHelper;
use fortrabbit\Copy\Helpers\PathHelper;
use fortrabbit\Copy\Services\LocalFilesystem;
use fortrabbit\Yii2ArtisanBridge\base\Commands;
use yii\console\ExitCode;

class VolumesUpAction extends StageAwareBaseAction
{
    use ConsoleOutputHelper;
    use PathHelper;

    public bool $dryRun = false;

    public bool $verbose = false;

    public function __construct(
        string $id,
        Commands $controller,
        protected LocalFilesystem $localFilesystem,
        array $config = []
    ) {
        parent::__construct($id, $controller, $config);
    }

    /**
     * Upload assets in Volumes
     *
     * @param string|null $stage Name of the stage config. Use '?' to choose.
     * @param array|null $volumeHandles Limit the command to specific volumes
     *
     * @throws VolumeNotFound
     */
    public function run(?string $stage = null, ?array $volumeHandles = null): int
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
            $fs = $this->localFilesystem->filterByHandle($volumeHandles);
            $lastFs = end($fs);
        } catch (VolumeNotFound) {
            $this->line('No local volumes found.' . PHP_EOL);

            return ExitCode::OK;
        }

        foreach ($fs as $filesystem) {
            $path = $this->prepareForRsync($filesystem->path);

            // Info
            $this->rsyncInfo(
                $path,
                $this->plugin->rsync->remoteUrl,
                $filesystem->handle
            );

            if (! is_dir($path)) {
                $this->errorBlock("{$path} does not exist");

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
                $filesystem === $lastFs ? 'All done.' : "{$filesystem->name} done, next volume:"
            );
            $this->line(PHP_EOL);
        }

        return ExitCode::OK;
    }
}
