<?php
defined('SYSPATH') or die('No direct access allowed.');

/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * 活动控制器
 */
class Activity_Controller extends Controller
{
    /**
     * 是否发布模式
     */
    const ALLOW_PRODUCTION = TRUE;
    /**
     * 活动模型
     * @var Activity_Model
     */
    protected $model;
    /**
     * 用户ID
     * @var int
     */
    //protected $user_id = 10901978;
	//protected $user_id = 12138637;
    /**
     * 构造函数
     */
    public function __construct ()
    {
        //必须继承父类控制器
        parent::__construct();
        $this->user_id = $this->getUid();
        //实例化模型
        $this->model = new Activity_Model();
    }

    /**
     * 获取活动列表
     */
    public function index ()
    {
        if ($this->get_method() != 'GET') {
            $this->send_response(405, NULL, '请求的方法不存在');
        } else {
            $data = $this->get_data();
            $type = $this->input->get('filter', 'all');
            $end = intval($this->input->get('end', 1));
            if($end != 1) {
            	$end = 0;
            }
            $pos = (int)($this->input->get('pagesize', 0));
            $page = (int)($this->input->get('page', 1));
            $start = abs($pos * ($page - 1));
            $typeArray = Kohana::config('activity.request_type');
            if(!in_array($type, $typeArray)) {
                $this->send_response(400, NULL, '400504:请求的活动类型非法');
            }
            $friendModel = new Friend_Model();
			$fidList = $friendModel->getAllFriendIDs($this->user_id);
			$fids = implode(',', $fidList);
			$result = array('total'=>0, 'data'=>array());
			switch ($type) {
				case 'all' :
					$count = $this->model->userAboutActivityNum($this->user_id, $end);
					$data = $this->model->userAboutActivityList($this->user_id, $start, $pos, $end);
					$activityList = $this->_arrange_activity_list($data, -2);
					break;
				case 'me_tab_show' :
					$userModel = new User_Model();
					$tablist = $userModel->getAllTabList($this->user_id, 15);
					$dataList = array();
					$index = 0;
					foreach ($tablist as $value) {
						$activity = $this->model->getActivityInfo($value['id']);
						if(!$activity) {
							continue;	//活动不存在
						} else if($activity['end_time'] < time()) {
							continue;	//活动已结束
						}
						$apply_type = $this->model->getActivityApplyType($value['id'], $this->user_id);
						if($apply_type != Kohana::config('activity.apply_type.join') && $apply_type != Kohana::config('activity.apply_type.interest')) {
							continue;	//你未参加或感兴趣此活动
						}
						if($pos == 0 || ($index >= $start && $index < ($start + $pos))) {
							$activity['apply_type'] = $apply_type;
							$activity['is_hide'] = intval($value['is_show']) == 1 ? 0 : 1;
							$dataList[] = $activity;
						}
						$index++;
					}
					$activityList = $this->_arrange_activity_list($dataList, -1);
					$count = $index;
					break;
				case 'me_launch' :
					//获取我发起的活动
					$count = $this->model->getCreateActivityNum ( $this->user_id, $end );
					$dataList = $this->model->getCreateActivityList ( $this->user_id, $start, $pos, $end);
					$activityList = $this->_arrange_activity_list($dataList, Kohana::config('activity.apply_type.join'));
					break;
				case 'me_joined' :
					$apply_type = Kohana::config('activity.apply_type.join');
					$count = $this->model->getApplyActivityNum($this->user_id, $apply_type, $end);
					$dataList = $this->model->getActivityList($this->user_id, $apply_type, $start, $pos, $end);
					$activityList = $this->_arrange_activity_list($dataList, Kohana::config('activity.apply_type.join'));
					break;
				case 'me_interested' :
					$apply_type = Kohana::config('activity.apply_type.interest');
					$count = $this->model->getApplyActivityNum($this->user_id, $apply_type, $end);
					$dataList = $this->model->getActivityList($this->user_id, $apply_type, $start, $pos, $end);
					$activityList = $this->_arrange_activity_list($dataList, Kohana::config('activity.apply_type.interest'));
					break;
				case 'me_not_join' :
					$apply_type = Kohana::config('activity.apply_type.not_join');
					$count = $this->model->getApplyActivityNum($this->user_id, $apply_type, $end);
					$dataList = $this->model->getActivityList($this->user_id, $apply_type, $start, $pos, $end);
					$activityList = $this->_arrange_activity_list($dataList, Kohana::config('activity.apply_type.not_join'));
					break;
				case 'friend_launch' :
					if(!$fidList) {
						$this->send_response(200, $result);
					}
					$aidList = $this->model->getFriendCreateAidList($fids, $end);
					$data = $this->_fill_activity_list($aidList, $start, $pos);
					$count = $data['count'];
					$activityList = $data['data'];
					break;
				case 'friend_joined' :
					if(!$fidList) {
						$this->send_response(200, $result);
					}
					$apply_type = Kohana::config('activity.apply_type.join');
					$aidList = $this->model->getFriendAidList($fids, $apply_type, $end);
					$data = $this->_fill_activity_list($aidList, $start, $pos);
					$count = $data['count'];
					$activityList = $data['data'];
					break;
				case 'friend_interested' :
					if(!$fidList) {
						$this->send_response(200, $result);
					}
					$apply_type = Kohana::config('activity.apply_type.interest');
					$aidList = $this->model->getFriendAidList($fids, $apply_type, $end);
					$data = $this->_fill_activity_list($aidList, $start, $pos);
					$count = $data['count'];
					$activityList = $data['data'];
					break;
				default :
					break;
			}
            $result['total'] = intval($count);
            $result['data'] = $activityList;
            $this->send_response(200, $result);
        }
    }
    
