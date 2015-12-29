<?php
defined('SYSPATH') or die('No direct script access.');
/*
 * [MOMO API] (C)1999-2012 ND Inc.
 * Event_Image模型文件
 */
/**
 * Event_Image模型
 */
class Event_Image_Model extends Model {

	/**
	 * 实例
	 * @var App_Model
	 */
	protected static $instance;

	/**
	 * 单例模式
	 * @return App_Model
	 */
	public static function &instance()
	{
		if (! isset(self::$instance))
		{
			// Create a new instance
			self::$instance = new Event_Image_Model();
		}
		return self::$instance;
	}

	/**
	 * 构造函数
	 */
	public function __construct()
	{
		parent::__construct();
	}

    /**
     * 
     * 按照活动获取活动照片
     * @param int $uid
     */
    public function listByEvent($eid) {
        $query = $this->db->query("SELECT * FROM event_image WHERE eid=$eid ORDER BY `created` DESC");
        if($query->count() == 0) {
            return array();
        }
        return $query->result_array(FALSE);
    }
    /**
     * 
     * 按照活动获取活动照片
     * @param int $uid
     */
    public function getCover($eid) {
    	$row = $this->db->getRow('event_image', '*', "eid = $eid AND cover=1 ");
    	if($row)	
    		return $row['url'];
    }

    /**
     * 
     * 按照用户获取活动照片
     * @param int $uid
     */
    public function listByUser($uid) {
        $query = $this->db->query("SELECT * FROM event_image WHERE uid=$uid ORDER BY `created` DESC");
        if($query->count() == 0) {
            return array();
        }
        return $query->result_array(FALSE);
    }
	
    /**
     * 
     * 获取活动照片详情
     * @param $id
     */
	public function get($id) {
		return $this->db->getRow('event_image', '*', "id = $id");
	}
	
	/**
	 * 
	 * 创建活动
	 * @param array $event_image
	 */
	public function create($event_image){
	    return $this->db->insertData('event_image', $event_image);
	}

	/**
	* 删除活动照片
	* @param int $uid
	* @param int $id
	*/			
	public function deleteByEvent($eid,$cover=0) {
	    return $this->db->deleteData("event_image", "eid = $eid AND cover = $cover");
	}
	
	/**
	* 删除活动照片
	* @param int $uid
	* @param int $id
	*/			
	public function delete($uid, $id,$cover=0) {
	    return $this->db->deleteData("event_image", "uid = $uid AND id = $id AND cover = $cover");
	}


}
