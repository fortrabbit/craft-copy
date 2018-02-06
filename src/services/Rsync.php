<?php
/**
 * sync plugin for Craft CMS 3.x
 *
 * ss
 *
 * @link      http://www.fortrabbit.com
 * @copyright Copyright (c) 2018 Oliver Stark
 */

namespace fortrabbit\Sync\services;

use Craft;
use craft\base\Component;

/**
 * Rsync Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Oliver Stark
 * @package   Sync
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
