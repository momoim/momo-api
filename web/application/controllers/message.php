<?php defined('SYSPATH') OR die('No direct access allowed.');
class Message_controller extends Controller {
    public function __construct() {
        parent::__construct();
        $this->model   = new Message_Model();
        $this->message_type=null;

        $this->activityModel = Activity_Model::instance();
        $this->friendModel = Friend_Model::instance();
        $this->groupModel = Group_Model::instance();
	$this->groupContactModel = Group_Contact_Model::instance();
        $this->contactModel = Contact_Model::instance();
    }

    /**
     * 获取系统消息
     */
    public function index ()
    {
        if ($this->get_method() != 'GET') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        $pagesize   = $this->input->get('pagesize',5);
        $start   = $this->input->get('start',0);
        $new   = $this->input->get('new',0);
        $res = $this->model->getNewNoticeNum($this->user_id);
        if ($res) {
            $noticeNum = (int)$res;
        } else {
            $noticeNum = 0;
        }

        $i = 0;
        $str = $autoUpId = array();
        $body = '';
        $preurl = url::base();
        //@todo msgid 属性等整合完毕拿掉
        $result= $this->model->getNewNoticeList($this->user_id,$start,$pagesize,null,$new);
        //print_r($result);exit;
        if($result) {
            $config = Kohana::config_load('noticetpl');//加载模板配置文件
            foreach ($result as $val) {
                //查找模板
                foreach ($config as $tval) {
                    $tplid = !empty($val['status']) ? ($val['tplid'].'0'.$val['status']) : $val['tplid'];
                    if ($tval['id'] == $tplid) {
                        $tpl = $tval; break;
                    }
                }
                $title = $this->model->parsedata($val['title'], $tpl['title'], $preurl);
                $body = '';
                if($val['body']){
                    $body = json_decode($val['body'],true);
                }
                $group_obj = json_decode($val['title']);
                $gid=isset($group_obj->group[0]->id)?$group_obj->group[0]->id:'';
                $gname=isset($group_obj->group[0]->name)?$group_obj->group[0]->name:'';
                $uid = $group_obj->uid;
                $uname = $group_obj->name;
                $sender_id = $uid?$uid:$val['authorid'];
                $sender_name = $uname?$uname:$val['author'];
                $item['id'] = $this->_str($val['id'],0);
                $item['created_at'] = api::get_date($val['addtime']);
                $item['sender']['id'] = $this->_str($sender_id,0);
                $item['sender']['name'] = $this->_str($sender_name);
                $item['sender']['avatar'] = sns::getavatar($val['authorid']);
                $item['need_handle'] = in_array($tplid,Kohana::config('uap.message_handle'))?1:0;
                $item['type']['id'] = $this->_str($tplid,0);
                $item['type']['name'] = '';
                $item['is_handle'] = in_array($tplid,array(1,5,7,9,12,16))?1:0;
                $item['text'] = $this->_str($title);
                if($val['tplid'] == 6 || preg_match('/^5/is',$val['tplid'])) {
                    $apply = $this->groupModel->getUserApplyGroup($gid, $val['authorid']);
                    $item['explain'] = $this->_str($apply['reason']);
                } else {
                    $item['explain'] = isset($body['explain'])?str::unhtmlspecialchars($body['explain']):'';
                }
                $item['is_new'] = $this->_str($val['isnew'],0);
                if($this->has_message_type($val['tplid'])) {
                    if($this->message_type != null) {
                        $opt[$this->message_type]  = $this->message_opt($val['title'],$val['id'],$body);
                    }
                } else {
                    $opt = new stdClass();
                }

                $item['opt'] = $opt;
                $str[] = $item;
                unset($opt);
            }
        }
        unset($config, $result);

        //更新己读
        if(!empty($autoUpId)) {
            $this->model->putSetNoticeOld($this->user_id,$autoUpId);
        }

        $remainNum = $this->model->getNewNoticeNum($this->user_id);//获取新通知条数
        if ($remainNum) {
            $remainNum = (int)$remainNum;
        } else {
            $remainNum = 0;
        }

        if(!empty($str)) {
            $data['count'] = $noticeNum;
            $data['new_count'] = $remainNum;
            $data['unhandle_count'] = $remainNum;
            $data['message'] = $str;
        }else {
            $data['count'] = 0;
            $data['new_count'] = 0;
            $data['unhandle_count'] = 0;
            $data['message'] = array();
        }

        $this->send_response(200,$data);
    }

