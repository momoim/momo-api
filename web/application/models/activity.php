<?php defined('SYSPATH') or die('No direct script access.');
/**
 * [momo移动社区] (C)1999-2010 ND Inc.
 * 活动模型文件
 */

class Activity_Model extends Model {
	public static $instances = null;
    public function __construct() {
        parent::__construct();
		$this->uid = Session::instance()->get('uid');
    }
	
	public static function &instance() {
		if (! is_object ( Activity_Model::$instances )) {
			// Create a new instance
			Activity_Model::$instances = new Activity_Model ();
		}
		return Activity_Model::$instances;
	}
    
	/**
     * 获取单个活动信息
     * @param int $aid 活动ID
     * @return array
     */
	public function getActivityInfo($aid){
		$query = $this->db->fetchData('action', '*', array('aid' =>$aid));
		if ($query->count() == 0) {
			return array();
		}
		$result = $query->result_array(FALSE);
		return $result[0];
	}

    /**
     * 获取活动类型
     * @param int $aid 活动ID
     * @return int
     */
	public function getActivityType($aid){
		$query = $this->db->fetchData('action', 'type', array('aid' =>$aid));
		if ($query->count() == 0) {
			return false;
		}
		$result = $query->result_array(FALSE);
		return (int)$result[0]['type'];
	}

    /**
     * 获取我创建的活动列表
     * @param int $uid 用户ID
     * @param int $start
     * @param int $pos
     * @return array
     */
	public function getCreateActivityList($uid, $start = 0, $pos = 10, $end = 0){
		if($end == 0) {
			$nowTime = time();
			$where = "creator_id = $uid AND $nowTime < end_time";
		} else {
			$where = "creator_id = $uid";
		}
		$query = $this->db->query("SELECT * FROM action where $where ORDER BY aid DESC limit $start, $pos");
		if ($query->count() == 0) {
			return array();
		}
		return $query->result_array(FALSE);
	}
	
    /**
     * 获取我发起的活动数量
     * @param int $uid 用户ID
     * @return int
     */
	public function getCreateActivityNum($uid, $end = 0){
		if($end == 0) {
			$nowTime = time();
			return $this->db->getCount('action', "creator_id = $uid AND $nowTime < end_time");
		} else {
			return $this->db->getCount('action', "creator_id = $uid");
		}
	}

    /**
     * 获取我报名的所有活动列表
     * @param int $uid 用户ID
     * @param int $start
     * @param int $pos
     * @return array
     */
	public function getAllActivityList($uid, $start = 0, $pos = 10){
		$query = $this->db->query("SELECT b.aid,b.creator_id,b.title,b.start_time,b.end_time,b.spot,b.content,b.is_allow_invite,b.is_publish,b.type FROM action_member a LEFT JOIN action b ON a.aid = b.aid WHERE a.uid = $uid ORDER BY b.start_time DESC LIMIT $start, $pos");
		if ($query->count() == 0) {
			return array();
		}
		return $query->result_array(FALSE);
	}

    /**
     * 获取我报名的活动列表
     * @param int $uid 用户ID
     * @param int $apply_type 报名类型
     * @param int $start
     * @param int $pos
     * @return array
     */
	public function getActivityList($uid, $apply_type, $start = 0, $pos = 10, $end = 0){
		$limit = '';
		if($end == 0) {
			$nowTime = time();
			$where = "a.uid = $uid AND a.apply_type = $apply_type AND $nowTime < b.end_time";
		} else {
			$where = "a.uid = $uid AND a.apply_type = $apply_type";
		}
		if($pos > 0) {
			$limit .= "LIMIT $start, $pos";
		}
		$query = $this->db->query("SELECT b.aid,b.creator_id,b.title,b.start_time,b.end_time,b.spot,b.content,b.is_allow_invite,b.is_publish,b.type FROM action_member a LEFT JOIN action b ON a.aid = b.aid WHERE $where ORDER BY b.start_time DESC $limit");
		if ($query->count() == 0) {
			return array();
		}
		return $query->result_array(FALSE);
	}

    /**
     * 获取我报名的活动数量
     * @param int $uid 用户ID
     * @param int $apply_type 报名类型(0表示获取所有参与的活动)
     * @return int
     */
	public function getApplyActivityNum($uid, $apply_type = 0, $end = 0){
		$where = "uid = $uid";
		if($end == 0) {
			$nowTime = time();
			$where .= " AND $nowTime < end_time";
		}
		if($apply_type) {
			$where .= " AND apply_type = $apply_type";
		}
		$query = $this->db->query("SELECT count(a.aid) as num FROM action_member a LEFT JOIN action b ON a.aid = b.aid WHERE $where");
		$result = $query->result_array(FALSE);
		return intval($result[0]['num']);
		//return $this->db->getCount('action_member', $where);
	}

    /**
     * 获取活动关注数量（参加数量+感兴趣数量）
     * @param int $aid 活动ID
     * @param int $apply_type 报名类型(0表示获取所有参与的活动)
     * @return int
     */
	public function getFollowNum($aid) {
		$join = Kohana::config('activity.apply_type.join');
		$interest = Kohana::config('activity.apply_type.interest');
		return $this->db->getCount('action_member', "aid=$aid AND (apply_type=$join OR apply_type=$interest)");
	}

