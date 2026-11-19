<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Exceptions;

class CraftNotInstalledException extends RemoteException
{
    /**
     * @var string
     */
    public $message = 'Craft was not found on the fortrabbit App. If Craft is in a subfolder of your Git repository, set craft_path in config/craft-copy/fortrabbit.{stage}.yaml.';
}
