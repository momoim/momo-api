<?php

defined('SYSPATH') OR die('No direct access allowed.');

/*
 * [UAP Portal] (C)1999-2012 ND Inc.
 * 91通行证接口配置文件
 */

$config['api_name'] = 'momo';
$config['91cloud_user_limit'] = 200000;
$config['momo_appid'] = '12';
$config['reg_flag'] = '94';
$config['site_flag'] = '258';
$config['sj_appid'] = '106576';
$config['sj_check_uin_bind_act'] = '55';
$config['sj_check_phone_bind_act'] = '67';
$config['sj_bind_phone_act'] = '65';
$config['sj_unbind_phone_act'] = '66';
$config['sj_check_sid_act'] = '4';
//iphoto的app id
$config['iphoto_app_id'] = '105098';
$config['iphoto_app_key'] = 'c2bdde4a4acc1d92a10053bf45c376dcdaebf7c013ff200f';
//91办公在线接口-会话验证
$config['oap_passport_check'] = 'passport/check';
//91办公在线接口-获取用户基本资料
$config['oap_user_info'] = 'user/info';
//授权91服务器IP
$config['authorized_ip'] = array(
	"10.1.242.201",
	"121.207.242.201",
	"10.1.242.234",
	"121.207.242.234",
	"10.1.242.48",
	"121.207.242.48",
	"10.1.242.60",
	"121.207.242.60",
	"10.2.103.59",
	"58.22.103.59",
	"10.2.105.68",
	"58.22.103.29"
);
//91云
$config['cloud_api_name'] = '91cloud';
$config['cloud_reg_flag'] = '130';
$config['cloud_site_flag'] = '501';

