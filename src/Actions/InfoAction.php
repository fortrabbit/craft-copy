<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Actions;

use Closure;
use fortrabbit\Copy\Helpers\ConsoleOutputHelper;
use fortrabbit\Copy\Plugin;
use fortrabbit\Copy\Services\DeprecatedConfigFixer;
use ostark\Yii2ArtisanBridge\base\Action;
use Symfony\Component\Console\Helper\TableSeparator;
use Throwable;
use yii\console\ExitCode;

class InfoAction extends Action
{
    use ConsoleOutputHelper;

    public $verbose = false;

    private $remoteInfo = [];

    /**
     * Environment check
     */
    public function run()
    {
        $plugin = Plugin::getInstance();
        $stages = $plugin->stage->getConfigOptions();

        if (DeprecatedConfigFixer::hasDeprecatedConfig()) {
            $fixer = new DeprecatedConfigFixer($this, $plugin->stage);
            $fixer->showWarning();
            $fixer->askAndRun();
            return 0;
        }

        if (count($stages) === 0) {
            $this->errorBlock(
                'The plugin is not configured yet. Make sure to run this setup command first:'
            );
            $this->cmdBlock('php craft copy/setup');
            return ExitCode::UNSPECIFIED_ERROR;
        }

        foreach ($stages as $key => $stageName) {
            $this->head('Environment check', "<info>$stageName</info>", $key === 0 ? true : false);

            $plugin->stage->setName($stageName);
            $stage = $plugin->stage->get();

            $app = $stage->app;
            $plugin->ssh->remote = $stage->sshUrl;

            // Get environment info from remote
            try {
                $plugin->ssh->exec('php vendor/bin/craft-copy-env.php');
                $this->remoteInfo = json_decode($plugin->ssh->getOutput(), true);
            } catch (Throwable $e) {
                $this->errorBlock(
                    "Unable to get information about the fortrabbit App using '{$stage->sshUrl}'"
                );
                return ExitCode::UNSPECIFIED_ERROR;
            }

            // Rows
            $rows = [
                $this->envRow('ENVIRONMENT', function ($local, $remote) {
                    return ! $local || ! $remote ? false : ($local !== $remote);
                }),
                new TableSeparator(),

                $this->envRow('SECURITY_KEY', null, true),
                new TableSeparator(),

                $this->envRow('DB_TABLE_PREFIX'),
                $this->envRow('DB_SERVER', function ($local, $remote) {
                    return stristr($remote, '.frbit.com');
                }),
            ];

            // Optional rows
            foreach (['OBJECT_STORAGE_', 'S3_'] as $volumeConfigPrefix) {
                if (isset($this->remoteInfo[$volumeConfigPrefix . 'BUCKET'])) {
                    $rows[] = new TableSeparator();
                    foreach ($this->remoteInfo as $key => $value) {
                        if (strstr($key, $volumeConfigPrefix)) {
                            $rows[] = $this->envRow(
                                $key,
                                null,
                                in_array($key, ['OBJECT_STORAGE_SECRET', 'S3_SECRET'], true)
                            );
                        }
                    }
                }
            }

            // Print table
            $this->table(
                ['Key', 'Local', sprintf('Remote (App:%s)', $app), '  '],
                $rows
            );

            // Error message with more instructions
            $errors = array_filter(['SECURITY_KEY', 'DB_TABLE_PREFIX'], function ($key) {
                return ! self::assertEquals(
                    $this->remoteInfo[$key],
                    getenv($key)
                );
            });

            if (count($errors)) {
                $varsUrl = sprintf('%s/apps/%s/vars', Plugin::DASHBOARD_URL, $app);
                $messages = ['These local ENV vars are not in sync with the fortrabbit App:'];

                foreach ($errors as $key) {
                    $messages[] = "<fg=white>$key=" . getenv($key) . '</>';
                }

                $messages[] = count($errors) === 1
                    ? 'Copy the line above and paste it here:' . PHP_EOL . $varsUrl
                    : 'Copy the lines above and paste them here:' . PHP_EOL . $varsUrl;

                $this->block($messages, 'WARNING', 'fg=red;', ' ', true, false);
            }

            $configFile = $plugin->stage->getFullPathToConfig();
            $rawYaml = file_get_contents($configFile);
            $this->table([$configFile], [[$rawYaml]]);
            $this->output->writeln(PHP_EOL . PHP_EOL);
        }

        return ExitCode::OK;
    }

    /**
     * @param string|bool $localValue
     * @param string|bool $remoteValue
     *
     * @return bool
     */
    protected static function assertEquals(
        $localValue,
        $remoteValue,
        ?Closure $comparisonCallback = null
    ) {
        if ($comparisonCallback === null) {
            return $localValue === $remoteValue ? true : false;
        }

        return $comparisonCallback($localValue, $remoteValue) ? true : false;
    }

    /**
     * @param string $key
     * @param \Closure|null $callback
     * @param bool $obfuscate
     *
     * @return array
     */
    protected function envRow($key, $callback = null, $obfuscate = false)
    {
        $localValue = getenv($key);
        $remoteValue = $this->remoteInfo[$key] ?? '';
        $success = self::assertEquals($localValue, $remoteValue, $callback);

        $icon = $success ? '👌' : '💥';
        $color = $success ? 'white' : 'red';

        return [
            "<fg=$color>$key</>",
            $obfuscate && $this->verbose === false ? $this->obfuscate($localValue) : $localValue,
            $obfuscate && $this->verbose === false ? $this->obfuscate($remoteValue) : $remoteValue,
            $icon,
        ];
    }

    /**
     * @param int $visibleChars
     *
     * @return string
     */
    protected function obfuscate(string $value, $visibleChars = 5)
    {
        return substr($value, 0, $visibleChars) . '*******';
    }
}
