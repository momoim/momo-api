<?php
class Cs_Apns_Model extends Model {
	
	public $error_msg = '';
	public static $instances = null;
	
	public function __construct() {
		parent::__construct ();
	}

	public function subscribe($uid, $guid, $token)
	{
		$id_t = $this->db->escape($uid?''.$uid:$guid);
		$type = $uid?1:0;
		$token_t = $this->db->escape($token);
		
		$sql = "REPLACE INTO cs_apns_device (cs_id,type, device_token) VALUES ($id_t,$type, $token_t)";
		$this->db->query($sql);
		
		return array('result'=>200,"msg"=>NULL);
	}
	
	public function unsubscribe($uid, $guid, $token)
	{
		$id = $this->db->escape($uid?''.$uid:$guid);
		$type = $uid?1:0;
		$token_t = $this->db->escape($token);
		$sql = "DELETE FROM cs_apns_device WHERE cs_id = $id AND type = $type";
		$this->db->query($sql);
	}
}