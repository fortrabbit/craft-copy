<?php namespace fortrabbit\Copy\services;

use craft\base\Component;
use fortrabbit\Copy\exceptions\CraftNotInstalledException;
use fortrabbit\Copy\exceptions\PluginNotInstalledException;
use fortrabbit\Copy\exceptions\RemoteException;
use Symfony\Component\Process\Process;
use yii\base\InvalidConfigException;
use yii\console\Exception;

/**
 * Ssh Service
 *
 * @author    Oliver Stark
 * @package   Copy
 * @since     1.0.0
 */
class Ssh extends Component
{
    public $remote;

    protected $output;

    // Public Methods
    // =========================================================================

    /**
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

    public function upload($src, $target, $gzip = false)
    {
        $process = new Process("cat {$src} | gzip | ssh {$this->remote} 'zcat > {$target}'");
        $process->run();

        if ($process->isSuccessful()) {
            return true;
        }

        throw new RemoteException($process->getExitCodeText());

    }

    /**
     * @throws \fortrabbit\Copy\exceptions\CraftNotInstalledException
     * @throws \fortrabbit\Copy\exceptions\PluginNotInstalledException
     * @throws \fortrabbit\Copy\exceptions\RemoteException
     */
    public function checkPlugin() {
        $this->exec("php craft help copy");
    }

    public function getOutput() {
        return $this->output;
    }
}
