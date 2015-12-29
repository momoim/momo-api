<?php 
/**
* [移动SNS网站] (C) 1999-2009 ND Inc.
* 群模块模型类
**/
defined('SYSPATH') or die('No direct script access.');

class Group_Model extends Model {
	public static $instances = null;
    public function __construct() {
        // 加载数据库类。以下可以使用 $this->db 操作数据库（如果不要求可以省略）
        parent::__construct();
	}
	
	public static function &instance() {
		if (! is_object ( Group_Model::$instances )) {
			// Create a new instance
			Group_Model::$instances = new Group_Model ();
		}
		return Group_Model::$instances;
	}
	
	//获取用户加入的所有群组id
	public function getUserAllGroupId($uid){
		$query = $this->db->fetchData('group_member', 'gid', array('uid' => $uid), array('join_time' => 'DESC'));
		if ($query->count() == 0) {
			return array();
		}
		return $query->result_array(FALSE);
	}
	
	//获取用户所有群组id、名称
	public function getUserAllGroup($uid, $type = 0){
		$query = $this->db->query("SELECT g.* FROM `group` g LEFT JOIN group_member gm ON gm.gid=g.gid WHERE gm.uid=".$uid." ORDER BY gm.join_time DESC");
		$groupList = $query->result_array(FALSE);
		return $groupList;
	}
	
	//获取用户所有群组id、名称
	public function getUserGroupIdList($uid, $type = 0){
		$query = $this->db->query("select a.gid from `group_member` a left join `group` b on a.gid = b.gid where a.uid = $uid AND b.type = $type");
		return $query->result_array(FALSE);
	}

	public function getUserGroupCount($uid){
		$query = $this->db->query("SELECT COUNT(*) num FROM group_member WHERE uid = $uid");
		$result = $query->result_array(FALSE);
		return $result[O]['num'];
	}

	//获取群管理员数量
	public function getGroupManagerNum($gid){
		return $this->db->getCount('group_member', "gid=$gid AND grade=2");
	}
	
	//获取公司小组
	public function getCompanyGroupList($uid, $id = 0, $isMember = 1, $start = 0, $pos = 10){
		$type = Kohana::config('group.groupType.company');
		$gidsArray = $this->getUserAllGroupId($uid);
		if(!$gidsArray){
			return array('count'=>0,'data'=>array());
		}
		$groupList = array();
		$gids = ''; 
		$index = 0;
		foreach($gidsArray as $value){
			if(!$index){
				$gids .= $value['gid'];
			}else{
				$gids = $gids.', '.$value['gid'];
			}
			$index++;
		}
		$limitFilter = "";
		if($pos){
			$limitFilter .="LIMIT $start, $pos";
		}
		if($isMember){
			$query = $this->db->query("SELECT gid,gname,creator_id,introduction,member_number FROM `group` WHERE type = $type AND id=$id AND verify=1 AND gid IN ($gids) ORDER BY gid DESC $limitFilter");
			$num = $this->db->query("SELECT COUNT(gid) as num FROM `group` WHERE type = $type AND id=$id AND verify=1 AND gid IN ($gids)");
		}else{
			$query = $this->db->query("SELECT gid,gname,creator_id,introduction,member_number FROM `group` WHERE type = $type AND id=$id AND verify=1 AND view=1 AND gid NOT IN ($gids) ORDER BY gid DESC $limitFilter");
			$num =  $this->db->query("SELECT COUNT(gid) as num FROM `group` WHERE type = $type AND id=$id AND verify=1 AND view=1 AND gid NOT IN ($gids)");
		}
		$count = $num->result_array(FALSE);
		if($query->count() == 0) {
			return array('count'=>$count[0]['num'], 'data'=>array());
		}
		$result = $query->result_array(FALSE);
		return array('count'=>$count[0]['num'], 'data'=>$result);
	}

