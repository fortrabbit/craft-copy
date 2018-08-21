<?php

namespace fortrabbit\Copy\commands;

use ostark\Yii2ArtisanBridge\base\Action;

class BaseAction extends Action
{

    /**
     * @var string Name of the App (to apply multi staging configs)
     */
    public $app;
}
