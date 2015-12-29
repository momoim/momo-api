<?php

defined('SYSPATH') OR die('No direct access allowed.');

/*
 * [MOMO API] (C)1999-2013 ND Inc.
 * 通讯录配置文件
 */

// neo4j地址


if (IN_PRODUCTION === TRUE)
{
	$config['host'] = '10.1.155.78';
	$config['port'] = 8080;
	$config['timeout'] = 5;
}
else
{
	$config['host'] = '192.168.19.176';
	$config['port'] = 7474;
	$config['timeout'] = 5;
}