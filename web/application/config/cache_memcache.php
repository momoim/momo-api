<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * @package  Cache:Memcache
 *
 * memcache server configuration.
 */
//根据环境配置
if(IN_PRODUCTION === TRUE) {
    $config['servers'] = array
    (
    	array
    	(
    		'host' => '10.1.242.124',
    		'port' => 11211,
    		'persistent' => FALSE,
    	),
	array
    	(
    		'host' => '10.1.242.125',
    		'port' => 11211,
    		'persistent' => FALSE,
    	)
    );

	//联系人使用独立缓存
	$config['contact'] = array
	(
		array
		(
			'host' => '10.1.242.124',
			'port' => 11212,
			'persistent' => FALSE,
		),
		array
		(
			'host' => '10.1.242.125',
			'port' => 11212,
			'persistent' => FALSE,
		)
	);

} else {
    $config['servers'] = array
    (
    	array
    	(
    		'host' => 'memcached',
    		'port' => 11211,
    		'persistent' => FALSE,
    	)
    );

	//联系人使用独立缓存
	$config['contact'] = array
	(
		array
		(
			'host' => 'memcached',
			'port' => 11211,
			'persistent' => FALSE,
		)
	);
}


/**
 * Enable cache data compression.
 */
$config['compression'] = TRUE;
