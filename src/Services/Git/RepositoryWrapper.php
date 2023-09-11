<?php

namespace fortrabbit\Copy\Services\Git;

use Gitonomy\Git\Repository;
use Symfony\Component\Process\Process;

class RepositoryWrapper
{
    public Repository $repo;

    public function __construct(Repository $repository)
    {
        $this->repo = $repository;
    }

    public function start(string $command, array $args = []): Process
    {
        $process = $this->getProcess($command, $args);

        if ($this->repo->getLogger()) {
            $this->repo->getLogger()->info(sprintf('run command: %s "%s" ', $command, implode(' ', $args)));
            $before = microtime(true);
        }

        $process->start();

        return $process;
    }

    private function getProcess($command, $args = []): Process
    {
        $reflectionClass = new \ReflectionClass($this->repo);
        $reflectionMethod = $reflectionClass->getMethod('getProcess');
        $reflectionMethod->setAccessible(true);

        $params = array_slice(func_get_args(), 2); //get all the parameters after $methodName
        return $reflectionMethod->invokeArgs($this, $params);
    }


}
