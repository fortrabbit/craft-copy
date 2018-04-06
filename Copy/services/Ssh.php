<?php namespace fortrabbit\Copy\services;

use craft\base\Component;
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

    // Public Methods
    // =========================================================================


    public function exec($cmd)
    {

        $process = new Process("ssh {$this->remote} $cmd", CRAFT_BASE_PATH);
        $process->run();

        if ($process->isSuccessful()) {
            return true;
        }

        if ("Could not open input file: craft" == trim($process->getOutput())) {
            throw new InvalidConfigException("Craft is not installed on remote.");
        }

        if (stristr($process->getOutput(), "unknown command")) {
            throw new InvalidConfigException("Plugin is not installed on remote.");
        }

        throw new Exception("SSH Remote error: " . $process->getOutput());

    }

    public function upload($src, $target, $gzip = false)
    {
        $process = new Process("cat {$src} | ssh {$this->remote} 'cat > {$target}'");
        $process->run();

        if ($process->isSuccessful()) {
            return true;
        }

        throw new Exception($process->getExitCodeText());

    }

    public function checkPlugin() {
        $this->exec("php craft help sync");
    }
}