    /**
     * 获取活动详细信息
     */
    public function show ($id)
    {
	    if ($this->get_method() != 'GET') {
            $this->send_response(405, NULL, '请求的方法不存在');
        } elseif (empty($id)) {
            $this->send_response(400, NULL, '400501:活动ID为空');
        }
        $isShowMember = (int)($this->input->get('member', 0)) == 1 ? 1 : 0;
        $webRequest = (int)($this->input->get('web', 0));
        if($webRequest != 1 && $webRequest != 2) {
        	$webRequest = 0;
        }
        $activityInfo = $this->model->getActivityInfo($id);
        if(!$activityInfo) {
        	if($webRequest > 0) {
        		echo '活动不存在';
        		exit;
        	}
        	$this->send_response(400, NULL, '400502:活动不存在');
        }
        $permit = $this->_check_activity_view_permission($activityInfo, $this->user_id);
        if(!$permit) {
        	if($webRequest > 0) {
        		echo '无权限查看活动信息';
        		exit;
        	}
        	$this->send_response(400, NULL, '400503:无权限查看活动信息');
        }
        $activity = array();
        $aid = $activityInfo['aid'];
        $activity['id'] = floatval($aid);
        $user = array();
        $user['id'] = floatval($activityInfo['creator_id']);
		$userInfo = sns::getuser($activityInfo['creator_id']);
        $user['name'] = $userInfo['realname'];
        $user['avatar'] = sns::getavatar($activityInfo['creator_id']);
        $user['mobile'] = $userInfo['mobile'];
        $activity['user'] = $user;
        unset($userInfo);
        unset($user);
        $organizer = array();
        $organizerList = $this->model->getActivityOrganizer($aid);
        $organizerIdList = array();
        foreach($organizerList as $value) {
        	$user = array();
	        $user['id'] = floatval($value['uid']);
			$userInfo = sns::getuser($value['uid']);
	        $user['name'] = $userInfo['realname'];
	        $user['avatar'] = sns::getavatar($value['uid']);
	        $user['mobile'] = $userInfo['mobile'];
	        $organizer[] = $user;
	        $organizerIdList[] = $user['id'];
	        unset($userInfo);
        	unset($user);
        }
        $isOrganizer = in_array($this->user_id, $organizerIdList);
        $activity['organizer'] = $organizer;
        $activity['title'] = $activityInfo['title'];
        $nowTime = time();
        if($activityInfo['end_time'] < $nowTime){
			$status = Kohana::config('activity.status.end.id');
		} else if($activityInfo['start_time'] > $nowTime) {
			$status = Kohana::config('activity.status.enroll.id');
		}else{
			$status = Kohana::config('activity.status.working.id');
		}
		$activity['status'] = $status;
        $activity['start_at'] = api::get_date($activityInfo['start_time']);
        $activity['end_at'] = api::get_date($activityInfo['end_time']);
        $activity['spot'] = $activityInfo['spot'];
        $activity['content'] = $activityInfo['content'];
        $activity['type'] = intval($activityInfo['type']);
        $joined = array('number' => 0, 'member' => array());
        $interested = array('number' => 0, 'member' => array());
        $unconfirmed = array('number' => 0, 'member' => array());
        if($isShowMember) {
        	$apply_type = Kohana::config('activity.apply_type.join');
        	$joined['number'] = (int)$this->model->getActivityMemberNum($aid, $apply_type);
			$joinedList = $this->model->getActivityMember($aid, $apply_type);
			$joined['member'] = array();
			foreach($joinedList as $value) {
				$user['id'] = floatval($value['uid']);
				$user['name'] = $value['realname'];
				$user['avatar'] = sns::getavatar($value['uid']);
				$user['mobile'] = "";
				if($isOrganizer || in_array($user['id'], $organizerIdList)) {
					$user['mobile'] = $value['mobile'];
				}
				$joined['member'][] = $user;
				unset($user);
			}
			$apply_type = Kohana::config('activity.apply_type.interest');
			$interested['number'] = (int)$this->model->getActivityMemberNum($aid, $apply_type);
			$interestedList = $this->model->getActivityMember($aid, $apply_type);
			$interested['member'] = array();
        	foreach($interestedList as $value) {
				$user['id'] = floatval($value['uid']);
				$user['name'] = $value['realname'];
				$user['avatar'] = sns::getavatar($value['uid']);
        		$user['mobile'] = "";
				if($isOrganizer || in_array($user['id'], $organizerIdList)) {
					$user['mobile'] = $value['mobile'];
				}
				$interested['member'][] = $user;
				unset($user);
			}
			$apply_type = Kohana::config('activity.apply_type.not_join');
			$notJoined['number'] = (int)$this->model->getActivityMemberNum($aid, $apply_type);
			$notJoinedList = $this->model->getActivityMember($aid, $apply_type);
			$notJoined['member'] = array();
        	foreach($notJoinedList as $value) {
				$user['id'] = floatval($value['uid']);
				$user['name'] = $value['realname'];
				$user['avatar'] = sns::getavatar($value['uid']);
        		$user['mobile'] = "";
				if($isOrganizer || in_array($user['id'], $organizerIdList)) {
					$user['mobile'] = $value['mobile'];
				}
				$notJoined['member'][] = $user;
				unset($user);
			}
			$unconfirmedList = $this->model->getInviteUnset($aid);
			$unconfirmed['number'] = count($unconfirmedList);
			$unconfirmed['member'] = array();
			foreach ($unconfirmedList as $value) {
				$user['id'] = floatval($value['uid']);
				$user['name'] = $value['realname'];
				$user['avatar'] = sns::getavatar($value['uid']);
				$user['mobile'] = "";
				if($isOrganizer || in_array($user['id'], $organizerIdList)) {
					$user['mobile'] = $value['mobile'];
				}
				$unconfirmed['member'][] = $user;
				unset($user);
			}
			$activity['joined'] = $joined;
			$activity['interested'] = $interested;
			$activity['refused'] = $notJoined;
			$activity['unconfirmed'] = $unconfirmed;
        } else {
        	$apply_type = Kohana::config('activity.apply_type.join');
        	$joined['number'] = (int)$this->model->getActivityMemberNum($aid, $apply_type);
			$joined['member'] = array();
			$apply_type = Kohana::config('activity.apply_type.interest');
			$interested['number'] = (int)$this->model->getActivityMemberNum($aid, $apply_type);
			$interested['member'] = array();
			$apply_type = Kohana::config('activity.apply_type.not_join');
			$notJoined['number'] = (int)$this->model->getActivityMemberNum($aid, $apply_type);
			$notJoined['member'] = array();
			$unconfirmedList = $this->model->getInviteUnset($aid);
			$unconfirmed['number'] = count($unconfirmedList);
			$unconfirmed['member'] = array();
        }
        $activity['joined'] = $joined;
        $activity['interested'] = $interested;
		$activity['refused'] = $notJoined;
        $activity['unconfirmed'] = $unconfirmed;
    	if($webRequest > 0) {
    		$types = array_flip(Kohana::config('activity.type'));
    		$typeId = $types[$activity['type']];
    		$typeNames = Kohana::config('activity.typeName');
    		$apply_type = $this->model->getActivityApplyType($activity['id'], $this->user_id);
    		$type = $typeNames[$typeId];
        	$view = new View('activity/details');
        	$view->webRequest = $webRequest;
        	$view->type = $type;
        	$activityInfo['user'] = $activity['user'];
        	$activityInfo['organizer'] = $activity['organizer'];
        	$view->activity = $activityInfo;
        	$view->apply_type = $apply_type;
        	$view->render(true);
			exit;
        }
        $this->send_response(200, $activity);
    }
    
