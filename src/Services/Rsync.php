<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Services;

use AFM\Rsync\Command;
use AFM\Rsync\Rsync as RsyncLib;
use InvalidArgumentException;

/**
 * Rsync Service
 */
class Rsync
{
    /**
     * Permissions of uploaded files and folders (755/644), so the web server can read them.
     * Mutagen (DDEV) shows files created on the host as 0600/0700 inside the container.
     */
    public const UPLOAD_CHMOD = 'Du=rwx,Dgo=rx,Fu=rw,Fgo=r';

    protected bool $remoteOrigin = false;

    protected function __construct(
        protected RsyncLib $rsync,
        public ?string $remoteUrl = null,
        protected string $remotePath = ''
    ) {
    }

    /**
     * @param string $remotePath Path to Craft on the remote, relative to the SSH home dir
     */
    public static function remoteFactory($remoteUrl, string $remotePath = ''): \fortrabbit\Copy\Services\Rsync
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

        return new self($rsync, $remoteUrl, $remotePath);
    }

    /**
     * Rsync config
     */
    public function setOption(string $key, $value): void
    {
        if ($key === 'remoteOrigin') {
            $this->remoteOrigin = (bool) $value;
        }

        $setter = 'set' . ucfirst($key);
        $this->rsync->{$setter}($value);
    }

    public function sync(string $dir): void
    {
        $this->buildCommand($dir)->execute($this->rsync->getShowOutput());
    }

    public function getCommand(string $dir): string
    {
        return $this->buildCommand($dir)->getCommand();
    }

    /**
     * Same dir on both sides, the remote one relative to the path to Craft
     */
    protected function buildCommand(string $dir): Command
    {
        $remoteDir = ($this->remotePath === '' || str_starts_with($dir, '/'))
            ? $dir
            : "{$this->remotePath}/{$dir}";

        if ($this->remoteOrigin) {
            return $this->rsync->getCommand($remoteDir, $dir);
        }

        $command = $this->rsync->getCommand($dir, $remoteDir);
        $command->addArgument('chmod', self::UPLOAD_CHMOD);

        return $command;
    }
}
