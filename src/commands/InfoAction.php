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

        // Continue if ssh remote is set
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

        $this->remoteInfo['DB_TABLE_PREFIX2'] = null;

        $rows = [
            $this->row('ENVIRONMENT', function ($local, $remote) {
                return (!$local) ? false : ($local != $remote);
            }),
            new TableSeparator(),
            $this->row('SECURITY_KEY', true, true),
            new TableSeparator(),
            $this->row('DB_TABLE_PREFIX'),
            $this->row('DB_SERVER', function ($local, $remote) {
                return stristr($remote, '.frbit.com');
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

        // Error message with more instructions
        $errors = array_filter(['SECURITY_KEY', 'DB_TABLE_PREFIX2'], function ($key) {
            return (!self::assertEquals(
                $this->remoteInfo[$key],
                getenv($key)
            ));
        });

        if (count($errors)) {

            $varsUrl = sprintf("https://dashboard.fortrabbit.com/apps/%s/vars", getenv('APP_NAME'));
            $messages = ["These local ENV vars are not in sync with the remote:"];

            foreach ($errors as $key) {
                $messages[] = "<fg=white>$key=" . getenv($key)."</>";
            }

            $messages[] =(count($errors) == 1)
                ? "Copy the line above and paste it here:" . PHP_EOL . $varsUrl
                : "Copy the lines above and paste them here:" . PHP_EOL . $varsUrl;

            $this->block($messages, 'WARNING', 'fg=red;', ' ', true, false);

        }


    }


    /**
     * @param               $key
     * @param bool|callable $assertEquals
     * @param bool          $obfuscate
     *
     * @return array
     */
    protected function row($key, $assertEquals = true, $obfuscate = false)
    {
        $localValue  = getenv($key);
        $remoteValue = $this->remoteInfo[$key] ?? '';
        $success     = self::assertEquals($localValue, $remoteValue, $assertEquals);

        $icon  = ($success) ? "👌" : "💥";
        $color = ($success) ? "white" : "red";

        return [
            "<fg=$color>$key</>",
            ($obfuscate && $this->verbose === false) ? $this->obfuscate($localValue) : $localValue,
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

    /**
     * @param      $localValue
     * @param      $remoteValue
     * @param bool $assertEquals
     *
     * @return bool
     */
    protected static function assertEquals($localValue, $remoteValue, $assertEquals = true)
    {
        if (is_callable($assertEquals)) {
            return ($assertEquals($localValue, $remoteValue)) ? true : false;
        } elseif ($assertEquals === false) {
            return ($localValue != $remoteValue) ? true : false;
        }

        return ($localValue == $remoteValue) ? true : false;

    }
}