    /**
     * 获取好友发起的活动id列表
     * @param string $fids 好友id字符串，以,隔开
     * @return array
     */
	public function getFriendCreateAidList($fids, $end = 0){
		if($end == 0) {
			$nowTime = time();
			$where = "creator_id in ( $fids ) AND $nowTime < end_time";
		} else {
			$where = "creator_id in ( $fids )";
		}
		$query = $this->db->query("SELECT aid FROM action WHERE $where ORDER BY start_time DESC");
		if ($query->count() == 0) {
			return array();
		}
		return $query->result_array(FALSE);
	}

    /**
     * 获取好友报名参加和感兴趣的活动列表
     * @param string $fids 好友id字符串，以,隔开
     * @return array
     */
	public function getFriendApplyAidList($fids){
		$join = Kohana::config('activity.apply_type.join');
		$interest = Kohana::config('activity.apply_type.interest');
		$query = $this->db->query("SELECT DISTINCT a.aid FROM `action_member` a LEFT JOIN `action` b ON a.aid = b.aid WHERE (a.apply_type = $join OR a.apply_type = $interest) AND a.uid in ( $fids ) ORDER BY b.start_time DESC");
		$result = array();
		if ($query->count() == 0) {
			return array();
		}
		return $query->result_array(FALSE);
	}

    /**
     * 获取好友报名的活动列表
     * @param string $fids 好友id字符串，以,隔开
     * @param int $apply_type 报名类型
     * @return array
     */
	public function getFriendAidList($fids, $apply_type, $end = 0){
		if($end == 0) {
			$nowTime = time();
			$where = "a.apply_type = $apply_type AND a.uid in ( $fids ) AND $nowTime < b.end_time";
		} else {
			$where = "a.apply_type = $apply_type AND a.uid in ( $fids )";
		}
		$query = $this->db->query("SELECT DISTINCT a.aid FROM `action_member` a LEFT JOIN `action` b ON a.aid = b.aid WHERE $where ORDER BY b.start_time DESC");
		$result = array();
		if ($query->count() == 0) {
			return array();
		}
		return $query->result_array(FALSE);
	}

    /**
     * 获取群组所有活动
     * @param int $gid 群组id
     * @param int $start
     * @param int $pos
     * @return array
     */
	public function getGroupAidList($gid, $start = 0, $pos = 10){
		$query = $this->db->query("SELECT DISTINCT aid FROM action_group WHERE gid = $gid ORDER BY aid DESC LIMIT $start, $pos");
		if ($query->count() == 0) {
			return array();
		}
		return $query->result_array(FALSE);
	}

    /**
     * 判断我的好友是否有人报名(参加或者感兴趣)
	 * @param float $aid 活动id
     * @param string $fids 好友id字符串，以,隔开
     * @return Boolean
     */
	public function checkFriendsIsJoined($aid, $fids){
		$join = Kohana::config('activity.apply_type.join');
		$interest = Kohana::config('activity.apply_type.interest');
		$query = $this->db->query("SELECT uid FROM `action_member` WHERE aid = $aid AND uid in ($fids) AND (apply_type = $join OR apply_type = $interest)");
		$result = array();
		if ($query->count() == 0) {
			return false;
		}
		return true;
	}

    /**
     * 添加活动
     * @param array $activity_info 活动相关信息
     * @return boolean
     */
	public function add($activity_info){
		return $this->db->insertData('action', $activity_info);
	}
	
    /**
     * 修改活动
     * @param int $aid 活动id
     * @param array $activity_info 活动相关信息
     * @return boolean
     */
	public function update($aid, $activity_info){
		return $this->db->updateData('action', $activity_info, "aid = $aid");
	}

    /**
     * 获取全部群组活动数量
     * @param int $gid 群组id
     * @return int
     */
	public function getAllGroupActivityNum($gids){
		$query = $this->db->query("SELECT COUNT(DISTINCT aid) AS num FROM action_group WHERE gid IN ( $gids )");
		$result = $query->result_array(FALSE);
		return (int)$result[0]['num'];
	}

    /**
     * 获取全部群组活动id
     * @param int $gid 群组id
     * @param int $start
     * @param int $pos
     * @return array
     */
	public function getAllGroupAidList($gids, $start, $pos){
		$query = $this->db->query("SELECT DISTINCT aid FROM action_group WHERE gid IN ( $gids ) ORDER BY aid DESC LIMIT $start, $pos");
		if ($query->count() == 0) {
			return array();
		}
		return $query->result_array(FALSE);
	}
	

    /**
     * 获取群组活动数量
     * @param int $gid 群组id
     * @return int
     */
	public function getGroupActivityNum($gid){
		$query = $this->db->query("SELECT COUNT(DISTINCT aid) AS num FROM action_group WHERE gid = $gid");
		$result = $query->result_array(FALSE);
		return (int)$result[0]['num'];
	}

    /**
     * 添加活动参加的群组
     * @param int $aid 活动id
     * @param int $gid 群组id
     * @return int
     */
	public function addActivityGroup($aid, $gid){
		return $this->db->insertData('action_group', array('aid' => $aid, 'gid' => $gid));
	}

