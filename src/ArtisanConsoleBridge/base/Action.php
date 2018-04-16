<?php namespace fortrabbit\Copy\ArtisanConsoleBridge\base;

use fortrabbit\Copy\exceptions\CraftNotInstalledException;
use fortrabbit\Copy\exceptions\PluginNotInstalledException;
use fortrabbit\Copy\exceptions\RemoteException;
use fortrabbit\Copy\Plugin;

use yii\base\Action as YiiBaseAction;

abstract class Action extends YiiBaseAction
{

    use ArtisanOutputTrait;
    use BlockOutputTrait;

    public function getOption($name, $defaultValue = null)
    {
        return $this->controller->options[$name] ?? $defaultValue;
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

