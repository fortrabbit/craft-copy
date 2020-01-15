<?php

namespace fortrabbit\Copy\Helpers;

use ostark\Yii2ArtisanBridge\base\Commands;

/**
 * Class ArtisanStyleCommands
 *
 * @package fortrabbit\Copy\helpers
 */
class ArtisanStyleCommands extends Commands
{
    public function getHelpSummary()
    {
        return 'Copy Craft between environments.';
    }
}
