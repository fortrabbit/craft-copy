<?php namespace fortrabbit\Copy\commands;

use fortrabbit\Copy\exceptions\CraftNotInstalledException;
use fortrabbit\Copy\exceptions\PluginNotInstalledException;
use fortrabbit\Copy\exceptions\RemoteException;
use fortrabbit\Copy\Plugin;
use fortrabbit\Copy\services\ConsoleOutputHelper;
use Symfony\Component\Console\Output\ConsoleOutput;
use yii\base\Action;
use yii\helpers\Console;

abstract class ConsoleBaseAction extends Action
{
    const OPTIONS = [];

    protected $success = true;

    protected $args = [];

    protected $options = [];

    /**
     * @var \Symfony\Component\Console\Output\ConsoleOutputInterface
     */
    protected $output;

    use ConsoleOutputHelper;

    public function __construct($id, \yii\base\Controller $controller, array $config = [])
    {
        $this->output = $this->output = new ConsoleOutput();

        parent::__construct($id, $controller, $config);
    }


    public function getOption($name, $defaultValue = null)
    {
        return $this->controller->$name ?? $defaultValue;
    }

    /**
     * @param $question
     *
     * @return bool
     * @throws \yii\console\Exception
     */
    public function isForcedOrConfirmed($question)
    {
        if ($this->getOption('force')) {
            return true;
        }
        if ($this->controller->confirm(PHP_EOL . $question, true)) {
            return true;
        }

        throw new \yii\console\Exception("Cancelled. Action was not executed.");
    }

    public function remotePreCheck()
    {
        $plugin = Plugin::getInstance();
        try {
            $plugin->ssh->checkPlugin();
        } catch (CraftNotInstalledException $e) {
            $this->error($e->getMessage());
        } catch (PluginNotInstalledException $e) {
            $this->error($e->getMessage());
        } catch (RemoteException $e) {
            $this->error($e->getMessage());
        }
    }

    public function markdown($markdown) {
        return Console::markdownToAnsi($markdown);
    }
}

