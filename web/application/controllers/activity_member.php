<?php
defined('SYSPATH') or die('No direct access allowed.');

/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * 活动成员控制器
 */
class Activity_Member_Controller extends Controller
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
     * 获取活动成员列表
     */
    public function index ($id = 0)
    {
    	$data = $this->get_data();
        if ($this->get_method() != 'GET') {
            $this->send_response(405, NULL, '请求的方法不存在');
        } elseif (empty($id)) {
            $this->send_response(400, NULL, '400501:活动ID为空');
        }
        $data = $this->get_data();
        $apply_type = (int)$this->input->get('type', 0);
        $result = array ();
        if($apply_type < -1 || $apply_type > Kohana::config('activity.apply_type.interest')) {
            $this->send_response(400, NULL, '400508:活动报名类型非法');
        }
		$activityInfo = $this->model->getActivityInfo ( $id );
		if (! $activityInfo) {
			$this->send_response ( 400, NULL, '400502:活动不存在' );
		}
		$grade = $this->model->getMemberGrade ( $id, $this->user_id );
		if ($grade < Kohana::config ( 'activity.grade.normal' )) {
			$this->send_response ( 400, NULL, '400509:非活动报名者，无权限查看报名成员');
		}
    	$invitationList = $this->model->getMembersInviteUser($id);
		$inviteArray = array();
		foreach ($invitationList as $value) {
			if(!array_key_exists($value['uid'], $inviteArray)) {
				$inviteArray[$value['uid']] = array('id' => floatval($value['invite_uid']), 'name'=>$value['realname']);
			}
		}
		if ($apply_type == - 1) {
			//未确认成员
			$memberList = $this->model->getInviteUnset ( $id );
		} else if ($apply_type == 0) {
			//全部成员
			$apply_member = $this->model->getActivityAllMember ( $id );
			$invite_member = $this->model->getInviteUnset ( $id );
			$memberList = array_merge ( $apply_member, $invite_member );
		} else {
			//各报名类型成员
			$memberList = $this->model->getActivityMember ( $id, $apply_type );
		}
		foreach ($memberList as $value) {
			$member = array ();
			$user = array();
			$user['id'] = floatval($value['uid']);
			$user ['name'] = $value['realname'];
			$user ['avatar'] = sns::getavatar ( $value ['uid'] );
			$user ['mobile'] = "";
			$userGrade = intval($value['grade']);
			if($grade > Kohana::config('activity.grade.normal') || $userGrade > Kohana::config('activity.grade.normal')){
				$user['mobile'] = $value['mobile'];
			}
			$member ['user'] = $user;
			$invite_user = array();
			if(array_key_exists($value['uid'], $inviteArray)) {
				$invite_user = $inviteArray[$value['uid']];
			}
			$member ['invite_user'] = $invite_user;
			$member ['apply_type'] = intval($value['apply_type']);
			$member ['grade'] = $userGrade;
			$result[] = $member;
			unset($user);
			unset($member);
		}
		$this->send_response(200, $result);
    }

    /**
     * 报名活动
     */
    public function create ($id = 0)
    {
    	$data = $this->get_data();
        $aid = (int) $id;
        $apply_type = (int)$data['type'];
    	$webRequest = (int)($data['web']);
        if($webRequest != 2) {
        	$webRequest = 0;
        }
        if ($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        if($id == 0) {
        	$this->send_response(400, NULL, '400501:活动id为空');
        }
        if($apply_type < Kohana::config('activity.apply_type.join') || $apply_type > Kohana::config('activity.apply_type.interest')) {
        	$this->send_response(400, NULL, '400508:活动报名类型非法');
        }
        $activity = $this->model->getActivityInfo($aid);
        if(!$activity) {
        	$this->send_response(400, NULL, '400502:活动不存在');
        }
        if($activity['end_time'] < time()) {
        	$this->send_response(400, NULL, '活动已结束');
        }
        

        $permit = $this->_check_activity_view_permission($activity, $this->user_id);
        if(!$permit) {
        	$this->send_response(400, NULL, '400510:无权限报名活动');
        }

		if($webRequest > 0) {
			$types = array_flip(Kohana::config('activity.type'));
    		$typeId = $types[$activity['type']];
    		$typeNames = Kohana::config('activity.typeName');
    		$type = $typeNames[$typeId];
        	$view = new View('activity/details');
        	$view->webRequest = $webRequest;
        	$view->type = $type;
        	$user = array();
	        $user['id'] = floatval($activity['creator_id']);
			$userInfo = sns::getuser($activity['creator_id']);
	        $user['name'] = $userInfo['realname'];
	        $user['mobile'] = $userInfo['mobile'];
        	
	        $activity['user'] = $user;
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
	        $activity['organizer'] = $organizer;
        	$view->activity = $activity;
        	$view->apply_type = $apply_type;
		}
		$userModel = new User_Model();
        $nowTime = time();
        $feedModel = new Feed_Model();
    	//获取用户是否已经参与了报名
		$applyResult = $this->model->getActivityApplyType($aid, $this->user_id);
		if($applyResult){
			$tab = $userModel->getTag($this->user_id, 15, $aid);
			if($applyResult == $apply_type) {
				if($webRequest > 0) {
					$view->render(true);
					exit;
				}
				$this->send_response(200);
			} else if($applyResult ==  Kohana::config('activity.apply_type.not_join') && !$tab) {
				$userModel->insertTag($this->user_id, 15, $aid);
			} else if($apply_type ==  Kohana::config('activity.apply_type.not_join')) {
				//$userModel->deleteTag($this->user_id, 15, $aid);
			}
			$activityMember = array('apply_type' => $apply_type, 'apply_time' => $nowTime);
			$grade = $this->model->getMemberGrade($aid, $this->user_id);
			if($grade == Kohana::config('activity.grade.manager') && $apply_type != Kohana::config('activity.apply_type.join')) {
				$activityMember['grade'] = Kohana::config('activity.group.normal');
			}
			$result = $this->model->updateApplyActivity($aid, $this->user_id, $activityMember);
			$this->_add_feed_comment($activity, $applyResult, $apply_type, $this->user_id);
			$feedModel->addTab($aid, $activity['title'], 15, $this->user_id);
			if($webRequest > 0) {
				$view->render(true);
				exit;
			}
			$this->send_response(200);
		}
		$activityMember['aid'] = $aid;
		$activityMember['uid'] = $this->user_id;
		$activityMember['apply_type'] = $apply_type;
		$activityMember['apply_time'] = $nowTime;
		$activityMember['grade'] = Kohana::config('activity.grade.normal');
		$this->model->applyActivity($activityMember);
		$userModel->insertTag($this->user_id, 15, $aid);
    	$messageModel = new Message_Model();
		$appid = Kohana::config('uap.app.action');
		$tplid = Kohana::config('noticetpl.actionInvite.id');
		while(true) {
			$inviteMsg = $messageModel->getNoticeInfo(array('uid'=>$this->user_id, 'appid'=>$appid, 'tplid'=>$tplid, 'objid'=>$aid, 'status'=>0));
			if($inviteMsg){
				//修改系统消息模板
				$invite_uid = $inviteMsg['authorid'];
				$nid = $inviteMsg['id'];
				$messageModel->putChangeTplByid($this->user_id,$nid,$apply_type);
				$this->model->setInviteStatus($aid, $invite_uid, $this->user_id);
			} else {
				break;
			}
		}
    	if($apply_type == Kohana::config('activity.apply_type.not_join')) {
			if($webRequest > 0) {
				$view->render(true);
				exit;
			}
			$this->send_response(200);
		}
		$this->_add_feed_comment($activity, 0, $apply_type, $this->user_id);
		$feedModel->addTab($aid, $activity['title'], 15, $this->user_id);
		if($webRequest > 0) {
        	$view->render(true);
			exit;
		}
		$this->send_response(200);
    }
	
    //通过短信邀请链接参加活动
	public function invite_create() {
		if ($this->get_method () != 'POST') {
			$this->send_response ( 405, NULL, '请求的方法不存在' );
		}
		$data = $this->get_data();
		$invite_code = trim ( $data ['invite_code'] );
		if (strlen ( $invite_code ) != 32 || !preg_match ( '/^[0-9A-Za-z]{32}$/', $invite_code )) {
			$this->send_response ( 400, NULL, '活动邀请链接无效' );
		}
		$activityInviteModel = Activity_Invite_Model::instance();
		$invite_info = $activityInviteModel->getInvitationInfo($invite_code);
		if(!$invite_info) {
        	$this->send_response(400, NULL, '活动邀请链接无效');
        }
        $aid = $invite_info['aid'];
		$activity = $this->model->getActivityInfo($aid);
        if(!$activity) {
        	$this->send_response(400, NULL, '400502:活动不存在');
        }
        $now_time = time();
        if($now_time > $activity['end_time']) {
        	$this->send_response(400, NULL, '400502:活动已结束');
        }
        $applyResult = $this->model->getActivityApplyType($aid, $this->user_id);
        if($applyResult > 0) {
        	$this->send_response(400, NULL, '你已经报名了该活动');
        }
        $activityMember['aid'] = $aid;
		$activityMember['uid'] = $this->user_id;
		$activityMember['apply_type'] = Kohana::config('activity.apply_type.join');
		$activityMember['apply_time'] = $now_time;
		$activityMember['grade'] = Kohana::config('activity.grade.normal');
		$this->model->applyActivity($activityMember);
		$userModel = User_Model::instance();
		$userModel->insertTag($this->user_id, 15, $aid);
		$apply_type = Kohana::config('activity.apply_type.join');
		$this->_add_feed_comment($activity, 0, $apply_type, $this->user_id);
		$feedModel = Feed_Model::instance();
		$feedModel->addTab($aid, $activity['title'], 15, $this->user_id);
		$this->send_response(200);
	}
    
    private function _add_feed_comment($activity, $old_apply_type, $apply_type, $uid) {
    	$feedModel = new Feed_Model();
		if($apply_type == Kohana::config('activity.apply_type.join')) {
			$feedStatus = "参加";
			$applyStatus = "参加";
		} else if($apply_type == Kohana::config('activity.apply_type.interest')) {
			$feedStatus = "关注";
			$applyStatus = "感兴趣";
		}
		if($activity['gid'] != 0) {
			if($apply_type != Kohana::config('activity.apply_type.not_join')) {
				$commentModel = new Comment_Model();
				$content = "参与报名：".$applyStatus;
				if($activity['feed_id']) {
					$feed_id = $activity['feed_id'];
					$feedInfo = $feedModel->getFeedById($feed_id);
					if($feedInfo) {
						$group_type = $feedInfo[$feed_id]['group_type'];
						$group_id = $feedInfo[$feed_id]['group_id'];
						$owner_uid = $feedInfo[$feed_id]['owner_uid'];
						if(!$group_type) {		//好友
							$friendModel = Friend_Model::instance();
							$isFriend = $friendModel->check_isfriend($owner_uid, $uid);
							if($isFriend) {
								$commentModel->saveComment($feed_id, $content, $owner_uid);
							}
						} else if($group_type == 1){	//群内
							$groupModel = Group_Model::instance();
							$grade = $groupModel->getMemberGrade($group_id, $uid);
							if($grade > 0) {
								$commentModel->saveComment($feed_id, $content, $owner_uid);
							}
						} else if($group_type == 2){	//活动内
							$activityModel = Activity_Model::instance();
							$apply_type = $activityModel->getActivityApplyType($group_id, $uid);
							if($apply_type > 0) {
								$commentModel->saveComment($feed_id, $content, $owner_uid);
							}
						}
					}
				}
				if($activity['action_feed_id']) {
					$commentModel->saveComment($activity['action_feed_id'], $content, $activity['creator_id']);
				}
			}
		}
		if(!$old_apply_type && ($apply_type == Kohana::config('activity.apply_type.join') || $apply_type == Kohana::config('activity.apply_type.interest')) && $activity['is_allow_invite']) {
			$application = array('id'=>floatval($activity['aid']), 'title'=>'查看活动', 'url'=>'action/showblogbox/'.$activity['aid']);
			$feedModel->addFeed($uid, 7, $text=$feedStatus.'了活动：'.$activity['title'], $this->get_source(), $application, $at = array(), $images=array(),$sync=array(),$group_type=0,$group_id=0,$retweet_id=0,$allow_rt=0,$allow_comment=1,$allow_praise=1,$allow_del=1,$allow_hide=1);	
		}
    }

    /**
     * 重新报名
     * @param int $id 活动ID
     */
    public function update ($id = NULL)
    {

    }
    
    /**
     * 检查用户是否有查看活动的权限
     * @param array $activity 
     * @param float $uid
     * return boolean
     */
    private function _check_activity_view_permission($activity, $uid) {
		$permit = true;
		if($activity['gid'] == 0) {
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
    
} // End Activity_Member Controller


