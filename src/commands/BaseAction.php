<?php

namespace fortrabbit\Copy\commands;

use ostark\Yii2ArtisanBridge\base\Action;

class BaseAction extends Action
{

    /**
     * @var string Name of the App (to apply multi staging configs)
     */
    public $app = null;

    /**
     * @var string Name of the Environment (to apply multi staging configs)
     */
    public $env = null;


    protected $config = null;

    public function beforeRun()
    {
        die(var_dump($this->env));

        return true;
    }
}
