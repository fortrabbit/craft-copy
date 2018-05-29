<?php namespace fortrabbit\Copy\services;

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
    /**
     * @var string
     */
    public $remote ;

    /**
     * @var string
     */
    protected $output;

    // Public Methods
    // =========================================================================

    /**
     * Execute a command via ssh on remote
     *
     * @param $cmd
     *
     * @return bool
     * @throws \fortrabbit\Copy\exceptions\CraftNotInstalledException
     * @throws \fortrabbit\Copy\exceptions\PluginNotInstalledException
     * @throws \fortrabbit\Copy\exceptions\RemoteException
     */
    public function exec($cmd)
    {

        $process = new Process("ssh {$this->remote} $cmd", CRAFT_BASE_PATH);
        $process->run();

        if ($process->isSuccessful()) {
            $this->output = $process->getOutput();
            return true;
        }

        if ("Could not open input file: craft" == trim($process->getErrorOutput())) {
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
     * @param $src
     * @param $target
     *
     * @return bool
     * @throws \fortrabbit\Copy\exceptions\RemoteException
     */
    public function upload($src, $target)
    {
        $cmd = "cat {$src} | gzip | ssh {$this->remote} 'zcat > {$target}'";
        $process = new Process($cmd);
        $process->run();

        if ($process->isSuccessful()) {
            return true;
        }

        throw new RemoteException($cmd . PHP_EOL . $process->getErrorOutput());

    }

    /**
     * Download a single file
     *
     * @param $src
     * @param $target
     *
     * @return bool
     * @throws \fortrabbit\Copy\exceptions\RemoteException
     */
    public function download($src, $target)
    {
        $cmd = "ssh {$this->remote} 'cat {$src} | gzip' | zcat > {$target}";
        $process = new Process($cmd);
        $process->run();

        if ($process->isSuccessful()) {
            return true;
        }

        throw new RemoteException($cmd . PHP_EOL . $process->getErrorOutput());

    }


    /**
     * Plugin check on remote
     *
     * @throws \fortrabbit\Copy\exceptions\CraftNotInstalledException
     * @throws \fortrabbit\Copy\exceptions\PluginNotInstalledException
     * @throws \fortrabbit\Copy\exceptions\RemoteException
     */
    public function checkPlugin() {
        $this->exec("php craft help copy");
    }


    /**
     * Get output of command execution
     *
     * @return mixed
     */
    public function getOutput() {
        return $this->output;
    }
}
