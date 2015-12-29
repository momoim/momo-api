<?php

defined('SYSPATH') OR die('No direct access allowed.');

/*
 * [MOMO API] (C)1999-2012 ND Inc.
 * 通讯录配置文件
 */

//默认头像
$config['host'] = '10.1.155.78';
$config['port'] = 80;
$config['timeout'] = 5;

// 文件大小限制
$config['file_max_size'] = 67108864;

//本地临时文件存放目录
$config['dir_tmp'] = '/tmp/momofs/';

if (IN_PRODUCTION === TRUE)
{
	{
		$config['ndfs'] = array
		(
			'hostname'            => '10.1.11.47',
			'port'                => 3323,
			'username'            => 'yycp_web_1',
			'password'            => 'songquancheng',
			'write_once_size'     => '262144',
		);
	}
//	$config['ndfs'] =  array
//	(
//		'hostname'			=> '10.1.11.47',
//		'port'				=> 3323,
//		'username'			=> 'yycp_web_1',
//		'password'			=> 'songquancheng',
//		'write_once_size'	=> '262144',
//	);
}
else
{
	$config['ndfs'] = array
	(
		'hostname'            => '192.168.152.6',
		'port'                => 5325,
		'username'            => 'fidtest',
		'password'            => '123456',
		'write_once_size'     => '262144',
	);
}