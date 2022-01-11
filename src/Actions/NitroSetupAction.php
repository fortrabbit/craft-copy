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

    private const WRAPPER_SCRIPT = 'nitro-craft';
    private const INTRO_HEADLINE = 'Generate a shell script that will allow Craft Copy to work with Nitro';
    private const INTRO_MESSAGE = 'This script functions as a wrapper for running the craft cli inside a 
                                  Docker container which has the dependencies Craft Copy requires in order to run, 
                                  while still having read/write access to your code, assets + database in Nitro';

    private const OVERWRITE_MESSAGE = 'An entry point file already exists, do you want to overwrite it?
                                       (you should only need to do this if you have updated Craft Copy or 
                                       changed your Nitro PHP version)';

    private const SUCCESS_MESSAGE = 'This script should be run from your host machine (not inside of Nitro) 
                                     and should be used instead of `nitro craft` when running Craft Copy console commands 
                                     (all other Craft console commands should work too)
                                     e.g. `./nitro-craft copy/info`

                                     For full documentation see the Craft Copy README
                                     https://github.com/fortrabbit/craft-copy/#craft-nitro-support';


    /**
     * Generate a wrapper script to enable Copy to work with Nitro
     */
    public function run(): int
    {
        $targetPath = Craft::getAlias('@root/' . $this->outputFilename);

        $this->head(self::INTRO_HEADLINE);
        $this->block(self::INTRO_MESSAGE);

        if (file_exists($targetPath)) {
            if ($this->confirm(self::OVERWRITE_MESSAGE) === false) {
                return ExitCode::UNSPECIFIED_ERROR;
            }
        } elseif ($this->confirm('Do you want to generate the script now?', true) === false) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if ($this->createScript($targetPath)) {
            $this->successBlock(["The wrapper script was written to ./{$this->outputFilename}", self::SUCCESS_MESSAGE]);
            return ExitCode::OK;
        }

        $this->errorBlock('Could not write wrapper script to file.');

        return ExitCode::UNSPECIFIED_ERROR;
    }

    private function createScript(string $targetPath): bool
    {
        $phpShortVersion = PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;
        $scriptContents = Craft::$app->getView()->renderTemplate(
            'copy/nitro-craft.sh',
            [ 'phpVersion' => $phpShortVersion ]
        );

        if (file_put_contents($targetPath, $scriptContents) !== false && chmod($targetPath, 0755)) {
            return true;
        }

        return false;
    }


}
