<?php
/**
 * Copy plugin for Craft CMS 3.x
 *
 * @link      https://www.fortrabbit.com/
 * @copyright Copyright (c) 2018 Oliver Stark
 */

namespace fortrabbit\Copy\Services;

/**
 * Rsync Service
 *
 * @author    Oliver Stark
 * @package   Copy
 * @since     1.0.0
 */
class Rsync
{
    public $remoteUrl;

    protected $rsync;

    /**
     * Rsync constructor.
     *
     * @param \AFM\Rsync\Rsync $rsync
     * @param string           $remoteUrl
     */
    protected function __construct(\AFM\Rsync\Rsync $rsync, string $remoteUrl = null)
    {
        $this->rsync     = $rsync;
        $this->remoteUrl = $remoteUrl;
    }

    public static function remoteFactory($remoteUrl)
    {
        if (strpos($remoteUrl, '@') === false) {
            throw new \InvalidArgumentException("SSH remote URL must contain a user@host, '$remoteUrl' given.");
        }

        // split
        [$username, $host] = explode('@', $remoteUrl, 2);

        $rsync = new \AFM\Rsync\Rsync();
        $rsync->setVerbose(true);
        $rsync->setSshOptions([
            'host'     => $host,
            'username' => $username
        ]);

        return new self($rsync, $remoteUrl);
    }

    /**
     * Rsync config
     *
     * @param string $key
     * @param mixed  $value
     */
    public function setOption(string $key, $value)
    {
        $setter = 'set' . ucfirst($key);
        $this->rsync->$setter($value);
    }

    /**
     * @param string $dir
     */
    public function sync($dir)
    {
        $this->rsync->sync($dir, $dir);
    }

    /**
     * @param string $dir
     *
     * @return string
     */
    public function getCommand(string $dir): string
    {
        return $this->rsync->getCommand($dir, $dir)->getCommand();
    }
}
