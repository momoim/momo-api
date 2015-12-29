<?php defined('SYSPATH') or die('No direct access allowed.');

/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * 91 API类文件
 */

/**
 * 91 api类
 */
class api_91 extends Controller{

    /**
     ********************
     * 91通行证相关接口 
     ********************
     */
	
	/**
     * 
     * 91通行证帐号登录
     */
    static public function login($account,$password,$md5=0) {
    	$result = array();
    	if(!$md5)
			$password = md5($password."\xa3\xac\xa1\xa3"."fdjf,jkgfkl"); 
    	$to_post['action'] = Kohana::config('91.login_method');
    	$to_post['TimeStamp'] = date('YmdHis',time());
    	$to_post['UserName'] = Kohana::config('91.api_name');    	
    	$to_post['AccountName'] = $account;
    	$to_post['Password'] = $password;
    	$to_post['SiteFlag'] = Kohana::config('91.site_flag');    	
    	$to_post['IpAddress'] = self::get_ip();
    	$to_post['CheckCode'] = md5($to_post['AccountName'].$to_post['Password'].$to_post['SiteFlag'].$to_post['IpAddress'].$to_post['TimeStamp'].Kohana::config('91.api_passwd').Kohana::config('91.login_keyvalue'));
    	$to_post['Format'] = 'json';
    	$result = self::_uc_fopen(Kohana::config('91.momo_api_url'),0, self::to_postdata($to_post), 'POST');
    	if(isset($result['data'])) {
    		$result = json_decode($result['data'],1);
    		if (isset($result['Code']) && $result['Code']==1) {
    			return array('error'=>0,'code'=>1,'msg'=>'登录成功','user_id'=>$result['UserId'],'user_name'=>$result['UserName']);
    		}
    		return array('error'=>1,'code'=>$result['Code'],'msg'=>$result['Message']);
    	}
    	return array('error'=>1,'msg'=>'登录失败');
    }
    
	/**
     * 
     * 91通行证帐号登录
     */
    static public function login_test($account,$password,$md5=0) {
    	$result = array();
    	if(!$md5)
			$password = md5($password."\xa3\xac\xa1\xa3"."fdjf,jkgfkl"); 
    	$to_post['action'] = Kohana::config('91.login_method');
    	$to_post['TimeStamp'] = date('YmdHis',time());
    	$to_post['UserName'] = Kohana::config('91.api_name');    	
    	$to_post['AccountName'] = $account;
    	$to_post['Password'] = $password;
    	$to_post['SiteFlag'] = Kohana::config('91.site_flag');    	
    	$to_post['IpAddress'] = self::get_ip();
    	$to_post['CheckCode'] = md5($to_post['AccountName'].$to_post['Password'].$to_post['SiteFlag'].$to_post['IpAddress'].$to_post['TimeStamp'].Kohana::config('91.api_passwd').Kohana::config('91.login_keyvalue'));
    	$to_post['Format'] = 'json';
    	$result = self::_uc_fopen(Kohana::config('91.momo_api_url'),0, self::to_postdata($to_post), 'POST');
    	print_r($result);exit;
    	if(isset($result['data'])) {
    		$result = json_decode($result['data'],1);
    		if (isset($result['Code']) && $result['Code']==1) {
    			return array('error'=>0,'code'=>1,'msg'=>'登录成功','user_id'=>$result['UserId'],'user_name'=>$result['UserName']);
    		}
    		return array('error'=>1,'code'=>$result['Code'],'msg'=>$result['Message']);
    	}
    	return array('error'=>1,'msg'=>'登录失败');
    }
    
    
	/**
     * 
     * 91通行证uid登录
     */
    static public function login_by_uin($uin,$password) {
    	$result = array();
		$password = md5($password."\xa3\xac\xa1\xa3"."fdjf,jkgfkl"); 
    	$to_post['action'] = Kohana::config('91.login_by_uin_method');
    	$to_post['TimeStamp'] = date('YmdHis',time());
    	$to_post['UserName'] = Kohana::config('91.api_name');    	
    	$to_post['UserId'] = $uin;
    	$to_post['Password'] = $password;
    	$to_post['SiteFlag'] = Kohana::config('91.site_flag');    	
    	$to_post['IpAddress'] = self::get_ip();
    	$to_post['CheckCode'] = md5($to_post['UserId'].$to_post['Password'].$to_post['SiteFlag'].$to_post['IpAddress'].$to_post['TimeStamp'].Kohana::config('91.api_passwd').Kohana::config('91.login_by_uin_keyvalue'));
    	$to_post['Format'] = 'json';
    	$result = self::_uc_fopen(Kohana::config('91.momo_api_url'),0, self::to_postdata($to_post), 'POST');
    	if(isset($result['data'])) {
    		$result = json_decode($result['data'],1);
    		if (isset($result['Code']) && $result['Code']==1) {
    			return array('error'=>0,'msg'=>'登录成功','user_id'=>$result['UserId'],'user_name'=>$result['UserName']);
    		}
    		return array('error'=>1,'msg'=>$result['Message']);
    	}
    	return array('error'=>1,'msg'=>'登录失败');
    }
    
    
	/**
     * 
     * 91通行证注册
     */
    static public function register($account,$password,$prefix='') {
    	$result = array();
    	$to_post['action'] = Kohana::config('91.register_method');
    	$to_post['TimeStamp'] = date('YmdHis',time());
    	$to_post['UserName'] = Kohana::config('91.api_name');    	
    	$to_post['AccountName'] = $prefix.$account;
    	$to_post['Password'] = self::get_encr_pass($password);
    	$to_post['RegPlat'] = Kohana::config('91.reg_flag');
    	$to_post['IpAddress'] = self::get_ip();
    	$to_post['CheckCode'] = md5($to_post['AccountName'].$to_post['Password'].$to_post['RegPlat'].$to_post['IpAddress'].$to_post['TimeStamp'].Kohana::config('91.api_passwd').Kohana::config('91.register_keyvalue'));
    	$to_post['Format'] = 'json';
    	$result = self::_uc_fopen(Kohana::config('91.momo_api_url'), 0, self::to_postdata($to_post), 'POST');
    	if(isset($result['data'])) {
    		$result = json_decode($result['data'],1);
    		if (isset($result['Code']) && $result['Code']==27000) {
    			return array('error'=>0,'msg'=>'注册成功','user_id'=>$result['UserId'],'user_name'=>$result['UserName']);
    		}
    		return array('error'=>1,'msg'=>$result['Message']);
    	}
    	return array('error'=>1,'msg'=>'注册失败');
    }
	
