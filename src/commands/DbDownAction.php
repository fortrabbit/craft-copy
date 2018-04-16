<?php

namespace fortrabbit\Copy\commands;

use fortrabbit\Copy\ArtisanConsoleBridge\base\Action;


/**
 * Class DbDownAction
 *
 * @package fortrabbit\DeployTools\commands
 */
class DbDownAction extends Action
{

    /**
     * Download database
     * @param string|null $file Import a sql dump
     *
     * @return bool
     */
    public function run(string $file = null)
    {
        die('SOME CALLED ME!!');
        return 0;
    }
}
