<?php

namespace fortrabbit\Copy\Helpers;

use craft\helpers\Path;
use yii\helpers\StringHelper;

trait PathHelper
{

    /**
     * tries to get the path
     * from Alias: @assetBasePath
     * from Env var: ASSETS_BASE_PATH
     * defaults to web/assets
     */
    protected function getDefaultRelativeAssetPath(): string
    {
        // might be ./assets
        // or web/assets
        // or /full/path/web/assets
        $assetBasePath = \Craft::alias('@assetBasePath') ?: getenv('ASSET_BASE_PATH');

        if ($assetBasePath) {
            $lastPart = array_slice(explode('/', $assetBasePath), -1)[0];

            foreach (['web', 'public', 'public_html', 'html'] as $path) {
                if (is_dir(\Craft::getAlias("@root/{$path}/{$lastPart}"))) {
                    return "$path/$lastPart";
                }
            }
        }

        return "web/assets";
    }


    protected function prepareForRsync($path)
    {
        $path = rtrim(trim($path), '/');

        if (strpos($path, './') === 0) {
            return "$path/";
        }

        return "./$path/";
    }
}
