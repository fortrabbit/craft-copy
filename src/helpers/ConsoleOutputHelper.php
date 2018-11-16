<?php

namespace fortrabbit\Copy\helpers;

use fortrabbit\Copy\Plugin;
use Symfony\Component\Console\Helper\TableSeparator;

/**
 * Trait ConsoleOutputHelper
 *
 * @package fortrabbit\Copy\services
 *
 * @property string  $app
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

    /**
     * Formats a command comment.
     *
     * @param string|array $message
     */
    public function cmdBlock($cmd)
    {
        $here = str_replace(getenv("HOME"), '~', getcwd());
        $this->block($cmd, null, 'fg=white;bg=default', '<comment> ' . $here . ' ►  </comment>', false, false);
        return true;

    }

    public function head($message, $context = null, $clear = true)
    {
        if ($clear) {
            $this->output->write(sprintf("\033\143"));
        }

        $this->block($message, $context, 'fg=white;', '▶ ', false, false);
    }
}
