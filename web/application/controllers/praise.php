<?php defined('SYSPATH') OR die('No direct access allowed.');
//赞相关功能控制器

class Praise_Controller extends Controller {

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();

        //模型
        $this->model   = new Praise_Model;
		$this->uid = $this->getUid();
    }

    /**
     * 添加赞
     */
    public function create() {
    	$post = $this->get_data();
    	$statuses_id = $post['statuses_id'];
        if(empty($statuses_id)) {
            $this->send_response(400, NULL, '对象id为空');
        }
        $feed = new Feed_Model;
        $doc = $feed->findFeed($statuses_id);
        if(!$doc) {
            $this->send_response(404, NULL, '该动态不存在');
        }
        $owner = $doc['owner_uid'];
        $had_praise = 0;
        foreach($doc['like_list'] as $key => $var) {
            $uid = $var['uid']?$var['uid']:$var['id'];
            if((int)$uid == (int)$this->uid) {
                $had_praise = 1;
                break;
            }
        }
        $group_member = array();
        if($doc['group_type']>0) {
			$grade = Group_Model::instance()->getMemberGrade($doc['group_id'], $this->uid);
			if($grade < 1)
		    	$this->send_response(400, NULL, '400:你不是该群成员，无权限赞');
    		$group_member = Group_Model::instance()->getGroupAllMember($doc['group_id']);
		}
		
        $is_bubble = ($owner == Kohana::config('uap.xiaomo'))?false:true;
        if($doc['last_updated'] && $had_praise == 0) {
        	if(count($group_member)>0) {
        		foreach($group_member as $member) {
	        		if($member['uid'] != $this->uid) {
		                if(!$feed->addAboutme($member['uid'],$this->uid,$doc['typeid'],0,'',array(),$statuses_id,4)) {
		                    $this->send_response(400, NULL, $feed->get_error_msg());
		                }
		            }
        		}
        	}elseif($owner != $this->uid) {
                if(!$feed->addAboutme($owner,$this->uid,$doc['typeid'],0,'',array(),$statuses_id,4)) {
                    $this->send_response(400, NULL, $feed->get_error_msg());
                }
            }
            $feed->addLike($this->uid, sns::getrealname($this->uid), $statuses_id,$is_bubble);
            if($doc['group_type']==1 && $doc['group_id'])
            	Tab_Model::instance()->lastModify($this->uid,1,$doc['group_id']);
            $this->send_response(200);
        }
        $this->send_response(400, NULL, '赞失败,你已经赞过');
    }

    /**
     * 查看某个对象赞
     */
    public function getlists() {
        header('Content-Type:application/json');
        $appdescribe = $this->input->get('appdescribe');
        $objid = $this->input->get('appid');

        $JsonArray['likeCount']= $this->model->getPraiseCount($appdescribe,$objid);
        $JsonArray['likeList'] = '[]';
        //如果有赞则查询最新二条
        if($JsonArray['likeCount']) {
            $JsonArray['likeList'] = $this->model->getPraiseUser($appdescribe,$objid,null,null,$this->uid);
            $JsonArray['likeList'] = json_encode($JsonArray['likeList']);
        }//End
        die(json_encode($JsonArray));
    }
}

