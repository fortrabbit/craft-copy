<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Services;

use AFM\Rsync\Rsync as RsyncLib;
use InvalidArgumentException;

/**
 * Rsync Service
 */
class Rsync
{
    protected function __construct(protected RsyncLib $rsync, public ?string $remoteUrl = null)
    {
    }

    public static function remoteFactory($remoteUrl): \fortrabbit\Copy\Services\Rsync
    {
        if (!str_contains($remoteUrl, '@')) {
            throw new InvalidArgumentException(
                "SSH remote URL must contain a user@host, '{$remoteUrl}' given."
            );
        }

        // split
        [$username, $host] = explode('@', $remoteUrl, 2);

        $rsync = new RsyncLib();
        $rsync->setVerbose(true);
        $rsync->setSshOptions([
            'host' => $host,
            'username' => $username,
        ]);

        return new self($rsync, $remoteUrl);
    }

    /**
     * Rsync config
     */
    public function setOption(string $key, $value): void
    {
        $setter = 'set' . ucfirst($key);
        $this->rsync->{$setter}($value);
    }

    public function sync(string $dir): void
    {
        $this->rsync->sync($dir, $dir);
    }

    public function getCommand(string $dir): string
    {
        return $this->rsync->getCommand($dir, $dir)->getCommand();
    }
}
