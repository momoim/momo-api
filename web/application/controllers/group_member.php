<?php
defined('SYSPATH') or die('No direct access allowed.');

/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * 群成员控制器
 */
class Group_Member_Controller extends Controller
{
    /**
     * 是否发布模式
     */
    const ALLOW_PRODUCTION = TRUE;
    /**
     * 群成员模型
     * @var Group_Member_Model
     */
    protected $model;
    /**
     * 用户ID
     * @var int
     */

    /**
     * 构造函数
     */
    public function __construct ()
    {
        //必须继承父类控制器
        parent::__construct();
        $this->user_id = $this->getUid();
        //实例化模型
        $this->model = new Group_Model();
    }
    
    /**
     * 获取群成员列表
     */
    public function index ($id = NULL)
    {
        $data = $this->get_data();
        $id = (int) $id;
        $result = NULL;
        if ($this->get_method() != 'GET') {
            $this->send_response(405, NULL, '请求的方法不存在');
        } elseif (empty($id)) {
            $this->send_response(400, NULL, '400401:群ID为空');
        }
        $groupInfo = $this->model->getGroupInfo($id);
        if (!$groupInfo) {
            $this->send_response(400, NULL, '400402:群不存在');
        }
        $grade = $this->model->getMemberGrade($id, $this->user_id);
        if($grade < Kohana::config('group.grade.normal')) {
            $this->send_response(400, NULL, '400403:非群成员，无权限查看成员列表');
        }
        $start = 0;
        $maxMemberNum = Kohana::config('group.maxMemberNum');
        if($groupInfo['type'] == Kohana::config('group.type.public')) {
            $pos = $maxMemberNum['public'];
        } else {
            $pos = $maxMemberNum['private'];
        }
        $result = $this->model->getGroupMember($id, $start, $pos);
        $memberList = array();
        foreach ($result as $value) {
        	if($value['uid'] == Kohana::config('uap.xiaomo'))
        		continue;
        	$member = array();
        	$member['id'] = floatval($value['uid']);
        	$member['name'] = sns::getrealname($value['uid']);
        	$member['avatar'] = sns::getavatar($value['uid']);
        	$member['grade'] = intval($value['grade']);
        	$member['zone_code'] = $value['zone_code'];
        	$member['mobile'] = $value['mobile'];
        	$memberList[] = $member;
        	unset($member);
        }
        $this->send_response(200, $memberList);
    }
    
    /**
     * 
     * 添加群成员
     * @param $id
     */
    public function add($id = 0) {
    	if ($this->get_method() != 'POST') {
			$this->send_response(405, NULL, '请求的方法不存在');
        }
        $data = $this->get_data();
        $uid = $data['uid']?$data['uid']:'';
    	if(empty($uid)) {
            $this->send_response(400, NULL, '400403:成员uid为空');
        }
        $uids = explode(',', $uid);
        $groupId = (int) $id;
    	if(empty($id)) {
            $this->send_response(400, NULL, '400401:群ID为空');
        }
    	$groupInfo = $this->model->getGroupInfo($groupId);
        if (!$groupInfo) {
            $this->send_response(400, NULL, '400402:群不存在');
        }
        $user = sns::getuser($this->user_id);
        /*
    	$grade = $this->model->getMemberGrade($groupId, $this->user_id);
        if($grade < Kohana::config('group.grade.manager')) {
            $this->send_response(400, NULL, '400404:非群管理员，无权限添加成员');
        }
        */
        //查询群组成员总数是否超出最大限制(暂定100)
        $memberNum = $group_info['member_number'];
		if($group_info['type'] == Kohana::config('group.type.public')) {
			$maxMemberNum = Kohana::config('group.maxMemberNum.public');
		} else {
			$maxMemberNum = Kohana::config('group.maxMemberNum.private');
		}
        if($memberNum+count($uids) >= $maxMemberNum) {
            $this->send_response(400, NULL, '400110:群成员人数已满');
        }
        $add_uids = array();
        foreach ($uids as $v) {
	        $grade = $this->model->getMemberGrade($groupId, $v);
	        if(!$grade) {
	            $add_uids[] =$v;
	        }
        }
        $i = 0;
        $content = $user['realname'].'将您加入到群"'.$groupInfo['gname'].'"';
        $opt = array('group'=>array('type'=>1,'id'=>$groupId,'name'=>$groupInfo['gname']));
        $xiaomo_uid = Kohana::config('uap.xiaomo');
        if(count($add_uids) > 0) {
        	foreach($add_uids as $u) {
        		if($this->model->addGroupMember($groupId, $u, 1)) {
        			$i++;
        			Tab_Model::instance()->create($u,1,$groupId);
        			$this->model->addMemberNum($groupId);
	    			User_Model::instance()->present_mo_notice($xiaomo_uid,$u,$content,$opt);
        		}
        	}
        }
        $this->send_response(200, array('num'=>$i));
    }
    
   
    /**
     * 删除群成员
     */
    public function delete($id = 0) {
    	if ($this->get_method() != 'POST') {
			$this->send_response(405, NULL, '请求的方法不存在');
        }
        $data = $this->get_data();
        $uid = $data['uid']?$data['uid']:'';
    	if(empty($uid)) {
            $this->send_response(400, NULL, '400403:成员uid为空');
        }
        $uids = explode(',', $uid);
        $groupId = (int) $id;
    	if(empty($id)) {
            $this->send_response(400, NULL, '400401:群ID为空');
        }
    	$groupInfo = $this->model->getGroupInfo($groupId);
        if (!$groupInfo) {
            $this->send_response(400, NULL, '400402:群不存在');
        }
        $grade = $this->model->getMemberGrade($groupId, $this->user_id);
        if($grade < Kohana::config('group.grade.manager')) {
            $this->send_response(400, NULL, '400404:非群管理员，无权限删除成员');
        }
        $i=0;
    	foreach ($uids as $v) {
    		if($groupInfo['creator_id'] != $v) {
    			if($this->model->delGroupMember($groupId, $v)) {
    				$i++;
    				$this->model->reduceMemberNum($groupId);
    			} 
    		}
        }
        $this->send_response(200, array('num'=>$i));
    }

} // End Group_Member Controller
