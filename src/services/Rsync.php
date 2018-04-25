<?php
/**
 * Copy plugin for Craft CMS 3.x
 *
 * @link      http://www.fortrabbit.com
 * @copyright Copyright (c) 2018 Oliver Stark
 */

namespace fortrabbit\Copy\services;

/**
 * Rsync Service
 *
 * @author    Oliver Stark
 * @package   Copy
 * @since     1.0.0
 */
class Rsync
{

    public $remote;

    protected $rsync;

    protected function __construct(\AFM\Rsync\Rsync $rsync)
    {
        $this->rsync = $rsync;
    }

    public static function remoteFactory($remoteUrl) {

        if (strpos($remoteUrl, '@') === false) {
            throw new \InvalidArgumentException("SSH remote URL must contain a user@host, '$remoteUrl' given.");
        }

        // split
        [$username, $host] = explode('@', $remoteUrl, 2);

        $rsync = new \AFM\Rsync\Rsync();
        $rsync->setSshOptions([
            'host'     => $host,
            'username' => $username
        ]);

        return new self($rsync);
    }

    /**
     * Rsync config
     *
     * @param string $key
     * @param mixed $value
     */
    public function setOption(string $key, $value)
    {
        $setter = 'set' . ucfirst($key);
        $this->rsync->$setter($value);
    }

    /**
     * @param string $originDir
     * @param string $targetDir
     */
    public function syncFromRemote($originDir, $targetDir)
    {
        $this->rsync->setRemoteOrigin(true);
        $this->rsync->sync($originDir, $targetDir);
    }


    /**
     * @param string $originDir
     * @param string $targetDir
     */
    public function syncToRemote($originDir, $targetDir)
    {
        $this->rsync->setRemoteOrigin(false);
        $this->rsync->sync($originDir, $targetDir);
    }
}
