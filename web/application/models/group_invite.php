<?php defined('SYSPATH') or die('No direct access allowed.');
/*
 * [UAP Portal] (C)1999-2009 ND Inc.
 * 群邀请注册模块
 */

class Group_Invite_Model extends Model {
	public static $instances = null;

    public function __construct()
    {
        // 加载数据库类。以下可以使用 $this->db 操作数据库（如果不要求可以省略）
        parent::__construct();
    }
	
	public static function &instance() {
		if (! is_object ( Group_Invite_Model::$instances )) {
			// Create a new instance
			Group_Invite_Model::$instances = new Group_Invite_Model ();
		}
		return Group_Invite_Model::$instances;
	}

    public function add($invite_name,$invite_uid,$invite_code,$gid)
    {
        $data = array('invite_code'=>$invite_code,'status'=>0,'invite_uid'=>$invite_uid,'invite_realname'=>$invite_name, 'gid'=>$gid, 'invite_time'=>time());
        return $this->db->insertData('group_invite_register', $data);
    }

	public function getInviteCode($gid, $uid){
		//$nowtime = time();
		//$invite_limit_time = Kohana::config('group.invite_limit_time');
		$query = $this->db->query("SELECT invite_code FROM group_invite_register WHERE gid = $gid AND invite_uid = $uid");
		if ($query->count() == 0) {
			return 0;
		}
		$result = $query->result_array(FALSE);
		return $result[0]['invite_code'];
	}

	//获取群组邀请信息
	public function getInvitationInfo($invite_code) {
        $info_arr = array();
        $where = "invite_code= '{$invite_code}'";
        $query = $this->db->query("SELECT id,status,invite_uid,invite_realname,gid,invite_time FROM group_invite_register WHERE $where limit 1");
        if ($query->count() == 0) {
			return null;
		}
		$result = $query->result_array(FALSE);
        return $result[0];
    }
}