    /**
     * 获取系统消息列表，提供给j2me使用
     */
    public function lists()
    {
        if ($this->get_method() != 'GET') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        $pagesize   = $this->input->get('pagesize',5);
        $start   = $this->input->get('start',0);
        $new   = $this->input->get('new',0);
        $res = $this->model->getNewNoticeNum($this->user_id);
        if ($res) {
            $noticeNum = (int)$res;
        } else {
            $noticeNum = 0;
        }

        $i = 0;
        $str = $autoUpId = array();
        $body = '';
        $preurl = url::base();
        //@todo msgid 属性等整合完毕拿掉
        $result= $this->model->getNewNoticeList($this->user_id,$start,$pagesize,null,$new);
        if($result) {
            $config = Kohana::config_load('noticetpl');//加载模板配置文件
            foreach ($result as $val) {
                //查找模板
                foreach ($config as $tval) {
                    $tplid = !empty($val['status']) ? ($val['tplid'].'0'.$val['status']) : $val['tplid'];
                    if ($tval['id'] == $tplid) {
                        $tpl = $tval; break;
                    }
                }
                if($tplid == 10) { continue;}
                $title = $this->model->parsedata($val['title'], $tpl['title'], $preurl);
                $body = '';
                if($val['body']){
                    $body = json_decode($val['body'],true);
                }

                $group_obj = json_decode($val['title']);
                $gid=isset($group_obj->group[0]->id)?$group_obj->group[0]->id:'';
                $gname=isset($group_obj->group[0]->name)?$group_obj->group[0]->name:'';
                $item['id'] = $val['id'];
                $item['created_at'] = api::get_date($val['addtime']);
                $item['sender']['id'] = $val['authorid'];
                $item['sender']['name'] = $val['author'];
                $item['sender']['avatar'] = sns::getavatar($val['authorid']);
                $item['need_handle'] = in_array($tplid,Kohana::config('uap.message_handle'))?1:0;
                $item['type']['id'] = $tplid;
                $item['type']['name'] = '';
                $item['is_handle'] = in_array($tplid,array(1,5,7,9,12,16))?1:0;
                $item['text'] = str_replace('[action_info]','[action_invite]',$title);
                if($val['tplid'] == 6 || preg_match('/^5/is',$val['tplid'])) {
                    $apply = $this->groupModel->getUserApplyGroup($gid, $val['authorid']);
                    $item['explain'] = $apply['reason'] ? $apply['reason'] : '';
                } else {
                    $item['explain'] = isset($body['explain'])?str::unhtmlspecialchars($body['explain']):'';
                }
                $item['is_new'] = $val['isnew'];
                if($this->has_message_type($val['tplid'])) {
                    if($this->message_type != null) {
                        $opt[$this->message_type]  = $this->message_opt($val['title'],$val['id'],$body,true);
                    }
                } else {
                    $opt = new stdClass();
                }

                $item['opt'] = $opt;
                $str[] = $item;
                unset($opt);
            }
        }
        unset($config, $result);

        //更新己读
        if(!empty($autoUpId)) {
            $this->model->putSetNoticeOld($this->user_id,$autoUpId);
        }

        $remainNum = $this->model->getNewNoticeNum($this->user_id);//获取新通知条数
        if ($remainNum) {
            $remainNum = (int)$remainNum;
        } else {
            $remainNum = 0;
        }

        if(!empty($str)) {
            $data['count'] = $noticeNum;
            $data['new_count'] = $remainNum;
            $data['unhandle_count'] = $remainNum;
            $data['message'] = $str;
        }else {
            $data['count'] = 0;
            $data['new_count'] = 0;
            $data['unhandle_count'] = 0;
            $data['message'] = array();
        }

        $this->send_response(200,$data);
    }

    public function friend_apply() {
        $message_id   = $this->input->get('id',0);
        if(!$message_id) {
            $this->send_response(400, NULL, '400102:消息id非法');
        }
        $result = $this->model->getNoticeInfo(array('id'=>$message_id, 'uid'=>$this->user_id), true);
        if (!$result) {
            $this->send_response(400, NULL, '400101:消息体不存在或已经被处理');
        }
        $tmp = $result['body'] ? json_decode($result['body'], true) : array("explain"=>"");

        //取得共同好友
        $ffids = $this->friendModel->getAllFriendIDs($result['authorid'],false);
        $mfids = $this->friendModel->getAllFriendIDs($this->user_id,false);
        $together = array_intersect($ffids, $mfids);

        $data = sns::getuser($result['authorid']);
        $tmp['reside'] = '';
        if ($data['resideprovince'] || $data['residecity']) {
            $config = Kohana::config_load('cityarray');//加载城市数组

            $province = $data['resideprovince'] ? $config['province'][$data['resideprovince']] : "";
            $city = $data['residecity'] ? $config['city'][$data['residecity']] : "";
            $tmp['reside'] =  $province. " " . $city;
        }

        $tmp['name'] = $data['realname'];
        $tmp['sex'] = $data['sex']==1 ? "男" : "女";
        $tmp['fid'] = $result['authorid'];
        $tmp['explain'] = $tmp['explain']?str::unhtmlspecialchars($tmp['explain']):'';
        unset($data, $ffids, $mfids, $config);

        $str = "";
        $urlpre = url::base();
        $avatar = sns::getavatar($result['authorid']);

        $same_friend = array();
        $together_count = 0;
        if (!empty($together)) {
            $together_count = count($together);
            $i = 0;
            foreach ($together as $val) {
                $item = array();
                $item['id'] = $val;
                $item['name'] = sns::getrealname($val);
                $same_friend[] = $item;
                if(9 < ++$i) {
                    break;
                }

            }
        }
        $return_data  = array('id'=>$message_id,'friend'=>array('id'=>$result['authorid'],'name'=>$tmp['name'],'location'=>$tmp['reside'],'sex'=>$tmp['sex'],'avatar'=>$avatar),'explain'=>$tmp['explain'],'together_count'=>$together_count,'together'=>$same_friend);
        $this->send_response(200,$return_data);
    }