    /**
     * 检查用户是否有查看活动的权限
     * @param array $activity 
     * @param float $uid
     * return boolean
     */
    private function _check_activity_view_permission($activity, $uid) {
		$permit = true;
		$apply_type = $this->model->getActivityApplyType($activity['aid'], $uid);
		if($activity['gid'] == 0 && $apply_type == 0) {
			$permit = false;
			$friendModel = Friend_Model::instance();
			$isFriend = $friendModel->check_isfriend($activity['creator_id'], $uid);
			$fidList = $friendModel->getAllFriendIDs($uid);
			$friendsIsJoin = false;
			if($fidList) {
				$fids = implode(',', $fidList);
				$friendsIsJoin = $this->model->checkFriendsIsJoined($activity['aid'], $fids);
			}
			$invite = $this->model->getUserInviteUnset($activity['aid'], $uid);
			$isCompanyMember = false;
			if($activity['belong_type'] == Kohana::config('activity.belongType.company')) {
				$companyModel = new Company_Model();
				$companyId = floatval($activity['belong_id']);
				$isCompanyMember = $companyModel->isCompanyMember($companyId, $uid);
			}
			if($invite || $isCompanyMember) {
				$permit = true;
			} else if($activity['is_allow_invite'] && $friendsIsJoin) {
				$permit = true;
			}
		}
		return $permit;
    }
    
