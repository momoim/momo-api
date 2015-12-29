<?php
defined('SYSPATH') or die('No direct script access.');
/*
 * [MOMO API] (C)1999-2012 ND Inc.
 * Oauth模型文件
 */
/**
 * Oauth模型
 */
class Oauth_Model extends Model {

	/**
	 * 实例
	 * @var App_Model
	 */
	protected static $instance;

	/**
	 * 缓存
	 * @var Cache
	 */
	protected $cache;

	/**
	 * 缓存前缀
	 * @var string
	 */
	protected $cache_pre;
	/**
	 * 单例模式
	 * @return App_Model
	 */
	public static function &instance()
	{
		if (! isset(self::$instance))
		{
			// Create a new instance
			self::$instance = new Oauth_Model();
		}
		return self::$instance;
	}

	/**
	 * 构造函数
	 */
	public function __construct()
	{
		parent::__construct();
		$this->cache = Cache::instance();
		$this->cache_pre = CACHE_PRE.'app_';
	}

	/**
	 * 
	 * 检查appid是否存在
	 * @param unknown_type $appid
	 */
    public function isAppExist($appid) {
    	$query = $this->db->fetchData('oauth_server_registry', 'count(*) as total',array('ost_app_id'=>$appid));
		$result = $query->result_array(FALSE);
		if($result[0]['total']) {
			return true;
		}
		return false;	
    }

	/**
	 * 
	 * 获取appid对应的信息
	 * @param unknown_type $appid
	 */
    public function get_91($appid) {
    	$query = $this->db->fetchData('oauth_server_registry', '*',array('ost_app_id'=>$appid,'osr_name'=>91));
		$result = $query->result_array(FALSE);
		if($result) {
			return array('appid'=>$result[0]['ost_app_id'],'consumer_key'=>$result[0]['osr_consumer_key'],'consumer_secret'=>$result[0]['osr_consumer_secret']);
		}
		return null;	
    }
    
	/**
	 * 
	 * 新增oauth应用
	 * @param $appid
	 * @param $name
	 * @param $title
	 * @param $description
	 */
    public function create($appid,$osr_name,$dev_name,$title,$description) {
    	$consumer_key	= $this->generate_key(true);
		$consumer_secret= $this->generate_key();
		$appid = $this->db->insertData('oauth_server_registry',
			array(
			'ost_app_id'=>$appid, 
			'osr_name'=>$osr_name, 
			'osr_usa_id_ref'=>0, 
			'osr_consumer_key'=>$consumer_key, 
			'osr_consumer_secret'=>$consumer_secret, 
			'osr_enabled'=>1, 
			'osr_status'=>'active', 
			'osr_requester_name'=>$dev_name, 
			'osr_requester_email'=>'', 
			'osr_callback_uri'=>'', 
			'osr_application_uri'=>'', 
			'osr_application_title'=>$title, 
			'osr_application_descr'=>$description,
			'osr_application_notes'=>'',
			'osr_application_type'=>'',
			'osr_application_commercial'=>0, 
			'osr_issue_date'=>date('Y-m-d H:i:s'), 
			'osr_timestamp'=>date('Y-m-d H:i:s')
			));
        return array('appid'=>$appid,'consumer_key'=>$consumer_key,'consumer_secret'=>$consumer_secret);
    }
    
	/**
	 * Generate a unique key
	 * 
	 * @param boolean unique	force the key to be unique
	 * @return string
	 */
	private function generate_key ( $unique = false )
	{
		$key = md5(uniqid(rand(), true));
		if ($unique)
		{
			list($usec,$sec) = explode(' ',microtime());
			$key .= dechex($usec).dechex($sec);
		}
		return $key;
	}
	
	/**
	 * 
	 * 获取appid对应的信息
	 * @param unknown_type $appid
	 */
    public function check_app_key($app_key,$app_name='') {
    	$search_data = array();    
    	if($app_key) {
    		$search_data['osr_consumer_key'] = $app_key;
    	}
    	if($app_name) {
    		if(!in_array($app_name,array('single','all')))
    			$search_data['osr_name'] = $app_name;
    	}
    	if(count($search_data)>0){
    		$query = $this->db->fetchData('oauth_server_registry', 'osr_id,osr_consumer_key',$search_data);
			$result = $query->result_array(FALSE);
			return $result;
    	}
		return array();
    }
}