if (IN_PRODUCTION === TRUE) {
	//接口地址for 91
    $config['api_url'] = 'http://regapi.91.com/Account/Default.ashx';
	//接口地址for momo
    $config['momo_api_url'] = 'http://regapi.91.com/Momo/Default.ashx';
    //接口帐号密码
    $config['api_passwd'] = '0b173d79-eeb8-4f55-8559-830f7b5be3ff';
	//91通行证登录
	$config['login_method'] = 'Login';
    $config['login_keyvalue'] = 'd78f249b-8bfd-49eb-a068-b869ad3cf670';
    //91通行证通过用户id登录
	$config['login_by_uin_method'] = 'LoginByUin';
    $config['login_by_uin_keyvalue'] = '33e1994a-5013-4855-9df8-b8b623dbaaa7';
    //91通行证改绑密保手机
	$config['mobile_change_bind_method'] = 'MobileChangeBind';
    $config['mobile_change_bind_keyvalue'] = '9ec87b90-7aaf-413e-8e01-5910374da40d';
    //91通行证绑定密保手机
	$config['mobile_bind_method'] = 'MobileBind';
    $config['mobile_bind_keyvalue'] = '91afa89f-def3-428b-a613-af6330a7e70e';
    //91通行证修改密码
	$config['change_password_method'] = 'ChangePassword';
    $config['change_password_keyvalue'] = '42c4a647-4dc0-46e6-938e-288d60124501';
    //91通行证注册
	$config['register_method'] = 'Register';
    $config['register_keyvalue'] = '6b3a98cb-4a26-400c-a600-1a355347f869';
    //91通行证帐号有效性验证
	$config['check_user_name_method'] = 'CheckUserName';
    $config['check_user_name_keyvalue'] = '95f9b72b-62ab-4bfc-b87b-3ff9c85c6bc6';
    //91手机助手映射表appkey
	$config['sj_appkey'] = 'bf8446ed7224b0aea9e298a7b22691bbbf796e177198f75b';
    //91手机助手映射接口
	$config['sj_api_url'] = 'http://phone.sj.91.com/abp.ashx';
	//应用服务器91接口
	$config['service_sj_91_api_url'] = 'http://service.sj.91.com/usercenter/AP.aspx';
	//应用服务器momo接口
	$config['service_sj_momo_api_url'] = 'http://service.sj.91.com/usercenter/Momo/Momo.ashx';
	//应用服务器momo接口appkey
	$config['service_sj_momo_api_appkey'] = 'fadb9394b84ffcb5916670dc48d2ae84bd71f4e886d5347a';
	//91办公在线接口
	$config['oap_api_url'] = 'http://oap.91.com/';
	//根据cookie校验用户合法性
	$config['check_user_login_by_cookie_method'] = 'CheckUserLoginByCookie';
	//根据cookie校验用户合法性
	$config['check_user_login_by_cookie_keyvalue'] = 'ba71fb97-9235-427c-b2af-567cf746af6e';
	//91cloud password
	$config['cloud_api_passwd'] = '56fdeea5-cad6-43c3-93ce-c8843e236f8a';
} else {
	//接口地址for 91
    $config['api_url'] = 'http://testreg.91.com/Simple/Interface/Account/Default.ashx';
	//接口地址for momo
    $config['momo_api_url'] = 'http://testreg.91.com/Simple/Interface/Momo/Default.ashx';
    //接口帐号密码
    $config['api_passwd'] = 'd9d40371-ab65-43d7-859e-6099269f9132';
	//91通行证登录
	$config['login_method'] = 'Login';
    $config['login_keyvalue'] = 'e542c1f2-b79c-4dae-924a-1bbb3d78b456';
    //91通行证通过用户id登录
	$config['login_by_uin_method'] = 'LoginByUin';
    $config['login_by_uin_keyvalue'] = '22cf6601-094e-49f9-b8f5-affd83982cc1';
    //91通行证改绑密保手机
	$config['mobile_change_bind_method'] = 'MobileChangeBind';
    $config['mobile_change_bind_keyvalue'] = '875b7902-fe76-43f2-a5f5-296e7b79d2f9';
    //91通行证绑定密保手机
	$config['mobile_bind_method'] = 'MobileBind';
    $config['mobile_bind_keyvalue'] = '5e4210d3-3b1b-45ae-832e-4197189b3363';
    //91通行证修改密码
	$config['change_password_method'] = 'ChangePassword';
    $config['change_password_keyvalue'] = '03261c9c-dc48-4b58-b360-3db4cf464ecc';
    //91通行证注册
	$config['register_method'] = 'Register';
    $config['register_keyvalue'] = '9962a3a2-4e00-43f0-955c-40c469d7bc44';
    //91通行证帐号有效性验证
	$config['check_user_name_method'] = 'CheckUserName';
    $config['check_user_name_keyvalue'] = 'e436298b-e61e-4198-a690-3bd743ec17cc';
    //91手机助手映射表appkey
	$config['sj_appkey'] = 'a41779eaa50f27b6122a56bb9afae1588c61e130935f4df2';
    //91手机助手映射接口
	$config['sj_api_url'] = 'http://192.168.9.87:8005/abp.ashx';
	//应用服务器91接口
	$config['service_sj_91_api_url'] = 'http://service.sj.91.com/usercenter/AP.aspx';
	//应用服务器momo接口
	$config['service_sj_momo_api_url'] = 'http://192.168.9.87:4010/Momo/Momo.ashx';
	//应用服务器momo接口appkey
	$config['service_sj_momo_api_appkey'] = '0123456789abcdef';
	//91办公在线接口
	$config['oap_api_url'] = 'http://192.168.94.21/oap21/';
	//$config['oap_api_url'] = 'http://oap.91.com/';
	//根据cookie校验用户合法性
	$config['check_user_login_by_cookie_method'] = 'CheckUserLoginByCookie';
	//根据cookie校验用户合法性
	$config['check_user_login_by_cookie_keyvalue'] = 'ba71fb97-9235-427c-b2af-567cf746af6e';
	//91cloud password
	$config['cloud_api_passwd'] = '56fdeea5-cad6-43c3-93ce-c8843e236f8a';
}