    /**
     * 群组邀请
     */
    public function group_invite() {
        $message_id   = $this->input->get('id',0);
        if(!$message_id) {
            $this->send_response(400, NULL, '400102:消息id非法');
        }
        $result = $this->model->getNoticeInfo(array('id'=>$message_id, 'uid'=>$this->user_id), true);
        if (!$result) {
            $this->send_response(400, NULL, '400101:消息体不存在或已经被处理');
        }
        $tmpdata = json_decode($result['title'],true);
        $group_id=$tmpdata['group'][0]['id'];
        $gname=$tmpdata['group'][0]['name'];
        $friend_id=$tmpdata['uid'];
        $groupInfo = $this->groupModel->getGroupInfo($group_id);
        if(!$groupInfo) {
            $this->model->putChangeTplByid($this->user_id,$message_id,4);
            $this->send_response(400, NULL, '400102:群组不存在');
        }
        $tmp = $result['body'] ? json_decode($result['body'], true) : array("explain"=>"");

        //取得共同好友
        $memberList = $this->groupModel->getGroupMember($group_id, 0, 100);//获取群组成员id列表
        $memberids = array();
        foreach($memberList as $value) {
            if($value['uid'] != $friend_id) {
                $memberids[] = $value['uid'];
            }
        }
        $mfids = $this->friendModel->getAllFriendIDs($this->user_id,false);
        $together = array_intersect($memberids, $mfids);

        $str = "";
        $urlpre = url::base();

        $avatar = sns::getavatar($friend_id);

        $same_friend = array();
        $together_count=0;
        if (!empty($together)) {
            $together_count = count($together);
            $i = 0;
            foreach ($together as $val) {
                ++$i;
                $item = array();
                $item['id'] = $val;
                $item['name'] = sns::getrealname($val);
                $same_friend[] = $item;
                if($i > 9) {
                    break;
                }
            }
        }

        $return_data  = array('id'=>$message_id,'group_id'=>$group_id,'name'=>$groupInfo['gname'],'introduction'=>$groupInfo['introduction'],'together_count'=>$together_count,'together'=>$same_friend);
        $this->send_response(200,$return_data);
    }

    /**
     * 管理员获取群组申请请求
     */
    public function group_apply() {
        $message_id   = $this->input->get('id',0);
        if(!$message_id) {
            $this->send_response(400, NULL, '400102:消息id非法');
        }
        $result = $this->model->getNoticeInfo(array('id'=>$message_id, 'uid'=>$this->user_id), true);
        if (!$result) {
            $this->send_response(400, NULL, '400101:消息体不存在或已经被处理');
        }
        $title_obj = json_decode($result['title']);
        $gid = $title_obj->group[0]->id;
        $gname = $title_obj->group[0]->name;
        $req_uid = $result['authorid'];
        $group_info = $this->groupModel->getGroupInfo($gid);
        if(!$group_info) {
            $this->model->putChangeTplByid($this->user_id, $message_id, 6);
            $this->send_response(400, NULL, '400102:群组不存在');
        }
        $grade = $this->groupModel->getmembergrade($gid, $this->user_id);
        if($grade < 2){
            $this->model->putChangeTplByid($this->user_id, $message_id, 5);
            $this->send_response(400, NULL, '400103:您已不是该群管理员，无权限审核');
        }
        $urlpre = url::base();
        $data = sns::getuser($req_uid);
        $tmp['reside'] = '';
        if ($data['resideprovince'] || $data['residecity']) {
            $config = Kohana::config_load('cityarray');//加载城市数组

            $province = $data['resideprovince'] ? $config['province'][$data['resideprovince']] : "";
            $city = $data['residecity'] ? $config['city'][$data['residecity']] : "";
            $tmp['reside'] =  $province. " " . $city;
        }
        $tmp['name'] = $data['realname'];
        $tmp['sex'] = $data['sex']==1 ? "男" : "女";
        unset($data, $result, $config);

        $apply = $this->groupModel->getUserApplyGroup($gid, $req_uid);
        if (!$apply) {
            $this->send_response(400, NULL, '400104:群申请记录不存在');
        }
        if($apply['status'] && $apply['manager_uid'] != $this->user_id){
            $this->model->putChangeTplByid($this->user_id, $message_id, 3);
            $this->send_response(400, NULL, '400105:请求已被其他管理员处理');
        }
        $note = $apply['reason'];
        
        $return_data  = array('id'=>$message_id,'group_id'=>$gid,'name'=>$gname,'explain'=>$note,'introduction'=>$group_info['introduction'],'friend'=>array('id'=>$req_uid,'name'=>$tmp['name'],'location'=>$tmp['reside'],'sex'=>$tmp['sex'],'avatar'=>sns::getavatar($req_uid)));
        $this->send_response(200,$return_data);
    }