	/**
     * 
     * 检查用户名是否注册91通行证
     */
    static public function check_register($account_name) {
    	$result = array();
    	$to_post['action'] = Kohana::config('91.check_user_name_method');
    	$to_post['TimeStamp'] = date('YmdHis',time());
    	$to_post['UserName'] = Kohana::config('91.api_name');
    	$to_post['AccountName'] = $account_name;
    	$to_post['CheckCode'] = md5($to_post['AccountName'].$to_post['TimeStamp'].Kohana::config('91.api_passwd').Kohana::config('91.check_user_name_keyvalue'));
    	$to_post['Format'] = 'json';
    	$result = self::_uc_fopen(Kohana::config('91.api_url'), 0, self::to_postdata($to_post), 'POST');
		if(isset($result['data'])) {
    		$result = json_decode($result['data'],1);
    		//未注册
    		if (isset($result['Code']) && $result['Code']==1) {
    			return array('error'=>0,'registered'=>0,'msg'=>$result['Message']);
    		}
    		return array('error'=>0,'registered'=>1,'msg'=>$result['Message']);
    	}
    	return array('error'=>1,'msg'=>'查询失败');
    }
    

	/**
     * 
     * 根据cookie校验用户合法性
     */
    static public function check_user_login_by_cookie($cookie) {
    	$result = array();
    	$to_post['Action'] = Kohana::config('91.check_user_login_by_cookie_method');
    	$to_post['TimeStamp'] = $cookie['TimeStamp'];
    	$to_post['UserName'] = Kohana::config('91.cloud_api_name');
    	$to_post['AccountName'] = $cookie['AccountName'];
    	$to_post['UserId'] = $cookie['UserId'];
    	$to_post['SiteFlag'] = Kohana::config('91.cloud_site_flag');
    	$to_post['IpAddress'] = self::get_ip();
    	$to_post['CookieOrdernumberMaster'] = $cookie['CookieOrdernumberMaster'];
    	$to_post['CookieOrdernumberSlave'] = $cookie['CookieOrdernumberSlave'];
    	$to_post['CookieSiteflag'] = $cookie['CookieSiteflag'];
    	$to_post['CookieCheckcode'] = $cookie['CookieCheckcode'];
    	$to_post['CheckCode'] = md5($to_post['UserId'].$to_post['SiteFlag'].$to_post['IpAddress'].$to_post['CookieOrdernumberMaster'].$to_post['CookieOrdernumberSlave'].$to_post['CookieSiteflag'].$to_post['CookieCheckcode'].$to_post['TimeStamp'].Kohana::config('91.cloud_api_passwd').Kohana::config('91.check_user_login_by_cookie_keyvalue'));
    	$to_post['Format'] = 'json';
    	$result = self::_uc_fopen(Kohana::config('91.api_url'), 0, self::to_postdata($to_post), 'POST');
		if(isset($result['data'])) {
    		$result = json_decode($result['data'],1);
    		//未注册
    		if (isset($result['Code']) && $result['Code']==20000) {
    			return array('error'=>0,'user_id'=>$result['UserId'],'user_name'=>$result['UserName']);
    		}
    		return array('error'=>1,'msg'=>$result['Message']);
    	}
    	return array('error'=>1,'msg'=>'查询失败');
    }

