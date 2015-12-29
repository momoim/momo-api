<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Provides a driver-based interface for finding, creating, and deleting cached
 * resources. Caches are identified by a unique string. Tagging of caches is
 * also supported, and caches can be found and deleted by id or tag.
 *
 *
 * @package    Cache
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Redis_Core extends Redis
{

    protected static $instances = array();

    // Configuration
    protected $config;

    /**
     * Returns a singleton instance of Cache.
     *
     * @param   string|bool $config configuration
     * @return  Redis_Core
     */
    public static function & instance($config = FALSE)
    {
        if (!isset(Redis_Core::$instances[$config])) {
            // Create a new instance
            Redis_Core::$instances[$config] = new Redis_Core($config);
        }

        return Redis_Core::$instances[$config];
    }

    /**
     * Loads the configured driver and validates it.
     * @param array|string|bool $config custom configuration or config group name
     * @throws Kohana_Exception
     */
    public function __construct($config = FALSE)
    {
        if (is_string($config)) {
            $name = $config;

            // Test the config group name
            if (($config = Kohana::config('redis.' . $config)) === NULL)
                throw new Kohana_Exception('cache.undefined_group', $name);
        }

        if (is_array($config)) {
            // Append the default configuration options
            $config += Kohana::config('redis.default');
        } else {
            // Load the default group
            $config = Kohana::config('redis.default');
        }

        // Cache the config in the object
        $this->config = $config;

        parent::connect($this->config['host'], $this->config['port'], $this->config['timeout']);
        if (!empty($config['auth'])) {
            parent::auth($config['auth']);
        }
        if (!empty($config['db'])) {
            parent::select($config['db']);
        }

        Kohana::log('debug', 'Redis Library initialized');
    }


} // End Cache
