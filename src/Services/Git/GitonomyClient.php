<?php

namespace fortrabbit\Copy\Services\Git;

use fortrabbit\Copy\Exceptions\GitException;
use Gitonomy\Git\Admin;
use Gitonomy\Git\Exception\ProcessException;
use Gitonomy\Git\Reference\Branch;
use Gitonomy\Git\Repository;
use LogicException;
use RuntimeException;

class GitonomyClient implements Client
{

	private Repository $repository;

	private string $directory;

	public function clone(
		string $repository,
		?string $directory = null,
		array $options = []
	) {
		$this->directory = $directory;
		$this->repository = Admin::cloneTo($directory, $repository, true, $options);
	}

	public function push(string $upstream, string $branch = 'master'): string
	{
		return $this->run(GitCommand::PUSH, [$upstream, $branch]);
	}

	public function pull(string $upstream, string $branch = 'master'): string
	{
		return $this->run(GitCommand::PULL, [$upstream, $branch]);
	}

	public function getLocalHead(): ?string
	{
		$head = $this->repository->getHead();
		if ($head instanceof Branch) {
			return $head->getName();
		}
		return $head->getFullName();
	}

	public function getLocalBranches(): array
	{
		$localBranches = array_filter(
			$this->repository->getReferences()->getBranches(),
			fn ($branch) => $branch->isLocal()
		);

		return array_map(function ($branch) {
			return $branch->getName();
		}, $localBranches);
	}

	public function getRemotes(?string $for = 'push'): array
	{
		if (!in_array($for, ['push', 'pull'], true)) {
			throw new LogicException(
				sprintf(
					'Argument 1 passed to %s must be "pull" or "push", %s given.',
					'fortrabbit\Copy\Services\Git\GitonomyClient::getRemotes()',
					$for
				)
			);
		}

		$remotes = explode(PHP_EOL, rtrim($this->run(GitCommand::REMOTE, ['-v'])));

		return $remotes;
	}

	public function getTracking(bool $includeBranch = false): ?string
	{
		try {
			$result = $this->repository->run(GitCommand::REV_PARSE, [
				'--symbolic-full-name',
				'@{u}',
				'--abbrev-ref',
			]);
		} catch (ProcessException) {
			return null;
		}

		if ($includeBranch) {
			return $result;
		}

		// Split upstream/branch and return upstream only
		return explode('/', $result)[0];
	}

	public function checkout(string $branch)
	{
		$this->repository->run(GitCommand::CHECKOUT, [$branch]);
	}

	public function addRemote(string $name, ?string $url)
	{
		$this->repository->run(GitCommand::REMOTE, ['add', $name, $url]);
	}

	public function setDirectory(string $directory)
	{
		$this->directory = $directory;
		$this->repository = new Repository($directory);
	}

	public function getDirectory(): string
	{
		return $this->directory;
	}

	public function init()
	{
		Admin::init($this->directory, $this->repository->isBare());
	}

	public function log(...$argsOrOptions): string
	{
		return $this->run(GitCommand::LOG, $argsOrOptions);
	}

	public function hasChanges(): bool
	{
		return $this->getStatus() !== '';
	}

	public function getStatus(): string
	{
		return $this->run(GitCommand::STATUS, ['-s']);
	}

	public function add(string $filepattern, array $options = []): string
	{
		return $this->run(GitCommand::ADD, [$filepattern]);
	}

	public function commit(...$argsOrOptions): string
	{
		if (isset($argsOrOptions[0]) && is_string($argsOrOptions[0]) && !isset($argsOrOptions[1])) {
			$argsOrOptions = [
				'-a',
				'-m ' . $argsOrOptions[0],
			];
		}

		return $this->run(GitCommand::COMMIT, $argsOrOptions);
	}

	private function run(string $command, array $args): string
	{
		try {
			return $this->repository->run($command, $args);
		} catch (RuntimeException $exception) {
			throw new GitException($exception->getMessage(), $exception->getCode(), $exception);
		}
	}
}
