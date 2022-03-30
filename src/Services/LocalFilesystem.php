<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Services;

use craft\fs\Local;
use craft\models\Volume;
use craft\helpers\App;
use craft\services\Volumes;
use fortrabbit\Copy\Exceptions\VolumeNotFound;

/**
 * LocalFilesystem Service
 */
class LocalFilesystem
{
    public function __construct(protected Volumes $volumeService)
    {
    }

    /**
     * Get local volumes filtered by handle(s)
     *
     * @param array|null $handleFilter Optional handle filter
     *
     * @return Local[]
     * @throws VolumeNotFound
     */
    public function filterByHandle(?array $handleFilter = null): array
    {
        /** @var Local[] $filesystems */
        $filesystems = [];

        foreach ($this->volumeService->getAllVolumes() as $volume) {
            if (! ($volume->getFs() instanceof Local)) {
                continue;
            }


            if ($handleFilter === null || in_array($volume->handle, $handleFilter, true)) {
                /** @var Local $local */
                $local = $volume->getFs();
                $local->path = $this->getRelativePathFromVolume($volume);
                $filesystems[] = $local;
            }
        }

        if ($filesystems === []) {
            throw new VolumeNotFound();
        }

        return $filesystems;
    }

    /**
     * It's not clear if we really need this with Craft 4
     */
    protected function getRelativePathFromVolume(Volume $volume): string
    {
        if (!($volume->getFs() instanceof Local)) {
            throw new \InvalidArgumentException("{$volume->getFs()->handle} is not a local filesystem");
        }

        /** @var Local $fs */
        $fs = $volume->getFs();

        // Parse ENV var in subdirectories
        $parts = explode(DIRECTORY_SEPARATOR, $fs->getRootPath());
        $path = implode(
            DIRECTORY_SEPARATOR,
            array_map(
                fn($part) => App::parseEnv($part),
                $parts
            )
        );

        // Tweak ./ path
        $path = str_replace('./', App::parseEnv('@webroot') . '/', $path);

        // Return path relative to @root
        return ltrim(str_replace(App::parseEnv('@root'), '', $path), '/');
    }
}
