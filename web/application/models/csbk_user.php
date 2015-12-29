<?php
class Csbk_User_Model extends Model {
	
	public $error_msg = '';
	public static $instances = null;
	
	public function __construct() {
		parent::__construct ();
	}

	public function check_permission($uid, $ip, $resource, $permission)
	{
		$admin = Kohana::config('callshow.backend.admin');
		$iplist = Kohana::config('callshow.backend.ip');
		
		if( $admin[$uid] && $admin[$uid][$resource] && $admin[$uid][$resource][$permission]
			&&($admin[$uid]['super'] || $iplist[$ip]) )
		{
			return true;
		}
		
		return false;
	}
	
	public function check_permission_by_imsi($imsi,$resource,$permission)
	{
		$admin_imsi = Kohana::config('callshow.backend.admin_imsi');
		if( $admin_imsi[$imsi] && $admin_imsi[$imsi][$resource] && $admin_imsi[$imsi][$resource][$permission] )
		{
			return true;
		}
		
		return false;
	}
	
	public function permission_info($uid, $ip)
	{
		$admin = Kohana::config('callshow.backend.admin');
		$iplist = Kohana::config('callshow.backend.ip');
		
		if(!$iplist[$ip])
		{
			return array('result'=>403, "msg"=>"当前ip无法访问该接口");
		}
		
		if(!$admin[$uid])
		{
			return array('result'=>403, "msg"=>"当前用户无法访问该接口");
		}
		
		return array('result'=>200, "msg"=>$admin[$uid]); 
	}
}