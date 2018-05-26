<?php

namespace fortrabbit\Copy\commands;

use fortrabbit\Copy\Plugin;
use GitWrapper\GitException;
use ostark\Yii2ArtisanBridge\base\Action;
use yii\console\ExitCode;

class CodeDownAction extends Action
{


    /**
     * @param null   $remote
     * @param string $remoteBranch
     *
     * @return int
     */
    public function run($remote = null, $remoteBranch = 'master')
    {
        $git = Plugin::getInstance()->git;
        $git->getWorkingCopy()->init();

        $localBranches = $git->getLocalBranches();
        $branch        = $git->getLocalHead();

        if (count($localBranches) > 1) {
            $branch = str_replace('* ', '', $this->choice('Select a local branch:', $localBranches, $branch));
            $git->run('checkout', $branch);
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