    /**
     * 判断是否已报名参加了活动
     * @param int $aid 活动id
     * @param int $uid 用户id
     * @param int $apply_type 活动报名类型
     * @return int
     */
	public function isActivityMember($aid, $uid, $apply_type){
		return $this->db->getCount('action_member', "aid = $aid AND uid = $uid AND apply_type = $apply_type");
	}

    /**
     * 获取活动报名数量
     * @param int $aid 活动id
     * @param int $apply_type 报名类型
     * @return int
     */
	public function getActivityMemberNum($aid, $apply_type){
		return (int)$this->db->getCount('action_member', "aid = $aid AND apply_type = $apply_type");
	}

    /**
     * 获取活动报名成员
     * @param int $aid 活动id
     * @param int $apply_type 报名类型,默认为确认参加活动类型:1：参加 2：不参加 3：感兴趣
     * @return array
     */
	public function getActivityMember($aid, $apply_type = 1){
		$query = $this->db->query("SELECT a.uid, realname, mobile, b.grade, $apply_type AS apply_type FROM membersinfo a LEFT JOIN action_member b ON a.uid = b.uid WHERE aid = $aid AND apply_type = $apply_type ORDER BY b.apply_time DESC");
		if($query->count() == 0){
			return array();
		}
		return $query->result_array(FALSE);
	}
	
    /**
     * 获取活动报名成员
     * @param int $aid 活动id
     * @param int $join 所有活动参加者 (1，包含，0不包含，后面参数类似)
     * @param int $notJoin 所有活动不参加者
     * @param int $interest 所有活动感兴趣者
     * @param int $unconfirmed 所有活动未确认者
     * @return array
     */
	public function getActivityMembers($aid, $join = 1, $notJoin = 0, $interest = 0, $unconfirmed = 0){
		if(!$join && !$notJoin && !$interest && !$unconfirmed) {
			return array();
		}
		$where = "aid = $aid";
		$filter = array();
		if($join) {
			$filter[] = "apply_type = ".Kohana::config("activity.apply_type.join");
		}
		if($notJoin) {
			$filter[] = "apply_type = ".Kohana::config("activity.apply_type.not_join");
		}
		if($interest) {
			$filter[] = "apply_type = ".Kohana::config("activity.apply_type.interest");
		}
		$applyResult = array();
		if($filter) {
			if(count($filter) > 1) {
				$where .= ' AND ( '.implode(" OR ", $filter).' )';
			} else if(count($filter) == 1) {
				$where .= ' AND '.$filter[0]; 
			}
			$query = $this->db->query("SELECT a.uid, realname, mobile, b.grade, b.apply_type FROM membersinfo a LEFT JOIN action_member b ON a.uid = b.uid WHERE $where");
			$applyResult = $query->result_array(FALSE);
		}
		if($unconfirmed) {
			$unconfirmedResult = $this->getInviteUnset($aid);
			return  array_merge($applyResult, $unconfirmedResult);
		}
		return $applyResult;
	}
	
	public function getMembersInviteUser($aid) {
		$query = $this->db->query("SELECT a.invite_uid AS uid, a.uid AS invite_uid, b.realname FROM action_invite a LEFT JOIN membersinfo b ON a.uid = b.uid WHERE a.aid = $aid ORDER BY a.status DESC");
		if($query->count() == 0){
			return array();
		}
		return $query->result_array(FALSE);
	}

    /**
     * 获取活动报名参加和感兴趣的成员
     * @param int $aid 活动id
     * @param int $apply_type 报名类型,默认为确认参加活动类型
     * @return array
     */
	public function getApplyMember($aid){
		$join = Kohana::config('activity.apply_type.join');
		$interest = Kohana::config('activity.apply_type.interest');
		$query = $this->db->query("SELECT uid, apply_time, apply_type FROM action_member WHERE aid = $aid AND (apply_type = $join OR apply_type = $interest) ORDER BY apply_time DESC");
		if($query->count() == 0){
			return array();
		}
		return $query->result_array(FALSE);
	}

    /**
     * 获取活动所有报名参加或者感兴趣的成员
     * @param int $aid 活动id
     * @return array
     */
	public function getActivityApplyMember($aid){
		$join = Kohana::config('activity.apply_type.join');
		$interest = Kohana::config('activity.apply_type.interest');
		$query = $this->db->query("SELECT uid FROM action_member WHERE aid = $aid AND (apply_type = $join OR apply_type = $interest) ORDER BY apply_time DESC");
		if($query->count() == 0){
			return array();
		}
		return $query->result_array(FALSE);
	}

    /**
     * 获取活动所有报名成员
     * @param int $aid 活动id
     * @return array
     */
	public function getActivityAllMember($aid){
		$query = $this->db->query("SELECT a.uid, realname, mobile, b.grade, b.apply_type FROM membersinfo a LEFT JOIN action_member b ON a.uid = b.uid WHERE aid = $aid ORDER BY b.apply_time DESC");
		if($query->count() == 0){
			return array();
		}
		return $query->result_array(FALSE);
	}
	
