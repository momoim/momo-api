<?php

defined('SYSPATH') OR die('No direct access allowed.');

/*
 * [MOMO API] (C)1999-2012 ND Inc.
 * 通讯录配置文件
 */

//默认头像
$config['avatar'] = 'style/images/noavatar_120.jpg';

//默认类型
$config['type'] = array('home', 'work');

$config['emailType'] = array('internet,home', 'internet,work', 'internet');

//IM协议
$config['protocol'] = array('qq', 'gtalk', 'aim', 'yahoo', 'skype', 'msn', 'icq', 'jabber', '91u');

//电话类型
$config['telType'] = array(
	'home',
	'work',
	'cell',
	'home,fax',
	'work,fax',
	'pager',
	'other'
);

//系统分组
$config['sys_groups'] = array(
	array(
		'id'   => 'all',
		'name' => '全部联系人',
	),
	array(
		'id'   => 'none',
		'name' => '未分组',
	),
	array(
		'id'   => 'recycled',
		'name' => '回收站',
	),
);
/*
//默认分组
$config['defaultGroups'] = array(
    '家人',
    '好友',
    '同学',
);

$config['prefix'] = array(
    'Dr.',
    'Miss',
	'Mr.',
	'Mrs.',
	'Ms.',
	'Prof'
);

$config['suffix'] = array(
  '先生',
  '女士',
	'夫人',
	'太太',
	'小姐',
	'同志'
);
*/
//旧分表规则
$config['old_divide_table'] = array(

);

//新分表规则
$config['new_divide_table'] = array(
	'contacts'        => '800',
	'contact_tels'    => '800',
	'contact_history' => '800',
	'contact_customs' => '800',
	//快照按月分表
	'*_snapshot'      => 'y_m',
);

//分库数
$config['db_count'] = 1;
//是否保存快照
$config['save_snapshot'] = TRUE;
//是否过滤空数据
$config['filter'] = FALSE;

// 是否根据新库分表规则
$config['new_db_rule'] = FALSE;
// 判断是否是否从分组表获取数据
$config['from_category_table'] = TRUE;
// new_db_rule 为TRUE时生效，是否使用cobar
$config['use_cobar'] = FALSE;
// 回收站分页上限
$config['recycled_page_limit'] = 5000;
