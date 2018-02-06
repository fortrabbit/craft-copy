<?php namespace fortrabbit\Sync\services;

use craft\base\Component;
use Symfony\Component\Process\Process;
use yii\console\Exception;

/**
 * Ssh Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Oliver Stark
 * @package   Sync
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

        throw new Exception($process->getExitCodeText());

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
}
