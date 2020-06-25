<?php

namespace fortrabbit\Copy\Services;

use craft\base\LocalVolumeInterface;
use craft\base\Volume;
use craft\base\VolumeInterface;
use craft\services\Volumes;
use fortrabbit\Copy\Exceptions\VolumeNotFound;

/**
 * LocalVolume Service
 */
class LocalVolume
{
    /**
     * @var Volumes
     */
    protected $volumeService;


    public function __construct(Volumes $volumeService)
    {
        $this->volumeService = $volumeService;
    }

    /**
     * Get local volumes filtered by handle(s)
     *
     * @param array|null $handleFilter Optional handle filter
     *
     * @return Volume[]
     * @throws VolumeNotFound
     */
    public function filterByHandle(array $handleFilter = null): array
    {
        /**
         * @var Volume[] $volumes
         */
        $volumes = [];

        /**
         * @var Volume $volume
         */
        foreach ($this->volumeService->getAllVolumes() as $volume) {
            if (!($volume instanceof LocalVolumeInterface)) {
                continue;
            }

            if (is_null($handleFilter) || in_array($volume->handle, $handleFilter)) {
                $volume->path = $this->getRelativePathFromVolume($volume);
                $volumes[] = $volume;
            }
        }

        if (0 === count($volumes)) {
            throw new VolumeNotFound();
        }

        return $volumes;
    }

    /**
     * @param VolumeInterface $volume
     *
     * @return string|null
     */
    protected function getRelativePathFromVolume(VolumeInterface $volume): ?string
    {
        // Parse ENV var in subdirectories
        $parts = explode(DIRECTORY_SEPARATOR, $volume->getRootPath());
        $path = implode(
            DIRECTORY_SEPARATOR,
            array_map(
                function ($part) {
                    return \Craft::parseEnv($part);
                },
                $parts
            )
        );

        // Tweak ./ path
        $path = str_replace('./', \Craft::parseEnv('@webroot') . '/', $path);

        // Return path relative to @root
        return ltrim(str_replace(\Craft::parseEnv('@root'), '', $path), '/');
    }
}