    public function update() {
        $data = $this->get_data();
        $result['user_id'] = $this->user_id;
        $result['text'] = $data['text'];

        $this->send_response(200,$result);
    }

    public function has_message_type($typeid) {
        $is_typed = false;
        if($typeid == 10) {
            $this->message_type =   'action_info_modify';
            $is_typed = true;
        }elseif($typeid == 3 || $typeid == 1 || preg_match('/^10/is',$typeid)) {
            $this->message_type = 'friend_apply';
            $is_typed = true;
        }elseif($typeid == 4) {
            $this->message_type =  'recommend_friend';
            $is_typed = true;
        }elseif($typeid == 6 || preg_match('/^5/is',$typeid)) {
            $this->message_type =   'group_apply';
            $is_typed = true;
        }elseif(preg_match('/^7/is',$typeid) || preg_match('/^16/is',$typeid) || preg_match('/^12/is',$typeid)) {
            $this->message_type =   'group_invite';
            $is_typed = true;
        }elseif($typeid == 8) {
            $this->message_type =   'personal_info_modify';
            $is_typed = true;
        }elseif($typeid == 9 || $typeid == 13 || $typeid == 17 || $typeid == 18) {
            $this->message_type =   'action_info';
            $is_typed = true;
        }
        return $is_typed;
    }


    public function message_opt($data,$message_id,$body=null,$mini=false) {
        switch($this->message_type) {
            case 'recommend_friend':
               return $this->message_opt_recommend_friend($data,$message_id,$mini);
               break;
            case 'action_info':
               return $this->message_opt_action_info($data,$message_id,$mini);
               break;
            case 'birthday_remind':
               return $this->message_opt_birthday_remind($data,$message_id,$body,$mini);
               break;
            case 'friend_apply':
               return $this->message_opt_friend_apply($data,$message_id,$mini);
               break;
            case 'group_invite':
               return $this->message_opt_group_invite($data,$message_id,$mini);
               break;
            case 'group_apply':
               return $this->message_opt_group_apply($data,$message_id,$mini);
               break;
            case 'personal_info_modify':
               return $this->message_opt_personal_info_modify($data,$message_id,$body,$mini);
               break;
            case 'action_info_modify':
               return $this->message_opt_action_info_modify($data,$message_id,$body,$mini);
               break;
        }
    }
    
    public function message_opt_group_apply($data,$message_id,$mini) {
        $result = $this->model->getNoticeInfo(array('id'=>$message_id, 'uid'=>$this->user_id), true);
        $title_obj = json_decode($result['title']);
        $gid = $title_obj->group[0]->id;
        $gname = $title_obj->group[0]->name;
        $req_uid = $result['authorid'];
        if(!$mini) {
            $group_info = $this->groupModel->getGroupInfo($gid);
            $urlpre = url::base();
            $data = sns::getuser($req_uid);
            $tmp['reside'] = '';
            if ($data['resideprovince'] || $data['residecity']) {
                $config = Kohana::config_load('cityarray');//加载城市数组

                $province = $data['resideprovince'] ? $config['province'][$data['resideprovince']] : "";
                $city = $data['residecity'] ? $config['city'][$data['residecity']] : "";
                $tmp['reside'] =  $province. " " . $city;
            }
            $tmp['name'] = $data['realname'];
            $tmp['sex'] = $data['sex']==1 ? "男" : "女";
            unset($data, $result, $config);

            $group_together_friends = $this->get_group_together_friends($gid,$req_uid);
            $note = $apply['reason'];
            return array('id'=>$this->_str($gid,0),'name'=>$this->_str($gname),'introduction'=>$this->_str($group_info['introduction']),'together_count'=>$this->_str($group_together_friends['count'],0),'together'=>$this->_str($group_together_friends['data'],array()),'friend'=>array('id'=>$this->_str($req_uid,0),'name'=>$this->_str($tmp['name']),'location'=>$this->_str($tmp['reside']),'sex'=>$this->_str($tmp['sex'],0),'avatar'=>$this->_str(sns::getavatar($req_uid))));
        }
        return array('id'=>$this->_str($gid,0),'name'=>$this->_str($gname,''));
        
    }

