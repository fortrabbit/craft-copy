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

    /**
     * @var string
     */
    public $remote;

    /**
     * @var string
     */
    protected $output;

    /**
     * @var bool
     */
    protected $verbose = false;

    // Public Methods
    // =========================================================================
    /**
     * Upload a single file
     *
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
     *
     * @return mixed
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
            '{target}' => $target,
            '{remote}' => $this->remote,
        ];

        return str_replace(array_keys($tokens), array_values($tokens), $cmd);
    }

    public function setVerbose(bool $verbose = true): void
    {
        $this->verbose = $verbose;
    }

    protected function getDownloadCommand(string $src, string $target): string
    {
        $cmd = self::DOWNLOAD_COMMAND;
        $tokens = [
            '{src}' => $src,
            '{target}' => $target,
            '{remote}' => $this->remote,
        ];

        return str_replace(array_keys($tokens), array_values($tokens), $cmd);
    }
}
