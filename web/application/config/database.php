<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * @package  Database
 *
 * Database connection settings, defined as arrays, or "groups". If no group
 * name is used when loading the database library, the group named "default"
 * will be used.
 *
 * Each group can be connected to independently, and multiple groups can be
 * connected at once.
 *
 * Group Options:
 *  benchmark     - Enable or disable database benchmarking
 *  persistent    - Enable or disable a persistent connection
 *  connection    - Array of connection specific parameters; alternatively,
 *                  you can use a DSN though it is not as fast and certain
 *                  characters could create problems (like an '@' character
 *                  in a password):
 *                  'connection'    => 'mysql://dbuser:secret@localhost/kohana'
 *  character_set - Database character set
 *  table_prefix  - Database table prefix
 *  object        - Enable or disable object results
 *  cache         - Enable or disable query caching
 *	escape        - Enable automatic query builder escaping
 */
//根据环境配置
if(IN_PRODUCTION === TRUE) {
    $config['default'] = array
    (
    	'benchmark'     => TRUE,
    	'persistent'    => FALSE,
    	'connection'    => array
    	(
    		'type'     => 'mysql',
    		'user'     => 'momo_v3',
    		'pass'     => 'NXTdc67QCfqU8ua6',
    		'host'     => '10.1.242.206:3306',
    		'port'     => FALSE,
    		'socket'   => FALSE,
    		'database' => 'momo_v3'
    	),
    	'character_set' => 'utf8',
    	'table_prefix'  => '',
    	'object'        => TRUE,
    	'cache'         => FALSE,
    	'escape'        => TRUE
    );

	$config['slave_0'] = array
	(
		'benchmark'     => TRUE,
		'persistent'    => FALSE,
		'connection'    => array
		(
			'type'     => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.242.127:3306',
			'socket'   => FALSE,
			'database' => 'momo_v3'
		),
		'character_set' => 'utf8',
		'table_prefix'  => '',
		'object'        => TRUE,
		'cache'         => FALSE,
		'escape'        => TRUE
	);

	$config['slave_1'] = array
	(
		'benchmark'     => TRUE,
		'persistent'    => FALSE,
		'connection'    => array
		(
			'type'     => 'mysql',
    		'user'     => 'momo',
    		'pass'     => 'dC4DqVCQKAZScY4M',
    		'host'     => '10.1.242.128:3306',
    		'socket'   => FALSE,
    		'database' => 'momo_v3'
		),
		'character_set' => 'utf8',
		'table_prefix'  => '',
		'object'        => TRUE,
		'cache'         => FALSE,
		'escape'        => TRUE
	);
	
	$config['contact'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysqli',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.242.206',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_contact'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);


	$config['contact_0'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysqli',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.146',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_contact_0'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['contact_1'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysqli',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.147',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_contact_1'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['contact_2'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysqli',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.148',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_contact_2'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['contact_3'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysqli',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.149',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_contact_3'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['contact_4'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysqli',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.146',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_contact_4'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['contact_5'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysqli',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.147',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_contact_5'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['contact_6'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysqli',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.148',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_contact_6'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['contact_7'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysqli',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.149',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_contact_7'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);
	
		$config['contact_slave_0'] = array(
		'benchmark' => FALSE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysqli',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.147',
			'port'     => 3308,
			'socket'   => FALSE,
			'database' => 'momo_contact_0'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['contact_slave_1'] = array(
		'benchmark' => FALSE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysqli',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.146',
			'port'     => 3308,
			'socket'   => FALSE,
			'database' => 'momo_contact_1'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['contact_slave_2'] = array(
		'benchmark' => FALSE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysqli',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.149',
			'port'     => 3308,
			'socket'   => FALSE,
			'database' => 'momo_contact_2'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['contact_slave_3'] = array(
		'benchmark' => FALSE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysqli',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.148',
			'port'     => 3308,
			'socket'   => FALSE,
			'database' => 'momo_contact_3'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['contact_slave_4'] = array(
		'benchmark' => FALSE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysqli',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.147',
			'port'     => 3308,
			'socket'   => FALSE,
			'database' => 'momo_contact_4'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['contact_slave_5'] = array(
		'benchmark' => FALSE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysqli',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.146',
			'port'     => 3308,
			'socket'   => FALSE,
			'database' => 'momo_contact_5'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['contact_slave_6'] = array(
		'benchmark' => FALSE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysqli',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.149',
			'port'     => 3308,
			'socket'   => FALSE,
			'database' => 'momo_contact_6'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['contact_slave_7'] = array(
		'benchmark' => FALSE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysqli',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.148',
			'port'     => 3308,
			'socket'   => FALSE,
			'database' => 'momo_contact_7'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	//momo api数据分库配置
	//主库
	$config['momo_api_0'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.146',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_api_0'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['momo_api_1'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.147',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_api_1'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['momo_api_2'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.148',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_api_2'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['momo_api_3'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.149',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_api_3'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['momo_api_4'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.146',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_api_4'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['momo_api_5'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.147',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_api_5'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['momo_api_6'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.148',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_api_6'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['momo_api_7'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.149',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_api_7'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	//从库
	$config['momo_api_slave_0'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.147',
			'port'     => 3308,
			'socket'   => FALSE,
			'database' => 'momo_api_0'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['momo_api_slave_1'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.146',
			'port'     => 3308,
			'socket'   => FALSE,
			'database' => 'momo_api_1'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['momo_api_slave_2'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.149',
			'port'     => 3308,
			'socket'   => FALSE,
			'database' => 'momo_api_2'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['momo_api_slave_3'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.148',
			'port'     => 3308,
			'socket'   => FALSE,
			'database' => 'momo_api_3'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['momo_api_slave_4'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.147',
			'port'     => 3308,
			'socket'   => FALSE,
			'database' => 'momo_api_4'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['momo_api_slave_5'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.146',
			'port'     => 3308,
			'socket'   => FALSE,
			'database' => 'momo_api_5'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['momo_api_slave_6'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.149',
			'port'     => 3308,
			'socket'   => FALSE,
			'database' => 'momo_api_6'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['momo_api_slave_7'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.148',
			'port'     => 3308,
			'socket'   => FALSE,
			'database' => 'momo_api_7'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);
	
    //短信数据库配置
    $config['sms_0'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.146',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_sms_0'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['sms_1'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.147',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_sms_1'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['sms_2'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.148',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_sms_2'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['sms_3'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.149',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_sms_3'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['sms_4'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.146',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_sms_4'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['sms_5'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.147',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_sms_5'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['sms_6'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.148',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_sms_6'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['sms_7'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.149',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_sms_7'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);


    //通话记录数据库配置
    $config['call_records_0'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.146',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_call_records_0'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['call_records_1'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.147',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_call_records_1'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['call_records_2'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.148',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_call_records_2'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['call_records_3'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.149',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_call_records_3'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['call_records_4'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.146',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_call_records_4'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['call_records_5'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.147',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_call_records_5'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['call_records_6'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.148',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_call_records_6'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

	$config['call_records_7'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE,
		'connection' => array(
			'type' => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.191.149',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_call_records_7'
		),
		'character_set' => 'utf8',
		'table_prefix' => '',
		'object' => TRUE,
		'cache' => FALSE,
		'escape' => TRUE
	);

    //相册数据库配置
    $config['sns_album'] = array
    (
    	'benchmark'     => TRUE,
    	'persistent'    => FALSE,
    	'connection'    => array
    	(
    		'type'     => 'mysql',
    		'user'     => 'momo_v3',
    		'pass'     => 'NXTdc67QCfqU8ua6',
    		'host'     => '10.1.242.206:3306',
    		'port'     => FALSE,
    		'socket'   => FALSE,
    		'database' => 'momo_v3'
    	),
    	'character_set' => 'utf8',
    	'table_prefix'  => '',
    	'object'        => TRUE,
    	'cache'         => FALSE,
    	'escape'        => TRUE
    );

    $config['momo_im'] = array
    (
    	'benchmark'     => TRUE,
    	'persistent'    => FALSE,
    	'connection'    => array
    	(
    		'type'     => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
    		'host'     => '10.1.242.206:3306',
    		'port'     => FALSE,
    		'socket'   => FALSE,
    		'database' => 'momo_v3'
    	),
    	'character_set' => 'utf8',
    	'table_prefix'  => '',
    	'object'        => TRUE,
    	'cache'         => FALSE,
    	'escape'        => TRUE
    );

	//短信
	$config['sms'] = array
	(
		'benchmark'     => TRUE,
		'persistent'    => FALSE,
		'connection'    => array
		(
			'type'     => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.242.206:3306',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_sms'
		),
		'character_set' => 'utf8',
		'table_prefix'  => '',
		'object'        => TRUE,
		'cache'         => FALSE,
		'escape'        => TRUE
	);

	//通话记录
	$config['call_records'] = array
	(
		'benchmark'     => TRUE,
		'persistent'    => FALSE,
		'connection'    => array
		(
			'type'     => 'mysql',
			'user'     => 'momo',
			'pass'     => 'dC4DqVCQKAZScY4M',
			'host'     => '10.1.242.206:3306',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_call_records'
		),
		'character_set' => 'utf8',
		'table_prefix'  => '',
		'object'        => TRUE,
		'cache'         => FALSE,
		'escape'        => TRUE
	);
} else {

    $config['default'] = array
    (
    	'benchmark'     => TRUE,
    	'persistent'    => FALSE,
    	'connection'    => array
    	(
    		'type'     => 'mysqli',
    		'user'     => 'root',
    		'pass'     => '123456',
    		'host'     => 'mysql',
    		'port'     => FALSE,
    		'socket'   => FALSE,
    		'database' => 'momo_v3'
    	),
    	'character_set' => 'utf8',
    	'table_prefix'  => '',
    	'object'        => TRUE,
    	'cache'         => FALSE,
    	'escape'        => TRUE
    );

	$config['contact'] = array(
		'benchmark' => TRUE,
		'persistent' => FALSE, 
		'connection' => array(
			'type' => 'mysqli',
			'user'     => 'root',
			'pass'     => '123456',
			'host'     => 'mysql',
			'port'     => FALSE,
			'socket'   => FALSE,
			'database' => 'momo_contact'
		), 
		'character_set' => 'utf8', 
		'table_prefix' => '', 
		'object' => TRUE, 
		'cache' => FALSE, 
		'escape' => TRUE
	);
	
	
	$config['contact_slave'] = array(
        'benchmark' => TRUE,
        'persistent' => FALSE,
        'connection' => array(
            'type' => 'mysqli',
            'user'     => 'root',
            'pass'     => '123456',
            'host'     => 'mysql',
            'port'     => FALSE,
            'socket'   => FALSE,
            'database' => 'momo_contact'
        ),
        'character_set' => 'utf8',
        'table_prefix' => '',
        'object' => TRUE,
        'cache' => FALSE,
        'escape' => TRUE
    );
    $config['sns_album'] = array
    (
    	'benchmark'     => TRUE,
    	'persistent'    => FALSE,
    	'connection'    => array
    	(
    		'type'     => 'mysql',
            'user'     => 'root',
            'pass'     => '123456',
            'host'     => 'mysql',
    		'port'     => FALSE,
    		'socket'   => FALSE,
    		'database' => 'momo_album'
    	),
    	'character_set' => 'utf8',
    	'table_prefix'  => '',
    	'object'        => TRUE,
    	'cache'         => FALSE,
    	'escape'        => TRUE
    );

    $config['momo_im'] = array
    (
    	'benchmark'     => TRUE,
    	'persistent'    => FALSE,
    	'connection'    => array
    	(
    		'type'     => 'mysql',
            'user'     => 'root',
            'pass'     => '123456',
            'host'     => 'mysql',
    		'port'     => FALSE,
    		'socket'   => FALSE,
    		'database' => 'momo_space'
    	),
    	'character_set' => 'utf8',
    	'table_prefix'  => '',
    	'object'        => TRUE,
    	'cache'         => FALSE,
    	'escape'        => TRUE
    );
}