	public function getFriendGroupList($fids, $cid, $type, $start = 0, $pos = 3){
		if(!$fids){
			return array('count'=>0, 'data'=>array());
		}
		$query = $this->db->query("SELECT gid FROM group_member WHERE uid in ($fids)");
		if($query->count() == 0) {
			return array('count'=>0, 'data'=>array());
		}
		$result = $query->result_array(FALSE);
		$gids = '';
		if($result){
			$index = 0;
			foreach($result as $key => $value){
				$grade = $this->getmembergrade($value['gid'], $this->uid);
				if($grade > 0){
					continue;
				}
				if($index){
					$gids .= ",".$value['gid'];
				}else{
					$gids .= $value['gid'];
				}
				$index++;
			}
		}
		unset($query);
		unset($result);
		if(!$gids){
			return array('count'=>0, 'data'=>array());
		}
		$num = $this->db->query("SELECT COUNT(gid) AS num FROM `group` WHERE type=$type AND id=$cid AND view=1 AND verify=1 AND gid in ($gids)");
		$query = $this->db->query("SELECT gid,gname,creator_id,introduction FROM `group` WHERE type=$type AND id=$cid AND view=1 AND verify=1 AND gid in ($gids) ORDER BY gid DESC LIMIT $start, $pos");
		$count = $num->result_array(FALSE);
		if($query->count() == 0) {
			return array('count'=>0, 'data'=>array());
		}
		$result = $query->result_array(FALSE);
		return array('count'=>$count[0]['num'], 'data'=>$result);
	}
	
	//根据群组名称查询群组
	public function searchCompanyGroup($id, $keyword, $isMember = 0, $start = 0, $pos = 10){
		$type = Kohana::config('group.groupType.company');
		if(!$keyword){
			return array('count'=>0, 'data'=>array());
		}
		if($isMember == 2){
			$query = $this->db->query("SELECT gid,gname,creator_id,introduction,member_number FROM `group` WHERE type = $type AND id=$id AND verify=1 AND view=1 AND gname LIKE '%$keyword%' ORDER BY gid DESC LIMIT $start, $pos");
			$num = $this->db->query("SELECT COUNT(gid) AS num FROM `group` WHERE type = $type AND id=$id AND verify=1 AND view=1 AND gname LIKE '%$keyword%' ORDER BY gid DESC");
		}else{
			$result = $this->getCompanyGroupList($this->uid, $id, $isMember, 0, 0);
			$gids = '';
			if($result['data']){
				foreach($result['data'] as $key=>$value){
					if(!$key){
						$gids .= $value['gid'];
					}else{
						$gids .= ','.$value['gid'];
					}
				}	
			}
			$filter = '';
			if($isMember == 0){
				$filter = "AND view = 1";
			}
			if(!$gids){
				$query = $this->db->query("SELECT gid,gname,creator_id,introduction,member_number FROM `group` WHERE type = $type AND id=$id AND verify=1 $filter AND gname LIKE '%$keyword%' ORDER BY gid DESC LIMIT $start, $pos");
				$num = $this->db->query("SELECT COUNT(gid) AS num FROM `group` WHERE type = $type AND id=$id AND verify=1 $filter AND gname LIKE '%$keyword%' ORDER BY gid DESC");
			}else{
				$filter .= " AND gid IN ($gids)";
				$query = $this->db->query("SELECT gid,gname,creator_id,introduction,member_number FROM `group` WHERE type = $type AND id=$id AND verify=1 $filter AND gname LIKE '%$keyword%' ORDER BY gid DESC LIMIT $start, $pos");
				$num = $this->db->query("SELECT COUNT(gid) AS num FROM `group` WHERE type = $type AND id=$id AND verify=1 $filter AND gname LIKE '%$keyword%' ORDER BY gid DESC");
			}
		}
		$count = $num->result_array(FALSE);
		if($query->count() == 0) {
			return array('count'=>$count[0]['num'], 'data'=>array());
		}
		$result = $query->result_array(FALSE);
		return array('count'=>$count[0]['num'], 'data'=>$result);
	}

	//获取群组列表
	public function getGroupList($id = 0, $type = 0, $start = 0, $pos = 0){
		if(!$pos){
			$query = $this->db->fetchData('group', '*', array('type' => $type, 'id' => $id, 'verify' => 1, 'view' => 1), array('create_time'=>'DESC'));
		}else{
			$query = $this->db->fetchData('group', '*', array('type' => $type, 'id' => $id, 'verify' => 1, 'view' => 1), array('create_time'=>'DESC'), $pos, $start);
		}
		if ($query->count() == 0) {
			return array();
		}
		return $query->result_array(FALSE);
	}
	
