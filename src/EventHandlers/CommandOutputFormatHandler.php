<?php

namespace fortrabbit\Copy\EventHandlers;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use yii\base\ActionEvent;

class CommandOutputFormatHandler
{

    public function __invoke(ActionEvent $event) : void
    {
        /** @var \ostark\Yii2ArtisanBridge\base\Action $action */
        $action = $event->action;
        $style = new OutputFormatterStyle('blue');
        $action->output->getFormatter()->setStyle('comment', $style);
        $action->output->getFormatter()->setStyle('info', $style);
    }
}
