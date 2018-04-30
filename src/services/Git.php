<?php

namespace fortrabbit\Copy\services;


use fortrabbit\Copy\Plugin;
use GitWrapper\GitException;
use GitWrapper\GitWorkingCopy;
use GitWrapper\GitWrapper;

/**
 * Git Service
 *
 * @package fortrabbit\Copy\services
 */
final class Git
{

    /**
     * @var \GitWrapper\GitWorkingCopy
     */
    protected $gitWorkingCopy;

    private function __construct(GitWorkingCopy $gitWorkingCopy)
    {
        $this->gitWorkingCopy = $gitWorkingCopy;
    }


    /**
     * Directoy Factory
     *
     * @param string $directory Path to the directory containing the working copy.
     *
     * @return \fortrabbit\Copy\services\Git
     */
    public static function fromDirectory(string $directory)
    {
        $wrapper = new GitWrapper();

        return new Git($wrapper->workingCopy($directory));
    }

    /**
     * Clone Factory
     *
     * @param string  $repository The Git URL of the repository being cloned.
     * @param string  $directory  The directory that the repository will be cloned into.
     * @param mixed[] $options    An associative array of command line options.
     *
     * @return \fortrabbit\Copy\services\Git
     */
    public static function fromClone(string $repository, ?string $directory = null, array $options = [])
    {
        $wrapper = new GitWrapper();

        return new Git($wrapper->cloneRepository($repository, $directory, $options));
    }

    /**
     * @return \GitWrapper\GitWorkingCopy
     */
    public function getWorkingCopy(): GitWorkingCopy
    {
        return $this->gitWorkingCopy;
    }

    /**
     * @param string $upstream
     * @param string $branch
     *
     * @return string
     */
    public function push(string $upstream, string $branch = 'master'): string
    {
        return $this->gitWorkingCopy->push($upstream, $branch);
    }

    /**
     * @param string $upstream
     * @param string $branch
     *
     * @return string
     */
    public function pull(string $upstream, string $branch = 'master'): string
    {
        return $this->gitWorkingCopy->pull($upstream, $branch);
    }


    /**
     * @return array
     */
    public function getLocalBranches(): array
    {
        $localBranches = [];
        foreach (explode(PHP_EOL, trim($this->gitWorkingCopy->run('branch'))) as $branch) {
            $localBranches[trim(ltrim($branch, '*'))] = $branch;
        };

        return $localBranches;
    }


    /**
     * @return null|string
     */
    public function getLocalHead(): ?string
    {
        foreach ($this->getLocalBranches() as $key => $name) {
            if (stristr($name, '*')) {
                return $key;
            }
        }

        return null;
    }


    /**
     * @param null|string $for 'push' or 'pull'
     *
     * @return array
     */
    public function getRemotes(?string $for = 'push'): array
    {
        if (!in_array($for, ['push', 'pull'])) {
            throw new \LogicException(sprintf('Argument 1 passed to fortrabbit\Copy\services\Git::getRemotes() must be "pull" or "push", %s given.', $for));
        }

        try {
            $remotes = $this->gitWorkingCopy->getRemotes();
        } catch (GitException $e) {
            return [];
        }

        foreach ($remotes as $name => $upstreams) {
            $remotes[$name] = $upstreams[$for];
        }

        return $remotes;
    }

    /**
     * Returns remote tracking upstream/branch for HEAD.
     *
     * @param bool $includeBranch
     *
     * @return null|string
     */
    public function getTracking($includeBranch = false): ?string
    {
        try {
            $result = $this->run('rev-parse', '@{u}', ['abbrev-ref' => true, 'symbolic-full-name' => true]);
        } catch (GitException $gitException) {
            return false;
        }

        if ($includeBranch) {
            return $result;
        }

        // Split upstream/branch and return upstream only
        return explode('/', $result)[0];

    }

    /**
     * @param string $sshRemote
     *
     * @return string $app Name of the remote
     */
    public function addRemote(string $sshRemote)
    {
        if (!stristr($sshRemote, 'frbit.com')) {
            throw new \InvalidArgumentException(sprintf('Wrong $sshRemote must follow this pattern {app}@deploy.{region}.frbit.com, %s given.', $sshRemote));
        }

        $app = explode('@', $sshRemote)[0];
        $this->getWorkingCopy()->addRemote($app, "{$sshRemote}:{$app}.git");

        return $app;
    }

    /**
     * @param string $command
     * @param array  $argsAndOptions
     *
     * @return string
     */
    public function run(string $command, ...$argsAndOptions): string
    {
        return $this->gitWorkingCopy->run($command, $argsAndOptions);
    }


    /**
     * Create .gitignore or adjust the existing
     *
     * @return bool
     * @throws \Exception
     */
    public function assureDotGitignore()
    {
        $path                 = $this->getWorkingCopy()->getDirectory();
        $gitignoreFile        = "$path/.gitignore";
        $gitignoreExampleFile = Plugin::PLUGIN_ROOT_PATH . "/.gitignore.example";

        if (!file_exists($gitignoreExampleFile)) {
            throw new \Exception("Unable to read .gitignore.example.");
        }

        if (!file_exists($gitignoreFile)) {
            return copy($gitignoreExampleFile, $gitignoreFile);
        }

        if (!$gitignored = file_get_contents($gitignoreFile)) {
            throw new \Exception("Unable to read .gitignore.");
        }

        if (strpos($gitignored, "web/assets") === false) {
            $gitignored .= PHP_EOL . '# ASSETS (added by fortrabbit/craft-copy)';
            $gitignored .= PHP_EOL . '/web/assets/*' . PHP_EOL;

            return (file_put_contents($gitignoreFile, $gitignored)) ? true : false;
        }

        return false;

    }


}