    /**
     * 整理活动列表信息
     * @param array $activityList 活动列表
     * return array
     */
    private function _arrange_activity_list($data, $apply_type) {
    	$activityList = array();
    	$activityType = array_flip(Kohana::config('activity.type'));
    	$activityTypeName = Kohana::config('activity.typeName');
    	$nowTime = time();
    	foreach($data as $value) {
    		$activity = array();
    		$activity['id'] = floatval($value['aid']);
    		$user = array();
    		$user['id'] = floatval($value['creator_id']);
    		$user['name'] = sns::getrealname($value['creator_id']);
    		$user['avatar'] = sns::getavatar($value['creator_id']);
    		$activity['user'] = $user;
    		$activity['title'] = $value['title'];
    		$activity['start_at'] = api::get_date($value['start_time']);
    		$activity['end_at'] = api::get_date($value['end_time']);
    		$activity['spot'] = $value['spot'];
    		//$activity['content'] = $value['content'];
    		$type = $activityType[$value['type']];
    		$activity['type'] = $activityTypeName[$type];
    		if($value['end_time'] < $nowTime){
				$status = Kohana::config('activity.status.end.id');
			} else if($value['start_time'] > $nowTime) {
				$status = Kohana::config('activity.status.enroll.id');
			}else{
				$status = Kohana::config('activity.status.working.id');
			}
			$activity['status'] = $status;
			if($apply_type == -1) {
				$activity['apply_type'] = $value['apply_type'];
				$activity['is_hide'] = $value['is_hide'];
			} else if($apply_type == -2){
				$activity['apply_type'] = $this->model->getActivityApplyType($activity['id'], $this->user_id);
			} else {
				$activity['apply_type'] = $apply_type;
				$activity['is_hide'] = -1;
			}
			$result = $this->model->getActivityOrganizer($activity['id']);
			$organizer = array();
			unset($user);
			foreach($result as $val) {
				$user = array();
				$user['id'] = floatval($val['uid']);
				$userInfo = sns::getuser($val['uid']);
				$user['name'] = $userInfo['realname'];
				$user['avatar'] = sns::getavatar($val['uid']);
				$user['mobile'] = $userInfo['mobile'];
				$organizer[] = $user;
				unset($userInfo);
				unset($user);
			}
			$activity['organizer'] = $organizer;
			$activityList[] = $activity;
			unset($activity);
			unset($user);
    	}
    	return $activityList;
    }
    
