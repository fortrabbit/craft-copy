<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Helpers;

trait PathHelper
{
    protected function prepareForRsync($path)
    {
        $path = rtrim(trim($path), '/');

        if (str_starts_with($path, './')) {
            return "{$path}/";
        }

        return "./{$path}/";
    }
}