	//获取热门群组
	public function getHotGroupList($id = 0, $type = 0, $isMember = false, $start = 0, $pos = 10){
		if($isMember){
			$query = $this->db->fetchData('group', 'gid,gname,creator_id', array('type' => $type, 'id' => $id, 'verify' => 1, 'view' => 1), array('member_number'=>'DESC'), $pos, $start);
		}else{
			$gidsArray = $this->getUserAllGroupId($this->uid);
			if(!$gidsArray){
				$query = $this->db->fetchData('group', 'gid,gname,creator_id', array('type' => $type, 'id' => $id, 'verify' => 1, 'view' => 1), array('member_number'=>'DESC'), $pos, $start);
			}else{
				$gids = '';
				foreach($gidsArray as $key=>$value){
					if(!$key){
						$gids .= $value['gid'];
					}else{
						$gids .= ','.$value['gid'];
					}
				}
				$query = $this->db->query("SELECT gid,gname,creator_id FROM `group` WHERE type=$type AND id=$id AND verify=1 AND view=1 AND gid NOT IN($gids) ORDER BY member_number DESC LIMIT $start, $pos");
			}
		}
		
		return $query->result_array(FALSE);
	}
	
	//获取待审核班级
	public function getVerifyClass($type = 1, $start = 0, $pos = 0, $uid = 0){
		$filterArray = array('type' => 1, 'school_type' => $type, 'verify' => 0);
		if($uid){
			$filterArray['creator_id'] = $uid;
		}
		$query = $this->db->fetchData('group', '*', $filterArray, array('create_time'=>'DESC'), $pos, $start);
		if ($query->count() == 0) {
			return array();
		}
		return $query->result_array(FALSE);
	}
	
	//获取待审核班级总数
	public function getVerifyClassTotal($type = 1, $uid = 0){
		$filter = "type=1 AND school_type=$type AND verify=0";
		if($uid){
			$filter .= " AND creator_id=$uid";
		}
		return $this->db->getCount('`group`', $filter);
	}
	
	//审核通过班级
	public function verifyClass($gid){
		return $this->db->updateData('group', array('verify'=>1), "gid=$gid");
	}
	
	//审核拒绝班级创建申请
	public function verifyDenyClass($gid){
		$result = $this->db->deleteData('group', array('gid'=>$gid, 'verify'=>0));
		if(!$result){
			return false;
		}
		return $this->db->deleteData('group_member',array('gid'=>$gid));
	}

	//获取群组总数
	public function getGroupTotal($id = 0, $type = 0){
		return $this->db->getCount('`group`', "id=$id AND type=$type AND verify=1 AND view=1");
	}

	// 通过gid获取群组
	public function getGroupInfo($gid) {
		$query = $this->db->fetchData('group', '*', array('gid' => $gid));
		if ($query->count() == 0) {
			return false;
		}
		$result = $query->result_array(FALSE);
		return $result[0];
	}

	// 创建群组
	public function add($group_info) {
		return $this->db->insertData('group', $group_info);
	}
	
	//更新用户所有群组的修改时间（专供用户头像修改时，手机端同步下来）
	public function updateGroupModifytime($uid){
		$gidList = $this->getUserAllGroupId($uid);
		if($gidList){
			$dateline = time();
			$ginfo['modify_time'] = $dateline;
			foreach($gidList as $value){
				$gid = $value['gid'];
				$group_info = $this->getGroupInfo($gid);
				$this->modifyGroup($gid, $ginfo);
			}
		}
	}

	// 修改群组
	public function modifyGroup($gid, $group_info) {
		return $this->db->updateData('group', $group_info, "gid=$gid");
	}

	// 删除群组
	public function delete($gid) {
            $del = $this->db->deleteData('group',array('gid'=>$gid));
            if($del){
                    $del = $this->db->deleteData('group_member',array('gid'=>$gid));
            }
            return $del;
	}