    /**
     * 填充活动列表信息
     * @param array $aidList 活动ID列表
     * @param int $start 开始位置
     * @param int $pos 获取数量
     * return array
     */
    private function _fill_activity_list($aidList, $start, $pos) {
    	$activityList = array();
    	$activityType = array_flip(Kohana::config('activity.type'));
    	$activityTypeName = Kohana::config('activity.typeName');
    	$nowTime = time();
    	$friendModel = new Friend_Model();
    	$groupModel = new Group_Model();
    	$index = 0;
    	foreach($aidList as $value){
    		$aid = $value['aid'];
			$gidArray =  $this->model->getActivityGroupId($aid);
			$activityInfo = $this->model->getActivityInfo($aid);
    		if($activityInfo['creator_id'] != $this->user_id && !$activityInfo['is_allow_invite']){
				$permit = false;
				foreach($gidArray as $val) {
					if($val['gid'] == -1) {
						$isFriend = $friendModel->check_isfriend($this->user_id, $activityInfo['creator_id']);
						if($isFriend) {
							$permit = true;
							break;
						}
						continue;
					}
					$grade = $groupModel->getMemberGrade($val['gid'], $this->user_id);
					if($grade > 0) {
						$permit = true;
						break;
					}
				}
				if(!$permit) {
					continue;
				}
			}
			if($pos == 0 || ($index >= $start && $index < ($start + $pos))) {
				$activity = array();
				$activity['id'] = floatval($activityInfo['aid']);
	    		$user = array();
	    		$user['id'] = floatval($activityInfo['creator_id']);
	    		$user['name'] = sns::getrealname($activityInfo['creator_id']);
	    		$user['avatar'] = sns::getavatar($activityInfo['creator_id']);
	    		$activity['user'] = $user;
	    		$activity['title'] = $activityInfo['title'];
	    		$activity['start_at'] = api::get_date($activityInfo['start_time']);
	    		$activity['end_at'] = api::get_date($activityInfo['end_time']);
	    		$activity['spot'] = $activityInfo['spot'];
	    		//$activity['content'] = $activityInfo['content'];
	    		$type = $activityType[$activityInfo['type']];
	    		$activity['type'] = $activityTypeName[$type];
	    		if($activityInfo['end_time'] < $nowTime){
					$status = Kohana::config('activity.status.end.id');
				} else if($activityInfo['start_time'] > $nowTime) {
					$status = Kohana::config('activity.status.enroll.id');
				}else{
					$status = Kohana::config('activity.status.working.id');
				}
				$activity['status'] = $status;
				$activity['apply_type'] = $this->model->getActivityApplyType($activity['id'], $this->user_id);
				$activity['is_hide'] = -1;
				$result = $this->model->getActivityOrganizer($activity['id']);
				$organizer = array();
				unset($user);
				foreach($result as $val) {
					$user = array();
					$user['id'] = floatval($val['uid']);
					$userInfo = sns::getuser($val['uid']);
					$user['name'] = $userInfo['realname'];
					$user['avatar'] = sns::getavatar($val['uid']);
					$user['mobile'] = $userInfo['mobile'];
					$organizer[] = $user;
					unset($userInfo);
					unset($user);
				}
				$activity['organizer'] = $organizer;
				$activityList[] = $activity;
				unset($activityInfo);
				unset($activity);
				unset($user);
			}
			$index++;
    	}
    	return array('data'=>$activityList, 'count'=>$index);
    }

