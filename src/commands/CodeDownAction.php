<?php

namespace fortrabbit\Copy\commands;

use fortrabbit\Copy\Plugin;
use GitWrapper\GitException;
use yii\console\ExitCode;

class CodeDownAction extends EnvironmentAwareBaseAction
{

    /**
     * Git pull
     *
     * @param string $remote
     * @param string $remoteBranch
     *
     * @return int
     */
    public function run(string $remote = null, $remoteBranch = 'master')
    {
        $git = Plugin::getInstance()->git;
        $git->getWorkingCopy()->init();

        $localBranches = $git->getLocalBranches();
        $branch        = $git->getLocalHead();

        if (count($localBranches) > 1) {
            $branch = str_replace('* ', '', $this->choice('Select a local branch:', $localBranches, $branch));
            $git->run('checkout', $branch);
        }

        // Use configured remote
        if (is_string($this->app) && is_null($remote)) {
            $remote = Plugin::getInstance()->getSettings()->getStageConfig($this->app)->gitRemoteName;
        }

        $remote = $remote ?: $git->getTracking();

        try {
            $this->section("git pull ($remote/$remoteBranch)");
            $git->getWorkingCopy()->getWrapper()->streamOutput();
            $git->pull($remote, $remoteBranch);
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