	/**
     * 
     * 91通行证帐号绑定密保手机
     */
    static public function bind_mobile($account_name,$mobile) {
    	$result = array();
    	$to_post['action'] = Kohana::config('91.mobile_bind_method');
    	$to_post['TimeStamp'] = date('YmdHis',time());
    	$to_post['UserName'] = Kohana::config('91.api_name');
    	$to_post['AccountName'] = $account_name;
    	$to_post['Mobile'] = $mobile;
    	$to_post['CheckCode'] = md5($to_post['AccountName'].$to_post['Mobile'].$to_post['TimeStamp'].Kohana::config('91.api_passwd').Kohana::config('91.mobile_bind_keyvalue'));
    	$to_post['Format'] = 'json';
    	$result = self::_uc_fopen(Kohana::config('91.momo_api_url'), 0, self::to_postdata($to_post), 'POST');
    	if(isset($result['data'])) {
    		$result = json_decode($result['data'],1);
    		if (isset($result['Code']) && $result['Code']==23001) {
    			return array('error'=>0,'msg'=>$result['Message']);
    		}
    		return array('error'=>1,'msg'=>$result['Message']);
    	}
    	return array('error'=>1,'msg'=>'查询失败');
    }

	/**
     * 
     * 91通行证帐号解绑密保手机
     */
    static public function change_bind_mobile($account_name,$mobile,$mobile_old) {
    	$result = array();
    	$to_post['action'] = Kohana::config('91.mobile_change_bind_method');
    	$to_post['TimeStamp'] = date('YmdHis',time());
    	$to_post['UserName'] = Kohana::config('91.api_name');
    	$to_post['AccountName'] = $account_name;
    	$to_post['Mobile'] = $mobile;
    	$to_post['MobileOld'] = $mobile_old;
    	$to_post['CheckCode'] = md5($to_post['AccountName'].$to_post['MobileOld'].$to_post['Mobile'].$to_post['TimeStamp'].Kohana::config('91.api_passwd').Kohana::config('91.mobile_change_bind_keyvalue'));
    	$to_post['Format'] = 'json';
    	$result = self::_uc_fopen(Kohana::config('91.momo_api_url'), 0, self::to_postdata($to_post), 'POST');
    	if(isset($result['data'])) {
    		$result = json_decode($result['data'],1);
    		if (isset($result['Code']) && $result['Code']==23001) {
    			return array('error'=>0,'msg'=>$result['Message']);
    		}
    		return array('error'=>1,'msg'=>$result['Message']);
    	}
    	return array('error'=>1,'msg'=>'查询失败');
    }
    
    /**
     ********************
     * 无线通用平台相关接口 
     ********************
     */
    