    /**
     * 获取活动报名成员
     * @param int $aid 活动id
     * @param int $uid 邀请用户id
     * @param int $invite_uid 被邀请用户id
     * @param int $invite_time 被邀请时间
     * @return bool
     */
	public function inviteToActivity($aid, $uid, $invite_uid, $invite_time){
		$query = $this->db->fetchData('action_invite', '*', array('aid' => $aid, 'uid' => $uid, 'invite_uid' => $invite_uid));
		if($query->count() == 0){
			$result = $this->db->insertData('action_invite', array('aid' => $aid, 'uid' => $uid, 'invite_uid' => $invite_uid, 'invite_time' => $invite_time, 'status' => 0));
		}else{
			$result = $this->db->updateData('action_invite', array('invite_time' => $invite_time), "aid = $aid AND uid=$uid AND invite_uid = $invite_uid AND status = 0");
		}
		
		if (!$result) {
			return false;
		}
		else{
			return true;
		}
	}

    /**
     * 获取活动邀请记录
     * @param int $aid 活动id
     * @return array
     */
	public function getActivityInvite($aid){
		$query = $this->db->fetchData('action_invite', 'invite_uid', array('aid' => $aid), array('invite_time' => 'DESC'));
		if($query->count() == 0){
			return array();
		}
		return $query->result_array(FALSE);
	}

    /**
     * 获取用户活动被邀请记录
     * @param int $aid 活动id
     * @param int $uid 邀请用户id
     * @param int $invite_uid 被邀请用户id
     * @return array
     */
	public function getUserInvite($aid, $uid, $invite_uid){
		$query = $this->db->fetchData('action_invite', '*', array('aid' => $aid, 'uid' => $uid, 'invite_uid' => $invite_uid));
		if($query->count() == 0){
			return array();
		}
		return $query->result_array(FALSE);
	}

    /**
     * 设置被邀请记录已处理
     * @param int $aid 活动id
     * @param int $uid 邀请用户id
     * @param int $invite_uid 被邀请用户id
     * @return bool
     */
	public function setInviteStatus($aid, $uid, $invite_uid){
		return $this->db->updateData('action_invite', array('status'=>1), "aid=$aid AND uid=$uid AND invite_uid = $invite_uid");
	}

    /**
     * 获取活动被邀请未处理成员
     * @param int $aid 活动id
     * @return array
     */
	public function getInviteUnset($aid){
		$query = $this->db->query("SELECT a.invite_uid AS uid, b.realname, b.mobile, 0 AS grade, 0 AS apply_type, COUNT(DISTINCT a.invite_uid) FROM action_invite a LEFT JOIN membersinfo b ON a.invite_uid = b.uid WHERE a.aid = $aid AND `status` = 0 GROUP BY invite_uid");
		return $query->result_array(FALSE);
	}

    /**
     * 获取用户被邀请未处理成员
     * @param int $aid 活动id
     * @param int $uid 活动被邀请者id
     * @return array
     */
	public function getUserInviteUnset($aid, $uid){
		$query = $this->db->query("SELECT DISTINCT invite_uid AS uid FROM action_invite WHERE `aid` = $aid AND `invite_uid` = $uid AND `status` = 0");
		if($query->count() == 0){
			return array();
		}
		return $query->result_array(FALSE);
	}

    /**
     * 获取用户被邀请之人
     * @param int $aid 活动id
     * @param int $uid 活动被邀请者id
     * @return array
     */
	public function getUserInvitedUid($aid, $uid){
		$query = $this->db->query("SELECT DISTINCT uid AS invite_uid  FROM action_invite WHERE `aid` = $aid AND `invite_uid` = $uid ORDER BY status DESC");
		if($query->count() == 0){
			return 0;
		}
		$result = $query->result_array(FALSE);
		return (int)$result[0]['invite_uid'];
	}

    /**
     * 获取活动可参与的群组id
     * @param int $aid 活动id
     * @return array
     */
	public function getActivityGroupId($aid){
		$query = $this->db->fetchData('action_group', 'gid', array('aid' => $aid));
		if($query->count() == 0){
			return array();
		}
		return $query->result_array(FALSE);
	}

    /**
     * 获取用户活动报名类型
     * @param int $aid 活动id
     * @param int $uid 用户id
     * @return int
     */
	public function getActivityApplyType($aid, $uid){
		$query = $this->db->fetchData('action_member', 'apply_type', array('aid' => $aid, 'uid' => $uid));
		if($query->count() == 0){
			return 0;
		}
		$result = $query->result_array(FALSE);
		return (int)$result[0]['apply_type'];
	}

    /**
     * 报名活动
     * @param array $activity_member 报名信息
     * @return int
     */
	public function applyActivity($activity_member){
		return $this->db->insertData('action_member', $activity_member);
	}

    /**
     * 修改活动报名
     * @param int $aid 活动id
     * @param int $uid 用户id
     * @param array $action_member 报名信息
     * @return bool
     */
	public function updateApplyActivity($aid, $uid, $action_member){
		return $this->db->updateData('action_member', $action_member, "aid = $aid AND uid = $uid");
	}


