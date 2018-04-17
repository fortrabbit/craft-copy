<?php

namespace fortrabbit\Copy\commands;

use Craft;
use craft\helpers\Console;
use fortrabbit\Copy\ArtisanConsoleBridge\base\Action;
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
    const ENV_NAME_APP = 'APP_NAME';
    const ENV_NAME_SSH_REMOTE = 'APP_SSH_REMOTE';
    const REGIONS = [
        'us1' => 'US (AWS US-EAST-1 / Virginia)',
        'eu2' => 'EU (AWS EU-WEST-1 / Ireland)'
    ];

    protected $app;
    protected $sshUrl;

    /**
     * @var bool Verbose output
     */
    public $verbose = false;


    public function __construct($id, \yii\base\Controller $controller, array $config = [])
    {
        parent::__construct($id, $controller, $config);
    }

    public function __destruct()
    {
        // TODO: Implement __destruct() method.
    }

    /**
     * Setup your App
     *
     * @return bool
     */
    public function run()
    {

        $this->block('Setup', 'header');

        // Ask for App name
        $this->controller->prompt(PHP_EOL . 'What\'s the name of your App?', ['error' => '', 'validator' => function ($app) {

            if (strlen($app) < 3 || strlen($app) > 16) {
                return false;
            }

            $this->info(PHP_EOL . "Performing DNS check for '$app' ", false);

            if (!$region = $this->guessRegion($app)) {
                $this->error('⚠  App not found');
                return false;
            }


            if (in_array($region, array_keys(self::REGIONS))) {

                $this->write('OK');
                $this->write(PHP_EOL . "<info>Region detected </info>" . self::REGIONS[$region], true);

                $this->app    = $app;
                $this->sshUrl = "{$this->app}@deploy.{$region}.frbit.com";

                return true;
            }

            return false;
        }]);


        // Perform exec checks
        $this->info(PHP_EOL . "Testing mysqldump ", false);
        $this->write($mysqldump = $this->canExecBinary("mysqldump --help") ? "OK" : "<error>⚠ Error</error>");

        $this->info(PHP_EOL . "Testing rsync ", false);
        $this->write($rsync = $this->canExecBinary("rsync --help") ? "OK" : "<error>⚠ Error</error>");

        $this->info(PHP_EOL . "Testing ssh access ", false);
        $this->write($ssh = $this->canExecBinary("ssh {$this->app}@deploy.{$this->region}.frbit.com secrets") ? "OK" : "<error>⚠ Error</error>");
        $this->write(PHP_EOL);


        // Write .env
        if ($this->controller->confirm(PHP_EOL . "Update .env file?", true)) {

            try {
                $this->writeDotEnv();
            } catch (\Exception $e) {
                $this->controller->stderr($e->getMessage() . PHP_EOL, Console::FG_RED);
            }
        }


        // Show summary
        if (getenv(self::ENV_NAME_SSH_REMOTE)) {

            $this->controller->stdout(PHP_EOL . "Now you can run these commands:" . PHP_EOL . PHP_EOL, Console::FG_GREY);

            $this->controller->stdout("./craft copy/db/up ", Console::FG_BLUE);
            $this->controller->stdout("to dump your local db" . PHP_EOL);

            $this->controller->stdout("./craft copy/db/down ", Console::FG_BLUE);
            $this->controller->stdout("to import your dump to the remote db" . PHP_EOL);

            $this->controller->stdout("./craft copy/assets/up ", Console::FG_BLUE);
            $this->controller->stdout("to rsync your assets with the remote" . PHP_EOL);

        }

        $this->controller->stdout(PHP_EOL);

        if (!$this->pleaseConfirm("Do you want initialize the plugin on the remote?")) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return $this->setupRemote();

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
     * @throws \yii\base\Exception
     */
    protected function writeDotEnv()
    {
        $vars = [
            self::ENV_NAME_APP        => $this->app,
            self::ENV_NAME_SSH_REMOTE => $this->sshUrl
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

        if ($plugin->ssh->exec('php vendor/bin/craft-copy-installer.php')) {
            $this->write($plugin->ssh->getOutput());
        };

        Craft::$app->runAction('copy/db/up', ['force' => false]);

        $this->write(PHP_EOL);
        $this->write("Check it the browser: http://{$this->app}.frb.io", true);
        $this->write(PHP_EOL);

        return true;

    }

}
