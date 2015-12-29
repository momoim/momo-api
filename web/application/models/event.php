<?php defined('SYSPATH') or die('No direct script access.');
/**
 * [momo移动社区] (C)1999-2010 ND Inc.
 * 活动模型文件
 */

class Event_Model extends Model {
	public static $instances = null;
    public function __construct() {
        parent::__construct();
		$this->uid = Session::instance()->get('uid');
    }
	
	public static function &instance() {
		if (! is_object ( Event_Model::$instances )) {
			// Create a new instance
			Event_Model::$instances = new Event_Model ();
		}
		return Event_Model::$instances;
	}

    /**
     * 添加活动
     * @param array $event_info 活动相关信息
     * @return boolean
     */
	public function add($event_info){
		return $this->db->insertData('event', $event_info);
	}
	
    /**
     * 查询活动
     * @param array $event_info 活动相关信息
     * @return boolean
     */
	public function get($eid){
		$query = $this->db->fetchData('event', '*',array(eid=>$eid));
		if ($query->count() == 0) {
			return array();
		}
		$result = $query->result_array(FALSE);
		return $result[0];
	}
	
    /**
     * 修改活动
     * @param int $aid 活动id
     * @param array $event_info 活动相关信息
     * @return boolean
     */
	public function update($eid, $event_info){
		return $this->db->updateData('event', $event_info, "eid = $eid");
	}
   
    /**
     * 判断是否有报名
     * @param array $event_user 报名信息
     * @return int
     */
	public function hasApplyEvent($event_user){
		$query = $this->db->fetchData('event_user', 'count(*) as total',$event_user);
		$result = $query->result_array(FALSE);
		if($result[0]['total']) {
			return true;
		}
		return false;		
	}
    /**
     * 删除报名用户
     * @param array $event_user 报名信息
     * @return int
     */
	public function deleteEventUser($event_user){
		return $this->db->delete('event_user',$event_user);
	}
	
	/**
	 * 
	 * 获取用户的报名状态
	 * @param int $uid
	 * @param int $eid
	 */
	public function getApplyType($event_user) {
		$query = $this->db->fetchData('event_user', 'apply_type',$event_user);
		$result = $query->result_array(FALSE);
		if($result[0]['apply_type']) {
			return $result[0]['apply_type'];
		}
		return 0;		
	}

	/**
	 * 
	 * 获取用户的报名信息
	 * @param int $uid
	 * @param int $eid
	 */
	public function getApplyInfo($event_user) {
		$query = $this->db->fetchData('event_user', '*',$event_user);
		$result = $query->result_array(FALSE);
		if($result[0]) {
			return $result[0];
		}
		return null;		
	}

    /**
     * 报名活动
     * @param array $event_user 报名信息
     * @return int
     */
	public function applyEvent($event_user,$update_apply_type=false){
		if($update_apply_type) {
			return $this->updateApplyEvent($event_user['eid'],$event_user['uid'],$event_user);
		}
		return $this->db->insertData('event_user', $event_user);
	}

    /**
     * 修改活动报名
     * @param int $aid 活动id
     * @param int $uid 用户id
     * @param array $action_member 报名信息
     * @return bool
     */
	public function updateApplyEvent($eid, $uid, $apply_data,$extra=''){
		return $this->db->updateData('event_user', $apply_data, "eid = $eid AND uid = $uid ".$extra);
	}
	
	/**
	 * 
	 * 修改家属报名
	 * @param unknown_type $eid
	 * @param unknown_type $uid
	 */
	public function updateDependentApply($eid, $uid,$apply_data) {
		return $this->db->updateData('event_user', $apply_data, "eid = $eid AND pid = $uid AND uid=0");
	}
	
	/**
	 * 
	 * 获取过滤条件
	 * @param $uid
	 * @param $apply_type
	 * @param $end
	 * @param $city
	 */
	private function getFilter($uid,$apply_type, $end,$city,$private){
		$now = time();
		//过滤类型
		$filter = ' 1=1 ';
		if(is_array($apply_type) && count($apply_type) >0 && $uid>0) {
			if(in_array(-1,$apply_type)) {
				if(count($apply_type)==1) {
					$filter .= ' AND e.organizer='.$uid;
				} else {
					$apply_type_str = join(',',$apply_type);
					$filter .= ' AND (euuu.apply_type in ('.$apply_type_str.') OR e.organizer='.$uid.')';
				}
			} else {
				if(in_array(0,$apply_type)) {
					$filter .= ' AND euuu.uid='.$uid;
				}else{
					$apply_type_str = join(',',$apply_type);
					$filter .= ' AND euuu.apply_type in ('.$apply_type_str.') AND euuu.uid='.$uid;
				}
			}
		}
		//是否显示已结束的
		if(!$end) {
			$filter .= " AND e.status!=3";
		}
		if($private ==0) {
			$filter .= " AND e.private=0";
		}
		if($city) {
			$filter .= " AND e.city = $city";
		}
		return $filter;
	}