 	/**
     * 
     * 查询通用平台91 uin是否绑定
     */
    static public function check_uin_bind($uin) {
    	$to_post['Uin'] = $uin?trim($uin):0;
    	if (empty ( $to_post['Uin'] )) {
			return array('error'=>1,'msg'=>'用户uin为空');
		}
    	$sign = md5(base64_encode(json_encode($to_post)).Kohana::config('91.sj_appkey'));
    	$result = self::_uc_fopen(Kohana::config('91.sj_api_url').'?act='.Kohana::config('91.sj_check_uin_bind_act').'&appid='.Kohana::config('91.sj_appid').'&sign='.$sign, 0, base64_encode(json_encode($to_post)), 'POST');
	   	if(isset($result['data'])) {
    		$result = json_decode($result['data'],1);
			if (isset($result['PhoneNo']) && empty($result['PhoneNo'])) {
    			return array('error'=>0,'binded'=>0,'msg'=>'91用户未绑定手机');
    		}
    		return array('error'=>0,'binded'=>1,'msg'=>'91用户已绑定手机','phone'=>$result['PhoneNo']);
    	}
    	return array('error'=>1,'msg'=>'查询失败');
    }
    
    /**
     * 
     * 查询通用平台手机是否被绑定
     */
    static public function check_phone_bind($phone) {
    	$to_post['PhoneNo'] = $phone;
    	if (empty ( $to_post['PhoneNo'] )) {
			return array('error'=>1,'msg'=>'手机号为空');
		}
    	$sign = md5(base64_encode(json_encode($to_post)).Kohana::config('91.sj_appkey'));
    	$result = self::_uc_fopen(Kohana::config('91.sj_api_url').'?act='.Kohana::config('91.sj_check_phone_bind_act').'&appid='.Kohana::config('91.sj_appid').'&sign='.$sign, 0, base64_encode(json_encode($to_post)), 'POST');
		if(isset($result['data'])) {
    		$result = json_decode($result['data'],1);
			if (empty($result['Uin'])) {
    			return array('error'=>0,'binded'=>0,'msg'=>'手机号未绑定');
    		}
    		return array('error'=>0,'binded'=>1,'msg'=>'手机号已绑定','user_id'=>$result['Uin'],'user_name'=>$result['UserName']);
    	}
    	return array('error'=>1,'msg'=>'查询失败');
    }
    
    /**
     * 
     * 通用平台91 uin绑定手机号码
     */
    static public function bind_phone($uin,$phone) {
    	$to_post['Uin'] = $uin?trim($uin):0;
    	$to_post['PhoneNo'] = $phone?trim($phone):'';
    	if (empty ( $to_post['Uin'] )) {
			return array('error'=>1,'msg'=>'用户uin为空');
		}
    	if (empty ( $to_post['PhoneNo'] )) {
			return array('error'=>1,'msg'=>'手机号为空');
		}
    	$sign = md5(base64_encode(json_encode($to_post)).Kohana::config('91.sj_appkey'));
    	$result = self::_uc_fopen(Kohana::config('91.sj_api_url').'?act='.Kohana::config('91.sj_bind_phone_act').'&appid='.Kohana::config('91.sj_appid').'&sign='.$sign, 0, base64_encode(json_encode($to_post)), 'POST');
    	if(isset($result['data'])) {
    		$result = json_decode($result['data'],1);
    		if (isset($result['ResultCode']) && $result['ResultCode'] == 0) {
    			return array('error'=>0,'msg'=>'91账号绑定手机成功');
    		}
    		return array('error'=>1,'msg'=>$result['ResultDesc']);
    	}
    	return array('error'=>1,'msg'=>'91账号绑定手机失败');
    }
    
	/**
     * 
     * 通用平台91 uin解绑手机号码
     */
    static public function unbind_phone($uin,$phone) {
    	$to_post['Uin'] = $uin?trim($uin):0;
    	$to_post['PhoneNo'] = $phone?trim($phone):'';
    	if (empty ( $to_post['Uin'] )) {
			return array('error'=>1,'msg'=>'用户uin为空');
		}
    	if (empty ( $to_post['PhoneNo'] )) {
			return array('error'=>1,'msg'=>'手机号为空');
		}
    	$sign = md5(base64_encode(json_encode($to_post)).Kohana::config('91.sj_appkey'));
    	$result = self::_uc_fopen(Kohana::config('91.sj_api_url').'?act='.Kohana::config('91.sj_unbind_phone_act').'&appid='.Kohana::config('91.sj_appid').'&sign='.$sign, 0, base64_encode(json_encode($to_post)), 'POST');
    	if(isset($result['data'])) {
    		$result = json_decode($result['data'],1);
    		if (isset($result['ResultCode']) && $result['ResultCode'] == 0) {
    			return array('error'=>0,'msg'=>'91账号解绑手机成功');
    		}
    		return array('error'=>1,'msg'=>$result['ResultDesc']);
    	}
    	return array('error'=>1,'msg'=>'91账号解绑手机失败');
    }
    
