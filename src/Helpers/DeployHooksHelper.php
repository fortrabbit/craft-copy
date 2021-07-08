<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Helpers;

use Symfony\Component\Process\Process;

use craft\helpers\App;

trait DeployHooksHelper
{
    use ConsoleOutputHelper;

    /**
     * Calls 'before' scripts
     */
    protected function runBeforeDeployCommands(): bool
    {
        return $this->runDeployCommands('before');
    }

    /**
     * Calls 'after' scripts
     */
    protected function runAfterDeployCommands(): bool
    {
        return $this->runDeployCommands('after');
    }

    /**
     * Executes scripts defined in the config before or after Craft Copy commands
     * e.g. code/up
     */
    protected function runDeployCommands(string $when): bool
    {
        $action = str_replace('copy/', '', $this->controller->id . '/' . $this->id);
        $actions = $this->stage->{$when};
        $scripts = $actions[$action] ?? [];

        if (count($actions) === 0 || count($scripts) === 0) {
            return true;
        }

        $this->head("Run $when scripts", null, false);

        $timeout = App::env('CRAFT_COPY_SCRIPT_TIMEOUT') ?: 300;

        foreach ($scripts as $script) {
            $this->cmdBlock($script);
            $process = Process::fromShellCommandline($script);
            $process-setTimeout($timeout);
            $process->run();
            if (! $process->isSuccessful()) {
                $this->errorBlock($process->getErrorOutput());
                return false;
            }
            $outputLines = explode(PHP_EOL, $process->getOutput());
            foreach ($outputLines as $line) {
                $this->output->writeln("   $line");
            }
        }

        $this->output->write(PHP_EOL);

        return true;
    }
}
