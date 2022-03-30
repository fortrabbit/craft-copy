<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Actions;

use Craft;
use craft\helpers\App;
use craft\helpers\FileHelper;
use Exception;
use fortrabbit\Copy\Services\Git;
use fortrabbit\Copy\Services\LocalFilesystem;
use Symplify\GitWrapper\Exception\GitException;
use ostark\Yii2ArtisanBridge\base\Commands;
use Throwable;
use yii\console\ExitCode;

class CodeUpAction extends StageAwareBaseAction
{
    public function __construct(
        string $id,
        Commands $controller,
        protected LocalFilesystem $localFilesystem,
        array $config = []
    ) {
        parent::__construct($id, $controller, $config);
    }

    /**
     * Git push
     *
     * @param string|null $stage Name of the stage config
     *
     * @throws Exception
     */
    public function run(?string $stage = null): int
    {
        $this->head(
            'Deploy recent code changes.',
            $this->getContextHeadline($this->stage)
        );

        $git = $this->plugin->git;
        $git->getWorkingCopy()->init();

        // Project .gitignore
        $git->assureDotGitignore();

        $localBranches = $git->getLocalBranches();
        $branch = $git->getLocalHead();

        if (count($localBranches) > 1) {
            $question = 'Select a local branch (checkout):';
            $branch = str_replace('* ', '', $this->choice($question, $localBranches, $branch));
            $git->run('checkout', [$branch]);
        }

        // Ask for remote
        // or create one
        // or pick the only one
        if (!($upstream = $this->getUpstream($git))) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // Volume .gitignore
        $this->assureVolumesAreIgnored();

        try {
            if ($log = $git->getWorkingCopy()->log(
                '--format=(%h) %cr: %s ',
                "{$upstream}/master..HEAD"
            )) {
                $this->noteBlock('Recent changes:' . PHP_EOL . trim($log));
            }
        } catch (Throwable) {
        }

        if (! $git->getWorkingCopy()->hasChanges() && ! $this->confirm('About to push latest commits, proceed?', true)) {
            return ExitCode::OK;
        }

        if ($status = $git->getWorkingCopy()->getStatus()) {
            // Changed files
            $this->noteBlock('Uncommitted changes:' . PHP_EOL . $status);
            $defaultMessage = $this->interactive ? null : 'init Craft';

            if (! $msg = $this->ask(
                'Enter a commit message, or leave it empty to abort the commit',
                $defaultMessage
            )) {
                $this->errorBlock('Abort');

                return ExitCode::UNSPECIFIED_ERROR;
            }

            // Add and commit
            $git->getWorkingCopy()->add('.');
            $git->getWorkingCopy()->commit($msg);
        } else {
            $msg = 'empty commit';
        }

        // Run 'before' commands and stop on error
        if (! $this->runBeforeDeployCommands()) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        try {
            $this->section("git push ({$msg})");
            $git->getWorkingCopy()->getWrapper()->streamOutput();
            $git->push($upstream, "{$branch}:master");
        } catch (GitException $gitException) {
            $lines = count(explode(PHP_EOL, $gitException->getMessage()));
            $this->output->write(str_repeat("\x1B[1A\x1B[2K", $lines));
            $this->errorBlock('Ooops.');
            $this->output->write("<fg=red>{$gitException->getMessage()}</>");

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->output->write(PHP_EOL);
        $this->successBlock('Code deployed successfully.');

        return ExitCode::OK;
    }

    /**
     * Dialog helper to choose the Remote
     *
     * @return string upstream
     */
    protected function getUpstream(Git $git): string
    {
        // Get configured remote & sshUrl
        $upstream = explode('/', $this->stage->gitRemote)[0];
        $sshUrl = $this->stage->sshUrl;
        $remotes = $git->getRemotes();

        // Nothing found
        if ([] === $remotes && $this->confirm("No git remotes configured. Do you want to add '{$sshUrl}'?")) {
            return $git->addRemote($sshUrl);
        }

        // Auto setup
        if (! array_key_exists($upstream, $remotes)) {
            $git->addRemote($sshUrl);
            $remotes = $git->getRemotes();
        }

        // Just one
        if (count($remotes) === 1) {
            return array_keys($remotes)[0];
        }

        // return the configured upstream
        return $upstream;
    }

    /**
     * Creates a .gitignore file in the directory of each local volume
     */
    protected function assureVolumesAreIgnored(): void
    {
        try {
            $volumes = $this->localFilesystem->filterByHandle();
            foreach ($volumes as $volume) {
                $path = App::parseEnv('@root') . DIRECTORY_SEPARATOR . $volume->path;
                FileHelper::writeGitignoreFile($path);
            }
        } catch (Throwable $throwable) {
            $this->line(PHP_EOL);
            $this->line($throwable->getMessage());
            $this->line(PHP_EOL);
        }
    }
}
