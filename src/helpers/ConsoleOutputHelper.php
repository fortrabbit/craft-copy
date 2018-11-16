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
     * Command line block
     *
     * @param string $cmd
     *
     * @return bool
     */
    public function cmdBlock(string $cmd)
    {
        $here = str_replace(getenv("HOME"), '~', getcwd());
        $this->block($cmd, null, 'fg=white;bg=default', '<comment> ' . $here . ' ►  </comment>', false, false);
        return true;
    }

    /**
     * @param string      $message
     * @param string|null $context
     * @param bool        $clear
     */
    public function head(string $message, string $context = null, $clear = true)
    {
        if ($clear) {
            $this->output->write(sprintf("\033\143"));
        }

        $this->block("<options=bold;fg=white>$message</>", $context, 'fg=white;', '▶ ', false, false);
    }
}