    /**
     * 获取用户参加的所有活动列表(确认参加与可能参加)
     * @param int $aid 活动id
     * @param int $apply_type 参与活动类型，默认为参加类型
     * @param bool $now 活动是否正在进行（未结束状态）
     * @return array
     */
	public function userAllJoinActivity($uid, $apply_type = 1, $now = false){
		$aidList = array();
		$activityList = array();
		$join = Kohana::config('activity.apply_type.join');
		$interest = Kohana::config('activity.apply_type.interest');
		$query = $this->db->query("SELECT aid FROM action_member WHERE uid = $uid AND (apply_type = $join OR apply_type = $interest)  ORDER BY apply_time DESC");
		if($query->count() != 0){
			$aidList = $query->result_array(FALSE);
		}
		$nowtime = time();
		foreach($aidList as $value){
			$activity = $this->getActivityInfo($value['aid'],false);
			$activity['creator_name'] = sns::getrealname($activity['creator_id']);
			
			if($now && $nowtime > $activity['end_time'] + Kohana::config('activity.additional_time')){
				continue;
			}
			if(date('Y',$activity['start_time']) != date('Y',$activity['end_time']) || date('Y') != date('Y',$activity['start_time'])){
				$activity['start_time'] = date('Y年m月d日 H:i', $activity['start_time']);
				$activity['end_time'] = date('Y年m月d日 H:i', $activity['end_time']);
			} else if(date('Y-m-d',$activity['start_time']) != date('Y-m-d',$activity['end_time'])){
				$activity['start_time'] = date('m月d日 H:i', $activity['start_time']);
				$activity['end_time'] = date('m月d日 H:i', $activity['end_time']);
			} else {
				$activity['start_time'] = date('m月d日 H:i', $activity['start_time']);
				$activity['end_time'] = date('H:i', $activity['end_time']);
			}
			$activityList[] = $activity;
		}
		return $activityList;
	}

	/**
     * 获取小秘所有活动列表(确认参加与可能参加)
     * @return array
     */
	public function getXiaoMiActivityList(){
		$uid = Kohana::config('uap.xiaomo');
		$query = $this->db->query("SELECT * FROM action WHERE creator_id = $uid ORDER BY create_time DESC");
		if($query->count() == 0){
			return array();
		}
		$activityResult = $query->result_array(FALSE);
		$nowtime = time();
		$activityList = array();
		foreach($activityResult as $activity){
			if($nowtime > $activity['end_time'] + Kohana::config('activity.additional_time')){
				continue;
			}
			if(date('Y',$activity['start_time']) != date('Y',$activity['end_time']) || date('Y') != date('Y',$activity['start_time'])){
				$activity['start_time'] = date('Y年m月d日 H:i', $activity['start_time']);
				$activity['end_time'] = date('Y年m月d日 H:i', $activity['end_time']);
			} else if(date('Y-m-d',$activity['start_time']) != date('Y-m-d',$activity['end_time'])){
				$activity['start_time'] = date('m月d日 H:i', $activity['start_time']);
				$activity['end_time'] = date('m月d日 H:i', $activity['end_time']);
			} else {
				$activity['start_time'] = date('m月d日 H:i', $activity['start_time']);
				$activity['end_time'] = date('H:i', $activity['end_time']);
			}
			$activityList[] = $activity;
		}
		return $activityList;
	}

    /**
     * 获取用户在活动的权限
     * @param int $aid 活动id
     * @param int $uid 用户id
     * return int 参加了活动:2(可上传活动图片、查看活动图片)，活动可见:1(可查看活动图片)，无任何权限:0(不可上传图片和查看图片)
     */
	public function getActivityPrivacy($aid, $uid){
		$apply_type = $this->getActivityApplyType($aid, $uid);
		if($apply_type == Kohana::config('activity.apply_type.join') || $apply_type == Kohana::config('activity.apply_type.interest')){
			return 2;
		}
		return 0;
	}

    /**
     * 导出活动确认参加和感兴趣成员
     * @param array $aid 活动id
     * @return string
     */
    public function exportXls($aid, $apply_type){
		$output = '';
		$xlsFormat = Kohana::config('activity.xlsFormat');
		$output[] = array_values($xlsFormat);
		$join = Kohana::config('activity.apply_type.join');
		$interest = Kohana::config('activity.apply_type.interest');
		if($apply_type == -1){
			
		}
		$query = $this->db->query("SELECT uid, apply_type FROM action_member WHERE aid = $aid AND (apply_type = $join OR apply_type = $interest) ORDER BY apply_time DESC");
		if($query->count() == 0){
			$member_list = array();
		}else{
			$member_list = $query->result_array(FALSE);
		}
		$userModel = new User_Model();
		foreach($member_list as $value){
			$result = $userModel->getUserById($value['uid']);
			$member['username'] = $result['body']['realname'];
			$member['mobile'] = $result['body']['mobile'];
			
			if($value['apply_type'] == $join){
				$member['status'] = '确认参加';
			}else{
				$member['status'] = '可能参加';
			}
			$output[] = array_values($member);
			unset($member);
		}
		return $output;
	}

    /**
     * 删除活动
     * @param int $aid 活动id
     * return Boolean
     */
	public function delete($aid){
		$result = $this->db->deleteData('action', array('aid'=>$aid));
		if($result) {
			$this->db->deleteData('action_member', array('aid'=>$aid));
			$this->db->deleteData('action_group', array('aid'=>$aid));
			$this->db->deleteData('action_invite', array('aid'=>$aid));
			$this->db->deleteData('action_user', array('aid'=>$aid));
			return true;
		}
		return false;
	}

