<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Helpers;

use ostark\Yii2ArtisanBridge\base\Commands;

/**
 * Class ArtisanStyleCommands
 *
 * @package fortrabbit\Copy\helpers
 */
class ArtisanStyleCommands extends Commands
{
    public function getHelpSummary(): string
    {
        return 'Craft Copy - deployment tooling for fortrabbit.';
    }
}
