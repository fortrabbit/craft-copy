<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Actions;

use Craft;
use fortrabbit\Copy\Helpers\ConsoleOutputHelper;
use ostark\Yii2ArtisanBridge\base\Action;
use yii\console\ExitCode;

class NitroSetupAction extends Action
{
    use ConsoleOutputHelper;

    /**
     * @var string Name of the generated wrapper script
     */
    private $outputFilename = 'nitro-craft';
    private $introMessage = 'Generate a shell script that will allow Craft Copy to work with Nitro

This script functions as a wrapper for running the craft cli inside a Docker container which has the dependencies Craft Copy requires in order to run, while still having read/write access to your code, assets + database in Nitro';
    private  $overwriteMessage = 'An entry point file already exists, do you want to overwrite it?

(you should only need to do this if you have updated Craft Copy or changed your Nitro PHP version)';

    private $successMessage = "

This script should be run from your host machine (not inside of Nitro) and should be used instead of `nitro craft` when running Craft Copy console commands (all other Craft console commands should work too)

e.g. `./nitro-craft copy/info`

For full documentation see the Craft Copy README

https://github.com/fortrabbit/craft-copy/#craft-nitro-support";
    /**
     * Generate a wrapper script to enable Copy to work with Nitro
     */
    public function run() : int
    {
        $targetPath = Craft::getAlias('@root/' . $this->outputFilename);

        $this->head($this->introMessage);

        if (file_exists($targetPath)) {
            if (! $this->confirm(
                $this->overwriteMessage, false
            )) {
                return ExitCode::UNSPECIFIED_ERROR;
            }
        } elseif (! $this->confirm('Do you want to generate the script now?', true)) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $scriptContents = Craft::$app->getView()->renderTemplate(
            'copy/nitro/nitro-craft.sh',
            [ 'phpVersion' => $this->shortPhpVersion() ]
        );

        if (file_put_contents($targetPath, $scriptContents) && chmod($targetPath, 0755)) {
            $this->successBlock("The wrapper script was written to ./" . $this->outputFilename . $this->successMessage);

            return ExitCode::OK;
        }

        $this->errorBlock('Could not write wrapper script to file');

        return ExitCode::UNSPECIFIED_ERROR;
    }

    private function shortPhpVersion() : string {
        return PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;
    }
}
