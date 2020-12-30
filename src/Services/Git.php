<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Services;

use Exception;
use fortrabbit\Copy\Plugin;
use GitWrapper\Exception\GitException;
use GitWrapper\GitWorkingCopy;
use GitWrapper\GitWrapper;
use InvalidArgumentException;
use LogicException;

/**
 * Git Service
 */
final class Git
{
    /**
     * @var \GitWrapper\GitWorkingCopy
     */
    private $gitWorkingCopy;

    private function __construct(GitWorkingCopy $gitWorkingCopy)
    {
        $this->gitWorkingCopy = $gitWorkingCopy;
    }

    /**
     * Directory Factory
     *
     * @param string $directory Path to the directory containing the working copy.
     *
     * @return \fortrabbit\Copy\Services\Git
     */
    public static function fromDirectory(string $directory)
    {
        $wrapper = new GitWrapper();
        $wrapper->setTimeout(300);

        return new self($wrapper->workingCopy($directory));
    }

    /**
     * Clone Factory
     *
     * @param string $repository The Git URL of the repository being cloned.
     * @param string|null $directory The directory that the repository will be cloned into.
     * @param mixed[] $options An associative array of command line options.
     *
     * @return \fortrabbit\Copy\Services\Git
     */
    public static function fromClone(
        string $repository,
        ?string $directory = null,
        array $options = [
        ]
    ) {
        $wrapper = new GitWrapper();
        $wrapper->setTimeout(300);

        return new self($wrapper->cloneRepository($repository, $directory, $options));
    }

    public function push(string $upstream, string $branch = 'master'): string
    {
        return $this->gitWorkingCopy->push($upstream, $branch);
    }

    public function pull(string $upstream, string $branch = 'master'): string
    {
        return $this->gitWorkingCopy->pull($upstream, $branch);
    }

    public function getLocalHead(): ?string
    {
        foreach ($this->getLocalBranches() as $key => $name) {
            if (stristr($name, '*')) {
                return $key;
            }
        }

        return null;
    }

    public function getLocalBranches(): array
    {
        $localBranches = [];
        foreach (explode(PHP_EOL, trim($this->gitWorkingCopy->run('branch'))) as $branch) {
            $localBranches[trim(ltrim($branch, '*'))] = $branch;
        }

        return $localBranches;
    }

    /**
     * @param string|null $for 'push' or 'pull'
     */
    public function getRemotes(?string $for = 'push'): array
    {
        if (! in_array($for, ['push', 'pull'], true)) {
            throw new LogicException(
                sprintf(
                    'Argument 1 passed to %s must be "pull" or "push", %s given.',
                    'fortrabbit\Copy\Services\Git::getRemotes()',
                    $for
                )
            );
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
     */
    public function getTracking($includeBranch = false): ?string
    {
        try {
            $result = $this->run('rev-parse', '@{u}', [
                'abbrev-ref' => true,
                'symbolic-full-name' => true,
            ]);
        } catch (GitException $gitException) {
            return null;
        }

        if ($includeBranch) {
            return $result;
        }

        // Split upstream/branch and return upstream only
        return explode('/', $result)[0];
    }

    /**
     * @param array ...$argsAndOptions
     */
    public function run(string $command, ...$argsAndOptions): string
    {
        return $this->gitWorkingCopy->run($command, $argsAndOptions);
    }

    /**
     * @return string Name of the remote
     */
    public function addRemote(string $sshRemote)
    {
        if (! stristr($sshRemote, 'frbit.com')) {
            throw new InvalidArgumentException(
                sprintf(
                    'Wrong $sshRemote must follow this pattern {app}@deploy.{region}.frbit.com, %s given.',
                    $sshRemote
                )
            );
        }

        $app = explode('@', $sshRemote)[0];
        $this->getWorkingCopy()->addRemote($app, "{$sshRemote}:{$app}.git");

        return $app;
    }

    public function getWorkingCopy(): GitWorkingCopy
    {
        return $this->gitWorkingCopy;
    }

    /**
     * Create .gitignore or adjust the existing
     *
     * @return bool
     * @throws Exception
     */
    public function assureDotGitignore()
    {
        $path = $this->getWorkingCopy()->getDirectory();
        $gitignoreFile = "$path/.gitignore";
        $gitignoreExampleFile = Plugin::PLUGIN_ROOT_PATH . '/.gitignore.example';

        if (! file_exists($gitignoreExampleFile)) {
            throw new Exception('Unable to read .gitignore.example.');
        }

        // No .gitignore? use our full example
        if (! file_exists($gitignoreFile)) {
            return copy($gitignoreExampleFile, $gitignoreFile);
        }

        if (! $gitignored = file_get_contents($gitignoreFile)) {
            throw new Exception('Unable to read .gitignore.');
        }

        // Append existing .gitignore
        if (strpos($gitignored, '.sql') === false) {
            $gitignored .= PHP_EOL;
            $gitignored .= PHP_EOL . '# Prevent to .sql files (added by fortrabbit/craft-copy)';
            $gitignored .= PHP_EOL . '*.sql' . PHP_EOL;

            return file_put_contents($gitignoreFile, $gitignored) ? true : false;
        }

        return true;
    }
}
