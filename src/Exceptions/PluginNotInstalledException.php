<?php

namespace fortrabbit\Copy\Exceptions;

class PluginNotInstalledException extends RemoteException
{
    /**
     * @var string
     */
    public $message = 'The plugin is not installed in the remote environment.';
}