    public function message_opt_group_invite($data,$message_id,$mini) {
        $tmpdata = json_decode($data, TRUE);
        $result = $this->model->getNoticeInfo(array('id'=>$message_id, 'uid'=>$this->user_id), true);
        $gid=$tmpdata['group'][0]['id'];
        $gname=$tmpdata['group'][0]['name'];
        $fid=$tmpdata['uid'];
        if(!$mini) {
            $groupInfo = $this->groupModel->getGroupInfo($gid);
            $tmp = $result['body'] ? json_decode($result['body'], true) : array("explain"=>"");
            $group_together_friends = $this->get_group_together_friends($gid,$fid);

            return array('id'=>$this->_str($gid,0),'name'=>$this->_str($groupInfo['gname']),'introduction'=>$this->_str($groupInfo['introduction']),'together_count'=>$this->_str($group_together_friends['count'],0),'together'=>$this->_str($group_together_friends['data'],array()))
                ;
        } else {
            return array('id'=>$this->_str($gid,0),'name'=>$this->_str($gname));
        }
    }

    /**
     * 取群组共同好友
     * @param <type> $gid
     * @return <type>
     */
    public function get_group_together_friends($gid,$fid) {
        $same_friend = array();
        $together_count = 0;
        //取得共同好友
        $memberList = $this->groupModel->getGroupMember($gid, 0, 100);//获取群组成员id列表
        $memberids = array();
        foreach($memberList as $value) {
            if($value['uid'] != $fid) {
                $memberids[] = $value['uid'];
            }
        }
        $mfids = $this->friendModel->getAllFriendIDs($this->user_id,false);
        $together = array_intersect($memberids, $mfids);

        $str = "";
        $urlpre = url::base();

        $avatar = sns::getavatar($fid);

        $same_friend = array();
        $together_count=0;
        if (!empty($together)) {
            $together_count = count($together);
            $i = 0;
            foreach ($together as $val) {
                ++$i;
                $item = array();
                $item['id'] = $val;
                $item['name'] = sns::getrealname($val);
                $same_friend[] = $item;
                if($i > 10) {
                    break;
                }
            }
        }
        return array('count'=>$together_count,'data'=>$same_friend);
    }

    public function message_opt_action_info_modify($data,$message_id,$body,$mini) {
        $chagne_item = array();
        if(isset($body['summary']) && is_array($body['summary'])) {
            foreach($body['summary'] as $key => $value) {
                $chagne_item[] = array('name'=>$this->_str($value['describe']),'text'=>$this->_str($value['info']));
            }
        }
        return $chagne_item;
    }
    public function message_opt_personal_info_modify($title,$message_id,$body,$mini) {
        $chagne_item = array();
        if(isset($body['summary']) && is_array($body['summary'])) {
            foreach($body['summary'] as $key => $value) {
                $chagne_item[] = array('name'=>$this->_str($value['describe']),'text'=>$this->_str($value['info']));
            }
        }
        return $chagne_item;
    }

    public function message_opt_birthday_remind($data,$message_id,$body,$mini) {

        $chagne_item = array();
        if(isset($body['summary']) && is_array($body['summary'])) {
            foreach($body['summary'] as $key => $value) {
                $chagne_item[] = array('name'=>$this->_str($value['describe']),'text'=>$this->_str($value['info']));
            }
        }
        return $chagne_item;
    }

    public function message_opt_friend_apply($data,$message_id,$mini) {
        if($mini) return array();
        $result = $this->model->getNoticeInfo(array('id'=>$message_id, 'uid'=>$this->user_id), true);
        $tmp = $result['body'] ? json_decode($result['body'], true) : array("explain"=>"");

        //取得共同好友
        $ffids = $this->friendModel->getAllFriendIDs($result['authorid'],false);
        $mfids = $this->friendModel->getAllFriendIDs($this->user_id,false);
        $together = array_intersect($ffids, $mfids);

        $data = sns::getuser($result['authorid']);
        $tmp['reside'] = '';
        if ($data['resideprovince'] || $data['residecity']) {
            $config = Kohana::config_load('cityarray');//加载城市数组

            $province = isset($data['resideprovince']) ? isset($config['province'][$data['resideprovince']])?$config['province'][$data['resideprovince']]:'' : "";
            $city = isset($data['residecity']) ? isset($config['city'][$data['residecity']])?$config['city'][$data['residecity']]:'' : "";
            $tmp['reside'] =  $province. " " . $city;
        }

        $tmp['name'] = $data['realname'];
        $tmp['sex'] = $data['sex']==1 ? "男" : "女";
        $tmp['fid'] = $result['authorid'];
        $tmp['explain'] = $tmp['explain']?str::unhtmlspecialchars($tmp['explain']):'';
        unset($data, $ffids, $mfids, $config);

        $str = "";
        $urlpre = url::base();
        $avatar = sns::getavatar($result['authorid']);

        $same_friend = array();
        $together_count = 0;
        if (!empty($together)) {
            $together_count = count($together);
            $i = 0;
            foreach ($together as $val) {
                $item = array();
                $item['id'] = $val;
                $item['name'] = sns::getrealname($val);
                $same_friend[] = $item;
                if(9 < ++$i) {
                    break;
                }

            }
        }
        return array('location'=>$this->_str($tmp['reside']),'sex'=>$this->_str($tmp['sex'],0),'together_count'=>$together_count,'together'=>$same_friend);
    }
    

