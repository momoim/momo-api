<?php

defined('SYSPATH') OR die('No direct access allowed.');

/*
 * [UAP Portal] (C)1999-2010 ND Inc.
 * 通讯录配置文件
 */

//每天邀请数限制
$config['day_sms_limit'] = 50;
//邀请有效期
$config['invite_limit'] = 259200;//三天

//分库数
$config['divide_db'] = 8;
//分库数
$config['divide_table'] = array('sms'=>800,'backup_batch'=>80,'backup_history'=>80,'restore_history'=>80,'delete_history'=>40);

//邀请有效期

if (IN_PRODUCTION === TRUE) {
    $config['invite_site'] = 'http://simulate.momo.im/i/'; //正式: http://momo.im/i/
} else {
    $config['invite_site'] = 'http://uap26.91.com/i/';
}

$config['content_app'] = array(29=>array('txt'=>'%s (短信密码)【91来电秀】','show_mobile'=>0));