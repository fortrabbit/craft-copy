<?php

namespace fortrabbit\Copy\Services\Git;

use Symfony\Component\Process\Process;

interface Client
{
	public function clone(
		string $repository,
		?string $directory = null,
		array $options = []
	);

	public function push(string $upstream, string $branch = 'master'): string;

    public function pushAndStream(string $upstream, string $branch = 'master'): Process;

    public function pull(string $upstream, string $branch = 'master'): string;

	public function getLocalHead(): ?string;

	public function getLocalBranches(): array;

	public function getRemotes(?string $for = 'push'): array;

	public function getTracking(bool $includeBranch = false): ?string;

	public function checkout(string $branch);

	public function addRemote(string $name, ?string $url);

	public function setDirectory(string $directory);

	public function getDirectory(): string;

	public function init();

	public function log(...$argsOrOptions): string;

	public function hasChanges(): bool;

	public function getStatus(): string;

	public function add(string $filepattern, array $options = []): string;

	public function commit(...$argsOrOptions): string;
}