    /**
     * 
     * oap会话验证
     * @param string $sid
     */
    static public function oap_passport_check($sid) {
    	$to_post['uap_sid'] = $sid?trim($sid):0;
    	$to_post['insidepassport'] = 0;
    	if (empty ( $to_post['uap_sid'] )) {
			return array('error'=>1,'msg'=>'sid为空');
		}
    	$result = self::_uc_fopen(Kohana::config('91.oap_api_url').Kohana::config('91.oap_passport_check'), 0, json_encode($to_post), 'POST');
    	if(isset($result['data'])) {
    		$result = json_decode($result['data'],1);
    		if (isset($result['uap_uid']) && $result['uap_uid'] > 0) {
    			$user_info = self::oap_user_info($result['uid'],$sid);
    			return array('error'=>0,'msg'=>'sid验证成功','uap_uid'=>$result['uap_uid'],'uid'=>$result['uid'],'username'=>$user_info['username']);
    		}
    		return array('error'=>1,'msg'=>$result['msg']);
    	}
    	return array('error'=>1,'msg'=>'sid验证失败');
    }

    
    /**
     * 
     * oap会话验证
     * @param string $sid
     */
    static public function oap_user_info($uid,$sid) {
    	if (empty ( $uid)) {
			return array('error'=>1,'msg'=>'uid为空');
		}
    	if (empty ( $sid)) {
			return array('error'=>1,'msg'=>'sid为空');
		}
		//@todo for deal
		$result = self::_uc_fopen(Kohana::config('91.oap_api_url').'user/info?uid='.$uid, 0, '', 'GET','PHPSESSID='.$sid);
    	//$result = self::_uc_fopen(Kohana::config('91.oap_api_url').Kohana::config('91.oap_user_info').'?uid='.$uid, 0, '', 'GET','PHPSESSID='.$sid);
    	if(isset($result['data'])) {
    		$result = json_decode($result['data'],1);
    		if (isset($result['uap_uid']) && $result['uap_uid'] > 0) {
    			return array('error'=>0,'msg'=>'sid验证成功','uid'=>$result['uap_uid'],'username'=>$result['username'],'unitid'=>$result['unitid'],'unitname'=>$result['unitname']);
    		}
    		return array('error'=>1,'msg'=>$result['msg']);
    	}
    	return array('error'=>1,'msg'=>'sid验证失败');
    }
    
    /**
     * 
     * 91会话验证
     * @param string $sid
     */
    static public function check_sid($appid,$sid,$uin) {
    	$to_post['AppId'] = $appid?(int)$appid:0;
    	$to_post['SessionId'] = $sid?trim($sid):'';
    	$to_post['Uin'] = $uin?$uin:0;
    	if (empty ( $to_post['AppId'] )) 
			return array('error'=>1,'msg'=>'appid为空');
    	if (empty ( $to_post['SessionId'] )) 
			return array('error'=>1,'msg'=>'sid为空');
    	if (empty ( $to_post['Uin'] )) 
			return array('error'=>1,'msg'=>'uin为空');
    	$to_post['Act'] = 2;
    	$to_post['Ver'] = 1;
		$to_post['Sign'] = md5($to_post['Act'].$to_post['Ver'].$to_post['AppId'].$to_post['SessionId'].Kohana::config('91.service_sj_momo_api_appkey'));
		$result = self::_uc_fopen(Kohana::config('91.service_sj_momo_api_url'), 0, json_encode($to_post), 'POST');
		if(isset($result['data'])) {
    		$result = json_decode($result['data'],1);
    		if ($result['ErrorCode'] == 1) {
    			return array('error'=>0,'msg'=>'sid验证成功','uid'=>$to_post['Uin']);
    		}
    		return array('error'=>1,'msg'=>$result['ErrorDesc']);
    	}
    	return array('error'=>1,'msg'=>'sid验证失败');
    }
} // End api