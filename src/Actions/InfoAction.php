<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Actions;

use Closure;
use fortrabbit\Copy\Helpers\ConsoleOutputHelper;
use fortrabbit\Copy\Plugin;
use ostark\Yii2ArtisanBridge\base\Action;
use Symfony\Component\Console\Helper\TableSeparator;
use Throwable;
use yii\console\ExitCode;

class InfoAction extends Action
{
    use ConsoleOutputHelper;

    public bool $verbose = false;

    private array $remoteInfo = [];

    /**
     * Environment check
     */
    public function run(): int
    {
        $plugin = Plugin::getInstance();
        $stages = $plugin->stage->getConfigOptions();

        if ($stages === []) {
            $this->errorBlock(
                'The plugin is not configured yet. Make sure to run this setup command first:'
            );
            $this->cmdBlock('php craft copy/setup');

            return ExitCode::UNSPECIFIED_ERROR;
        }

        foreach ($stages as $key => $stageName) {
            $this->head('Environment check', "<info>{$stageName}</info>", $key === 0);

            $plugin->stage->setName($stageName);
            $stage = $plugin->stage->get();

            $app = $stage->app;
            $plugin->ssh->remote = $stage->sshUrl;

            // Get environment info from remote
            try {
                $plugin->ssh->exec('php vendor/bin/craft-copy-env.php');
                $this->remoteInfo = json_decode($plugin->ssh->getOutput(), true, 512, JSON_THROW_ON_ERROR);
            } catch (Throwable) {
                $this->errorBlock(
                    "Unable to get information about the fortrabbit App using '{$stage->sshUrl}'"
                );

                return ExitCode::UNSPECIFIED_ERROR;
            }

            // Rows
            $rows = [
                $this->envRow('ENVIRONMENT', fn($local, $remote) => ! $local || ! $remote ? false : ($local !== $remote)),
                new TableSeparator(),

                $this->envRow('SECURITY_KEY', null, true),
                new TableSeparator(),

                $this->envRow('DB_SERVER', fn($local, $remote) => stristr($remote, '.frbit.com')),
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
            $errors = array_filter(['SECURITY_KEY'], fn($key) => ! self::assertEquals(
                $this->remoteInfo[$key],
                getenv($key)
            ));

            if ($errors !== []) {
                $varsUrl = sprintf('%s/apps/%s/vars', Plugin::DASHBOARD_URL, $app);
                $messages = ['These local ENV vars are not in sync with the fortrabbit App:'];

                foreach ($errors as $key) {
                    $messages[] = "<fg=white>{$key}=" . getenv($key) . '</>';
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

    protected static function assertEquals(
        bool|string $localValue,
        bool|string $remoteValue,
        ?Closure $comparisonCallback = null
    ): bool {
        if ($comparisonCallback === null) {
            return $localValue === $remoteValue;
        }

        return $comparisonCallback($localValue, $remoteValue) ? true : false;
    }

    /**
     *
     * @return mixed[]
     */
    protected function envRow(string $key, ?\Closure $callback = null, bool $obfuscate = false): array
    {
        $localValue = getenv($key);
        $remoteValue = $this->remoteInfo[$key] ?? '';
        $success = self::assertEquals($localValue, $remoteValue, $callback);

        $icon = $success ? '👌' : '💥';
        $color = $success ? 'white' : 'red';

        return [
            "<fg={$color}>{$key}</>",
            $obfuscate && !$this->verbose ? $this->obfuscate($localValue) : $localValue,
            $obfuscate && !$this->verbose ? $this->obfuscate($remoteValue) : $remoteValue,
            $icon,
        ];
    }

    protected function obfuscate(string $value, int $visibleChars = 5): string
    {
        return substr($value, 0, $visibleChars) . '*******';
    }
}
