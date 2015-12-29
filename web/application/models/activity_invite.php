<?php defined('SYSPATH') or die('No direct access allowed.');
/*
 * 活动邀请注册模块
 */

class Activity_Invite_Model extends Model {
	public static $instances = null;

    public function __construct()
    {
        // 加载数据库类。以下可以使用 $this->db 操作数据库（如果不要求可以省略）
        parent::__construct();
    }
	
	public static function &instance() {
		if (! is_object ( Activity_Invite_Model::$instances )) {
			// Create a new instance
			Activity_Invite_Model::$instances = new Activity_Invite_Model ();
		}
		return Activity_Invite_Model::$instances;
	}

    public function add($aid, $invite_uid,$invite_code) {
        $data = array('invite_code'=>$invite_code,'invite_uid'=>$invite_uid, 'aid'=>$aid);
        return $this->db->insertData('action_invite_register', $data);;
    }

	public function getInviteCode($aid, $uid) {
		$query = $this->db->fetchData('action_invite_register', 'invite_code', array('aid'=>$aid , 'invite_uid'=>$uid));
		if($query->count() == 0){
			return 0;
		} else {
			$result = $query->result_array(FALSE);
			return $result[0]['invite_code'];
		}
	}

	//获取活动邀请信息
	public function getInvitationInfo($invite_code) {
        $where = "invite_code= '{$invite_code}'";
        $query = $this->db->query("SELECT invite_uid,aid FROM action_invite_register WHERE $where limit 1");
        if ($query->count() == 0) {
			return array();
		}
		$result = $query->result_array(FALSE);
        return $result[0];
    }
}


