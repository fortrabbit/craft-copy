<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Services;

use Exception;
use fortrabbit\Copy\Plugin;
use fortrabbit\Copy\Services\Git\Client;
use fortrabbit\Copy\Services\Git\GitonomyClient;
use InvalidArgumentException;
use Symfony\Component\Process\Process;

/**
 * Git Service
 */
final class Git
{
    private function __construct(private Client $gitClient)
    {   
    }

    /**
     * Directory Factory
     *
     * @param string $directory Path to the directory containing the working copy.
     */
    public static function fromDirectory(string $directory): \fortrabbit\Copy\Services\Git
    {
        $client = new GitonomyClient();
        $client->setDirectory($directory);

        return new self($client);
    }

    /**
     * Clone Factory
     *
     * @param string $repository The Git URL of the repository being cloned.
     * @param string|null $directory The directory that the repository will be cloned into.
     * @param mixed[] $options An associative array of command line options.
     */
    public static function fromClone(
        string $repository,
        ?string $directory = null,
        array $options = [
        ]
    ): \fortrabbit\Copy\Services\Git {
        $client = new GitonomyClient();
        $client->clone($repository, $directory, $options);

        return new self($client);
    }

    public function push(string $upstream, string $branch = 'master'): Process
    {
        return $this->gitClient->pushAndStream($upstream, $branch);
    }

    public function pull(string $upstream, string $branch = 'master'): string
    {
        return $this->gitClient->pull($upstream, $branch);
    }

    public function getLocalHead(): ?string
    {
        return $this->gitClient->getLocalHead();
    }

    /**
     * @return array<string, string>
     */
    public function getLocalBranches(): array
    {
        return $this->gitClient->getLocalBranches();
    }

    /**
     * @param string|null $for 'push' or 'pull'
     * @return mixed[]
     */
    public function getRemotes(?string $for = 'push'): array
    {
        return $this->gitClient->getRemotes($for);
    }

    /**
     * Returns remote tracking upstream/branch for HEAD.
     */
    public function getTracking(bool $includeBranch = false): ?string
    {
        return $this->gitClient->getTracking($includeBranch);
    }

    /**
     * @return string Name of the remote
     */
    public function addRemote(string $sshRemote): string
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
        $this->gitClient->addRemote($app, "{$sshRemote}:{$app}.git");

        return $app;
    }

    public function getClient(): Client
    {
        return $this->gitClient;
    }

    /**
     * Create .gitignore or adjust the existing
     *
     * @throws Exception
     */
    public function assureDotGitignore(): bool
    {
        $path = $this->gitClient->getDirectory();
        $gitignoreFile = "{$path}/.gitignore";
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
        if (!str_contains($gitignored, '*.sql')) {
            $gitignored .= PHP_EOL;
            $gitignored .= PHP_EOL . '# Prevent to .sql files (added by fortrabbit/craft-copy)';
            $gitignored .= PHP_EOL . '*.sql' . PHP_EOL;

            return file_put_contents($gitignoreFile, $gitignored) ? true : false;
        }

        return true;
    }
}
