<?php
defined('SYSPATH') or die('No direct access allowed.');

/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * 群控制器
 */
class Group_Controller extends Controller
{
    /**
     * 是否发布模式
     */
    const ALLOW_PRODUCTION = TRUE;
    /**
     * 群模型
     * @var Group_Model
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
     * 获取群列表
     */
    public function index ()
    {
        if ($this->get_method() != 'GET') {
            $this->send_response(405, NULL, '请求的方法不存在');
        } else {
            $data = $this->get_data();
            $type = (int)$this->input->get('type', 0);
            $result = $this->model->getUserAllGroup($this->user_id, $type);
            $showTabList = Tab_Model::instance()->getList($this->user_id, 1);
            $gidList = array();
            foreach ($showTabList as $key => $value) {
            	if($value['id'] != Kohana::config('uap.xiaomo_qun')) {
            		$gidList[$value['id']] = $key;
            	}
            }
            $groupList = array();
            $privateType = Kohana::config('group.type.private');
            foreach($result as $value) {
            	$sort = (int)$gidList[$value['gid']];
            	$groupInfo = array();
            	$groupInfo['is_hide'] = 0;
            	if(array_key_exists($value['gid'],	$gidList)) {
            		$groupInfo['is_hide'] = 0;
            	}
            	$groupInfo['id'] = floatval($value['gid']);
            	$groupInfo['name'] = $value['gname'];
            	$groupInfo['notice'] = $value['notice'];
            	$groupInfo['introduction'] = $value['introduction'];
            	$groupInfo['privacy'] = intval($value['privacy']);
            	$groupInfo['created_at'] = api::get_date($value['create_time']);
            	$groupInfo['modified_at'] = api::get_date($value['modify_time']);
            	$creator = array();
            	$creator['id'] = floatval($value['creator_id']);
            	$creator['name'] = sns::getrealname($value['creator_id']);
            	$creator['avatar'] = sns::getavatar($value['creator_id']);
            	$groupInfo['creator'] = $creator;
            	$groupInfo['master'] = $creator;
            	$managerIdList = $this->model->getManagerId($groupInfo['id']);
            	$managerList = array();
            	foreach ($managerIdList as $val) {
            		$manager = array();
            		$manager['id'] =  floatval($val['uid']);
            		$manager['name'] = sns::getrealname($manager['id']);
            		$manager['avatar'] = sns::getavatar($manager['id']);
            		$managerList[] = $manager;
            		unset($manager);
            	}
            	$groupInfo['manager'] = $managerList;
            	$groupInfo['member_count'] = (int)$this->model->getGroupMemberNum($value['gid']);
            	$groupList[$sort] = $groupInfo;
            	unset($groupInfo);
            	unset($creator);
            }
            ksort($groupList);
            foreach($groupList as $group) {
            	$return_list[] = $group;
            }
            $this->send_response(200, $return_list);
        }
    }
    
    /**
     * 
     * 获取群组信息
     * @param $id
     */
    public function get($id=NULL) {
    	if ($this->get_method() != 'GET') {
            $this->send_response(405, NULL, '请求的方法不存在');
        } 
    	if(empty($id)) {
        	$this->send_response(400, NULL, '400501:群组ID为空');
        }
		$data = $this->model->getGroupInfo($id);
		if(!$data) 
        	$this->send_response(400, NULL, '400502:群组不存在');
		$group_info = array(
			'id'=>$data['gid'],
			'name'=>$data['gname'],
			'name'=>$data['gname'],
			'notice'=>$data['notice'],
			'introduction'=>$data['introduction'],
			'privacy'=>$data['privacy'],
			'created_at'=>$data['create_time'],
			'modified_at'=>$data['modify_time'],
			'creator'=>array('id'=>$data['creator_id'],'name'=>sns::getrealname($data['creator_id']),'avatar'=>sns::getavatar($data['creator_id'])),
			'master'=>array('id'=>$data['master_id'],'name'=>sns::getrealname($data['master_id']),'avatar'=>sns::getavatar($data['master_id'])),
			'manager'=>$this->_get_group_manager($data['gid']),
			'member_count'=>(int)$this->model->getGroupMemberNum($data['gid']),
			'is_hide'=>$data['gname']
		);
		$this->send_response(200, $group_info);
    }

