<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Exceptions;

class PluginNotInstalledException extends RemoteException
{
    /**
     * @var string
     */
    public $message = 'The plugin is not installed on the fortrabbit App.';
}
