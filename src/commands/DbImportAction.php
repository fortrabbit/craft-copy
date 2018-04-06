<?php namespace fortrabbit\Copy\commands;


use fortrabbit\Copy\services\ConsoleOutputHelper;

/**
 * Class DbImportAction
 *
 * @package fortrabbit\DeployTools\commands
 */
class DbImportAction extends ConsoleBaseAction
{

    /**
     * Import database
     *
     * @param string|null $file Import a sql dump
     *
     * @return bool
     */
    public function run(string $file = null)
    {
        $this->isForcedOrConfirmed("Do you really dump your local DB");

        die('SOME CALLED ME!!');
        return true;
    }
}
