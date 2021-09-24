<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Exceptions;

class CraftNotInstalledException extends RemoteException
{
    /**
     * @var string
     */
    public $message = 'Craft is not installed on the fortrabbit App.';
}
