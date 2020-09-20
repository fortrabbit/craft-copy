<?php

namespace fortrabbit\Copy\Helpers;

use fortrabbit\Copy\Plugin;
use Symfony\Component\Process\Process;

trait ConfigHelper
{
    use ConsoleOutputHelper;

    /**
     * Extracts the name of the stage from the run command signature
     */
    protected function getStageName(): ?string
    {
        $action    = new \ReflectionClass(get_class($this));
        $runMethod = $action->getMethod('run');

        if (count($runMethod->getParameters()) === 0) {
            throw new \InvalidArgumentException("function run() has no parameters.");
        };

        if ($runMethod->getParameters()[0]->getName() !== 'stage') {
            throw new \InvalidArgumentException('First parameter of run() is not $stage.');
        };


        return \Yii::$app->requestedParams[0]
            ?? getenv(Plugin::ENV_DEFAULT_STAGE)
                ?: 'production';
    }

    /**
     * Calls 'before' scripts
     */
    protected function runBeforeDeployCommands() : bool
    {
        return $this->runDeployCommands('before');
    }

    /**
     * Calls 'after' scripts
     */
    protected function runAfterDeployCommands() : bool
    {
        return $this->runDeployCommands('after');
    }

    /**
     * Executes scripts defined in the config before or after Craft Copy commands
     * e.g. code/up
     */
    protected function runDeployCommands(string $when) : bool
    {
        $action   = str_replace('copy/', '', $this->controller->id . '/' . $this->id);
        $actions  = $this->stage->$when;
        $scripts = $actions[$action] ?? [];

        if (count($actions) === 0 || count($scripts) === 0) {
            return true;
        }

        $this->head(
            "Run $when scripts",
            "<comment>{$this->stage}</comment> {$this->stage->app}.frb.io",
            false
        );

        foreach ($scripts as $script) {
            $this->cmdBlock(" $script");
            $process = new Process($script);
            $process->run();
            if (!$process->isSuccessful()) {
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