	// 更新群组
	public function update($gid, $group_info) {
		$group_info_update = array();
		if(isset($group_info['name']) && !empty($group_info['name'])) {
			$group_info_update['gname'] = $group_info['name'];
		}
		if(isset($group_info['notice']) && !empty($group_info['notice'])) {
			$group_info_update['notice'] = $group_info['notice'];
		}
		if(isset($group_info['introduction']) && !empty($group_info['introduction'])) {
			$group_info_update['introduction'] = $group_info['introduction'];
		}
		if(isset($group_info['modify_time']) && !empty($group_info['modify_time'])) {
			$group_info_update['modify_time'] = $group_info['modify_time'];
		}
		if(count($group_info_update) > 0 )
			return $this->db->updateData('group', $group_info_update, "gid=$gid");
		return true;
	}

	// 获取群成员
	public function getGroupMember($gid, $start =0, $pos = 50) {	
		$query = $this->db->query("SELECT gm.gid,gm.uid,gm.grade,gm.join_time,m.zone_code,m.mobile FROM group_member gm LEFT JOIN members m ON gm.uid = m.uid WHERE gm.gid = $gid ORDER BY gm.join_time DESC");
		if ($query->count() == 0) {
			return array();
		}
		return $query->result_array(FALSE);
	}
	
	//获取群组的管理员列表
    public function getGroupManager($gid) {
        $query = $this->db->query("SELECT uid FROM `group_member` WHERE gid = $gid AND grade > 1");
        if ($query->count() == 0) {
            return array();
        }
        return $query->result_array(FALSE);
    }
	
	// 获取群所有成员
	public function getGroupAllMember($gid) {
		$query = $this->db->query("SELECT a.gid, a.uid, a.grade, b.mobile, b.realname FROM group_member a LEFT JOIN membersinfo b ON a.uid = b.uid WHERE a.gid = $gid ORDER BY a.grade DESC, a.join_time DESC");
		if ($query->count() == 0) {
			return array();
		}
		return $query->result_array(FALSE);
	}
    
	// 群成员数量加1
	public function addMemberNum($gid) {
		return $this->db->query("UPDATE `group` SET member_number=member_number+1 WHERE gid=$gid");
	}

	// 群成员数量减1
	public function reduceMemberNum($gid) {
		return $this->db->query("UPDATE `group` SET member_number=member_number-1 WHERE gid=$gid");
	}
	
	// 获取群组成员
	public function getGroupMemberNum($gid) {
		return $this->db->getCount('group_member', "gid=$gid");
	}

	// 获取成员权限
	public function getMemberGrade($gid, $uid) {
		$query = $this->db->fetchData('group_member', 'grade', array('gid' => $gid, 'uid' => $uid));
		if ($query->count() == 0) {
			return 0;
		}
		$result = $query->result_array(FALSE);
		return $result[0]['grade'];
	}

	//修改公司小组在公司首页的可见状态
	public function setGroupView($gid, $view){
		return $this->db->updateData('group', array('view' => $view), "gid=$gid");
	}

	// 修改群组成员的权限
	public function modifyGroupMember($gid, $uid, $grade) {
		return $this->db->updateData('group_member', array('grade' => $grade), "gid=$gid AND uid=$uid");
	}

	// 加入群
	public function addGroupMember($gid, $uid, $grade) {
		return $this->db->query("REPLACE INTO group_member VALUE ($gid, $uid, $grade, ".time().")");
	}

	// 退出群
	public function delGroupMember($gid, $uid) {
		return $this->db->deleteData('group_member',array('gid'=>$gid,'uid'=>$uid));
	}
	
	//获取用户创建的群组数量
	public function getCreateGroupNum($uid){
		return $this->db->getCount("group_member", "uid=$uid AND grade=3");
	}

	
	//获取用户管理的群,0普通群组，1学校班级，2公司小组，3全部，其他错误
	public function getManageGroupId($uid) {
		$query = $this->db->query("SELECT gid FROM group_member WHERE uid=$uid $filter AND grade > 1");
		if ($query->count() == 0) {
			return array();
		}
		return $query->result_array(FALSE);
	}
	
	//获取用户加入的群
	public function getJoinGroupId($uid) {
		$query = $this->db->query("SELECT gid FROM group_member WHERE uid=$uid AND grade = 1");
		if ($query->count() == 0) {
			return array();
		}
		return $query->result_array(FALSE);
	}

	//获取用户申请加入群的记录
	public function getUserApplyGroup($gid, $uid){
		$query = $this->db->fetchData('group_apply', 'status, manager_uid, reason', array('gid'=>$gid, 'uid'=>$uid));
		if ($query->count() == 0) {
			return false;
		}
		$result = $query->result_array(FALSE);
		return $result[0];
	}

