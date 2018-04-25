<?php

namespace fortrabbit\Copy\commands;

use fortrabbit\Copy\Plugin;
use ostark\Yii2ArtisanBridge\base\Action;
use yii\console\ExitCode;

/**
 * Class AssetsUpAction
 *
 * @package fortrabbit\Copy\commands
 */
class AssetsUpAction extends Action
{

    /**
     * Upload Assets
     *
     * @param string|null $app
     *
     * @return bool
     */
    public function run(string $app = null)
    {
        // Ask
        if (!$this->confirm("Do you really want to sync your local assets?")) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $plugin = Plugin::getInstance();
        $dir = './web/assets/';

        $plugin->rsync->setOption('dryRun', false);
        $plugin->rsync->setOption('verbose', true);

        $this->section('Rsync started');
        $plugin->rsync->syncToRemote($dir, $dir);
        $this->section('done');

        return true;
    }
}
