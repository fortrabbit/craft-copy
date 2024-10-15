<?php

declare(strict_types=1);

namespace fortrabbit\Copy\EventHandlers;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use yii\base\ActionEvent;

/**
 * Handler that takes care of console output
 */
class CommandOutputFormatHandler
{
    public function __invoke(ActionEvent $event): void
    {
        /** @var \fortrabbit\Yii2ArtisanBridge\base\Action $action */
        $action = $event->action;

        $style = new OutputFormatterStyle('blue');
        $action->getOutput()->getFormatter()->setStyle('comment', $style);
        $action->getOutput()->getFormatter()->setStyle('info', $style);

        $action->getOutput()->getFormatter()->setStyle(
            'underline',
            (new OutputFormatterStyle('blue', null, ['underscore']))
        );
    }
}
