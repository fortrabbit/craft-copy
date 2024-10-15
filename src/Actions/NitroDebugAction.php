<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Actions;

use fortrabbit\Copy\Exceptions\RemoteException;
use fortrabbit\Copy\Helpers\ConsoleOutputHelper;
use fortrabbit\Copy\Plugin;
use yii\console\ExitCode;

class NitroDebugAction extends StageAwareBaseAction
{
    use ConsoleOutputHelper;

    /**
     * Upload a folder
     *
     * @param string|null $stage Name of the stage config
     */
    public function run(?string $stage = null): int
    {
        $plugin = Plugin::getInstance();
        $plugin->ssh->setVerbose(true);
        try {
            $plugin->ssh->exec('ls -l');
            $this->output->write($plugin->ssh->getOutput());
        } catch (RemoteException $remoteException) {
            $this->output->write($remoteException->getMessage());
            $this->output->error('Above you should see ssh debug output');
            return ExitCode::UNSPECIFIED_ERROR;
        }


        $this->output->success('LGTM');

        return ExitCode::OK;
    }



}