    /**
     * 创建群
     */
    public function create ()
    {
        if($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        $data = $this->get_data();
		$post = new Validation($data);
		$post->add_rules('name', 'required', 'length[1, 70]');
		$post->add_rules('introduction', 'length[0, 255]');
		$post->add_rules('notice', 'length[0, 255]');
		if ($post->validate()) {
			$groupInfo = $post->as_array();
			$groupInfo['gname'] = $groupInfo['name'];
			if(!isset($groupInfo['introduction'])) {
				unset($groupInfo['introduction']);
			}
			if(!isset($groupInfo['notice'])) {
				unset($groupInfo['notice']);
			}
			unset($groupInfo['name']);
			$groupInfo['type'] = isset($groupInfo['type'])?intval($groupInfo['type']):1;
			$groupInfo['privacy'] = isset($groupInfo['privacy'])?intval($groupInfo['privacy']):1;
			$groupNum = $this->model->getCreateGroupNum($this->user_id);
			$userModel = User_Model::instance();
			//$result = $userModel->get_user_info($this->user_id);
			if($groupInfo['privacy'] == Kohana::config('group.privacy.public')) {
				$groupLimit = Kohana::config('group.limit.public');
			} else {
				$groupLimit = Kohana::config('group.limit.private');
			}
			if($groupNum >= $groupLimit){
				$this->send_response(400, NULL, '400409:群可创建数已用完');
			}
			$nowTime = time();
			$groupInfo['create_time'] = $nowTime;
			$groupInfo['modify_time'] = $nowTime;
			$groupInfo['creator_id'] = $this->user_id;
			$groupInfo['master_id'] = $this->user_id;
			$groupInfo['member_number'] = 1;
			
			$group_id = $this->model->add($groupInfo);
			if(!$group_id){
				$this->send_response(400, NULL, '创建群失败');
			}
			$result = $this->model->addGroupMember($group_id, $this->user_id, Kohana::config('group.grade.master'));
			if(!$result) {
				$this->send_response(400, NULL, '添加群成员失败');
			}
			Tab_Model::instance()->create($this->user_id,Kohana::config('group.type.group'),$group_id);
			$this->send_response(200, array('id'=>floatval($group_id)));
		}
		$errors = $post->errors();
		foreach($errors as $key=>$value) {
			switch($key) {
				case 'type':
					$this->send_response(400, NULL, '400405:群类型非法');
					break;
				case 'name':
					if('required' == $value) {
						$this->send_response(400, NULL, '400404:群名称为空');
					} elseif('length' == $value) {
						$this->send_response(400, NULL, '400406:群名称超出长度限制');
					}
					break;
				case 'introduction':
					$this->send_response(400, NULL, '400407:群介绍超出长度限制');
					break;
				case 'notice':
					$this->send_response(400, NULL, '400408:群公告超出长度限制');
					break;
				default:
					$this->send_response(400, NULL, '400413:群数据非法');
					break;
			}
		}
		$this->send_response(400, NULL, '400413:群数据非法');
    }


    /**
     * 更新群信息
     * @param int $id 群组ID
     */
    public function update ($id = NULL)
    {
    	if($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        if(empty($id)) {
        	$this->send_response(400, NULL, '400409:群ID为空');
        }
        $grade = $this->model->getMemberGrade($id, $this->user_id);
        if($grade < Kohana::config('group.grade.manager')) {
            $this->send_response(400, NULL, '400404:非群管理员，无权限更新群信息');
        }
        $data = $this->get_data();
		if(isset($data['name']) && (strlen($data['name'])<2 || strlen($data['name'])>70)) {
			$this->send_response(400, NULL, '400404:群名称长度非法');
		}
		if(isset($data['notice']) && (strlen($data['notice'])<1 || strlen($data['name'])>500)) {
			$this->send_response(400, NULL, '400406:群公告长度非法');
		}
		$data['gname'] = $data['name'];
		if($this->model->update($id,$data)) {
			$this->send_response(200);
		}
		$this->send_response(400, NULL, '400414:更新失败');
    }
    
    /**
     * 删除群
     * @param int $id 群组ID
     */
    public function destroy ($id = NULL)
    {
     	if($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        } elseif (empty($id)) {
            $this->send_response(400, NULL, '400401:群ID为空');
        }
        $groupInfo = $this->model->getGroupInfo($id);
        if(!$groupInfo) {
        	$this->send_response(400, NULL, '400402:群不存在');
        }
        $grade = $this->model->getMemberGrade($id, $this->user_id);
        if($grade < Kohana::config('group.grade.master')) {
        	$this->send_response(400, NULL, '400413:非群主，无权限删除群');
        }
        
    	$memberList = $this->model->getGroupAllMember($id);
		$result = $this->model->delete($id);
		if($result) {
			$feedModel = Feed_Model::instance();
			$userModel = User_Model::instance();
			$content = '您加入的群"'.$groupInfo['gname'].'"已解散';
			foreach($memberList as $value) {
				$feedModel->deleteItem($id, 32, $value['uid']);
				$userModel->present_mo_notice(Kohana::config('uap.xiaomo'), $value['uid'], $content);
			}
			$this->send_response(200);
		}
		$this->send_response(400, NULL, '删除群失败');
    }
    
    /**
     * 
     * 退出群
     * @param $id
     */
    public function quit($id = 0) {
    	if ($this->get_method() != 'POST') {
			$this->send_response(405, NULL, '请求的方法不存在');
        }
        $data = $this->get_data();
        $groupId = (int) $id;
    	if(empty($id)) {
            $this->send_response(400, NULL, '400401:群ID为空');
        }
    	$groupInfo = $this->model->getGroupInfo($groupId);
        if (!$groupInfo) {
            $this->send_response(400, NULL, '400402:群不存在');
        }
        $grade = $this->model->getMemberGrade($groupId, $this->user_id);
        if($grade < Kohana::config('group.grade.normal')) {
            $this->send_response(400, NULL, '400403:你不是该群成员');
        }
        if($grade == Kohana::config('group.grade.master')) {
            $this->send_response(400, NULL, '400405:群创建者不允许退出群');
        }
    	if($this->model->delGroupMember($groupId, $this->user_id)) 
        	$this->send_response(200);
        $this->send_response(400, NULL, '400404:退出群失败');	
    }
    
    /**
     * 
     * 获取群组管理员
     */
    private function _get_group_manager($gid) {
    	$gm_user = array();
    	$gm = $this->model->getGroupManager($gid);
    	if($gm)	{
    		foreach($gm as $v) {
    			$gm_user[] = array('id'=>$v['uid'],'name'=>sns::getrealname($v['uid']),'avatar'=>sns::getavatar($v['uid']));
    		}
    	}
    	return $gm_user;
    }
    
    public function updateTab() {
    	$result = $this->model->updateTab();
    	$this->send_response(200,$result);
    }
    
    public function updateNum() {
    	$result = $this->model->updateNum();
    	$this->send_response(200,$result);
    }

} // End Group Controller
