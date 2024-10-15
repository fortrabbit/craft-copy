<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Actions;

use Craft;
use fortrabbit\Copy\Helpers\ConsoleOutputHelper;
use fortrabbit\Copy\Helpers\PathHelper;
use fortrabbit\Copy\Models\StageConfig;
use fortrabbit\Copy\Plugin;
use fortrabbit\Yii2ArtisanBridge\base\Action;
use Symfony\Component\Process\Process;
use yii\console\ExitCode;
use yii\helpers\Inflector;

class SetupAction extends Action
{
    use ConsoleOutputHelper;
    use PathHelper;

    /**
     * @var string
     */
    public const HELP_MYSQLDUMP_URL = 'https://github.com/fortrabbit/craft-copy#the-mysqldump-command-does-not-exist';

    /**
     * @var string
     */
    public const HELP_SSH_URL = 'https://help.fortrabbit.com/ssh-keys';

    /**
     * @var bool Verbose output
     */
    public $verbose = false;

    /**
     * Connect local dev with fortrabbit App
     *
     * @throws \fortrabbit\Copy\Exceptions\CraftNotInstalledException
     * @throws \fortrabbit\Copy\Exceptions\PluginNotInstalledException
     * @throws \fortrabbit\Copy\Exceptions\RemoteException
     * @throws \yii\base\Exception
     */
    public function run(): int
    {
        $this->input->setInteractive(true);
        $app = $this->ask("What's the name of your fortrabbit App?", '');
        $this->input->setInteractive($this->interactive);

        if (strlen($app) < 3 || strlen($app) > 16) {
            $this->errorBlock('Invalid App name.');

            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (! $region = $this->guessRegion($app)) {
            $this->errorBlock('⚠  App not found');

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $stageName = $this->anticipate(
            "What's a good name for the stage of the fortrabbit App? <fg=default>(use arrow keys or type)</>",
            [$app, 'production', "{$app}-prod", "{$app}-staging", 'prod', 'staging'],
            $app
        );

        // Persist config
        $config = $this->writeStageConfig($app, $region, Inflector::slug($stageName));

        // Perform exec checks
        $this->checkAndWrite('Testing DNS - ' . Plugin::REGIONS[$region], true);
        $this->checkAndWrite('Testing rsync', $this->canExecBinary('rsync --help'));

        $mysql = $this->checkAndWrite(
            'Testing mysqldump',
            $this->canExecBinary('mysqldump --help')
        );
        $ssh = $this->checkAndWrite(
            'Testing SSH access',
            $this->canExecBinary("ssh {$config->sshUrl} -o PasswordAuthentication=no secrets")
        );

        if (! $mysql) {
            $this->errorBlock(
                'Mysqldump is required. Please install it with your local development environment.'
            );
            $this->line('Get Help: ' . self::HELP_MYSQLDUMP_URL);
        }

        if (! $ssh) {
            $this->errorBlock(
                'SSH key authentication is required. Please add your SSH key to your fortrabbit Account first.'
            );
            $this->line('Get Help: ' . self::HELP_SSH_URL);
        }

        if (!$mysql || !$ssh) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return $this->setupRemote($config)
            ? ExitCode::OK
            : ExitCode::UNSPECIFIED_ERROR;
    }

    /**
     * Get from DNS record of the App
     */
    protected function guessRegion(string $app): ?string
    {
        if ($records = dns_get_record("{$app}.frb.io", DNS_CNAME)) {
            return explode('.', $records[0]['target'])[1];
        }

        return null;
    }

    /**
     * Write config file
     */
    protected function writeStageConfig(string $app, string $region, string $stageName): StageConfig
    {
        $config = new StageConfig();
        $config->app = $app;
        $config->sshUrl = "{$app}@deploy.{$region}.frbit.com";
        $config->gitRemote = "{$app}/master";
        $config->setName($stageName);
        Plugin::getInstance()->stage->setName($stageName);

        // Check if file already exist
        if (file_exists(Plugin::getInstance()->stage->getFullPathToConfig())) {
            $file = Plugin::getInstance()->stage->getConfigFileName();
            if (! $this->confirm("Do you want to overwrite your existing config? ({$file})", true)) {
                return $config;
            }
        }

        // Write
        Plugin::getInstance()->stage->persist($config);
        Plugin::getInstance()->stage->setName($stageName);

        // Write .env
        foreach ([
            Plugin::ENV_DEFAULT_STAGE => $stageName,
        ] as $name => $value) {
            Craft::$app->getConfig()->setDotEnvVar($name, $value);
            putenv("{$name}={$value}");
        }

        return $config;
    }

    protected function checkAndWrite(string $message, bool $success): bool
    {
        $this->output->write(PHP_EOL . $message);
        $this->output->write($success ? ' <info>OK</info>' : ' <error>⚠ Error</error>');

        return $success;
    }

    protected function canExecBinary(string $cmd): bool
    {
        $process = Process::fromShellCommandline($cmd, CRAFT_BASE_PATH);
        $exitCode = $process->run();

        return $exitCode === 0;
    }

    /**
     * @throws \fortrabbit\Copy\Exceptions\CraftNotInstalledException
     * @throws \fortrabbit\Copy\Exceptions\PluginNotInstalledException
     * @throws \fortrabbit\Copy\Exceptions\RemoteException
     */
    protected function setupRemote(StageConfig $config): bool
    {
        $plugin = Plugin::getInstance();

        // Is copy deployed already?
        if ($plugin->ssh->exec('ls vendor/bin/craft-copy-import-db.php | wc -l')) {
            // Yes. Existing setup?
            if (trim($plugin->ssh->getOutput()) === '1') {
                $this->successBlock(
                    [
                        'Craft was detected on the fortrabbit App.',
                        'Run the following commands to get a copy of the fortrabbit App:',
                    ]
                );

                $this->cmdBlock('php craft copy/db/down');
                $this->cmdBlock('php craft copy/code/down');
                $this->cmdBlock('php craft copy/volumes/down');
                $this->line(PHP_EOL);

                return true;
            }

            // Not installed
            $this->successBlock(
                [
                    'Local setup completed.',
                    'Run this command to deploy code, database and volumes in one go:',
                ]
            );

            $this->cmdBlock('php craft copy/all/up');
            $this->line(PHP_EOL);

            return true;
        }

        $this->errorBlock('Unable to run SSH command.');

        return false;
    }
}
