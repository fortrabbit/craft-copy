<?php

namespace fortrabbit\Copy\models;

class StageConfig
{
    public $app;

    public $sshRemoteUrl;

    public $gitRemoteName;


    /**
     * StageConfig constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $classAttributes = array_keys(get_class_vars(self::class));

        foreach ($attributes as $key => $value) {
            if (!in_array($key, $classAttributes)) {
                $whitelist = implode(', ', $classAttributes);
                throw new \InvalidArgumentException("Unsupported 'stack' config key: '$key'. Allowed keys are: $whitelist");
            }
            $this->{$key} = $value;
        }
    }
}