	// 获取申请加入群的用户
	public function getApplyMember($gid, $start = 0, $pos = 0) {
		$query = $this->db->fetchData('group_apply', '*', array('gid'=>$gid), array('time'=>'ASC'));
		if ($query->count() == 0) {
			return false;
		}
		return $query->result_array(FALSE);
	}

	// 新增申请加入群的用户
	public function addApplyMember($gid, $uid, $reason) {
		$query = $this->db->fetchData('group_apply', '*', array('gid'=>$gid, 'uid'=>$uid));
		if($query->count() == 0){
			$result = $this->db->insert('group_apply', array('gid' => $gid, 'uid' => $uid, 'reason' => $reason, 'time' => time()));
		} else {
			$result = $this->db->updateData('group_apply', array('reason'=>$reason,'time'=>time(),'status'=>0,'manager_uid'=>0), "gid=$gid AND uid=$uid");
		}
		return $result;
	}
	
	//管理员处理群申请，设置申请记录状态为已处理
	public function dealApplyMember($gid, $uid, $manager_uid){
		return $this->db->updateData('group_apply', array('status' => 1, 'manager_uid' => $manager_uid), "gid=$gid AND uid=$uid");
	}

	// 获取群管理员列表
	public function getManagerId($gid) {
		$query = $this->db->query("SELECT uid FROM group_member WHERE gid=$gid AND grade > 1 ORDER BY grade DESC");
		if ($query->count() == 0) {
			return array();
		}
		return $query->result_array(FALSE);
	}

	// 获取申请加入群的人数
	public function getTobeJoinNum($gid){
		return $this->db->getCount('group_apply', "gid = $gid");
	}

	// 用户是否已经被邀请加入群
	public function isMemberInvited($gid, $uid) {
		$ret = $this->db->getCount('group_invite',"gid = $gid AND uid = $uid");
		return ($ret > 0) ? true : false;
	}

	//获取用户被邀请加入群组记录
	public function getMemberInvite($gid, $uid) {
		$ret = $this->db->query("SELECT muid FROM group_invite WHERE gid = $gid AND uid = $uid");
		if (!$ret) {
			return false;
		}
		return $ret->result_array(FALSE);
	}

	public function deleteMemberInvite($gid, $uid) {
		$ret = $this->db->delete('group_invite', array('gid'=>$gid, 'uid'=>$uid));
		return count($ret);
	}

	// 用户是否已经申请加入群
	public function isMemberApply($gid, $uid) {
		$ret = $this->db->getCount('group_apply', '*', array('gid'=>$gid, 'uid'=>$uid));
		return ($ret > 0) ? true : false;
	}
	
	//添加到邀请表中
	public function addInviteMember($gid, $fid, $uid){
		$ret = $this->db->query("REPLACE INTO group_invite VALUE($gid, $fid, $uid)");
		if (!$ret) {
			return false;
		}
		else{
			return true;
		}
	}
	
	//搜索班级
	public function searchClass($school_id, $startYear, $keyword, $start, $pos){
		if(!$startYear && !$keyword){
			return false;
		}
		$filter = "id=$school_id AND verify = 1 AND type = ".Kohana::config('group.groupType.school');
		if(!$startYear){
			$filter .= " AND gname LIKE  '%".$keyword."%'";
		}elseif(!$keyword){
			$filter .= " AND start_year = $startYear";
		}else{
			$filter .= " AND start_year = $startYear AND gname LIKE '%".$keyword."%'";
		}
		$query = $this->db->query("SELECT gid, gname, start_year, introduction, creator_id, member_number FROM `group` WHERE $filter LIMIT $start, $pos");
		if($query->count() == 0) {
			return false;
		}
		return $query->result_array(FALSE);
	}

