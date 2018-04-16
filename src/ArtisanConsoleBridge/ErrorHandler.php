<?php
/**
 * Created by PhpStorm.
 * User: os
 * Date: 12.04.18
 * Time: 09:16
 */

namespace fortrabbit\Copy\ArtisanConsoleBridge;


class ErrorHandler extends \yii\console\ErrorHandler
{

    /**
     * TODO: Tweeks required
     *
     * @param \Exception $exception
     */
    protected function renderException($exception)
    {
        parent::renderException($exception);
    }
}
