<?php

namespace fortrabbit\Copy\commands;

use fortrabbit\Copy\Plugin;
use ostark\Yii2ArtisanBridge\base\Action;
use Symfony\Component\Console\Helper\TableSeparator;
use yii\console\ExitCode;

class InfoAction extends Action
{

    private $remoteInfo = [];

    public $verbose = false;

    public function run()
    {
        $plugin = Plugin::getInstance();

        if (!$plugin->ssh->remote) {

            $this->errorBlock("The SSH remote is not configured yet.");
            $this->line('Run the setup command first:' . PHP_EOL);
            $this->output->type('php craft copy/setup' . PHP_EOL, 'fg=white', 20);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        // Get environment info from remote
        try {
            $plugin->ssh->exec('php vendor/bin/craft-copy-env.php');
            $this->remoteInfo = json_decode($plugin->ssh->getOutput(), true);
        } catch (\Exception $e) {
            $this->errorBlock('Unable to get information about the remote environment');
        }

        $this->section('Environments');

        $this->remoteInfo['DB_TABLE_PREFIX'] = null;

        $rows = [
            $this->row('ENVIRONMENT', function () {
                return (!$this->remoteInfo['ENVIRONMENT']) ? false : ($this->remoteInfo['ENVIRONMENT'] != getenv('ENVIRONMENT'));
            }),
            new TableSeparator(),
            $this->row('SECURITY_KEY', true, true),
            new TableSeparator(),
            $this->row('DB_TABLE_PREFIX'),
            $this->row('DB_SERVER', function () {
                return stristr($this->remoteInfo['DB_SERVER'], '.frbit.com');
            })
        ];

        // Optional
        foreach (['OBJECT_STORAGE_', 'S3_'] as $volumeConfigPrefix) {
            if (isset($this->remoteInfo[$volumeConfigPrefix . 'BUCKET'])) {
                $rows[] = new TableSeparator();
                foreach ($this->remoteInfo as $key => $value) {
                    if (strstr($key, $volumeConfigPrefix)) {
                        $rows[] = $this->row($key, true, in_array($key, ['OBJECT_STORAGE_SECRET', 'S3_SECRET']));
                    }
                }
            }
        }

        // Print table
        $this->table(
            ['Key', 'Local', sprintf("Remote (App:%s)", getenv('APP_NAME')), '  '],
            $rows
        );

    }

    /**
     * @param               $key
     * @param bool|callable $assertEqual
     * @param bool          $obfuscate
     *
     * @return array
     */
    protected function row($key, $assertEqual = true, $obfuscate = false)
    {

        $remoteValue = $this->remoteInfo[$key] ?? '';

        if (is_callable($assertEqual)) {
            $success = ($assertEqual()) ? true : false;
        } elseif ($assertEqual === false) {
            $success = (getenv($key) != $remoteValue) ? true : false;
        } else {
            $success = (getenv($key) === $remoteValue) ? true : false;
        }

        $icon  = ($success) ? "👌" : "💥";
        $color = ($success) ? "white" : "red";

        return [
            "<fg=$color>$key</>",
            ($obfuscate && $this->verbose === false) ? $this->obfuscate(getenv($key)) : getenv($key),
            ($obfuscate && $this->verbose === false) ? $this->obfuscate($remoteValue) : $remoteValue,
            $icon
        ];
    }

    /**
     * @param string $value
     * @param int    $visibleChars
     *
     * @return string
     */
    protected function obfuscate(string $value, $visibleChars = 5)
    {
        return (substr($value, 0, $visibleChars)) . '*******';
    }
}