	//搜索班级总数
	public function searchClassTotal($school_id, $startYear, $keyword){
		if(!$startYear && !$keyword){
			return 0;
		}
		$filter = "type = ".Kohana::config('group.groupType.school');
		$filter .= " AND id=$school_id";
		if(!$startYear){
			$filter .= " AND gname LIKE '%$keyword%'";
		}elseif(!$keyword){
			$filter .= " AND start_year = $startYear";
		}else{
			$filter .= " AND start_year = $startYear AND gname LIKE '%$keyword%'";
		}
		$query = $this->db->query("SELECT count(gid) as num FROM `group` WHERE $filter");
		if ($query->count()) {
            $result = $query->result_array(FALSE);
            return $result[0]['num'];
        }
        return 0;
	}

    private function _transTime($time) {
        $year = substr($time, 0, 4);
        $month = substr($time, 4, 2);
        $day = substr($time, 6, 2);
        $hour = substr($time, 8, 2);
        $minute = substr($time, 10, 2);
        $second = substr($time, 12, 2);
        return sns::gettime(mktime($hour, $minute, $second, $month, $day, $year));
    }

	//搜索群组
	public function search($keyword, $start, $pos, $type = 0){
		$query = $this->db->query("SELECT * FROM `group` WHERE type = $type AND gname like '%".$keyword."%' LIMIT $start, $pos");
		if($query->count() == 0){
			return false;
		}
		return $query->result_array(FALSE);
	}
	
	//搜索群组总数
	public function getSearchTotal($keyword, $type = 0){
		$query = $this->db->query("SELECT COUNT(*) num FROM `group` WHERE type = $type AND gname like '%".$keyword."%'");
		$result = $query->result_array(FALSE);
		return $result[0]['num'];
	}

	//群组照片缩略图验证
    public function url_validate($pid, $thumb_type)
    {
        if ($thumb_type != '80' && $thumb_type != '160' && $thumb_type != '320' && $thumb_type != '480' && $thumb_type != '780' && $thumb_type != '1024' && $thumb_type != '1600') {
            return 0;
        }
        $album_id = 0;
        $data = array();
        $result = $this->db->getRow("album_group_pic", "album_id, file_md5, group_id, file_type, is_animate" ,"pic_id =$pid  AND status >= '0'");
        if($result) {
            $data['album_id'] = $result['album_id'];
            $data['file_md5'] = $result['file_md5'];
            $data['group_id'] = $result['group_id'];
			$data['file_type'] = $result['file_type']; 
            $data['is_animate'] = $result['is_animate']; 
        }
        return $data;
    }

	/**
     * 获取群组相册的隐私等级及群组ID等信息
     * @param int $album_id
     */
    public function check_group_album_privacy($album_id)
    {
        $data = array();
        $result = $this->db->getRow("album_group_album","user_id, group_id, privacy_lev, album_name", "album_id=$album_id"); 
        if ($result) {
            $data['group_id'] = $result['group_id'];
            $data['privacy_lev'] = $result['privacy_lev'];
            $data['user_id'] = $result['user_id'];
			$data['album_name'] = $result['album_name'];
        } 
        return $data;
    }

