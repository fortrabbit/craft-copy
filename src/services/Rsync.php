<?php
/**
 * Copy plugin for Craft CMS 3.x
 *
 * @link      http://www.fortrabbit.com
 * @copyright Copyright (c) 2018 Oliver Stark
 */

namespace fortrabbit\Copy\services;

use Craft;
use craft\base\Component;

/**
 * Rsync Service
 *
 * @author    Oliver Stark
 * @package   Copy
 * @since     1.0.0
 */
class Rsync extends Component
{
    // Public Methods
    // =========================================================================


    /**
     * This function can literally be anything you want, and you can have as many service
     * functions as you want
     *
     * From any other plugin file, call it like this:
     *
     *     Sync::$plugin->rsync->exampleService()
     *
     * @return mixed
     */
    public function exampleService()
    {
        $result = 'something';

        return $result;
    }
}
