<?php

namespace fortrabbit\Copy\Actions;

use Craft;
use fortrabbit\Copy\Helpers\ConsoleOutputHelper;
use fortrabbit\Copy\Helpers\PathHelper;
use fortrabbit\Copy\Models\StageConfig;
use fortrabbit\Copy\Plugin;
use ostark\Yii2ArtisanBridge\base\Action;
use Symfony\Component\Process\Process;
use yii\console\ExitCode;
use yii\helpers\Inflector;

class SetupAction extends Action
{
    use ConsoleOutputHelper;
    use PathHelper;

    public const TROUBLE_SHOOTING_MYSQLDUMP_URL = "https://github.com/fortrabbit/craft-copy#the-mysqldump-command-does-not-exist";
    public const TROUBLE_SHOOTING_SSH_URL = "https://help.fortrabbit.com/ssh-keys";


    /**
     * @var bool Verbose output
     */
    public $verbose = false;

    /**
     * Connect local dev with fortrabbit App
     *
     * @return int
     * @throws \fortrabbit\Copy\Exceptions\CraftNotInstalledException
     * @throws \fortrabbit\Copy\Exceptions\PluginNotInstalledException
     * @throws \fortrabbit\Copy\Exceptions\RemoteException
     * @throws \yii\base\Exception
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


        $stageName = $this->anticipate(
            "What's a good name for the stage of the fortrabbit App? <fg=default>(use arrow keys or type)</>",
            [$app, "$app-prod", "$app-dev", "prod", "production", "staging", "dev"],
            "$app-prod"
        );

        // Persist config
        $config = $this->writeStageConfig($app, $region, Inflector::slug($stageName));

        // Perform exec checks
        $this->checkAndWrite("Testing DNS - " . Plugin::REGIONS[$region], true);
        $this->checkAndWrite("Testing rsync", $this->canExecBinary("rsync --help"));

        $mysql = $this->checkAndWrite("Testing mysqldump", $this->canExecBinary("mysqldump --help"));
        $ssh = $this->checkAndWrite("Testing SSH access", $this->canExecBinary("ssh {$config->sshUrl} secrets"));


        if (!$mysql) {
            $this->errorBlock('Mysqldump is required. Please install it with your local development environment.');
            $this->line("Get Help: " . self::TROUBLE_SHOOTING_MYSQLDUMP_URL);
        }

        if (!$ssh) {
            $this->errorBlock('SSH key authentication is required. Please add your SSH key to your fortrabbit Account first.');
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
     * @param string $stageName
     *
     * @return \fortrabbit\Copy\Models\StageConfig
     * @throws \yii\base\Exception
     */
    protected function writeStageConfig(string $app, string $region, string $stageName)
    {
        $config = new StageConfig();
        $config->app = $app;
        $config->sshUrl = "{$app}@deploy.{$region}.frbit.com";
        $config->gitRemote = "$app/master";
        $config->setName($stageName);
        Plugin::getInstance()->stage->setName($stageName);

        // Check if file already exist
        if (file_exists(Plugin::getInstance()->stage->getFullPathToConfig())) {
            $file = Plugin::getInstance()->stage->getConfigFileName();
            if (!$this->confirm("Do you want to overwrite your existing config? ($file)", true)) {
                return $config;
            }
        }

        // Write
        Plugin::getInstance()->stage->persist($config);
        Plugin::getInstance()->stage->setName($stageName);

        // Write .env
        foreach ([Plugin::ENV_DEFAULT_STAGE => $stageName] as $name => $value) {
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
    protected function setupRemote(StageConfig $config)
    {
        $plugin = Plugin::getInstance();
        $app = $plugin->stage->get()->app;

        // Is copy deployed aready?
        if ($plugin->ssh->exec("ls vendor/bin/craft-copy-import-db.php | wc -l")) {
            // Yes. Existing setup?
            if (trim($plugin->ssh->getOutput()) == "1") {
                $this->head(
                    "Craft was detected on the fortrabbit App.",
                    "<comment>{$config}</comment> {$config->app}.frb.io"
                );

                $this->section('Do you need a copy of the fortrabbit App?');
                $this->line("craft copy/db/down" . PHP_EOL);
                $this->line("craft copy/code/down" . PHP_EOL);

                return true;
            }

            // Not installed

            // Try to deploy code
            if ($this->confirm("Craft Copy is not installed with your fortrabbit App, maybe Craft is not even installed.  Do you want to deploy code and database now?", true)) {
                $this->cmdBlock('copy/code/up');
                if (Craft::$app->runAction('copy/code/up', ['interactive' => $this->interactive]) !== 0) {
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
        if (Craft::$app->runAction('copy/db/up', ['interactive' => true, 'force' => true]) !== 0) {
            return false;
        }

        $this->successBlock("Check it in your browser: https://{$app}.frb.io");

        return true;
    }
}