    public function message_opt_action_info($data,$message_id,$mini) {
        $tmpdata = json_decode($data, TRUE);
        $nowtime = time();
        $action_info = $this->activityModel->getActivityInfo($tmpdata['action'][0]['id']);
		if(!$action_info) {
			$action_info['aid'] = $tmpdata['action'][0]['id'];
			$action_info['title'] = $tmpdata['action'][0]['name'];
			$action_info['start_time'] = '';
			$action_info['end_time'] = '';
			$action_info['spot'] = '';
			$status = 3;
			$action_info['content'] = '';
		} else {
			if($action_info['end_time'] < $nowtime){
					$status = 3;
			} else if($action_info['start_time'] > $nowtime) {
					$status = 1;
			} else{
					$status = 2;
			}
		}
        if($mini) {
            return array('id'=>$this->_str($action_info['aid'],0),'name'=>$this->_str($action_info['title']));
        } else {
            return array('id'=>$this->_str($action_info['aid'],0),'name'=>$this->_str($action_info['title']),'start_time'=>(int)$action_info['start_time'],'end_time'=>(int)$action_info['end_time'],'location'=>$this->_str($action_info['spot']),'status'=>$this->_str($status),'text'=>strip_tags($this->_str($action_info['content'])));
        }
    }


    public function message_opt_recommend_friend($data,$message_id,$mini) {
        $tmpdata = json_decode($data,true);
        $recommend = array();
        if(is_array($tmpdata['recommend']) && count($tmpdata['recommend']) > 0) {
            foreach($tmpdata['recommend'] as $k=>$v) {
                $recommend[$k]['id'] = $this->_str($v['id'],0);
                $recommend[$k]['name'] = $this->_str($v['name']);
                $recommend[$k]['avatar'] = sns::getavatar($v['id']);
            }
        }
        return $recommend;
    }

    
    /**
     * 忽略好友请求
     * @return <type>
     */
    public function ignore_friend() {
        $data = $this->get_data();
        $message_id   = $data['id'];
        if(!$message_id) {
            $this->send_response(400, NULL, '400102:消息id非法');
        }
        $result = $this->model->getNoticeInfo(array('id'=>$message_id, 'uid'=>$this->user_id), true);
        if(!$result) {
            $this->send_response(400, NULL, '400101:消息体不存在或已经被处理');
        }

        $this->model->putSetNoticeOld($this->user_id, $message_id);
        $this->model->putChangeTplByid($this->user_id, $message_id, 2);
        $this->send_response(200);
    }

    /**
     * 接受好友请求
     * @return <type>
     */
    public function agree_friend() {
        $data = $this->get_data();
        $message_id   = $data['id'];
        if(!$message_id) {
            $this->send_response(400, NULL, '400102:消息id非法');
        }
        $result = $this->model->getNoticeInfo(array('id'=>$message_id, 'uid'=>$this->user_id), true);
        if(!$result) {
            $this->send_response(400, NULL, '400101:消息体不存在或已经被处理');
        }
        $friend_id = $result['authorid'];
        if ($this->user_id == $friend_id) {
            $this->send_response(400, NULL, '400106:自己不能加自己为好友');
        }

        //切换模板
        if (empty($message_id)) {
            $this->model->putChangeTpl($this->user_id, $friend_id, Kohana::config('noticetpl.friendReq.id'), 1);
        } else {
            $this->model->putChangeTplByid($this->user_id, $message_id, 1);
        }

        if (!$this->friendModel->check_isfriend($this->user_id, $friend_id)) {
            $this->friendModel->add_friend($this->user_id, $friend_id, $this->get_source());

            $user = sns::getrealname($this->user_id);
            $appid = Kohana::config('uap.app.friend');
            $title = array('uid' => $this->user_id, 'name' => $user);
            $this->model->putAddNotice($friend_id, $appid, 'agreeFriendReq', $title);

            $this->send_response(200);
        }
        $this->send_response(400, NULL, '400107:已经是好友');
    }

