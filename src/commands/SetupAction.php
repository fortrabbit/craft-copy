<?php

namespace fortrabbit\Copy\commands;

use Craft;
use ostark\Yii2ArtisanBridge\base\Action;
use fortrabbit\Copy\Plugin;
use Symfony\Component\Process\Process;
use yii\console\ExitCode;

/**
 * Class SetupAction
 *
 * @package fortrabbit\Copy\commands
 */
class SetupAction extends Action
{

    /**
     * @var bool Verbose output
     */
    public $verbose = false;

    protected $app;
    protected $sshUrl;

    /**
     * Setup your App
     *
     * @return bool
     */
    public function run()
    {
        $this->app = $this->ask("What's the name of your App?");

        if (strlen($this->app) < 3 || strlen($this->app) > 16) {
            $this->errorBlock("Invalid App name.");

            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (!$region = $this->guessRegion($this->app)) {
            $this->errorBlock('⚠  App not found');

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->sshUrl = "{$this->app}@deploy.{$region}.frbit.com";

        // Perform exec checks
        $dns   = $this->checkAndWrite("Testing DNS - " . Plugin::REGIONS[$region], true);
        $mysql = $this->checkAndWrite("Testing mysqldump", $this->canExecBinary("mysqldump --help"));
        $rsync = $this->checkAndWrite("Testing rsync", $this->canExecBinary("rsync --help"));
        $ssh   = $this->checkAndWrite("Testing ssh access", $this->canExecBinary("ssh {$this->sshUrl} secrets"));

        if ($this->confirm("Update .env file?")) {
            try {
                $this->writeDotEnv();
            } catch (\Exception $e) {
                $this->errorBlock($e->getMessage());
            }
        }

        getenv(Plugin::ENV_NAME_SSH_REMOTE);

        if ($mysql && $ssh) {

            if (!$this->confirm("Do you want initialize the plugin on the remote?")) {
                $this->noteBlock('Abort');
                return ExitCode::UNSPECIFIED_ERROR;
            }

            return $this->setupRemote();
        }




    }

    /**
     * @param $app
     *
     * @return string|null
     */
    protected function guessRegion($app)
    {
        if ($records = dns_get_record("$app.frb.io", DNS_CNAME)) {
            return explode('.', $records[0]['target'])[1];
        }

        return null;
    }

    /**
     * @param $app
     *
     */
    protected function canExecBinary($cmd)
    {
        $proc     = new Process($cmd);
        $exitCode = $proc->run();

        return ($exitCode == 0) ? true : false;
    }


    /**
     *
     * @throws \yii\base\Exception
     */
    protected function writeDotEnv()
    {
        $vars = [
            Plugin::ENV_NAME_APP        => $this->app,
            Plugin::ENV_NAME_SSH_REMOTE => $this->sshUrl
        ];

        $config = \Craft::$app->getConfig();

        foreach ($vars as $name => $value) {
            $config->setDotEnvVar($name, $value);
            putenv("$name=$value");
        }
    }

    protected function setupRemote()
    {
        $plugin = Plugin::getInstance();
        $plugin->ssh->remote = $this->sshUrl;

        if ($plugin->ssh->exec('php vendor/bin/craft-copy-installer.php')) {
            $this->output->write($plugin->ssh->getOutput());
        };

        Craft::$app->runAction('copy/db/up');

        $this->commentBlock("Check it the browser: http://{$this->app}.frb.io");


        return true;

    }

    protected function checkAndWrite($message, $success)
    {
        $this->output->write(PHP_EOL . $message);
        $this->output->write($success ? " <info>OK</info>" : " <error>⚠ Error</error>");

        return $success;
    }

}
