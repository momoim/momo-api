<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * @package  Core
 *
 * Sets the default route to "welcome"
 */
$config['_default'] = 'welcome';

//重写资源
//$config['([a-z_A-Z]+)\.(json|xml)'] = '$1/index';

//重写资源、动作
//$config['([a-z_A-Z]+)\/([a-z_A-Z]+)\.(json|xml)'] = '$1/$2';

//重写资源、动作、:id
//$config['([a-z_A-Z]+)\/([a-z_A-Z]+)\/([a-z_A-Z0-9]+)\.(json|xml)'] = '$1/$2/$3';

$config['([a-z_A-Z]+)\/([0-9]+)\.(json|xml)'] = '$1/index/$2';
$config['(.+)\.(json|xml)'] = '$1';