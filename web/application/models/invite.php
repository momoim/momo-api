<?php defined('SYSPATH') or die('No direct access allowed.');
/*
 * [UAP Portal] (C)1999-2009 ND Inc.
 * 投票模块模型
 */

class Invite_Model extends Model {

    public function __construct()
    {
        // 加载数据库类。以下可以使用 $this->db 操作数据库（如果不要求可以省略）
        parent::__construct();
    }


    public function add($invite_name,$invite_uid,$invite_code,$realname,$mobile, $gid = 0)
    {
        $data = array('invite_code'=>$invite_code,'status'=>0,'reg_date'=>0,'invite_uid'=>$invite_uid,'invite_realname'=>$invite_name,'realname'=>$realname,'mobile_check'=>md5($mobile),'uid'=>0,'is_mobile_fit'=>0, 'group_id'=>$gid);
        return $this->db->insertData('invitation', $data);
    }

    public function lists($own_uid, $gid=0) {
        $where = "invite_uid = " . $own_uid . " AND group_id = $gid order by id desc ";
        $data = $this->db->getAll('invitation', 'invite_code,status,reg_date,realname', $where);
        return $data;
    }
    
}

