<?php

namespace fortrabbit\Copy\services;

use craft\base\Component;
use fortrabbit\Copy\exceptions\CraftNotInstalledException;
use fortrabbit\Copy\exceptions\PluginNotInstalledException;
use fortrabbit\Copy\exceptions\RemoteException;
use Symfony\Component\Process\Process;

/**
 * Ssh Service
 *
 * @author    Oliver Stark
 * @package   Copy
 * @since     1.0.0
 */
class Ssh extends Component
{
    const UPLOAD_COMMAND = 'cat {src} | gzip | ssh {remote} "zcat > {target}"';

    const DOWNLOAD_COMMAND = 'ssh {remote} "cat {src} | gzip" | zcat > {target}';

    const SSH_EXEC_TIMEOUT = 1200;

    /**
     * @var string
     */
    public $remote;

    /**
     * @var string
     */
    protected $output;

    // Public Methods
    // =========================================================================

    /**
     * Execute a command via ssh on remote
     *
     * @param string $cmd
     *
     * @return bool
     * @throws \fortrabbit\Copy\exceptions\CraftNotInstalledException
     * @throws \fortrabbit\Copy\exceptions\PluginNotInstalledException
     * @throws \fortrabbit\Copy\exceptions\RemoteException
     */
    public function exec(string $cmd)
    {
        $cmd     = sprintf('ssh %s "%s"', $this->remote, $cmd);
        $process = new Process($cmd, CRAFT_BASE_PATH);
        $process->setTimeout(self::SSH_EXEC_TIMEOUT);
        $process->run();

        if ($process->isSuccessful()) {
            $this->output = $process->getOutput();

            return true;
        }

        if (trim($process->getErrorOutput()) == "Could not open input file: craft") {
            throw new CraftNotInstalledException("Craft is not installed on remote.");
        }

        if (stristr($process->getErrorOutput(), "unknown command")) {
            throw new PluginNotInstalledException("Plugin is not installed on remote.");
        }

        throw new RemoteException("SSH Remote error: " . $process->getErrorOutput());
    }


    /**
     * Upload a single file
     *
     * @param string $src
     * @param string $target
     *
     * @return bool
     * @throws \fortrabbit\Copy\exceptions\RemoteException
     */
    public function upload($src, $target)
    {
        $process = new Process($this->getUploadCommand($src, $target));
        $process->setTimeout(self::SSH_EXEC_TIMEOUT);
        $process->run();

        if ($process->isSuccessful()) {
            return true;
        }

        throw new RemoteException($process->getCommandLine() . PHP_EOL . $process->getErrorOutput());
    }

    /**
     * Download a single file
     *
     * @param string $src
     * @param string $target
     *
     * @return bool
     * @throws \fortrabbit\Copy\exceptions\RemoteException
     */
    public function download($src, $target)
    {
        $process = new Process($this->getDownloadCommand($src, $target));
        $process->setTimeout(self::SSH_EXEC_TIMEOUT);
        $process->run();

        if ($process->isSuccessful()) {
            return true;
        }

        throw new RemoteException($process->getCommandLine() . PHP_EOL . $process->getErrorOutput());
    }


    /**
     * Plugin check on remote
     *
     * @throws \fortrabbit\Copy\exceptions\CraftNotInstalledException
     * @throws \fortrabbit\Copy\exceptions\PluginNotInstalledException
     * @throws \fortrabbit\Copy\exceptions\RemoteException
     */
    public function checkPlugin()
    {
        $this->exec("php craft help copy");
    }


    /**
     * Get output of command execution
     *
     * @return mixed
     */
    public function getOutput()
    {
        return $this->output;
    }


    protected function getUploadCommand(string $src, string $target)
    {
        $cmd    = self::UPLOAD_COMMAND;
        $tokens = [
            '{src}'    => $src,
            '{target}' => $target,
            '{remote}' => $this->remote,
        ];

        return str_replace(array_keys($tokens), array_values($tokens), $cmd);
    }

    protected function getDownloadCommand(string $src, string $target)
    {
        $cmd    = self::DOWNLOAD_COMMAND;
        $tokens = [
            '{src}'    => $src,
            '{target}' => $target,
            '{remote}' => $this->remote,
        ];

        return str_replace(array_keys($tokens), array_values($tokens), $cmd);
    }

}
