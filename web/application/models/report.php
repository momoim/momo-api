<?php defined('SYSPATH') or die('No direct script access.');
/**
 */

class Report_Model extends Model { 
	public static $instances = null;
     
    /**
     * 构造函数，初始化数据库连接
     */
    public function __construct() {
        parent::__construct();
    }

    public static function &instance()
    {
        if (!is_object(Report_Model::$instances)) {
            // Create a new instance
            Report_Model::$instances = new Report_Model();
        }
        return Report_Model::$instances;
    }

	public function add($source,$reason,$report_phone,$description,$url_code) {
		return $this->db->insertData('report',array('source'=>$source,'reason'=>$reason,'report_phone'=>$report_phone,'description'=>$description,'url_code'=>$url_code));
	}
}
