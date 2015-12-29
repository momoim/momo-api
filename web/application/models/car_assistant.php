<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * 通话记录model
 */
class Car_Assistant_Model extends Model 
{
	
	public static $instances = null;
    public function __construct() {
        parent::__construct();
    }
	
	public static function &instance() {
		if (! is_object ( Car_Assistant_Model::$instances )) {
			// Create a new instance
			Car_Assistant_Model::$instances = new Car_Assistant_Model ();
		}
		return Car_Assistant_Model::$instances;
	}
	
	public function add_car($from_user,$to_user,$content,$plate_number,$vehicle_number,$msg_type,$time) {
		$plate_number = strtoupper($plate_number);
		$sql = "INSERT IGNORE INTO car_assistant (`from_user`, `to_user`,`content`, `plate_number`, `vehicle_number`, `msg_type`, `create_time`) VALUES ('".addslashes($from_user)."','".addslashes($to_user)."','".addslashes($content)."','".addslashes($plate_number)."','".addslashes($vehicle_number)."','".$msg_type."','".$time."')";
		return $this->db->query($sql);
	}
	
	public function add_daily_rotate_log($plate_number,$no,$add_time) {
		$plate_number = strtoupper($plate_number);
		$sql = "INSERT IGNORE INTO car_daily_rorate (`plate_number`, `no`, `add_time`) VALUES ('".addslashes($plate_number)."','".addslashes($no)."','".$add_time."')";
		return $this->db->query($sql);
	}

	
	public function get_break_rules($plate_number,$handled=0) {
		$plate_number = strtoupper($plate_number);
		$sql = "SELECT * FROM car_break_rules WHERE `plate_number` = '".addslashes($plate_number)."' AND handled='{$handled}' ORDER BY id DESC";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		if($result)
			return $result;
		return array();
	}
	
	public function add_break_rules($user_id,$plate_number,$no,$break_time,$break_address,$break_code,$detail_url,$delivered=1) {
		$plate_number = strtoupper($plate_number);
		$sql = "INSERT IGNORE INTO car_break_rules (`user_id`,`plate_number`, `no`, `break_time`, `break_address`, `break_code`, `detail_url`, `delivered`) VALUES ('".$user_id."','".$plate_number."','".$no."','".$break_time."','".$break_address."','".$break_code."','".$detail_url."','".$delivered."')";
		return $this->db->query($sql);
	}

	
	public function update_break_rules($plate_number,$setters=array()) {
		if($setters)
			$this->db->update('car_break_rules',$setters,array('plate_number'=>$plate_number));
	}
	
	
	public function get_car_assistant($plate_number) {
		$plate_number = strtoupper($plate_number);
		$sql = "SELECT * FROM car_assistant WHERE `plate_number` = '".addslashes($plate_number)."' ORDER BY id DESC";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		if($result[0])
			return $result[0];
		return false;
	}
	
	public function rotate() {
		$sql = "SELECT * FROM car_break_rules WHERE `delivered` = 0 ORDER BY id DESC";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		return $result;		
	}
	
	public function update_car_assistant($plate_number,$setters=array()) {
		if($setters)
			$this->db->update('car_assistant',$setters,array('plate_number'=>$plate_number));
	}
	
	public function update_userinfo_by_name($username,$fakeid,$nickname) {
		$sql = "UPDATE car_assistant SET user_id='{$fakeid}',nickname='{$nickname}' WHERE from_user='{$username}' ";
		return $this->db->query($sql);
		//return $this->db->update('car_assistant',array('user_id'=>$fakeid,'nickname'=>$nickname),array('from_user'=>$username));
	}
	
	public function getByName($from_name){
		$sql = "SELECT * FROM car_assistant WHERE `from_user` = '".addslashes($from_name)."'";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		if($result)
			return $result[0];
		return array();
	}
}