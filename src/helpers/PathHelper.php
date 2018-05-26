<?php namespace fortrabbit\Copy\helpers;


trait PathHelper
{
    protected function prepareForRsync($path)
    {
        $path = rtrim(trim($path), '/');

        if (0 === strpos($path, './')) {
            return "$path/";
        }

        return "./$path/";
    }
}
