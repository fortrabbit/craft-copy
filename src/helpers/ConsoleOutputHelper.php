<?php

namespace fortrabbit\Copy\helpers;

use fortrabbit\Copy\Plugin;
use Symfony\Component\Console\Helper\TableSeparator;

/**
 * Trait ConsoleOutputHelper
 *
 * @package fortrabbit\Copy\services
 *
 * @property string $app
 * @property boolean $dryRun
 */
trait ConsoleOutputHelper
{
    /**
     * @param string      $dir
     * @param string|null $remoteUrl
     */
    public function rsyncInfo(string $dir, string $remoteUrl = null)
    {
        $this->table(
            ['Key', 'Value'],
            [
                ['Asset directory', $dir],
                new TableSeparator(),
                ['SSH remote', $remoteUrl],
                new TableSeparator(),
                ['Dry run', $this->dryRun ? 'true' : 'false']
            ]
        );
    }
}
