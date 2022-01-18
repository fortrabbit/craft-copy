<?php

declare(strict_types=1);

namespace fortrabbit\Copy\Exceptions;

use Throwable;
use yii\base\Exception;

class RemoteException extends Exception
{
    public function __construct($message = '', $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($this->cleanMessage($message), $code, $previous);
    }

    protected function cleanMessage(string $message): string
    {
        // remove double new lines
        $message = preg_replace("/[\r\n]+/", "\n", $message);

        // strip after ∙ƒ
        if ($endPos = strpos($message, '∙ƒ')) {
            $message = substr($message, 0, $endPos);
        }

        return trim($message);
    }
}
