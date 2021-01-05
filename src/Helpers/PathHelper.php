<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Helpers;

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
