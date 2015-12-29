<?php defined('SYSPATH') OR die('No direct access allowed.');
//[UAP Portal] (C)1999-2009 ND Inc.
//消息功能模型
	    
class Message_Model extends Model {
    public function __construct() {
        // 加载数据库类。以下可以使用 $this->db 操作数据库（如果不要求可以省略）
        parent::__construct();
    }

    /**
     * 新增通知
     * @param  Integer $uid 用户ID
     * @param  Integer $appid 通知KEY所对应的应用ID
     * @param  String $tplname 通知模板名称
     * @param  Array $title 通知标题
     * $title= array(
     *     'actor' => 发表人,
     *	  'time'  => date('Y-m-d H:i:s')
     * )
     * @param  Array $body
     * $body = array(
     *     'summary' => 动态的内容
     *	  'url'=>查看详细的链接地址
     * )
     * @param  Integer $objid [可选为快捷回复、赞的对象ID]
     * @return Integer
     */
    public function putAddNotice($uid, $appid, $tplname, $title, $body=null, $objid=0)
    {
            $config = Kohana::config_load('noticetpl');//加载模板配置文件
            $return_id = '';
            if(!is_string($tplname)){
                    return '第三个参数必须为字符串';
            }
            if(!is_array($title) && !is_object($title)){
                    return '第四个参数必须为数组';
            }
            $tplid= isset($config[$tplname]['id'])?$config[$tplname]['id']:0;

            $opt = array();
            if($title['group']){
                    $opt['group'] = $title['group'];
            }
            if($title['action']){
                    $opt['action'] = $title['action'];
            }
            switch($config[$tplname]['id']){
                    case 1:
                            $content = '请求加你为好友';
                            break;
                    case 2:
                            $content = '刚刚加入了MOMMO空间,由于'.$title['who'].'是应你的邀请加入了momo空间，你们俩自动成为好友';
                            break;
                    case 3:
                            $content = '同意了您的MOMO好友请求';
                            break;
                    case 5:
                            $content = '申请加入群"'.$title['group'][0]['name'].'"';
                            break;
                    case 6:
                            $content = '同意了您加入群"'.$title['group'][0]['name'].'"';
                            break;
                    case 7:
                            $content = '邀请你加入群"'.$title['group'][0]['name'].'"';
                            break;
                    case 8:
                            $content = $title['caption'];
                            break;
                    case 9:
                            $content = '邀请你参加活动"'.$title['action'][0]['name'].'"';
                            break;
                    case 10:
                            $content = '修改了活动"'.$title['action'][0]['name'].'"';
                            break;
                    default:
                            $content = '';
            }
            
            $authorid = $this->getUid() ? $this->getUid() : (isset($title['uid']) ? $title['uid'] : NULL);
            $userinfo = sns::getuser($authorid);
            
            $title = json_encode($title);

            if($body){
                    if(!is_array($body) && !is_object($body)){
                            return '第五个参数只能为空或数组';
                    }
                    $body  = json_encode($body);
            }
            $body_arr = json_decode($body,true);


            if(!isset($config[$tplname]['id'])) return '模板不存在';

            

            //同样的只记录一次
            $hash   = md5($uid.$tplname.$appid.$authorid.$userinfo['realname'].$title.$objid);
            $result = $this->db->getRow('notification', 'id', "hash='$hash'");
            if($result){
                    $this->db->updateData('notification',
                                                              array('isnew'=>1, 'tplid'=>$config[$tplname]['id'], 'body'=>$body, 'addtime'=>time()),
                                                              array('hash'=>$hash) );

                    $return_id = $result['id'];
            }else{
            //入库操作
                    $return_id = $this->db->insertData('notification',
                                                                            array('uid'    =>$uid,
                                                                                      'tplid'  =>$config[$tplname]['id'],
                                                                                      'appid'  =>$appid,
                                                                                      'isnew'  =>1,
                                                                                      'authorid'=>$authorid,
                                                                                      'author' =>$userinfo['realname'],
                                                                                      'title'  =>$title,
                                                                                      'body'   =>$body,
                                                                                      'addtime'=>time(),
                                                                                      'hash'   =>$hash,
                                                                                      'objid'  =>$objid
                                                                            ));
            }

    $res = $this->getNewNoticeNum($uid);//获取新通知条数
    if ($res){
            $noticeNum = (int)$res;
    } else {
            $noticeNum = 0;
    }
            $this->_uc_fopen(Kohana::config('uap.http_push').$uid, 0, '{"kind":"notice","data":"'.$noticeNum.'"}', 'POST');
            $this->mq_send('{"kind":"notice","data":"'.$noticeNum.'"}', $uid);

            //发送手机端系统消息
				$need_handle =  in_array($tplid,Kohana::config('uap.message_handle'))?1:0;
                foreach ($config as $tval) {
                    if ($tval['id'] == $tplid) {
                        $tpl = $tval; break;
                    }
                }

                if($tplid == 8 && strstr($content, '冲突')){
                        //$need_handle = 1;
                    return ;
                }

				$opt = array();
                if($this->has_message_type($tplid)) {
                    $opt[$this->message_type]  = $this->message_opt($title,$return_id,$body_arr);
                } else {
                    $opt = new stdClass();
                }

                $mq_msg = array("kind"=>"sys",
                                "data"=>array(
                                   "id"=>$return_id,
                                   "type" => array('id'=>$tplid,'name'=>''),
                                   "need_handle"=>$need_handle,
                                   "text"=>$content,
                                   "created_at"=>time(),
                                   "sender"=> array("id"=>$this->_str($authorid,0),"name"=>$this->_str($userinfo['realname']),"avatar"=>sns::getavatar($authorid)),
                                   "is_handle" => in_array($tplid,array(1,5,7,9,12,16))?1:0,
                                   "explain" => isset($body_arr['explain'])?str::unhtmlspecialchars($body_arr['explain']):'',
                                   "is_new" => 1,
                                   "opt" => $opt
                                )
                    );

                $this->mq_send(json_encode($mq_msg), $uid, 'momo_sys');
            return $return_id;
    }
    
