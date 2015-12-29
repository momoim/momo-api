<?php defined('SYSPATH') OR die('No direct access allowed.');
/*
* [UAP Portal] (C)1999-2009 ND Inc.
* 群组服务端配置文件
*/
$config['gavatar'] = Kohana::config('album.avatar').'style/images/v2/gavatar.gif';

$config['groupType'] = array(
	'general' => 0,
	'company' => 1,
	'school' => 2
);

$config['schoolType'] = array(
	'university' => 1,
	'high' => 2,
	'junior' => 3
);

$config['type'] = array(
	'group' => 1,
	'event' => 2
);

$config['privacy'] = array(
	'public' => 1,
	'private' => 2
);

$config['grade'] = array(
	'normal' => 1,
	'manager' => 2,
    'master' => 3
);

//群组可创建总数
$config['limit'] = array(
	'public' => 150,
	'private' => 20
);


//群组最大成员总数
$config['maxMemberNum'] = array(
	'public' => 500,
	'private' => 100
);

$config['maxManagerNum'] = array(
	'public' => 6,
	'private' => 3
);

//群邀请链接有效期
$config['invite_limit_time'] = 86400; //60*60*24(一天)