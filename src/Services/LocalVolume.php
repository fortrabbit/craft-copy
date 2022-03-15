<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Services;

use Craft;
use craft\base\LocalVolumeInterface;
use craft\base\Volume;
use craft\services\Volumes;
use craft\volumes\Local;
use fortrabbit\Copy\Exceptions\VolumeNotFound;

/**
 * LocalVolume Service
 */
class LocalVolume
{
    public function __construct(protected Volumes $volumeService)
    {
    }

    /**
     * Get local volumes filtered by handle(s)
     *
     * @param array|null $handleFilter Optional handle filter
     *
     * @return Volume[]
     * @throws VolumeNotFound
     */
    public function filterByHandle(?array $handleFilter = null): array
    {
        /** @var Volume[] $volumes */
        $volumes = [];

        /**
         * @var Volume $volume
         */
        foreach ($this->volumeService->getAllVolumes() as $volume) {
            if (! ($volume instanceof LocalVolumeInterface)) {
                continue;
            }

            if ($handleFilter === null || in_array($volume->handle, $handleFilter, true)) {
                $volume->path = $this->getRelativePathFromVolume($volume);
                $volumes[] = $volume;
            }
        }

        if ($volumes === []) {
            throw new VolumeNotFound();
        }

        return $volumes;
    }

    protected function getRelativePathFromVolume(LocalVolumeInterface $volume): string
    {
        // Parse ENV var in subdirectories
        $parts = explode(DIRECTORY_SEPARATOR, $volume->getRootPath());
        $path = implode(
            DIRECTORY_SEPARATOR,
            array_map(
                fn($part) => Craft::parseEnv($part),
                $parts
            )
        );

        // Tweak ./ path
        $path = str_replace('./', Craft::parseEnv('@webroot') . '/', $path);

        // Return path relative to @root
        return ltrim(str_replace(Craft::parseEnv('@root'), '', $path), '/');
    }
}
