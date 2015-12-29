<?php defined('SYSPATH') OR die('No direct access allowed.');
/*
* [UAP Portal] (C)1999-2009 ND Inc.
* 活动服务端配置文件
*/

//活动类型
$config['type'] = array(
	'other' => 1,				//其它
	'party' => 2,				//聚会
	'sport' => 3,				//运动
	'travel' => 4				//旅行
);

//活动类型
$config['typeName'] = array(
	'other' => '其它',				//其它
	'party' => '聚会',				//聚会
	'sport' => '运动',				//运动
	'travel' => '旅行'				//旅行
);

//活动归属类型
$config['belongType'] = array(
	'general' => 0,
	'company' => 1,
	'school' => 2
);

//活动参与权限
$config['privacy'] = array(
	'invite' => 1,				//需要踩可参加
	'friend_and_group' => 2		//好友及群组成员
);

//活动成员权限
$config['grade'] = array(
	'creator' => 3,
	'manager' => 2,
	'normal' => 1
);

//报名类型
$config['apply_type'] = array(
	'join' => 1,				//参加
	'not_join' => 2,			//不参加
	'interest' => 3			//感兴趣
);

//活动状态
$config['status'] = array(
	'enroll' => array('id'=>1, 'name'=>'报名中'),
	'working' => array('id'=>2, 'name'=>'进行中'),
	'end' => array('id'=>3, 'name'=>'已结束')
);

//兼容excel 2003格式
$config['xlsFormat'] = array(
    'username' => '姓名',
	'mobile' => '手机',
    'status' => '报名状态'
);

$config['request_type'] = array(
    'all', 'me_tab_show', 'me_launch','me_joined','me_interested','me_not_join','friend_launch','friend_joined','friend_interested'
);

$config['additional_time'] = 86400; //一天

$config['recent_date_limit'] = 30*24*60*60; //最近活动的近期时间限制