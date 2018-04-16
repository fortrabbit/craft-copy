<?php namespace fortrabbit\Copy\ArtisanConsoleBridge\base;

use fortrabbit\Copy\exceptions\CraftNotInstalledException;
use fortrabbit\Copy\exceptions\PluginNotInstalledException;
use fortrabbit\Copy\exceptions\RemoteException;
use fortrabbit\Copy\Plugin;
use fortrabbit\Copy\services\ConsoleOutputHelper;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use yii\base\Action as YiiBaseAction;
use yii\helpers\Console;

abstract class Action extends YiiBaseAction
{

    protected $args = [];

    protected $options = [];

    protected $style;

    /**
     * @var \Symfony\Component\Console\Output\ConsoleOutputInterface
     */
    protected $output;

    use ArtisanTrait;

    public function __construct($id, \yii\base\Controller $controller, array $config = [])
    {
        $this->output = new ConsoleOutput();
        $this->style = new SymfonyStyle(new ArrayInput([]), $this->output);


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
    public function pleaseConfirm($question)
    {
        if ($this->getOption('force')) {
            return true;
        }
        if ($this->controller->confirm(PHP_EOL . $question, true)) {
            return true;
        }

        $this->block('Action was not executed.', 'error');

        return false;
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

}