    public function _str($str,$return='') {
    	return $str?$str:$return;
    }
    

    //系统消息相关处理
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
            case 'friend_apply':
               return $this->message_opt_friend_apply($data,$message_id,$mini);
               break;
            case 'personal_info_modify':
               return $this->message_opt_personal_info_modify($data,$message_id,$body,$mini);
               break;
        }
    }
    
    public function message_opt_personal_info_modify($title,$message_id,$body,$mini) {
        $chagne_item = array();
        if(isset($body['summary']) && is_array($body['summary'])) {
            foreach($body['summary'] as $key => $value) {
                $chagne_item[] = array('name'=>$value['describe'],'text'=>$value['info']);
            }
        }
        return $chagne_item;
    }
    
 	public function message_opt_friend_apply($data,$message_id,$mini) {
 		$location = '';
 		$sex =0;
 		$together_count = 0;
 		$same_friend = array();
 		
        $result = $this->getNoticeInfo(array('id'=>$message_id, 'authorid'=>$this->uid), true);
        if ($result) {
	        $tmp = $result['body'] ? json_decode($result['body'], true) : array("explain"=>"");
	
	        //取得共同好友
	        $friendModel = new Friend_Model();
	        $ffids = $friendModel->getAllFriendIDs($result['authorid'],false);
	        $mfids = $friendModel->getAllFriendIDs($this->uid,false);
	        $together = array_intersect($ffids, $mfids);
	
	        $data = sns::getuser($result['authorid']);
	        $tmp['reside'] = '';
	        if ($data['resideprovince'] || $data['residecity']) {
	            $config = Kohana::config_load('cityarray');//加载城市数组
	
	            $province = isset($data['resideprovince']) ? isset($config['province'][$data['resideprovince']])?$config['province'][$data['resideprovince']]:'' : "";
	            $city = isset($data['residecity']) ? isset($config['city'][$data['residecity']])?$config['city'][$data['residecity']]:'' : "";
	            $location =  $province. " " . $city;
	        }
	
	        $sex = $data['sex']==1 ? "男" : "女";
	        $tmp['fid'] = $result['authorid'];
	        $tmp['explain'] = $tmp['explain']?str::unhtmlspecialchars($tmp['explain']):'';
	        unset($data, $ffids, $mfids, $config);
	
	        $str = "";
	        $urlpre = url::base();
	        $avatar = sns::getavatar($result['authorid']);
	
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
        }
        return array('location'=>$location,'sex'=>$sex,'together_count'=>$together_count,'together'=>$same_friend);
    }

    //删除通知
    public function putDelNotice($uid,$id=null) {
        $where = $id==null?array('uid'=>$uid):array('id'=>$id,'uid'=>$uid);
        return $this->db->deleteData('notification',$where);
    }

    //获取所有通知列表
    public function getNoticeList($uid, $start=0, $pos=5, $appid=null) {
        $limit  = $start.','.$pos;
        $appstr = $appid===null?'':" AND appid IN($appid)";
        $result = $this->db->getAll('notification','*',"uid=$uid $appstr ORDER BY addtime DESC",$limit);
        return $result;
    }

    //获取新通知列表
    public function getNewNoticeList($uid, $start=0, $pos=5, $appid=null,$new=0) {
        $limit  = $start.','.$pos;
        $appstr = $appid===null?'':" AND appid IN($appid)";
        if($new) {
            return $this->db->getAll('notification','*',"uid=$uid $appstr AND (isnew=1 OR status=2) ORDER BY isnew DESC, addtime DESC",$limit);
        }
	return $this->db->getAll('notification','*',"uid=$uid $appstr ORDER BY isnew DESC, addtime DESC",$limit);
    }

    //获取所有通知数量
    public function getNoticeNum($uid, $appid=null) {
        $appstr = $appid===null?'':" AND appid IN($appid)";
        return $this->db->getCount('notification',"uid=$uid $appstr");
    }

    //获取新通知数量
    public function getNewNoticeNum($uid, $appid=null) {
        $appstr = $appid===null?'':" AND appid IN($appid)";
        return $this->db->getCount('notification',"uid=$uid AND isnew=1 $appstr");
    }

    //更新通知状态为己看
    public function putSetNoticeOld($uid,$ids) {
        $return = $this->db->from('notification')->set(array('isnew'=>0))->where(array('uid'=>$uid))->in('id', $ids)->update();
        return (count($return) > 0) ? count($return) : FALSE;
    }


    //按通知ID切换 状态
    public function putChangeTplByid($uid,$nid,$status=1)
    {
            $sql = "UPDATE `notification` SET `isnew`=0, `hash`='', `status`=$status WHERE `id`=$nid AND `uid`=$uid ";
            return $this->db->query($sql);
    }

    //按模板\发送者\接收者三者ID确定 切换 状态
    public function putChangeTpl($uid,$fid,$intplid, $status=1)
    {
            $sql = "UPDATE `notification` SET `isnew`=0, `hash`='', `status`=$status WHERE `uid`=$uid AND `tplid`=$intplid AND `authorid`=$fid ";
            return $this->db->query($sql);
    }

    /**
     * 获取单条通知
     * @param mixd
     * @return Array
     */
    public function getNoticeInfo($where, $node = null) {
        if ($node===null) {
            $this->db->select('id','uid','appid','isnew','authorid','addtime');
        }

        $result = $this->db->where($where)->get('notification',1,0);
        if ($result->count()) {
            $row = $result->result_array(FALSE);
            return $row[0];
        }
        return FALSE;
    }

    //解释模板
    public function parsedata($data, $tpl, $baseurl='') {
        $tmpdata = json_decode($data, TRUE);
        @extract($tmpdata);

        $tpl = preg_replace('/{\s*foreach\s+(\w+)\s+as\s+(\w+)\s*}/iU', '<?php if (is_array($\\1)) foreach ($\\1 as $\\2) {?>', $tpl);
        $tpl = preg_replace('/{\s*endforeach\s*}/i', '<?php }?>', $tpl);
        $tpl = preg_replace("/{(.+)}/U", "<?php echo $\\1;?>", $tpl);

        ob_start();
        eval(" ?>". $tpl ."<?php ");
        $result = ob_get_contents();
        ob_end_clean();

        if (!$result) {
            return NULL;
        }

        //应用网址附加处理
        if ($baseurl) {
            if (substr($baseurl, -1) != '/') $baseurl .= '/';
            $result = preg_replace('/(src|href)\s*=\s*([\'"])(?!http:\/\/)/iU', "\\1=\\2$baseurl", $result);
            $result = preg_replace("|(['\"])". preg_quote($baseurl) ."\\1|i", '""', $result); //处理空网址
        }

        return $result;
    }
}
