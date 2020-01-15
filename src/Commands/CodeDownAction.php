<?php

namespace fortrabbit\Copy\Commands;

use GitWrapper\GitException;
use yii\console\ExitCode;

/**
 * Class CodeDownAction
 *
 * @package fortrabbit\Copy\Commands
 */
class CodeDownAction extends ConfigAwareBaseAction
{

    /**
     * Git pull
     *
     * @param string|null $config Name of the deploy config
     * @param string $remoteBranch
     *
     * @return int
     */
    public function run(string $config = null, $remoteBranch = 'master')
    {
        $git = $this->plugin->git;
        $git->getWorkingCopy()->init();

        $localBranches = $git->getLocalBranches();
        $branch        = $git->getLocalHead();

        if (count($localBranches) > 1) {
            $branch = str_replace('* ', '', $this->choice('Select a local branch (checkout):', $localBranches, $branch));
            $git->run('checkout', $branch);
        }

        // Run 'before' commands and stop on error
        if (!$this->runBeforeDeployCommands()) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // Use configured remote
        $remote = $this->config->gitRemote ?: $git->getTracking(true);
        [$upstream, $branch] = explode('/', $remote);

        try {
            $this->section("git pull ($upstream/$branch)");
            $git->getWorkingCopy()->getWrapper()->streamOutput();
            $git->pull($upstream, $branch);
        } catch (GitException $exception) {
            $lines = count(explode(PHP_EOL, $exception->getMessage()));
            $this->output->write(str_repeat("\x1B[1A\x1B[2K", $lines));
            $this->errorBlock('Ooops.');
            $this->output->write("<fg=red>{$exception->getMessage()}</>");

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->output->write(PHP_EOL);
        $this->successBlock('Code pulled successfully.');

        return ExitCode::OK;
    }
}
