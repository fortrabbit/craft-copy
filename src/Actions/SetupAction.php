<?php

namespace fortrabbit\Copy\Actions;

use Craft;
use fortrabbit\Copy\Helpers\ConsoleOutputHelper;
use fortrabbit\Copy\Helpers\PathHelper;
use fortrabbit\Copy\Models\DeployConfig;
use fortrabbit\Copy\Plugin;
use ostark\Yii2ArtisanBridge\base\Action;
use Symfony\Component\Process\Process;
use yii\console\ExitCode;
use yii\helpers\Inflector;

/**
 * Class SetupAction
 *
 * @package fortrabbit\Copy\Commands
 */
class SetupAction extends Action
{
    use ConsoleOutputHelper;
    use PathHelper;

    public const TROUBLE_SHOOTING_MYSQLDUMP_URL = "https://github.com/fortrabbit/craft-copy#trouble-shooting";
    public const TROUBLE_SHOOTING_SSH_URL = "https://help.fortrabbit.com/ssh-keys";


    /**
     * @var bool Verbose output
     */
    public $verbose = false;

    /**
     * Setup your App
     *
     * @return int
     * @throws \fortrabbit\Copy\Exceptions\CraftNotInstalledException
     * @throws \fortrabbit\Copy\Exceptions\PluginNotInstalledException
     * @throws \fortrabbit\Copy\Exceptions\RemoteException
     */
    public function run()
    {
        $this->input->setInteractive(true);
        $app = $this->ask("What's the name of your fortrabbit App?");
        $this->input->setInteractive($this->interactive);

        if (strlen($app) < 3 || strlen($app) > 16) {
            $this->errorBlock("Invalid App name.");

            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (!$region = $this->guessRegion($app)) {
            $this->errorBlock('⚠  App not found');

            return ExitCode::UNSPECIFIED_ERROR;
        }


        $configName = $this->anticipate(
            "What's a good name for the environment of the fortrabbit App? <fg=default>(use arrow keys or type)</>",
            ['production', 'staging', 'stage', 'dev', 'prod'],
            'production'
        );

        // Persist config
        $config = $this->writeDeployConfig($app, $region, Inflector::slug($configName));

        // Perform exec checks
        $this->checkAndWrite("Testing DNS - " . Plugin::REGIONS[$region], true);
        $this->checkAndWrite("Testing rsync", $this->canExecBinary("rsync --help"));

        $mysql = $this->checkAndWrite("Testing mysqldump", $this->canExecBinary("mysqldump --help"));
        $ssh = $this->checkAndWrite("Testing SSH access", $this->canExecBinary("ssh {$config->sshUrl} secrets"));


        if (!$mysql) {
            $this->errorBlock('Mysqldump is required.');
            $this->line("Get Help: " . self::TROUBLE_SHOOTING_MYSQLDUMP_URL);
        }

        if (!$ssh) {
            $this->errorBlock('SSH is required.');
            $this->line("Get Help: " . self::TROUBLE_SHOOTING_SSH_URL);
        }

        if ($mysql != true || $ssh != true) {
            return ExitCode::UNSPECIFIED_ERROR;
        }


        return ($this->setupRemote($config))
            ? ExitCode::OK
            : ExitCode::UNSPECIFIED_ERROR;
    }


    /**
     * @param string $app
     *
     * @return null|string
     */
    protected function guessRegion(string $app)
    {
        if ($records = dns_get_record("$app.frb.io", DNS_CNAME)) {
            return explode('.', $records[0]['target'])[1];
        }

        return null;
    }

    /**
     * @param string $app
     * @param string $region
     * @param string $configName
     *
     * @return \fortrabbit\Copy\Models\DeployConfig
     * @throws \yii\base\Exception
     */
    protected function writeDeployConfig(string $app, string $region, string $configName)
    {
        $config = new DeployConfig();
        $config->app = $app;
        $config->sshUrl = "{$app}@deploy.{$region}.frbit.com";
        $config->gitRemote = "$app/master";
        $config->assetPath = $this->getDefaultRelativeAssetPath();
        $config->setName($configName);
        Plugin::getInstance()->config->setName($configName);

        // Check if file already exist
        if (file_exists(Plugin::getInstance()->config->getFullPathToConfig())) {
            $file = Plugin::getInstance()->config->getConfigFileName();
            if (!$this->confirm("Do you want to overwrite your existing config? ($file)", true)) {
                return $config;
            }
        }

        // Write
        Plugin::getInstance()->config->persist($config);
        Plugin::getInstance()->config->setName($configName);

        // Write .env
        foreach ([Plugin::ENV_DEFAULT_CONFIG => $configName] as $name => $value) {
            \Craft::$app->getConfig()->setDotEnvVar($name, $value);
            putenv("$name=$value");
        }

        return $config;
    }


    protected function checkAndWrite(string $message, bool $success)
    {
        $this->output->write(PHP_EOL . $message);
        $this->output->write($success ? " <info>OK</info>" : " <error>⚠ Error</error>");

        return $success;
    }

    /**
     * @param string $cmd
     *
     * @return bool
     */
    protected function canExecBinary(string $cmd): bool
    {
        $process = Process::fromShellCommandline($cmd, CRAFT_BASE_PATH);
        $exitCode = $process->run();

        return ($exitCode == 0) ? true : false;
    }

    /**
     * @return bool
     * @throws \fortrabbit\Copy\Exceptions\CraftNotInstalledException
     * @throws \fortrabbit\Copy\Exceptions\PluginNotInstalledException
     * @throws \fortrabbit\Copy\Exceptions\RemoteException
     */
    protected function setupRemote(DeployConfig $config)
    {
        $plugin = Plugin::getInstance();
        $app = $plugin->config->get()->app;

        // Is copy deployed aready?
        if ($plugin->ssh->exec("ls vendor/bin/craft-copy-import-db.php | wc -l")) {
            // Yes. Existing setup?
            if (trim($plugin->ssh->getOutput()) == "1") {
                $this->head(
                    "Craft was detected on remote.",
                    "<comment>{$config}</comment> {$config->app}.frb.io"
                );

                $this->section('Do you need a copy of the remote?');
                $this->line("craft copy/db/down" . PHP_EOL);
                $this->line("craft copy/code/down" . PHP_EOL);

                return true;
            }

            // Not installed

            // Try to deploy code
            if ($this->confirm("The plugin is not installed with your App! Do you want to deploy now?", true)) {
                $this->cmdBlock('copy/code/up');
                if (Craft::$app->runAction('copy/code/up', ['interactive' => $this->interactive]) != 0) {
                    // failed
                    return false;
                }
            } else {
                // failed
                return false;
            }
        }

        // Push DB
        $this->cmdBlock('php craft copy/db/up');
        if (Craft::$app->runAction('copy/db/up', ['interactive' => true, 'force' => true]) != 0) {
            return false;
        }

        $this->successBlock("Check it in the browser: https://{$app}.frb.io");

        return true;
    }
}
