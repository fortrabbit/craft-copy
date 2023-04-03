<?php

namespace fortrabbit\Copy\Helpers;

use Craft;
use fortrabbit\Copy\Plugin;

trait MysqlConfigFile
{
    public function assureMyCnf(): bool
    {
        $mycnfDest = Craft::getAlias('@root') . '/.my.cnf';
        $mycnfSrc = Plugin::PLUGIN_ROOT_PATH . '/.my.cnf.example';

        // Create
        if (! file_exists($mycnfDest)) {
            return copy($mycnfSrc, $mycnfDest);
        }

        // Update
        if (file_get_contents($mycnfSrc) !== file_get_contents($mycnfDest)) {
            return copy($mycnfSrc, $mycnfDest);
        }

        return true;
    }
}
