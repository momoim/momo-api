<?php
defined('SYSPATH') or die('No direct script access.');
/*
 * [MOMO API] (C)1999-2012 ND Inc.
 * Tab模型文件
 */
/**
 * Tab模型
 */
class Tab_Model extends Model {

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
			self::$instance = new Tab_Model();
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
    * 获取用户首页tab显示或者隐藏列表
    * @param integer $uid 用户ID
    * @param integer $isShow 显示1，隐藏0
    * @return array
    */
    public function getList($uid, $isShow) {
        $query = $this->db->query("SELECT * FROM tab WHERE uid=$uid AND is_show = $isShow ORDER BY `type_id` DESC,`last_modify` DESC");
        if($query->count() == 0) {
            return array();
        }
        return $query->result_array(FALSE);
    }
    
    /**
    * 获取用户首页tab显示和隐藏列表
    * @param integer $uid 用户ID
    * @param integer $isShow 显示1，隐藏0
    * @return array
    */
    public function getAllList($uid, $typeId) {
        $query = $this->db->query("SELECT * FROM tab WHERE uid=$uid AND type_id=$typeId ORDER BY `is_show` DESC ,`index` ASC");
        if($query->count() == 0) {
            return array();
        }
        return $query->result_array(FALSE);
    }
    
	/**
	* 获取首页tab显示或隐藏的第一个
	* @param <type> $uid
	* @param <type> $isShow 显示1/隐藏0
	*/
	public function getFirst($uid, $isShow) {
	    $query = $this->db->query("SELECT * FROM tab WHERE uid=$uid AND is_show = $isShow ORDER BY `index` ASC LIMIT 0, 1");
	    if($query->count() == 0) {
	        return array();
	    }
	    $result = $query->result_array(FALSE);
	    return $result[0];
	}
	
	/**
	 * 
	 * 获取tab
	 * @param unknown_type $uid
	 * @param unknown_type $typeId
	 * @param unknown_type $id
	 */
	public function get($uid, $typeId, $id) {
		return $this->db->getRow('tab', '*', "uid = $uid AND type_id = $typeId AND id = $id");
	}
	
	/**
	* 更新tab
	* @param <type> $uid
	* @param <type> $typeId tab类型(群/活动)
	* @param <type> $id	 群/活动id
	*/	
	public function lastModify($uid,$typeId,$id){
	    //插入首页tablist第一个
	    return $this->db->query("UPDATE tab SET last_modify=".time()." WHERE uid=$uid AND type_id=$typeId AND id = $id");
	}
	/**
	* 插入一个首页tab
	* @param <type> $uid
	* @param <type> $typeId tab类型(群/活动)
	* @param <type> $id	 群/活动id
	*/	
	public function create($uid, $typeId, $id){
	    //插入首页tablist第一个
	    $firstTab = $this->getFirst($uid, 1);
	    $index = 0;
	    $time = time();
	    if($firstTab) {
	        $index = $firstTab['index'] - 1;
	    }
	    return $this->db->query("REPLACE INTO tab VALUE($uid, $typeId, $id, 1, $index,$time)");
	}
	
	/**
	* 删除一个首页tab
	* @param <type> $uid
	* @param <type> $typeId tab类型(群/活动)
	* @param <type> $id	 群/活动id
	*/			
	public function delete($uid, $typeId, $id) {
	    return $this->db->deleteData("tab", "uid = $uid AND type_id = $typeId AND id = $id");
	}


}
