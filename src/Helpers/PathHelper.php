<?php

namespace fortrabbit\Copy\Helpers;

use craft\helpers\Path;
use yii\helpers\StringHelper;

trait PathHelper
{
    protected function prepareForRsync($path)
    {
        $path = rtrim(trim($path), '/');

        if (strpos($path, './') === 0) {
            return "$path/";
        }

        return "./$path/";
    }
}
