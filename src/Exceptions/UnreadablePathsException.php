<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Exceptions;

use yii\base\Exception;

class UnreadablePathsException extends Exception
{
	/**
     * @var string
     */
    public $message = 'Could not read paths from app';
}