    /**
     * 管理员接受群组申请
     * @return <type>
     */
    public function agree_group_admin() {
        $data = $this->get_data();
        $message_id   = $data['id'];
        if(empty($message_id)) {
            $this->send_response(400, NULL, '400102:消息id非法');
        }

        $result = $this->model->getNoticeInfo(array('id'=>$message_id, 'uid'=>$this->user_id), true);
        if (!$result) {
            $this->send_response(400, NULL, '400101:消息体不存在或已经被处理');
        }
        $group_tmp = json_decode($result['title'],true);
        $gid = $group_tmp['group'][0]['id'];
        $gname = $group_tmp['group'][0]['name'];

        $group_info = $this->groupModel->getGroupInfo($gid);
        if(!$group_info) {
            $this->model->putChangeTplByid($this->user_id, $message_id, 6);
            $this->send_response(400, NULL, '400109:群不存在');
        }
        $grade = $this->groupModel->getmembergrade($gid, $this->user_id);
        if($grade < 2){
            $this->model->putChangeTplByid($this->user_id, $message_id, 5);
            $this->send_response(400, NULL, '400111:你不是群组管理员，无权进行此操作');
        }
        $req_uid = $result['authorid'];

        $apply = $this->groupModel->getUserApplyGroup($gid, $req_uid);
        if(!$apply) {
            $this->send_response(400, NULL, '400112:群申请记录不存在');
        }
        if($apply['status'] && $apply['manager_uid'] != $this->user_id){
            $this->model->putChangeTplByid($this->user_id, $message_id, 3);
            $this->send_response(400, NULL, '400113:请求已被其他管理员处理');
        }
        $gname = $group_info['gname'];
        $username  = sns::getrealname($this->user_id);
        $title = array('uid'=>$this->user_id, 'name'=>$username, 'group'=>array(array('id'=>$gid,'name'=>$gname)));
        $grade = $this->groupModel->getmembergrade($gid, $req_uid);
        if($grade > 0){
            //已经是群成员
            $this->model->putChangeTplByid($this->user_id, $message_id, 3);
            $this->send_response(400, NULL, '400108:已经是群成员');
        }
        //查询群组成员总数是否超出最大限制(暂定100)
        $memberNum = $group_info['member_number'];
		if($group_info['type'] == Kohana::config('group.type.public')) {
			$maxMemberNum = Kohana::config('group.maxMemberNum.public');
		} else {
			$maxMemberNum = Kohana::config('group.maxMemberNum.private');
		}
        if($memberNum >= $maxMemberNum){
            $this->model->putChangeTplByid($this->user_id, $message_id, 4);
            $this->send_response(400, NULL, '400114:群组成员人数已满');
        }
        $ret = $this->groupModel->addGroupMember($gid, $req_uid, 1);
        $this->groupModel->addMemberNum($gid);
		$feedModel = new Feed_Model();
		if($group_info['type'] == Kohana::config('group.type.private')) {
			$dateline = time();
			try{
				//添加群组通讯录联系人
				$ret = $this->groupContactModel->addGroupContactByUserCard($gid, $req_uid, $dateline);
			}catch (Exception $exc) {
                                $this->send_response(400, NULL, '400115:导入个人名片到群组通讯录联系人失败');
			}
			$ginfo['modify_time'] = $dateline;
			$ret = $this->groupModel->modifyGroup($gid, $ginfo);
		} else if($group_info['type'] == Kohana::config('group.type.public')) {
			//发送加入公开群组动态
			$application = array('id'=>floatval($gid), 'title'=>'查看群组', 'url'=>'group/'.$gid);
			$feedModel->addFeed($req_uid, 2, $text='加入了群组：'.$group_info['gname'], $this->get_source(), $application, $at = array(), $images=array(),$sync=array(),$group_type=0,$group_id=0,$retweet_id=0,$allow_rt=0,$allow_comment=1,$allow_praise=1,$allow_del=1,$allow_hide=1);
		}
    	$commentModel = new Comment_Model();
    	if($group_info['feed_id']) {
			$friendModel = Friend_Model::instance();
			$isFriend = $friendModel->check_isfriend($req_uid, $group_info['creator_id']);
			if($isFriend) {
				$commentModel->saveComment($group_info['feed_id'], '加入了本群', $group_info['creator_id'], 0, 0, $req_uid);
			}
		}
		if($group_info['group_feed_id']) {
			$commentModel->saveComment($group_info['group_feed_id'], '加入了本群', $group_info['creator_id'], 0, 0, $req_uid);
		}
		//添加群到首页tab列表
		$userModel = new User_Model();
		$userModel->insertTag($req_uid, 7, $gid);
		$feedModel->addTab($gid, $group_info['gname'], 7, $req_uid);
        $appid = Kohana::config('uap.app.group');
        // 添加通知
        $this->model->putAddNotice($req_uid, $appid, 'agreeJoinGroup', $title);
        $this->model->putChangeTplByid($this->user_id, $message_id, 1);
        //更新申请加入群记录状态
	$this->groupModel->dealApplyMember($gid, $req_uid, $this->user_id);
        $this->send_response(200);
    }


    /**
     * 管理员忽略群组申请
     * @return <type>
     */
    public function ignore_group_admin() {
        $data = $this->get_data();
        $message_id   = $data['id'];
        if(empty($message_id)) {
            $this->send_response(400, NULL, '400102:消息id非法');
        }
		$result = $this->model->getNoticeInfo(array('id'=>$message_id, 'uid'=>$this->user_id), true);
        if (!$result) {
            $this->send_response(400, NULL, '400101:消息体不存在或已经被处理');
        }
		$this->model->putSetNoticeOld($this->user_id, $message_id);
        $this->send_response(200);
    }


