<?php namespace fortrabbit\Copy\commands;


/**
 * Class SetupAction
 *
 * @package fortrabbit\DeployTools\commands
 */
class AssetsDownAction extends ConsoleBaseAction
{

    /**
     * Download Assets
     *
     * @param string|null $app
     *
     * @return bool
     */
    public function run(string $app = null)
    {
        // Ask if not forced
        $this->isForcedOrConfirmed("Do you really want to sync upload your local assets? to ...");


        die('SOME CALLED ME!!');
        return true;
    }
}
