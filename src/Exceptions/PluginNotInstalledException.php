<?php

/**
 * Created by PhpStorm.
 * User: os
 * Date: 07.04.18
 * Time: 22:46
 */

namespace fortrabbit\Copy\Exceptions;

class PluginNotInstalledException extends RemoteException
{
    public $message = 'The plugin is not installed in the remote environment.';
}
