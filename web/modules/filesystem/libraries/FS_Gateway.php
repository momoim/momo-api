<?php defined('SYSPATH') or die('No direct access allowed.');
require_once MODPATH . 'filesystem/global.php';

interface FS_Gateway_Core
{
}

class FS_Container
{
    public static function instantiate($class, $args = array())
    {
        if (empty($args))
            return new $class();
        else {
            $ref = new ReflectionClass($class);
            return $ref->newInstanceArgs($args);
        }
    }
}
