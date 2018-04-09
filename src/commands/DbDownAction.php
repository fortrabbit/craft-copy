<?php namespace fortrabbit\Copy\commands;

use fortrabbit\Copy\services\ConsoleOutputHelper;

/**
 * Class DbDownAction
 *
 * @package fortrabbit\DeployTools\commands
 */
class DbDownAction extends ConsoleBaseAction
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
