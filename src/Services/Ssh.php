<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Services;

use craft\base\Component;
use fortrabbit\Copy\Exceptions\CraftNotInstalledException;
use fortrabbit\Copy\Exceptions\PluginNotInstalledException;
use fortrabbit\Copy\Exceptions\RemoteException;
use Symfony\Component\Process\Process;

/**
 * Ssh Service
 */
class Ssh extends Component
{
    /**
     * @var string
     */
    public const UPLOAD_COMMAND = 'cat {src} | gzip | ssh {remote} "zcat > {target}"';

    /**
     * @var string
     */
    public const DOWNLOAD_COMMAND = 'ssh {remote} "cat {src} | gzip" | zcat > {target}';

    /**
     * @var string
     */
    public const REMOTE_EXEC_COMMAND = 'ssh {remote} {options} "{command}"';

    /**
     * @var int
     */
    public const SSH_EXEC_TIMEOUT = 1200;

    public string $remote;

    /**
     * @var string Path to Craft on the remote, relative to the SSH home dir
     */
    public string $path = '';

    protected string $output;

    protected bool $verbose = false;


    /**
     * Upload a single file
     *
     * @throws \fortrabbit\Copy\Exceptions\RemoteException
     */
    public function upload(string $src, string $target): bool
    {
        $process = Process::fromShellCommandline($this->getUploadCommand($src, $target));
        $process->setTimeout(self::SSH_EXEC_TIMEOUT);
        $process->run();

        if ($process->isSuccessful()) {
            return true;
        }

        throw new RemoteException(
            $process->getCommandLine() . PHP_EOL . $process->getErrorOutput()
        );
    }

    /**
     * Download a single file
     *
     * @throws \fortrabbit\Copy\Exceptions\RemoteException
     */
    public function download(string $src, string $target): bool
    {
        $process = Process::fromShellCommandline($this->getDownloadCommand($src, $target));
        $process->setTimeout(self::SSH_EXEC_TIMEOUT);
        $process->run();

        if ($process->isSuccessful()) {
            return true;
        }

        throw new RemoteException(
            $process->getCommandLine() . PHP_EOL . $process->getErrorOutput()
        );
    }

    /**
     * Plugin check on remote
     *
     * @throws \fortrabbit\Copy\Exceptions\CraftNotInstalledException
     * @throws \fortrabbit\Copy\Exceptions\PluginNotInstalledException
     * @throws \fortrabbit\Copy\Exceptions\RemoteException
     */
    public function checkPlugin(): void
    {
        $this->exec('php craft help copy');
    }

    /**
     * Execute a command via ssh on remote
     *
     * @throws \fortrabbit\Copy\Exceptions\CraftNotInstalledException
     * @throws \fortrabbit\Copy\Exceptions\PluginNotInstalledException
     * @throws \fortrabbit\Copy\Exceptions\RemoteException
     */
    public function exec(string $cmd): bool
    {
        if ($this->path !== '') {
            $cmd = 'cd ' . escapeshellarg($this->path) . " && {$cmd}";
        }

        $tokens = [
            '{remote}' => $this->remote,
            '{command}' => $cmd,
            '{options}' => ($this->verbose) ? '-vvv' : '',
        ];

        // create full
        $cmd = str_replace(array_keys($tokens), array_values($tokens), self::REMOTE_EXEC_COMMAND);

        $process = Process::fromShellCommandline($cmd, CRAFT_BASE_PATH);

        $process->setTimeout(self::SSH_EXEC_TIMEOUT);
        $process->run();

        if ($process->isSuccessful()) {
            $this->output = $process->getOutput();
            return true;
        }

        $out = $process->getOutput();
        $err = $process->getErrorOutput();

        if (stristr($out, 'Could not open input file')) {
            throw new CraftNotInstalledException();
        }

        if (stristr($err, 'Could not open input file')) {
            throw new CraftNotInstalledException();
        }

        // craft_path points to a directory that doesn't exist on the App
        if ($this->path !== '' && preg_match('/\bcd: /', $err)) {
            throw new CraftNotInstalledException();
        }

        if (stristr($err, 'Unknown command')) {
            throw new PluginNotInstalledException();
        }

        throw new RemoteException(
            implode(PHP_EOL, [
                'SSH Remote error: ' . $process->getExitCode(),
                'Command: ' . $process->getCommandLine(),
                'Output:',
                $err,
            ])
        );
    }

    /**
     * Get output of command execution
     */
    public function getOutput(): string
    {
        return $this->output;
    }

    protected function getUploadCommand(string $src, string $target): string
    {
        $cmd = self::UPLOAD_COMMAND;
        $tokens = [
            '{src}' => $src,
            '{target}' => $this->remotePath($target),
            '{remote}' => $this->remote,
        ];

        return str_replace(array_keys($tokens), array_values($tokens), $cmd);
    }

    /**
     * Prefix a relative remote path with the path to Craft
     */
    public function remotePath(string $path): string
    {
        if ($this->path === '' || str_starts_with($path, '/')) {
            return $path;
        }

        return "{$this->path}/{$path}";
    }

    public function setVerbose(bool $verbose = true): void
    {
        $this->verbose = $verbose;
    }

    protected function getDownloadCommand(string $src, string $target): string
    {
        $cmd = self::DOWNLOAD_COMMAND;
        $tokens = [
            '{src}' => $this->remotePath($src),
            '{target}' => $target,
            '{remote}' => $this->remote,
        ];

        return str_replace(array_keys($tokens), array_values($tokens), $cmd);
    }
}
