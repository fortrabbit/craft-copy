<?php

namespace fortrabbit\Copy\commands;

use fortrabbit\Copy\helpers\ConsoleOutputHelper;
use fortrabbit\Copy\Plugin;
use ostark\Yii2ArtisanBridge\base\Action;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Yaml\Yaml;
use yii\console\ExitCode;

class InfoAction extends Action
{
    public $verbose = false;
    private $remoteInfo = [];

    use ConsoleOutputHelper;

    /**
     * @param string|bool   $localValue
     * @param string|bool   $remoteValue
     * @param bool|callable $assertEquals
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

    /**
     * Environment check
     */
    public function run()
    {
        $plugin  = Plugin::getInstance();
        $configs = $plugin->config->getConfigOptions();

        if (count($configs) === 0) {
            $this->errorBlock('The plugin is not configured yet. Make sure to run this setup command first:');
            $this->cmdBlock("php craft copy/setup");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        foreach ($configs as $key => $configName) {

            $this->head("Environment check", "<info>$configName</info>", ($key === 0) ? true : false);

            $plugin->config->setName($configName);
            $config = $plugin->config->get();

            $app                 = $config->app;
            $plugin->ssh->remote = $config->sshUrl;

            // Get environment info from remote
            try {
                $plugin->ssh->exec('php vendor/bin/craft-copy-env.php');
                $this->remoteInfo = json_decode($plugin->ssh->getOutput(), true);
            } catch (\Exception $e) {
                $this->errorBlock("Unable to get information about the remote environment using '{$config->sshUrl}'");
                return ExitCode::UNSPECIFIED_ERROR;
            }

            // Rows
            $rows = [
                $this->envRow('ENVIRONMENT', function ($local, $remote) {
                    return (!$local || !$remote) ? false : ($local != $remote);
                }),
                new TableSeparator(),

                $this->envRow('SECURITY_KEY', true, true),
                new TableSeparator(),

                $this->envRow('DB_TABLE_PREFIX'),
                $this->envRow('DB_SERVER', function ($local, $remote) {
                    return stristr($remote, '.frbit.com');
                })
            ];

            // Optional rows
            foreach (['OBJECT_STORAGE_', 'S3_'] as $volumeConfigPrefix) {
                if (isset($this->remoteInfo[$volumeConfigPrefix . 'BUCKET'])) {
                    $rows[] = new TableSeparator();
                    foreach ($this->remoteInfo as $key => $value) {
                        if (strstr($key, $volumeConfigPrefix)) {
                            $rows[] = $this->envRow($key, true, in_array($key, ['OBJECT_STORAGE_SECRET', 'S3_SECRET']));
                        }
                    }
                }
            }

            // Print table
            $this->table(
                ['Key', 'Local', sprintf("Remote (App:%s)", $app), '  '],
                $rows
            );

            // Error message with more instructions
            $errors = array_filter(['SECURITY_KEY', 'DB_TABLE_PREFIX'], function ($key) {
                return (!self::assertEquals(
                    $this->remoteInfo[$key],
                    getenv($key)
                ));
            });

            if (count($errors)) {
                $varsUrl  = sprintf("%s/apps/%s/vars", Plugin::DASHBOARD_URL, $app);
                $messages = ["These local ENV vars are not in sync with the remote:"];

                foreach ($errors as $key) {
                    $messages[] = "<fg=white>$key=" . getenv($key) . "</>";
                }

                $messages[] = (count($errors) == 1)
                    ? "Copy the line above and paste it here:" . PHP_EOL . $varsUrl
                    : "Copy the lines above and paste them here:" . PHP_EOL . $varsUrl;

                $this->block($messages, 'WARNING', 'fg=red;', ' ', true, false);
            }

            $configFile = $plugin->config->getFullPathToConfig();
            $rawYaml    = file_get_contents($configFile);
            $this->table([$configFile], [[$rawYaml]]);


        }

        return ExitCode::OK;
    }

    /**
     * @param string        $key
     * @param bool|callable $assertEquals
     * @param bool          $obfuscate
     *
     * @return array
     */
    protected function envRow($key, $assertEquals = true, $obfuscate = false)
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
}
