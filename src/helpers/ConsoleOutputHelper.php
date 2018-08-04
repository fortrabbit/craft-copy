<?php

namespace fortrabbit\Copy\helpers;

use fortrabbit\Copy\Plugin;
use Symfony\Component\Console\Helper\TableSeparator;

/**
 * Trait ConsoleOutputHelper
 *
 * @package fortrabbit\Copy\services
 */
trait ConsoleOutputHelper
{

    public function rsyncInfo(string $dir)
    {
        $this->table(
            ['Key', 'Value'],
            [
                ['Asset directory', $dir],
                new TableSeparator(),
                ['SSH remote', getenv(Plugin::ENV_NAME_SSH_REMOTE)],
                new TableSeparator(),
                ['Dry run', $this->dryRun ? 'true' : 'false']
            ]
        );
    }
}