    //群组照片缩略图输出信息
    public function getThumbfsname($pid, $type, $is_animate = false)
    {
        $data = array();
        $result = $this->db->getRow("album_group_pic", "create_time,file_md5, pic_width, pic_height", "pic_id = '$pid'");
        if($result) {           
            $data['create_time'] = $result['create_time'];
            $data['file_md5'] = $result['file_md5'];
            $data['pic_width'] = $result['pic_width'];
            $data['pic_height'] = $result['pic_height'];
        }
        $file_md5 = $data['file_md5'];
        $this->TT = new TTServer;
        $file_content = $this->TT->get($file_md5);

        if($file_content['pic_fs_path']) {
            $data['pic_fs_path'] =$file_content['pic_fs_path'];
        } else {
            return 0;
        }
        $fs_name = $data['pic_fs_path'];
        $pic_width = $data['pic_width'];
        $pic_height = $data['pic_height'];

        if ($fs_name) {
            $fsfile_name_array = explode('.', $fs_name);
            $fsfile_name = $fsfile_name_array[0];
            $fsfile_type = $fsfile_name_array[1];

			if($is_animate && $type == '80') $type = '160';

            $thumb_fs_path = $fsfile_name_array[0] . '_' . $type . '.' . $fsfile_name_array[1];
            $thumb_1600_fs_path = $fsfile_name_array[0] . '_1600.' . $fsfile_name_array[1];

            //过期时间
            $year = date("Y", $data['create_time']) + 1;
            $month = date("m", $data['create_time']);
            $day = date("d", $data['create_time']);
            $hour = date("H", $data['create_time']);
            $minute = date("i", $data['create_time']);
            $second = date("s", $data['create_time']);
            $s = mktime($hour, $minute, $second, $month, $day, $year);
            $expires = date("D, d M Y H:i:s ", $s) . 'GMT';
            $last_modify = date("D, d M Y H:i:s ", $data['create_time']) . 'GMT';
            $max_age = $s - $data['create_time'];
            $return = array('thumb_fs_path' => $thumb_fs_path,
                'file_fs_name' => $fs_name,
                'file_suffix' => $fsfile_name_array[1],
                'thumb_1600_fs_path' => $thumb_1600_fs_path,
                'last_modify' => $last_modify,
                'expires' => $expires,
                'max_age' => $max_age,
                'file_md5' => $data['file_md5']);
            $result = $this->check_thumb_type($pid, $type, $is_animate);
            if(!$result) $return['thumb_fs_path'] = $fs_name;
            return $return;
        } else {
            return 0;
        }
    }

	
    /**
     * 判断是否要生成新的缩略图
     * @param int
     */
    public function check_thumb_type($pic_id, $thumb_type, $is_animate = false)
    {
        if ($thumb_type != '80' && $thumb_type != '160' && $thumb_type != '320' && $thumb_type != '480' && $thumb_type != '780' && $thumb_type != '1024' && $thumb_type != '1600') {
            return false;
        }
		if($is_animate && $thumb_type != '160') return false;

        $width = 0;
        $height = 0;
        $file_md5 = '';
        $result = $this->db->getRow( "album_group_pic","file_md5, pic_width, pic_height"," pic_id = '$pic_id'"); 
        if ($result ) {
            $width = $result['pic_width'];
            $height = $result['pic_height'];
            $file_md5 = $result['file_md5'];
        }

        if(!$height || !$width) {
            //album pic 表中没有宽高记录、查询TT
            $this->TT = new TTServer;
            $result = $this->TT->get($file_md5);
            $data = unserialize($result['exif']);
            $height = $data['拍摄分辨率高'];
            $width = $data['拍摄分辨率宽'];
            if($width && $height) {
                //更新图片的宽度和高度
                $update = array('pic_width' => $width, 'pic_height' => $height);
                $this->db->updateDdata("album_group_pic", $update, array('pic_id' => $pid));
            }
        }

        if ($width >= $thumb_type || $height >= $thumb_type) {
            return true;
        } else {
            return false;
        }
    }

	/**
     * 获取图片md5，user_id
     * @param int $pic_id 图片ID
     */
    public function get_pic_info($pic_id)
    {
        $info = array();
        $result = $this->db->getRow( "album_group_pic","file_md5, user_id, pic_width, pic_height"," pic_id = '$pic_id'");   
        if ($result) {
            $info['file_md5'] = $result['file_md5'];
            $info['user_id'] = $result['user_id'];
            $info['pic_width'] = $result['pic_width'];
            $info['pic_height'] = $result['pic_height'];
        }

        return $info;
    }


