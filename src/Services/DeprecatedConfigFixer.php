<?php

namespace fortrabbit\Copy\Services;

use craft\elements\actions\RenameFile;
use craft\helpers\FileHelper;
use fortrabbit\Copy\Plugin;
use ostark\Yii2ArtisanBridge\base\Action;

class DeprecatedConfigFixer
{
    public const ENV_DEFAULT_CONFIG = "DEFAULT_CONFIG";

    private $action;
    private $stage;

    public function __construct(Action $action, StageConfigAccess $stage)
    {
        $this->action = $action;
        $this->stage = $stage;
    }

    public function showWarning()
    {
        $this->action->errorBlock(
            sprintf(
                "The environment variable '%s' is not supported anymore.",
                self::ENV_DEFAULT_CONFIG
            )
        );

        $this->action->errorBlock(
            "The location of the generated config files changed from /config to /config/craft-copy."
        );
    }

    public static function hasDeprecatedConfig(): bool
    {
        if (getenv(self::ENV_DEFAULT_CONFIG)) {
            return true;
        }

        if (count(glob(\Craft::$app->getPath()->getConfigPath() . '/fortrabbit.*'))) {
           return true;
        }

        return false;
    }

    public function askAndRun()
    {
        if ($this->action->confirm("Should we fix this for you?", true)) {
            $this->rewriteDotEnv();
            $this->moveConfigFiles();
            $this->action->successBlock("Please run the previous command again.");
        }
    }

    private function rewriteDotEnv()
    {
        $dotEnvFile = \Craft::getAlias('@root/.env');
        $dotEnvContent = file_get_contents($dotEnvFile);
        $dotEnvContent = str_replace(
            self::ENV_DEFAULT_CONFIG,
            Plugin::ENV_DEFAULT_STAGE,
            $dotEnvContent
        );

        file_put_contents($dotEnvFile, $dotEnvContent);
    }


    private function moveConfigFiles()
    {
        $oldPath = str_replace(StageConfigAccess::CONFIG_SUBFOLDER, '', $this->stage->getConfigPath());
        $globPattern = str_replace('{name}', '*', StageConfigAccess::FILE_NAME_TEMPLATE);

        // get config files
        $files = glob($oldPath . $globPattern);

        FileHelper::createDirectory($this->stage->getConfigPath());

        foreach ($files as $source) {
            $target = str_replace(
                $oldPath,
                $this->stage->getConfigPath() . DIRECTORY_SEPARATOR,
                $source
            );
            rename($source, $target);
        }
    }


}