    /**
     * 创建活动
     */
    public function create ()
    {
    	if ($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        $data = $this->get_data();
        if(!$data) {
        	$this->send_response(400, NULL, '400505:活动信息非法');
        }
        $post = new Validation($data);
		$post->add_rules('title', 'required', 'length[1, 30]');
		$post->add_rules('start_at', 'required', 'numeric');
		$post->add_rules('end_at', 'required', 'numeric');
		$post->add_rules('spot', 'required', 'length[1, 30]');
		$post->add_rules('type', 'required', 'numeric', array($this, '_check_type_validation'));
		$post->add_rules('is_allow_invite', 'required', 'numeric', array($this, '_check_allow_invite_validation'));
		$post->add_rules('content', 'length[0, 300]');
		$post->add_rules('group_ids', array($this, '_check_group_ids_validation'));
		$post->add_callbacks(TRUE, array($this, '_check_time_validation'));
		if ($post->validate()) {
			$activity = array();
			$form = $post->as_array();
			$activity['creator_id'] = $this->user_id;
			$activity['title'] = $form['title'];
			$activity['start_time'] = $form['start_at'];
			$activity['end_time'] = $form['end_at'];
			$nowTime = time();
			$activity['create_time'] = $nowTime;
			$activity['spot'] = $form['spot'];
			$activity['type'] = $form['type'];
			$activity['is_allow_invite'] = $form['is_allow_invite'];
			if(isset($form['content'])) {
				$activity['content'] = $form['content'];
			}
			$groupIds = array();
			if(isset($form['group_ids'])) {
				$groupIds = $form['group_ids'];
			}
			$groupModel = new Group_Model();
			$gidArray = array();
			foreach($groupIds as $id) {
				$id = floatval($id);
				if($id != -1) {
					$groupInfo = $groupModel->getGroupInfo($id);
					if(!$groupInfo) {
						$this->send_response(400, NULL, '400506:活动发布到的群不存在');
					}
					$grade = $groupModel->getMemberGrade($id, $this->user_id);
					if($grade < 1) {
						$this->send_response(400, NULL, '400507:您不是活动指定发布到群的成员');
					}
				}
				$gidArray[] = $id;
			}
			if(!$gidArray) {
				$activity['is_publish'] = 0;
			} else {
				$activity['is_publish'] = 1;
			}
			$activity_id = $this->model->add($activity);
			$activityMember = array(
				'aid' => $activity_id,
				'uid' => $this->user_id,
				'apply_type' => Kohana::config('activity.apply_type.join'),
				'apply_time' => $nowTime,
				'grade' => Kohana::config('activity.grade.creator')
			);
			$result = $this->model->applyActivity($activityMember);
			$this->model->addActivityUser($activity_id, $this->user_id);
			$friendModel = new Friend_Model();
			$fidList = $friendModel->getAllFriendIDs($this->user_id, false);
			//活动动态发送到指定momo成员
			foreach($gidArray as $gid) {
				$this->model->addActivityGroup($activity_id, $gid);
				if($gid == -1) {
					$friendModel = new Friend_Model();
					$fidList = $friendModel->getAllFriendIDs($this->user_id, false);
					foreach($fidList as $fid) {
						$this->model->addActivityUser($activity_id, $fid);
					}
				} else {
					$this->model->addActivityGroup($activity_id, $gid);
					$members = $groupModel->getGroupAllMember($gid);
					foreach($members as $value) {
						$this->model->addActivityUser($activity_id, $value['uid']);
					}
				}
			}
			$feedModel = new Feed_Model();
			$title =  array(
				'uid' => $this->user_id,
				'name' => sns::getrealname($this->user_id),
				'id' => $activity_id,
				'title' =>$activity['title']
			);
			$messageModel = new Message_Model();
			if($activity['is_publish']) {
				$feedModel->addFeed($this->user_id, 'action_add', Kohana::config('uap.app.action'), $title, array(), $activity_id);
			}
			$this->send_response(200, array('id' => floatval($activity_id)));
		}
		$errors = $post->errors();
		$this->send_response ( 400, NULL, '400505:活动信息非法');
    }
    
    /**
     * 检查活动发起到群的传入数据是否合法的回调方法
     * @param   $array   活动发布到群数组
     */
    public function _check_group_ids_validation($array) {
    	$groupIds = array();
    	if(isset($array)) {
    		$groupIds = $array;
    	}
    	if(count($groupIds, COUNT_RECURSIVE) != count($groupIds)) {
    		return FALSE;
    	}
		foreach($groupIds as $value) {
			$value = floatval($value);
			if (!is_float($value)) {
				return FALSE;
			}
		}
		return TRUE;
    }
    
    /**
     * 检查活动类型值是否合法的回调方法
     * @param  $type   活动类型值
     */
    public function _check_type_validation($type) {
    	$typeArray =  array_values(Kohana::config('activity.type'));
    	if(!in_array($type, $typeArray)) {
    		return FALSE;
    	}
    	return TRUE;
    }
    
    /**
     * 检查活动允许参加者可以邀请好友值是否合法的回调方法
     * @param  $value   活动是否允许参加成员邀请好友值
     */ 
    public function _check_allow_invite_validation($value) {
    	if(!in_array($value, array(0,1))) {
    		return FALSE;
    	}
    	return TRUE;
    }
    
    /**
     * 检查活动时间是否合法的回调方法
     * @param  $type   活动类型值
     */
    public function _check_time_validation($post) {
    	$array = $post->as_array();
    	if($array['start_at'] >= $array['end_at']) {
       		$post->add_error('end_at', 'end_time_before_or_equal_start_time');
    	}
    }
    
    /**
     * 删除活动
     * @param int $id 活动ID
     */
    public function destroy ($id = NULL)
    {

    }

    /**
     * 修改活动信息
     * @param int $id 活动ID
     */
    public function update ($id = NULL)
    {

    }
    
} // End Activity Controller


