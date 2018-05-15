<?php

namespace fortrabbit\Copy\commands;

use fortrabbit\Copy\Plugin;
use ostark\Yii2ArtisanBridge\base\Action;
use Symfony\Component\Console\Helper\TableSeparator;
use yii\console\ExitCode;

class InfoAction extends Action
{
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
        if ($plugin->ssh->exec('php vendor/bin/craft-copy-env.php')) {
            $remote = json_decode($plugin->ssh->getOutput(), true);
        };

        $this->section('Environments');


        $rows = [
            ['ENVIRONMENT', getenv('ENVIRONMENT'), $remote['ENVIRONMENT'], (getenv('ENVIRONMENT') != $remote['ENVIRONMENT']) ? "👌" : "💥"],
            new TableSeparator(),
            ['SECURITY_KEY', getenv('SECURITY_KEY'), $remote['SECURITY_KEY'], (getenv('ENVIRONMENT') === $remote['ENVIRONMENT']) ? "👌" : "💥"],
            new TableSeparator(),
            ['DB_TABLE_PREFIX', getenv('DB_TABLE_PREFIX'), $remote['DB_TABLE_PREFIX'], (getenv('DB_TABLE_PREFIX') === $remote['DB_TABLE_PREFIX']) ? "👌" : "💥"],
            ['DB_SERVER', getenv('DB_SERVER'), $remote['DB_SERVER'], (stristr($remote['DB_SERVER'], '.frbit.com')) ? "👌" : "💥"],
        ];

        // Optional
        foreach (['OBJECT_STORAGE_', 'S3_'] as $volumeConfigPrefix) {
            if (isset($remote[$volumeConfigPrefix . 'BUCKET'])) {
                $rows[] = new TableSeparator();
                foreach ($remote as $key => $value) {
                    if (strstr($key, $volumeConfigPrefix)) {
                        $rows[] = [$key, getenv($key), $value, (getenv($key) === $value) ? "👌" : "💥"];
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
}