	/**
     * 数据过滤
     * @param mixed $data 数据
     * @return mixed
     */
	private function _filter($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->db->escape($value);
            }
            return $data;
        } else {
            return $this->db->escape($data);
        }
    }

	
	 /**
     * 获取单张相册信息
     * @param int $album_id
     * @return array
     */
    public function getAlbumInfoByAid($album_id) {
        $result = $this->db->getRow("album_group_album", "group_id, user_id, album_name, album_spot, album_desc, privacy_lev, album_default", "album_id=$album_id");
        if($result) {
            $data = array(
                'album_id' => $album_id,
                'album_name' => $result['album_name'],
                'group_id' => $result['group_id'],
                'user_id' => $result['user_id'],
                'creator_id' => $result['user_id'],
                'album_spot' => $result['album_spot'],
                'album_desc' => $result['album_desc'],
                'privacy_lev' => $result['privacy_lev'], 
                'album_default' => $result['album_default']  
            );
            return array("code" => 200, "data" => $data);
        } else {
            return array("code" => 404, "data" => array("msg" => ""));
        } 
    }
	/**
     * 获取图片所在相册的位置（第几张）、相册总的照片数量
     * @param int $photo_id
     */
    public function get_photo_index_total($photo_id, $album_id=0) { 
        $i = 0;
        $index = 0;
		if(!$album_id) $album_id = $this->db->getOne("album_group_pic","album_id","pic_id=$photo_id");
        $result = $this->db->getAll("album_group_pic","pic_id","album_id = '$album_id' ORDER BY pic_id DESC"); 
        foreach ($result as $key => $value) {
            if($photo_id && $photo_id == $value['pic_id']) $index = $key;
            $i++;
        }
        return array("code" => 200 , "data" => array('index' => $index, 'total' => $i)); 
    }

	//获取群组相册中照片的上一张或下一张的链接
	public function getGroupPOrNphoto($pic_id, $album_id,$flag) {
		if(!$album_id) {
			$album_id = $this->db->getOne("album_group_pic", "album_id", "pic_id=$pic_id");
		} 
		if(!$album_id) return null;
		//获取排序
		$result = $this->db->getAll("album_group_pic", "pic_id", "album_id=$album_id ORDER BY pic_id DESC");
		if($result) {
			foreach($result as $key => $value) {
				if($key == 0) $pic_sorts = $value['pic_id'];
				else $pic_sorts .= ','.$value['pic_id'];
			}
		}
		$sorts = explode(",", $pic_sorts);
		$total = count($sorts);
		if($total == 1) {
			$pic_title = $this->db->getOne("album_group_pic", "pic_title", "pic_id=$pic_id");
			return array("pic_id" => $pic_id, "pic_title" => $pic_title, "pic_url" => Kohana::config('album.thumb'). 'groupthumb/' . $pic_id."_780.jpg", "total" => $total, "index" => 1);
		}
		$index = 0;
		foreach($sorts as $key => $row) {
			if($row == $pic_id) {
				$index = $key;
			}
		}
		if($flag == 'prev') {
				if($index == 0) $pic_id = $sorts[$total-1];
				else $pic_id = $sorts[$index-1];
		} else {
				if($index == $total-1) $pic_id = $sorts[0];
				else $pic_id = $sorts[$index+1];
		}

		$pic_title = $this->db->getOne("album_group_pic", "pic_title", "pic_id=$pic_id");
		return array("pic_id" => $pic_id, "pic_title" => $pic_title, "pic_url" => Kohana::config('album.thumb'). 'groupthumb/' . $pic_id."_780.jpg", "total" => $total, "index" => $index+1);

	}

	public function updateTab() {
		$sql = "SELECT * FROM group_member ORDER BY gid DESC";
		$query = $this->db->query($sql);
		$groupList = $query->result_array(FALSE);
		foreach($groupList as $var) {
			$sql = "SELECT * FROM tab WHERE id=".$var['gid']." AND type_id=1 AND uid=".$var['uid'];
			$query = $this->db->query($sql);
			$result = $query->result_array(FALSE);
			if($result && $result[0]['id']) {
				$update_num++;
				$sql = "UPDATE tab SET last_modify=".$var['join_time']." WHERE id=".$var['gid']." AND uid=".$var['uid'];
				$query = $this->db->query($sql);
			} else {
				$insert_num++;
				$sql = "INSERT INTO tab (`uid`, `type_id`, `id`, `is_show`, `index`, `last_modify`) VALUES (".$var['uid'].",1,".$var['gid'].",1,1,".time().")";
				$this->db->query($sql);
			}
		}
		return array('update_num'=>$update_num,'insert_num'=>$insert_num);
	}
	
	public function updateNum() {
		$sql = "SELECT * FROM `group` ORDER BY gid DESC";
		$query = $this->db->query($sql);
		$groupList = $query->result_array(FALSE);
		foreach($groupList as $var) {
			$sql = "SELECT count(*) as total FROM group_member WHERE gid=".$var['gid'];
			$query = $this->db->query($sql);
			$result = $query->result_array(FALSE);
			if($var['member_number'] != $result[0]['total']) {
				$sql = "UPDATE `group` SET member_number=".$result[0]['total']." WHERE gid=".$var['gid'];
				$query = $this->db->query($sql);
				$update_num++;
			} 
		}
		return array('update_num'=>$update_num);
	}
}
