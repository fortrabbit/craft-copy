<?php

namespace fortrabbit\Copy\Services\Git;

use fortrabbit\Copy\Services\Git\Client;
use Symplify\GitWrapper\GitWorkingCopy;
use Symplify\GitWrapper\GitWrapper;
use Symplify\GitWrapper\Exception\GitException;
use LogicException;

class GitWrapperClient implements Client {

	private GitWorkingCopy $gitWorkingCopy;

	private GitWrapper $wrapper;

	public function __construct()
    {
    	$this->wrapper = new GitWrapper('git');
        $this->wrapper->setTimeout(300);
    }

	public function clone(string $repository,
        ?string $directory = null,
        array $options = [
        ]) {
		$this->setWorkingCopy($this->wrapper->cloneRepository($repository, $directory, $options));
	}

	public function setDirectory(string $directory) {
		$this->setWorkingCopy($this->wrapper->workingCopy($directory));
	}

	public function push(string $upstream, string $branch = 'master'): string {
        return $this->gitWorkingCopy->push($upstream, $branch);
	}

	public function pull(string $upstream, string $branch = 'master'): string {
        return $this->gitWorkingCopy->pull($upstream, $branch);
	}

	public function getLocalHead(): ?string {
		foreach ($this->getLocalBranches() as $key => $name) {
            if (stristr($name, '*')) {
                return $key;
            }
        }

        return null;
	}

	public function getLocalBranches(): array {
		$localBranches = [];
        foreach (explode(PHP_EOL, trim($this->gitWorkingCopy->run('branch'))) as $branch) {
            $localBranches[trim(ltrim($branch, '*'))] = $branch;
        }

        return $localBranches;
	}

	public function getRemotes(?string $for = 'push'): array {
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
        } catch (GitException) {
            return [];
        }

        foreach ($remotes as $name => $upstreams) {
            $remotes[$name] = $upstreams[$for];
        }

        return $remotes;
	}

	public function getTracking(bool $includeBranch = false): ?string {
		try {
            $result = $this->gitWorkingCopy->run('rev-parse', ['@{u}', [
                'abbrev-ref' => true,
                'symbolic-full-name' => true,
            ]]);
        } catch (GitException) {
            return null;
        }

        if ($includeBranch) {
            return $result;
        }

        // Split upstream/branch and return upstream only
        return explode('/', $result)[0];
	}

	public function checkout(string $branch) {
        $this->getWorkingCopy()->run('checkout', [$branch]);
	}

	public function addRemote(string $name, ?string $url) {
        $this->gitWorkingCopy->addRemote($name, $url);

	}

	private function setWorkingCopy(GitWorkingCopy $gitWorkingCopy) {
		$this->gitWorkingCopy = $gitWorkingCopy;
	}

	private function getWorkingCopy(): GitWorkingCopy {
		return $this->gitWorkingCopy;
	}

	public function getDirectory(): string {
		return $this->gitWorkingCopy->getDirectory();
	}

	public function init() {
        $this->gitWorkingCopy->init();
	}

    public function log(...$argsOrOptions): string {
    	return $this->getWorkingCopy()->log($argsOrOptions);
    }

    public function hasChanges(): bool {
    	return $this->getWorkingCopy()->hasChanges();
    }

    public function getStatus(): string {
    	return $this->getWorkingCopy()->getStatus();
    }

    public function add(string $filepattern, array $options = []): string {
    	return $this->getWorkingCopy()->add($filepattern, $options);
    }

    public function commit(...$argsOrOptions): string {
    	return $this->getWorkingCopy()->commit($argsOrOptions);
    }

    public function streamOutput(bool $streamOutput = true): void {
    	$this->wrapper->streamOutput();
    }

}