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

        if (!$assetBasePath) {
           return "web/assets";
        }

        $lastPart = array_slice(explode('/', $assetBasePath), -1)[0];

        return "web/$lastPart";

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