    /**
     * 获取活动列表
     * @param int $type 活动归属类型
     * @param int $start
     * @param int $pos
     * return array
     */
	public function getActivityListByType($type = 0, $start = 0, $pos = 10) {
		$where = array();
		if($type > 0) {
			$where = array('type'=>$type);
		}
		$query = $this->db->fetchData('action', '*', $where, array('aid'=>'DESC'), $pos, $start);
		if($query->count() == 0){
			return array();
		} else {
			return $query->result_array(FALSE);
		}
	}

    /**
     * 获取活动数量
     * @param int $type 活动归属类型
     * @return int
     */
	public function getActivityListNumByType($type){
		$where = '';
		if($type > 0){
			$where = "type = $type";
		}
		return $this->db->getCount('action', $where);
	}

    /* 获取活动列表
     * @param int $type 活动归属类型
     * @param int $start
     * @param int $pos
     * return array
     */
	public function getActivityListByTime($time = 0, $start = 0, $pos = 10) {
		$where = '';
		if($time > 0) {
			$where = "WHERE time > $time";
		}
		$query = $this->db->query("SELECT * FROM action $where ORDER BY aid DESC LIMIT $start, $pos");
		if($query->count() == 0){
			return array();
		} else {
			return $query->result_array(FALSE);
		}
	}

    /**
     * 获取活动数量
     * @param int $time 活动时间间隔
     * @return int
     */
	public function getActivityListNumByTime($time){
		$where = '';
		if($time > 0){
			$where = "start_time > $time";
		}
		return $this->db->getCount('action', $where);
	}


    /**
     * 获取活动所有组织者
     * @param int $aid 活动id
     * return array
     */
	public function getActivityOrganizer($aid) {
		$query = $this->db->query("SELECT uid FROM action_member WHERE aid = $aid AND grade > 1 ORDER BY apply_time DESC");
		if($query->count() == 0){
			return array();
		} else {
			return $query->result_array(FALSE);
		}
	}
	
    /**
     * 获取活动报名成员权限
     * @param int $aid 活动id
     * @param int $uid 用户id
     * return array
     */
	public function getMemberGrade($aid, $uid) {
		$query = $this->db->fetchData('action_member', 'grade', array('aid'=>$aid , 'uid'=>$uid));
		if($query->count() == 0){
			return 0;
		} else {
			$result = $query->result_array(FALSE);
			return (int)$result[0]['grade'];
		}
	}

    /**
     * 获取活动组织者总数
     * @param int $aid 活动id
     * return int
     */
	public function getManagerCount($aid) {
		return $this->db->getCount('action_member', "aid=$aid AND grade = 2");
	}

    /**
     * 设置活动组织者或取消活动组织者
     * @param int $aid 活动id
     * @param int $uid 用户id
     * return boolean
     */
	public function manageOrganizer($aid, $uid, $grade) {
		return $this->db->updateData('action_member', array('grade' => $grade), "aid=$aid AND uid=$uid");
	}

    /**
     * 获取活动邀请码
     * @param int $aid 活动id
     * @param int $uid 用户id
     * return boolean
     */
	public function getInviteCode($aid, $uid) {
		$query = $this->db->fetchData('action_invite_register', 'invite_code', array('aid'=>$aid , 'invite_uid'=>$uid));
		if($query->count() == 0){
			return 0;
		} else {
			$result = $query->result_array(FALSE);
			return $result[0]['invite_code'];
		}
	}
        
    /**
     * 添加活动邀请码
     * @param int $aid 活动id
     * @param int $uid 用户id
     * return boolean
     */
	public function addInviteCode($aid, $invite_uid,$invite_code) {
            $data = array('invite_code'=>$invite_code,'invite_uid'=>$invite_uid, 'aid'=>$aid);
            return $this->db->insertData('action_invite_register', $data);
        }
	
    /**
     * 获取活动邀请信息
     * @param int $invite_code 活动邀请码
     * @param int $uid 用户id
     * return boolean
     */
	public function getInvitationInfo($invite_code) {
        $where = "invite_code= '{$invite_code}'";
        $query = $this->db->query("SELECT invite_uid,aid FROM action_invite_register WHERE $where limit 1");
        if ($query->count() == 0) {
			return array();
		}
		$result = $query->result_array(FALSE);
        return $result[0];
    }

	/************近期活动获取*************/
	public function getFriendsApplyAids($fids) {
		$aids = '';
		if(!$fids) {
			return $aids;
		}
		$joinType = Kohana::config('activity.apply_type.join');
		$interestType = Kohana::config('activity.apply_type.interest');
		$query = $this->db->query("SELECT aid FROM action_member WHERE uid IN ( $fids ) AND (apply_type = $joinType OR apply_type = $interestType)");
		if ($query->count() == 0) {
			$result = array();
		} else {
			$result = $query->result_array(FALSE);
		}
		$aidArray = array();
		
		$separator = '';
		foreach($result as $value) {
			$aids .= $separator.$value['aid'];
			$separator = ',';
		}
		return $aids;
	}

