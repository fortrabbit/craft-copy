<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Actions;

use GitWrapper\Exception\GitException;
use yii\console\ExitCode;

class CodeDownAction extends StageAwareBaseAction
{
    /**
     * Git pull
     *
     * @param string|null $stage Name of the stage config
     */
    public function run(?string $stage = null): int
    {
        $this->head(
            'Pull recent code changes for fortrabbit App.',
            $this->getContextHeadline($this->stage)
        );

        $git = $this->plugin->git;
        $git->getWorkingCopy()->init();

        $localBranches = $git->getLocalBranches();
        $branch = $git->getLocalHead();

        if (count($localBranches) > 1) {
            $question = 'Select a local branch (checkout):';
            $branch = str_replace('* ', '', $this->choice($question, $localBranches, $branch));
            $git->run('checkout', $branch);
        }

        // Run 'before' commands and stop on error
        if (! $this->runBeforeDeployCommands()) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // Use configured remote
        $remote = $this->stage->gitRemote ?: $git->getTracking(true);
        [$upstream, $branch] = explode('/', $remote);

        try {
            $this->section("git pull ({$upstream}/{$branch})");
            $git->getWorkingCopy()->getWrapper()->streamOutput();
            $git->pull($upstream, $branch);
        } catch (GitException $gitException) {
            $lines = count(explode(PHP_EOL, $gitException->getMessage()));
            $this->output->write(str_repeat("\x1B[1A\x1B[2K", $lines));
            $this->errorBlock('Ooops.');
            $this->output->write("<fg=red>{$gitException->getMessage()}</>");

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->output->write(PHP_EOL);
        $this->successBlock('Code pulled successfully.');

        return ExitCode::OK;
    }
}