    public function ignore_group() {
        $data = $this->get_data();
        $message_id   = $data['id'];
        if(empty($message_id)) {
            $this->send_response(400, NULL, '400102:消息id非法');
        }
		$result = $this->model->getNoticeInfo(array('id'=>$message_id, 'uid'=>$this->user_id), true);
        if (!$result) {
            $this->send_response(400, NULL, '400101:消息体不存在或已经被处理');
        }
        $this->model->putSetNoticeOld($this->user_id, $message_id);
        $this->send_response(200);
    }

    public function agree_group() {
        $data = $this->get_data();
        $message_id   = $data['id'];
        if(empty($message_id)) {
            $this->send_response(400, NULL, '400102:消息id非法');
        }

        $result = $this->model->getNoticeInfo(array('id'=>$message_id, 'uid'=>$this->user_id), true);
        if (!$result) {
            $this->send_response(400, NULL, '400101:消息体不存在或已经被处理');
        }
        $group_tmp = json_decode($result['title'],true);
        $gid = $group_tmp['group'][0]['id'];
        $gname = $group_tmp['group'][0]['name'];

        $grade = $this->groupModel->getmembergrade($gid, $this->user_id);
        if($grade > 0) {
            $this->model->putChangeTplByid($this->user_id, $message_id, 1);
            $this->send_response(400, NULL, '400108:您已经是群成员了');
        }
        $group_info = $this->groupModel->getGroupInfo($gid);
        if(!$group_info) {
            $this->model->putChangeTplByid($this->user_id, $message_id, 4);
            $this->send_response(400, NULL, '400109:群不存在');
        }
        //查询群组成员总数是否超出最大限制(暂定100)
        $memberNum = $group_info['member_number'];
		if($group_info['type'] == Kohana::config('group.type.public')) {
			$maxMemberNum = Kohana::config('group.maxMemberNum.public');
		} else {
			$maxMemberNum = Kohana::config('group.maxMemberNum.private');
		}
        if($memberNum >= $maxMemberNum) {
            $this->model->putChangeTplByid($this->user_id, $message_id, 3);
            $this->send_response(400, NULL, '400110:群成员人数已满');
        }
        $result= $this->groupModel->addGroupMember($gid, $this->user_id, 1);
        $this->groupModel->addMemberNum($gid);
		$feedModel = new Feed_Model();
		if($group_info['type'] == Kohana::config('group.type.private')) {
			$dateline = time();
			try {
				//添加群组通讯录联系人
				$this->groupContactModel->addGroupContactByUserCard($gid, $this->user_id, $dateline);
			}catch (Exception $exc) {
                                $this->send_response(400, NULL, '400111:导入个人名片到群组通讯录联系人失败');
			}
			$ginfo['modify_time'] = $dateline;
			$ret = $this->groupModel->modifyGroup($gid, $ginfo);
		} else if($group_info['type'] == Kohana::config('group.type.public')) {
			//发送加入公开群组动态
			$application = array('id'=>floatval($gid), 'title'=>'查看群组', 'url'=>'group/'.$gid);
			$feedModel->addFeed($this->user_id, 2, $text='加入了群组：'.$group_info['gname'], $this->get_source(), $application, $at = array(), $images=array(),$sync=array(),$group_type=0,$group_id=0,$retweet_id=0,$allow_rt=0,$allow_comment=1,$allow_praise=1,$allow_del=1,$allow_hide=1);
		}
		$commentModel = new Comment_Model();
    	if($group_info['feed_id']) {
			$friendModel = Friend_Model::instance();
			$isFriend = $friendModel->check_isfriend($this->user_id, $group_info['creator_id']);
			if($isFriend) {
				$commentModel->saveComment($group_info['feed_id'], '加入了本群', $group_info['creator_id']);
			}
		}
		if($group_info['group_feed_id']) {
			$commentModel->saveComment($group_info['group_feed_id'], '加入了本群', $group_info['creator_id']);
		}
		//添加群到首页tab列表
		$userModel = new User_Model();
		$userModel->insertTag($this->user_id, 7, $gid);
		$feedModel->addTab($gid, $group_info['gname'], 7, $this->user_id);
        //删除邀请表对应的记录
        $this->model->putChangeTplByid($this->user_id, $message_id, 1);
        $this->send_response(200);
    }

	
	public function newmsg_onget()
    {  	
     	$res = $this->model->getNewNoticeNum($this->user_id);//获取新通知条数
     	if ($res){
     		$noticeNum = (int)$res;
     	} else {
     		$noticeNum = 0;
     	}
		$feed = new Feed_Model;
		$aboutmeNum = $feed->aboutmeCount(1);
		//$imUser = $feed->findImNew();
		$this->setResponseCode(200);
        return array('noticeNum'=>$noticeNum,'aboutmeNum' => $aboutmeNum);
    }

    public function _str($str,$type='') {
        return $str?$str:$type;
    }
}
?>