	public function getApplyAids($uid) {
		$joinType = Kohana::config('activity.apply_type.join');
		$interestType = Kohana::config('activity.apply_type.interest');
		$query = $this->db->query("SELECT aid FROM action_member WHERE uid = $uid");
		if ($query->count() == 0) {
			$result = array();
		} else {
			$result = $query->result_array(FALSE);
		}
		$aidArray = array();
		foreach($result as $value) {
			$aidArray[] = $value['aid'];
		}
		unset($query);
		unset($result);
		unset($value);
		$query = $this->db->query("SELECT DISTINCT aid FROM action_invite WHERE invite_uid = $uid AND `status` = 0");
		if ($query->count() == 0) {
			$result = array();
		} else {
			$result = $query->result_array(FALSE);
		}
		foreach($result as $value) {
			if(!in_array($value['aid'], $aidArray)) {
				$aidArray[] = $value['aid'];
			}
		}
        return implode(',', $aidArray);
	}

	//用户相关的活动
	public function userAboutActivityList($uid, $start = 0, $pos = 5, $end = 0) {
		$groupModel = new Group_Model();
		$gidList = $groupModel->getUserAllGroupId($uid);
		$gids = '';
		$separator = '';
		foreach($gidList as $value) {
			$gids .= $separator.$value['gid'];
			$separator = ',';
		}
		$friendModel = new Friend_Model();
		$fidList = $friendModel->getAllFriendIDs($uid);
		$fids = '';
		if($fidList) {
			$fids = implode(',', $fidList);
		}
		$applyAids = $this->getApplyAids($uid);
		$companyModel = Company_Model::instance();
		$companyList = $companyModel->getCompanyList($uid);
		$companyIds = "";
		$separator = '';
		foreach ($companyList as $value) {
			$companyIds .= $separator.$value['cid'];
			$separator = ',';
		}
		$belongType = Kohana::config('activity.belongType.company');
		if(!empty($companyIds)) {
			if(empty($applyAids)) {
				$separator = '';
			} else {
				$separator = ',';
			}
			$activityList = $this->getBelongActivityList($belongType, $companyIds);
			foreach ($activityList as $value) {
				$applyAids .= $separator.$value['aid'];
				$separator = ',';
			}
		}
		if($fids) {
			$where = "(gid = -1 AND creator_id in ($fids))";
			$friendApplyaids = $this->getFriendsApplyAids($fids);
			if($friendApplyaids) {
				$where .= " OR (is_allow_invite = 1 AND aid IN ($friendApplyaids))";
			}
		}
		if($gids) {
			if($where) {
				$where .= " OR gid in ($gids)";
			} else {
				$where .= "gid in ($gids)";
			}
		}
		if($applyAids) {
			if($where) {
				$where .= " OR aid in ($applyAids)";
			} else {
				$where .= "aid in ($applyAids)";
			}
		}
		if(!$where) {
			return array();
		}
		if($end == 0) {
			$nowTime = time();
			$where = "$nowTime < end_time AND "."($where)"; 
		}
		$limit = "";
		if($pos > 0) {
			$limit = "LIMIT $start, $pos";
		}
		$query = $this->db->query("SELECT DISTINCT aid, creator_id, title, start_time, end_time, spot, content, is_allow_invite, gid, type, belong_type, belong_id FROM action WHERE $where ORDER BY start_time DESC $limit");
		if ($query->count() == 0) {
			return array();
		}
		return $query->result_array(FALSE);
	}

	public function userAboutActivityNum($uid, $end = 0) {
		$groupModel = new Group_Model();
		$gidList = $groupModel->getUserAllGroupId($uid);
		$gids = '';
		$separator = '';
		foreach($gidList as $value) {
			$gids .= $separator.$value['gid'];
			$separator = ',';
		}
		$friendModel = new Friend_Model();
		$fidList = $friendModel->getAllFriendIDs($uid);
		$fids = '';
		if($fidList) {
			$fids = implode(',', $fidList);
		}
		$applyAids = $this->getApplyAids($uid);
		$companyModel = Company_Model::instance();
		$companyList = $companyModel->getCompanyList($uid);
		$companyIds = "";
		$separator = '';
		foreach ($companyList as $value) {
			$companyIds .= $separator.$value['cid'];
			$separator = ',';
		}
		$belongType = Kohana::config('activity.belongType.company');
		if(!empty($companyIds)) {
			if(empty($applyAids)) {
				$separator = '';
			} else {
				$separator = ',';
			}
			$activityList = $this->getBelongActivityList($belongType, $companyIds);
			foreach ($activityList as $value) {
				$applyAids .= $separator.$value['aid'];
				$separator = ',';
			}
		}
		if($fids) {
			$where = "(gid = -1 AND creator_id in ($fids))";
			$friendApplyaids = $this->getFriendsApplyAids($fids);
			if($friendApplyaids) {
				$where .= " OR (is_allow_invite = 1 AND aid IN ($friendApplyaids))";
			}
		}
		if($gids) {
			if($where) {
				$where .= " OR gid in ($gids)";
			} else {
				$where .= "gid in ($gids)";
			}
		}
		if($applyAids) {
			if($where) {
				$where .= " OR aid in ($applyAids)";
			} else {
				$where .= "aid in ($applyAids)";
			}
		}
		if(!$where) {
			return 0;
		}
		if($end == 0) {	//未结束的活动
			$nowTime = time();
			$where = "$nowTime < end_time AND "."($where)"; 
		}
		$query = $this->db->query("SELECT COUNT(DISTINCT aid) AS num FROM action WHERE $where");
		$result = $query->result_array(FALSE);
		return (int)$result[0]['num'];
	}