	/**
	 * 
	 * 增加活动邀请
	 * @param int $eid
	 * @param int $invite_uid
	 * @param int $be_invite_uid
	 */
	public function be_event_invite($eid,$be_invite_uid) {
		return $this->db->getCount("event_invite", "be_event_invite=$be_invite_uid AND eid=$eid");
	}
	
	/**
	 * 
	 * 增加活动邀请
	 * @param int $eid
	 * @param int $invite_uid
	 * @param int $be_invite_uid
	 */
	public function add_event_invite($eid,$invite_uid,$be_invite_uid) {
		return $this->db->query("REPLACE INTO event_invite VALUE ($eid, $invite_uid, $be_invite_uid, ".time().")");
	}

	/**
	 * 
	 * 获取活动人数
	 * @param $uid
	 * @param $apply_type
	 * @param $end
	 * @param $city
	 */
	public function getEventNum( $uid,$apply_type, $end,$city,$private){
		$filter = $this->getFilter($uid,$apply_type, $end, $city,$private);
		$sql = "SELECT COUNT(DISTINCT(e.eid)) as total FROM event e LEFT JOIN event_user euuu ON euuu.eid=e.eid ";
		if($uid)
			$sql .= " AND euuu.uid=".$uid;
		$sql .= " WHERE $filter";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		return (int) $result[0]['total'];
	}
	
	/**
	 * 
	 * 获取活动列表
	 * @param $apply_type
	 * @param $end
	 * @param $city
	 * @param $start
	 * @param $pagesize
	 * @param $sort
	 */
	public function getEventList($uid, $apply_type, $end,$city,$start, $pagesize,$sort,$private) {
		$filter = $this->getFilter($uid,$apply_type, $end, $city,$private);
		$sql = "SELECT e.*,COUNT(eu.name+eu.uid) as joined_total, COUNT( euu.name+euu.uid ) AS interested_total";
		if($uid) 
			$sql .= ",euuu.apply_type ";
		$sql .= " FROM `event` e ";
		$sql .= "LEFT JOIN `event_user` eu ON eu.eid=e.eid AND eu.apply_type=".Kohana::config('event.apply_type.joined')." 
			LEFT JOIN  `event_user` euu ON euu.eid = e.eid AND euu.apply_type =".Kohana::config('event.apply_type.interested');
		if($uid)
			$sql .= " LEFT JOIN  `event_user` euuu ON euuu.eid = e.eid AND euuu.uid =".$uid;
		if($filter) {	
			$sql .= " WHERE $filter GROUP BY e.eid ORDER BY $sort DESC LIMIT $start,$pagesize";
		} else {
			$sql .= " GROUP BY e.eid ORDER BY $sort DESC LIMIT $start,$pagesize";
		}
		$query = $this->db->query($sql);
		if($query->count() == 0){
			return array();
		}
		return $query->result_array(FALSE);
	}
	
	/**
	 * 
	 * 获取活动信息
	 */
	public function getEvent($eid,$uid) {
		$sql = "SELECT e.* ";
		if($uid) 
			$sql .= ",eu.apply_type ";
		$sql .= " FROM `event` e ";
		if($uid)
			$sql .= " LEFT JOIN  `event_user` eu ON eu.eid = e.eid AND eu.uid =$uid";  
		$sql .= " WHERE  e.eid=$eid LIMIT 1";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		if($query->count() == 0){
			return array();
		}
		return $result[0];
	}
	
	/**
	 * 
	 * 获取活动用户
	 * @param $eid
	 * @param $apply_type
	 */
	public function getEventUser($eid,$apply_type) {
		$query = $this->db->fetchData('event_user', '*',array('eid'=>$eid,'apply_type'=>$apply_type));
		$result = $query->result_array(FALSE);
		if($query->count() > 0) {
			return $result;
		}
		return array();		
	}
	
	/**
	 * 
	 * 获取城市列表
	 */
	public function getCity() {
		$query = $this->db->fetchData('city', 'id,pid,name',array(),array('ord'=>'ASC'));
		$result = $query->result_array(FALSE);
		if($query->count() > 0) {
			return $result;
		}
		return array();		
	}
	
