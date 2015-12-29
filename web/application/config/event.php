<?php defined('SYSPATH') OR die('No direct access allowed.');
/*
* [UAP Portal] (C)1999-2009 ND Inc.
* 活动服务端配置文件
*/

//活动appid
$config['appid'] = 22; 

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


//活动排序
$config['grade'] = array(
	'creator' => 3,
	'manager' => 2,
	'normal' => 1
);

//排序
$config['sort'] = array(
	'time' => 'create_time',		//按时间排序
	'joined' => 'joined_total'			//按热度排序
);

//报名类型
$config['apply_type'] = array(
	'no_apply' => 0,				//未报名
	'joined' => 1,				//参加
	'refused' => 2,			//不参加
	'interested' => 3,			//感兴趣
	'unconfirmed' => 4			//未确定
);


//报名类型
$config['apply_type_cn'] = array(
	'0' => '未报名',				//未报名
	'1' => '参加',				//参加
	'2' => '不参加',			//不参加
	'3' => '感兴趣',			//感兴趣
	'4' => '未确定'			//未确定
);

//活动状态
$config['status'] = array(
	1=>'报名中',
	2=>'进行中',
	3=>'已结束'
);

//兼容excel 2003格式
$config['xlsFormat'] = array(
    'username' => '姓名',
	'mobile' => '手机',
    'status' => '报名状态'
);

$config['request_type'] = array(
    'all', 'me_tab_show', 'me_launch','me_joined','me_interested','me_not_join'
);

$config['additional_time'] = 86400; //一天

$config['recent_date_limit'] = 30*24*60*60; //最近活动的近期时间限制
