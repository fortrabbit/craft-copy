<?php namespace fortrabbit\Copy\commands;


use craft\errors\ActionCancelledException;
use fortrabbit\Copy\exceptions\CraftNotInstalledException;
use fortrabbit\Copy\exceptions\PluginNotInstalledException;
use fortrabbit\Copy\exceptions\RemoteException;
use fortrabbit\Copy\Plugin;
use fortrabbit\Copy\services\ConsoleOutputHelper;
use GuzzleHttp\Promise\CancellationException;
use Symfony\Component\Console\Output\ConsoleOutput;
use yii\base\Action;

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
     * @throws \craft\errors\CancellationException
     */
    public function isForcedOrConfirmed($question)
    {
        if ($this->getOption('force')) {
            return true;
        }
        if ($this->controller->confirm(PHP_EOL . $question, true)) {
            return true;
        }

        throw new CancellationException("Cancelled. Action was not executed.");
    }

    public function remotePreCheck()
    {
        $plugin = Plugin::getInstance();
        try {
            $plugin->ssh->checkPlugin();
        } catch (CraftNotInstalledException $e) {
            $this->error($e->getMessage());
        } catch (PluginNotInstalledException $e) {
            $plugin->ssh->installPlugin();
        } catch (RemoteException $e) {
            $this->error($e->getMessage());
        }
    }
}