	/**
	 * 
	 * 获取城市名字
	 * @param unknown_type $id
	 */
	public function getCityName($id) {
		$query = $this->db->fetchData('city', 'name',array('id'=>$id));
		$result = $query->result_array(FALSE);
		if($query->count() > 0) {
			return $result[0]['name'];
		}
		return '';		
	}
	
	/**
	 * 
	 * @param $id
	 * @param $status
	 * @return unknown_type
	 */
	public function getUserCount($event_user) {
		$query = $this->db->fetchData('event_user', 'count(*) as total',$event_user);
		$result = $query->result_array(FALSE);
		return (int)$result[0]['total'];
	}
	
	/**
	 * 
	 * @param $eid
	 * @param $title
	 * @return unknown_type
	 */
	public function getEventApplyDoc($eid,$title='') {
		$letters = array();
		if($eid)
			$letters['eid'] = $eid;
		if($title)
			$letters['title'] = $title;
		if(count($letters)>0) {
			$query = $this->db->fetchData('event_apply_doc', 'did,title',$letters);
			$result = $query->result_array(FALSE);
			if($query->count() > 0) {
				return $result;
			}
		}
		return ;		
	}

	/**
	 * 
	 * @param $eid
	 * @param $title
	 * @return unknown_type
	 */
	public function getEventApplyDocId($eid) {
		if(count($eid)>0) {
			$query = $this->db->fetchData('event_apply_doc', 'did',array('eid'=>$eid));
			$result = $query->result_array(FALSE);
			if($query->count() > 0) {
				foreach($result as $v) {
					$ids[] = $v['did'];
				}
				return $ids;
			}
		}
		return array();		
	}
	
	/**
	 * 
	 * @param $eid
	 * @param $title
	 * @return unknown_type
	 */
	public function addEventApplyDoc($eid,$title) {
		return $this->db->insertData('event_apply_doc', array('eid'=>$eid,'title'=>$title));
	}
	
	/**
	 * 
	 * @param $eid
	 * @param $title
	 * @return unknown_type
	 */
	public function deleteEventApplyDoc($did) {
		if($this->db->delete('event_apply_doc',array('did'=>$did))) {
			return $this->db->delete('event_user_apply_doc',array('did'=>$did));
		}
	}
	
	/**
	 * 
	 * @param $eid
	 * @param $title
	 * @return unknown_type
	 */
	public function updateEventApplyDoc($did,$title) {
		return $this->db->updateData('event_apply_doc', array('title'=>$title), "did = $did");
	}
	
	/**
	 * 
	 * @param $eid
	 * @param $uid
	 * @param $pid
	 * @param $name
	 * @param $apply_doc
	 * @return 
	 */
	public function addUserApplyDoc($eid,$uid,$pid,$name,$apply_doc) {
		foreach($apply_doc as $v) {
			$res = $this->getUserApplyDoc($eid,$uid,$pid,$v['did'],$name);
			if($res && $res[0]['id'])
				$this->_updateUserApplyDoc($res[0]['id'],$v['content']);
			else
				$this->_insertUserApplyDoc($v['did'],$eid,$uid,$pid,$name,$v['content']);
		}
	}
	
	
	/**
	 * 
	 * @param $did
	 * @param $eid
	 * @param $uid
	 * @param $pid
	 * @param $name
	 * @return 
	 */
	public function getUserApplyDoc($eid,$uid,$pid,$did='',$name='') {
		$sql = "SELECT ed.id,ed.content,ea.title,ea.did FROM event_user_apply_doc ed LEFT JOIN event_apply_doc ea ON ea.did=ed.did WHERE ed.eid='{$eid}' AND ed.uid='{$uid}' AND ed.pid='{$pid}'";
		if($did)
			$sql .= " AND ed.did='{$did}'";
		if($uid ==0 && $name)
			$sql .= " AND ed.name='{$name}'";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		if($query->count() > 0) {
			return $result;
		}
		return ;
	}
	
	/**
	 * 
	 * @param $id
	 * @param $content
	 * @return 
	 */
	private function _updateUserApplyDoc($id,$content) {
		return $this->db->updateData('event_user_apply_doc', array('content'=>$content), "id = '{$id}'");
	}
	
	/**
	 * 
	 * @param $did
	 * @param $eid
	 * @param $uid
	 * @param $pid
	 * @param $name
	 * @param $content
	 * @return 
	 */
	private function _insertUserApplyDoc($did,$eid,$uid,$pid,$name,$content) {
		return $this->db->insertData('event_user_apply_doc', array('did'=>$did,'eid'=>$eid,'uid'=>$uid,'pid'=>$pid,'name'=>$name,'content'=>$content));
	}
}
