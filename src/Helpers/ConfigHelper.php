<?php

namespace fortrabbit\Copy\Helpers;

use fortrabbit\Copy\Plugin;
use Symfony\Component\Process\Process;

trait ConfigHelper
{
    use ConsoleOutputHelper;

    /**
     * @return null|string
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


    protected function runBeforeDeployCommands()
    {
        return $this->runDeployCommands('before');
    }

    protected function runAfterDeployCommands()
    {
        return $this->runDeployCommands('after');
    }


    /**
     * @param string $when
     *
     * @return bool
     */
    protected function runDeployCommands(string $when)
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
