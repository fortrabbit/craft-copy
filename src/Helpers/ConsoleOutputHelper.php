<?php

namespace fortrabbit\Copy\Helpers;

use ostark\Yii2ArtisanBridge\OutputStyle;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\TableSeparator;

/**
 * Trait ConsoleOutputHelper
 *
 * @package fortrabbit\Copy\services
 *
 * @property string $app
 * @property boolean $dryRun
 * @property OutputStyle $output
 */
trait ConsoleOutputHelper
{
    public function rsyncInfo(string $dir, string $remoteUrl = null, string $volumeHandle = null)
    {
        $rows = [
            ['Directory', $dir],
            new TableSeparator(),
            ['SSH remote', $remoteUrl],
            new TableSeparator(),
            ['Dry run', $this->dryRun ? 'true' : 'false']
        ];

        if ($volumeHandle) {
            $rows[] = new TableSeparator();
            $rows[] = ['Volume', $volumeHandle];
        }

        $this->table(
            ['Key', 'Value'],
            $rows
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
        $this->block($cmd, null, 'fg=white;bg=default', '<comment>  $  </comment>', false, false);
        return true;
    }

    /**
     * @param string $message
     * @param string|null $context
     * @param bool $clear
     */
    public function head(string $message, string $context = null, $clear = true)
    {
        $messages = ["<options=bold;fg=white>$message</>"];

        // clear the screen
        if ($clear) {
            $this->output->write(sprintf("\033\143"));
        }

        // Add context before the actual message
        if (is_string($context)) {
            $messages = array_merge([$context], $messages);
        }

        $this->block($messages, null, 'fg=white;', "<comment>▏</comment>", false, false);
    }

    public function createProgressBar(int $steps): ProgressBar
    {
        // Custom format
        $lines = [
            '%message%',
            '%bar% %percent:3s% %',
            'time:  %elapsed:6s%/%estimated:-6s%'
        ];

        $bar = $this->output->createProgressBar($steps);

        $bar->setFormat(implode(PHP_EOL, $lines) . PHP_EOL . PHP_EOL);
        $bar->setBarCharacter('<info>' . $bar->getBarCharacter() . '</info>');
        $bar->setBarWidth(70);

        return $bar;
    }
}
