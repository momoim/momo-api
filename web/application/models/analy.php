<?php defined('SYSPATH') or die('No direct script access.');
/**
 * [momo移动社区] (C)1999-2010 ND Inc.
 * 模型文件
 */

class Analy_Model extends Model {
	public static $instances = null;
    public function __construct() {
        parent::__construct();
    }
	
	public static function &instance() {
		if (! is_object ( Analy_Model::$instances )) {
			// Create a new instance
			Analy_Model::$instances = new Analy_Model ();
		}
		return Analy_Model::$instances;
	}

    /**
     * 添加活动
     * @param array $event_info 活动相关信息
     * @return boolean
     */
	public function add($appid,$type,$code,$client_id,$content,$user_agent){
		$letters = array('appid'=>$appid,'type'=>$type,'code'=>$code,'client_id'=>$client_id,'content'=>$content,'user_agent'=>$user_agent,'created'=>time());
		return $this->db->insertData('analy_log', $letters);
	}
	
    /**
     * 查询活动
     * @param array $event_info 活动相关信息
     * @return boolean
     */
	public function lists($appid,$type='',$code='',$page=1,$size=20){
		$start = ($page-1)*$size;
		$sql = "SELECT * FROM analy_log WHERE appid='{$appid}' ";
		if($type)
			$sql .= " AND `type`='{$type}'";
		if($code)
			$sql .= " AND `code`='{$code}'";
		$sql .= "LIMIT {$start},{$size}";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		if($query->count() > 0){
			return $result;
		}
		return array();
	}
	
	public function stat($appid,$code,$type='',$start_date='',$end_date=''){
		$data = array();
		$sql = "SELECT code,type,COUNT( code ) AS total, FROM_UNIXTIME( created,  '%Y%m%d' ) AS created_day FROM  `analy_log` WHERE appid='{$appid}' ";
		if($type)
			$sql .= "AND type='{$type}'";
		if($start_date)
			$sql .= "AND created>='{$start_date}'";
		if($end_date)
			$sql .= "AND created<='{$end_date}'";
		$sql .= " GROUP BY created_day,code ORDER BY created_day DESC";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		if($result) {
			foreach($result as $k => $v) {
				$data[$v['created_day']][$v['code']] = $v['total'];
			}
		}
		return $data;
	}
}
