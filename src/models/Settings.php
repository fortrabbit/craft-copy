<?php

namespace fortrabbit\Copy\models;

use craft\base\Model;

/**
 * Craft Copy Settings Model
 *
 * This is a model used to define the plugin's settings.
 *
 * Models are containers for data. Just about every time information is passed
 * between services, controllers, and templates in Craft, it’s passed via a model.
 *
 * https://craftcms.com/docs/plugins/models
 *
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * Some field model attribute
     *
     * @var string
     */
    public $sshUploadCommand;


    /**
     * Some field model attribute
     *
     * @var string
     */
    public $sshDownloadCommand;


    /**
     * Some field model attribute
     *
     * @var \fortrabbit\Copy\models\StageConfig[]
     */
    public $stages = [];


    /**
     * @param array $values
     * @param bool  $safeOnly
     *
     * @see Model::setAttributes()
     */
    public function setAttributes($values, $safeOnly = true)
    {
        // Prepare stages
        if (isset($values['stages'])) {
            foreach ($values['stages'] as $key => $config) {
                if (is_array($config)) {
                    $config = new StageConfig($config);
                }

                if ($config instanceof StageConfig) {
                    $config->app            = $key;
                    $values['stages'][$key] = $config;
                }
            }
        }

        parent::setAttributes($values, $safeOnly);
    }

    /**
     * @param string $key
     *
     * @return \fortrabbit\Copy\models\StageConfig
     */
    public function getStageConfig(string $key): StageConfig
    {
        if (!array_key_exists($key, $this->stages)) {
            $whitelist = implode(PHP_EOL, array_keys($this->stages));
            throw new \InvalidArgumentException("Stage '$key' is not configured. Possible values are:" . PHP_EOL . $whitelist);
        }

        return $this->stages[$key];
    }
}
