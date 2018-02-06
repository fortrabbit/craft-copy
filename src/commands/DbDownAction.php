<?php namespace fortrabbit\Sync\commands;


/**
 * Class DbDownAction
 *
 * @package fortrabbit\DeployTools\commands
 */
class DbDownAction extends ConsoleBaseAction
{

    /**
     * @param string|null $file Import a sql dump
     *
     * @return bool
     */
    public function run(string $file = null)
    {
        die('SOME CALLED ME!!');
        return true;
    }
}