        /*
	public function recentActivity($start = 0, $pos = 3) {
		$groupModel = new Group_Model();
		$gidList = $groupModel->getUserAllGroupId($this->uid);
		$gids = '';
		$separator = '';
		foreach($gidList as $value) {
			$gids .= $separator.$value['gid'];
			$separator = ',';
		}
		$friendModel = new Friend_Model();
		$fidList = $friendModel->getAllFriendIDs($invite_uid);
		$fidList[] = $this->uid;
		$uids = implode(',', $fidList);
		$applyAids = $this->getApplyAids($this->uid);
		$recent_date_limit = Kohana::config('activity.recent_date_limit');
		$now_time = time();
		$where .= "(creator_id IN ($uids) AND gid = 0 AND is_publish = 1)";
		if($applyAids) {
			$where .= " OR aid IN ($applyAids)";
		}
		if($gids) {
			$where .= " OR gid IN ($gids)";
		}
		$where .= " AND start_time > $now_time - $recent_date_limit AND end_time > $now_time";
		$query = $this->db->query("SELECT DISTINCT aid FROM action WHERE $where ORDER BY aid DESC LIMIT $start, $pos");
		if ($query->count() == 0) {
			return array(); //近期没有活动
		}
		$aidList = $query->result_array(FALSE);
		$actionList = array();
		foreach($aidList as $value) {
			$action = $this->getActioninfo($value['aid']);
			$action['creator_name'] = sns::getrealname($action['creator_id']);
			
			if($now && $nowtime > $action['end_time'] + Kohana::config('action.additional_time')){
				continue;
			}
			$action['start_day'] = date('d', $action['start_time']);
			if(date('Y',$action['start_time']) != date('Y',$action['end_time']) || date('Y') != date('Y',$action['start_time'])){
				$action['start_time'] = date('Y年m月d日 H:i', $action['start_time']);
				$action['end_time'] = date('Y年m月d日 H:i', $action['end_time']);
			} else if(date('Y-m-d',$action['start_time']) != date('Y-m-d',$action['end_time'])){
				$action['start_time'] = date('m月d日 H:i', $action['start_time']);
				$action['end_time'] = date('m月d日 H:i', $action['end_time']);
			} else {
				$action['start_time'] = date('m月d日 H:i', $action['start_time']);
				$action['end_time'] = date('H:i', $action['end_time']);
			}
			$action['joinNum'] = $this->getActionMemberNum($value['aid'], Kohana::config('action.apply_type.join'));
			$action['interestNum'] = $this->getActionMemberNum($value['aid'], Kohana::config('action.apply_type.interest'));
			$actionList[] = $action;
		}
		return $actionList;
	}

         */

	public function getBelongActivityList($belongType, $belongIds, $start=0, $pos=5) {
		if($belongType && !$belongIds || $belongIds == "") {
			return array();
		}
		$limits = '';
		if($pos > 0) {
			$limits = "LIMIT $start, $pos";
		}
		$query = $this->db->query("SELECT * FROM action WHERE belong_type = $belongType AND belong_id IN ($belongIds) ORDER BY start_time DESC $limits");
		if($query->count() == 0) {
			return array();
		}
		return $query->result_array(FALSE);
	}

    /**
     * 获取用户近期参加或者感兴趣的活动
     * @param int $aid 活动id
     * @param int $apply_type 参与活动类型，默认为参加类型
     * @param bool $now 活动是否正在进行（未结束状态）
     * @return array
     */
	public function userRecentJoinAid($uid, $start = 0, $pos = 5){
		$recent_date_limit = Kohana::config('activity.recent_date_limit');
		$limit_time = time() - $recent_date_limit;
		$join = Kohana::config('activity.apply_type.join');
		$interest = Kohana::config('activity.apply_type.interest');
		$query = $this->db->query("SELECT a.aid FROM action_member a LEFT JOIN action b ON a.aid = b.aid WHERE a.uid = $uid AND (a.apply_type = $join OR a.apply_type = $interest) AND a.apply_time > $limit_time ORDER BY b.start_time DESC LIMIT $start, $pos");
		if($query->count() == 0){
			return array();
		}
		return $query->result_array(FALSE);
	}

    /**
     * 获取用户可见的活动被发起动态的活动列表
     * @param int $uid 用户id
     * return array
     */
	public function getUserActivityId($uid) {
		$query = $this->db->fetchData('action_user', 'aid', array('uid'=>$uid));
		if($query->count() == 0){
			return array();
		}
		return $query->result_array(FALSE);
	}

    /**
     * 获取活动被发起动态的可见成员列表
     * @param int $aid 活动id
     * return array
     */
	public function getActivityUserId($aid) {
		$query = $this->db->fetchData('action_user', 'uid', array('aid'=>$aid));
		if($query->count() == 0){
			return array();
		}
		return $query->result_array(FALSE);
	}

    /**
     * 添加活动被发起动态的可见成员记录
     * @param int $aid 活动id
     * @param int $uid 用户id
     * return boolean
     */
	public function addActivityUser($aid, $uid) {
		return $this->db->query("REPLACE into action_user(aid, uid) VALUES($aid, $uid)");
	} 

}
