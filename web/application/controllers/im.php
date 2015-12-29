<?php defined('SYSPATH') OR die('No direct access allowed.');

class Im_controller extends Controller {

    public function __construct() {
        parent::__construct();
        $this->uid  = $this->getUid();
        $this->imModel = Im_Model::instance();
    }

    /**
     *
     * 消息提醒
     * GET notify/:num.json?unread={过滤未读}
     */
    public function notify($num=5){
        $unread=$this->input->get('unread',0);
         
        $num=intval($num);
        $r=$this->imModel->getNotify($num,$unread);
        $this->send_response(200, $r);
    }

    /**
     *
     * 获取与某人的聊天记录
     * GET more/:uid.json?page={页数}&pagesize={每页条数}&lasttime={以最后一条记录的时间为偏移量来取}&forward={往前取}
     */
    public function more($uid){
        $page=$this->input->get('page',1);
        $pagesize=$this->input->get('pagesize',20);
        $lasttime=$this->input->get('lasttime',0);
        $forward=$this->input->get('forward',0);
        $msg_id=$this->input->get('msg_id',0);
        
        $timestamp=0;//j2me和黑莓需精确到毫秒
        if($msg_id){
            $msgidx=$this->imModel->getIMMessageIndex($msg_id);
            if($msgidx) $timestamp=$msgidx['timestamp'];
        }

        $offset=($page-1)*$pagesize;
        $r=$this->imModel->getMore($uid,$offset,$pagesize,$lasttime,$forward,$timestamp);
        
        if($r){
            $sms=array(
                'kind'=>'roger',
                'data'=>array(
                    'sender'=>$this->getUid(),
                    'receiver'=>$uid,
                    'timestamp'=>time(),
                    'status'=>array('msg_read'=>array()),
                    'client_id'=>$this->get_source(),
                )
            );
             
            $feedModel = new Feed_Model();
            $feedModel->mq_send(json_encode($sms), $uid, 'momo_im');
        }
        
        $this->send_response(200, $r);
    }

    /**
     *
     * 获取群发记录
     * GET group/:gid.json?page={页数}&pagesize={每页条数}&lasttime={以最后一条记录的时间为偏移量来取}
     */
    public function group($gid){
        $page=$this->input->get('page',1);
        $pagesize=$this->input->get('pagesize',20);
        $lasttime=$this->input->get('lasttime',0);
        $forward=$this->input->get('forward',0);

        $offset=($page-1)*$pagesize;
        $r=$this->imModel->getMore($gid,$offset,$pagesize,$lasttime,$forward);
        $this->send_response(200, $r);
    }
	
	/**
	 *
	 * 获取最近的聊天历史记录总数
	 * GET count.json?uid={会话uid}
	 */
	public function count() {
	    $uid=(int)$this->input->get('uid',0);
		$r=$this->imModel->getCount($uid);
		$this->send_response(200, $r);
	}
    
    /**
     *
     * 获取最近的聊天历史记录
     * GET all.json?page={页数}&pagesize={每页条数}&new={过滤出未读的}&lasttime={以最后一条记录的时间为偏移量来取}
     */
    public function all(){
        $page=$this->input->get('page',1);
        $pagesize=$this->input->get('pagesize',20);
        $new=$this->input->get('new',0);
        $lasttime=$this->input->get('lasttime',0);
        $msg_id=$this->input->get('msg_id',0);
        
        $timestamp=0;//j2me和黑莓需精确到毫秒
        if($msg_id){
            $msgidx=$this->imModel->getIMMessageIndex($msg_id);
            if($msgidx) $timestamp=$msgidx['timestamp'];
        }
        
        $offset=($page-1)*$pagesize;
        if($this->get_source()==1 || $this->get_source()==2 || $this->get_source()==7){//如果是android，iphone或webos
            $r=$this->imModel->getAllNonGroup($offset,$pagesize,$lasttime);
            if($r){
                $sms=array(
                    'kind'=>'roger',
                    'data'=>array(
                        'sender'=>$this->getUid(),
                        'receiver'=>0,
                        'timestamp'=>time(),
                        'status'=>array('msg_receive'=>array('id'=>'')),
                        'client_id'=>$this->get_source(),
                    )
                );
                $feedModel = new Feed_Model();
                foreach($r as $row){
                    $uid=$row['receiver'][0]['id'];
                    $sms['data']['receiver']=$uid;
                    $sms['data']['status']['msg_receive']['id']=$row['id'];
                    $feedModel->mq_send(json_encode($sms), $uid, 'momo_im');
                }
            }
            $this->send_response(200, $r);
        }
        if(! $new){//有对内容分组
            $r=$this->imModel->getAll($offset,$pagesize,$lasttime,$timestamp);
            $this->send_response(200, $r);
        }else{//3g触屏版需要
            $r=$this->imModel->getNew($offset,$pagesize);
            $this->send_response(200, $r);
        }
    }

    /**
     *
     * 获取长文本
     * GET longtext/:msgid.json?chunk={第几段}
     */
    public function longtext($msgid){
        $page=intval($this->input->get('chunk'));
        $this->send_response(200, $this->imModel->getMessage($msgid,$page));
    }

    /**
     *
     * 存储长文本
     * POST putlongtext.json
     * {
     * 	'msgid':'消息id',
     * 	'text':'长文本',
     * }
     */
    public function putlongtext(){
        $data=$this->get_data();
        $text=(string)$data['text'];
        $msgid=(string)$data['msgid'];

        $text_long=$this->imModel->long_text_array($text,420);
        $content_long=array('text'=>$text_long);

        if($msgid && $text_long){
            $message=array('msgid'=>$msgid,'content'=>$content_long);
            $content_key=$this->imModel->putMessage($message);
        }

        if($content_key){
            $r=array();
            $r['content_key']=$content_key;
            $r['text_long']=(string)$text_long[0];
            $this->send_response(200, $r);
        }else{
            $this->send_response(400, '');
        }
    }

    //输出长文本全文
    public function show_text($msgid){
        $idxinfo=$this->imModel->getIMMessageIndex($msgid);
        $r=$this->imModel->getMessage($msgid,NULL);
        header('Content-Type:text/html');
        if($idxinfo['ownerid']=='10643866' || $idxinfo['opid']=='10643866'){//91黄历
            $html=str_replace("\n", '</p><p>', $r['text']);
            $class='long-text weather';
        }else{
            $html=str_replace("\n", '</p><p>', htmlspecialchars($r['text']));
            $class='long-text';
        }
        
        echo <<<EOT
<html>
    <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0;" />
    <meta name="apple-mobile-web-app-capable"   content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black"/>
    <link media="all" rel="stylesheet" href="http://m.momo.im/themes/default_m/long_text.css" type="text/css" />
    </head>
    <body>
    <div class="$class">
    <p>$html</p>
    </div>
    </body>
</html>
EOT;
    }
    
    /**
     * 
     * 发送私聊信息
     * POST send.json
     * {
     * 	'guid':"消息id由客户端生成",
     * 	'receiver':"1,2,3如果是多个id以','分隔的话表示群发",
     * 	'client_id':"客户端类型id" //1:android，2:iphone，3:wm，4:s60v3，5:s60v5，6:java, 7:webos，8:blackberry, 9:ipad，10:3g，11:3g触屏版
     * 	    | 'text':"表情格式 [微笑]" 
     *      | 'picture':图片id 
     *      | 'file':文件id 
     *      | 'sender_card':名片uid 
     *      | 'audio':音频id 
     *      | 'msgid':转发的消息id 
     *      | 'location':地理位置
     *  'option':{'text_long_size':"长文本截取长度，单位字节"}
     * }
     */
    public function send(){
        $data=$this->get_data();
        
        if($data['receiver'] && $data['guid']){
            $msgid=$data['guid'];
            $receiver_arr=explode(',', $data['receiver']);
            if(count($receiver_arr)==1){//单个会话
                $receiver_id=$receiver_arr[0];
                $receiver[]=array('id'=>$receiver_id,'name'=>sns::getrealname($receiver_id),'avatar'=>sns::getavatar($receiver_id));
                $kind='sms';
                $routekey=$this->uid.'.'.$receiver_id;
            }else{//群发
                foreach($receiver_arr as $receiver_id){
                    $receiver[]=array('id'=>$receiver_id,'name'=>sns::getrealname($receiver_id),'avatar'=>sns::getavatar($receiver_id));
                }
                sort($receiver_arr);
                $group_id=md5(implode(',',$receiver_arr));
                $kind='group_sms';
                $routekey='sys';
            }

            //长文本判断
            if(isset($data['text'])){
                $text=$data['text'];
                $text_long_size=intval($data['option']['text_long_size']);
                if($text_long_size<3) $text_long_size=420;
                elseif($text_long_size>30000) $text_long_size=30000;
                
                if(strlen($text)>$text_long_size){
                    $text_long=$this->imModel->long_text_array($text,$text_long_size);
            
                    if($msgid && $text_long){
                        $message=array('msgid'=>$msgid,'content'=>array('text'=>$text_long));
                        $content_key=$this->imModel->putMessage($message);
                    }
        
                    if($content_key){
                        $content['text_long']=(string)$text_long[0];
                    }else{
                        $this->send_response(400,'','40003:长文本发送失败');
                    }
                }elseif(strlen($text)>0){
                    $content['text']=(string)$text;
                }
            }elseif($data['picture']){
                $pid=intval($data['picture']);
                $info=Photo_Controller::geturl(array($pid),130);
                $psrc=$info[0];
                 
                $content['picture']=array('url'=>$psrc);
            }elseif($data['audio']){
                $fid=intval($data['audio']);
                $fileinfo=File_Controller::getinfo($fid);
                
                $duration=$fileinfo['meta']['duration'];
                
                if(! $duration){
                    $duration=10;
                }
                if($data['option']['audio_duration']){
                    $duration=$data['option']['audio_duration'];
                }
                 
                $content['audio']=array('url'=>$fileinfo['src'], 'duration'=>$duration);
            }elseif($data['file']){
                $fid=intval($data['file']);
                $fileinfo=File_Controller::getinfo($fid);
                 
                $content['file']=array('url'=>$fileinfo['src'],'mime'=>$fileinfo['mime'],'size'=>$fileinfo['size'],'name'=>$fileinfo['name']);
            }elseif(isset($data['sender_card'])){
                foreach($receiver_arr as $uid){
                    User_Model::instance()->update_card_sharelog($uid,intval($data['sender_card']));
                }
                
                $content['sender_card'] = array('id'=>intval($data['sender_card']));
            }elseif(isset($data['location'])){
                $locationInfo = explode(',', $data['location']);
                
                $y = $locationInfo[0];
                $x = $locationInfo[1];
                
                if (isset($locationInfo[2])) {
                    $address = $locationInfo[2];
                } else {
                    $address = '';
                }
                
                if (isset($locationInfo[3])) {
                    $is_correct = $locationInfo[3];
                } else {
                    $is_correct = 0;
                }
                
                $x=floatval($x);
                $y=floatval($y);
                
                if($x && $y){
                    $content['location']=array('longitude'=>$x, 'latitude'=>$y, 'address'=>$address, 'is_correct'=>$is_correct);
                }
            }elseif(isset($data['msgid'])){
                $msg_content=$this->imModel->get_msg_by_msgid($data['msgid']);
                if($msgid && $msg_content['content']){
                    $message=array('msgid'=>$msgid,'content'=>$msg_content['content']);
                    $content_key=$this->imModel->putMessage($message);
                }
                $msg_content=$this->imModel->get_format_content($msg_content);
                $content=$msg_content['content'];
            }

            if(!isset($content['text'])
            && !$content['picture']
            && !$content['audio']
            && !$content['file']
            && !$content['sender_card']
            && !isset($content['text_long'])
            && !$content['location']
            && !$content['contact']){
                
                $this->send_response(400,'','40002:不能发送空消息');
            }else{
                $sender=array('id'=>$this->uid,'name'=>sns::getrealname($this->uid),'avatar'=>sns::getavatar($this->uid));
                $sms=array(
    				'kind'=>$kind,
    				'data'=>array(
    					'id'=>$msgid,
    					'sender'=>$sender,
    					'receiver'=>$receiver,
    					'timestamp'=>time(),
    					'content'=>$content,
    					'client_id'=>intval($data['client_id']),
                    )
                );
                 
                if($content_key) $sms['data']['content_key']=$content_key;
                 
                $feedModel = new Feed_Model();
                $feedModel->mq_send(json_encode($sms), $routekey, 'momo_im');
                
                if($kind=='group_sms') $sms['data']['group_id']=$group_id;
                $this->send_response(200,$sms);
            }
        }
        
        $this->send_response(400,'','40001:参数不完整');
    }
    
    /**
     * 
     * 根据消息id获取消息对象
     * POST
     * {
     *  'msgid':'消息id',
     *  'receiver_uid':'消息接收者',
     *  'sender_uid':'消息发送者'
     * }
     */
    public function get_message() {
        $data=$this->get_data();
        $msgid=$data['msgid'];
        $receiver_uid=$data['receiver_uid'];
        $sender_uid=$data['sender_uid'];
        if($msgid && $receiver_uid && $sender_uid){
            $message=$this->imModel->getIMMessage($msgid,$sender_uid,$receiver_uid);
            if($message)
                $this->send_response(200,$message);
        }
        
        $this->send_response(400,'','40001:找不到消息');
    }
    
    /**
     * 
     * 根据短信内容获取消息对象
     * POST
     * {
     * 	'sms':'短信内容'
     * }
     */
    public function get_message_by_sms(){
        $data=$this->get_data();
        $content=$data['sms'];
        
        if($content){
            if($result=$this->_get_message_by_sms($content)){
                $this->send_response(200,$result);
            }
            $this->send_response(400,'','40002:找不到消息');
        }else{
            $this->send_response(400,'','40001:参数不完整');
        }
    }
    
    private function _get_message_by_sms($content){
        if($content){
            $url_prefix=preg_quote(MO_SMS_JUMP);
            if(preg_match('@'.$url_prefix.'[a-zA-Z0-9/]+@', $content, $match)){
                if(preg_match('@([^/]+)$@', trim($match[0],'/'), $match1)){
                    $url_code=$match1[1];
                    $urlinfo=Url_Model::instance()->get($url_code);
                    if($urlinfo['type']=='im'){
                        $msgid=$urlinfo['msgid'];
                        $receiver_uid=$urlinfo['receiver_uid'];
                        $sender_uid=$urlinfo['sender_uid'];
                        if($msgid && $receiver_uid && $sender_uid){
                            $message=$this->imModel->getIMMessage($msgid,$sender_uid,$receiver_uid);
                            if($message)
                                return $message;
                        }
                    }
                }
            }
        }
        return '';
    }
    
    public function get_message_by_sms_batch(){
        $data=$this->get_data();
        if(!is_array($data) || empty($data))
            $this->send_response(400,'','40001:参数不完整');
            
        $result=array();
        foreach($data as $sms){
            $result[]=$this->_get_message_by_sms($sms);
        }
        $this->send_response(200,$result);
    }
    
    /**
     * 
     * 私聊发送给某个指定手机号码接口
     * POST
     * {
        'id':"消息id",
        'sender':{id:"int64 用户id", name:"用户名", avatar:"头像地址"},
        'receiver':[{id:"int64 用户id", mobile:"电话", name:"用户名", avatar:"头像地址"}], //如果接收者有id优先取id如果没有则根据mobile来生成id
        'timestamp':"发送时间",
        'content':消息体对象,
        'client_id':"客户端类型",
      }
     */
    public function send_message(){
        $data=$this->get_data();
        if($data['id'] && $data['sender'] && $data['sender']['id'] && $data['receiver'] && $data['content'] && $data['client_id']){
            $content=$this->imModel->_format_content($data['content']);
            if(!$content){
                $this->send_response(400,'','40003:不支持的消息格式');
            }
            if(count($data['receiver'])>1){
                //群发
                $sms=array(
                    'kind'=>'group_sms',
                    'data'=>array(
                        'id'=>$data['id'],
                        'sender'=>$data['sender'],
                        'receiver'=>$data['receiver'],
                        'timestamp'=>time(),
                        'content'=>$content,
                        'client_id'=>intval($data['client_id']),
                    )
                );
                
                $routekey = 'sys';
                $feedModel = new Feed_Model();
                $feedModel->mq_send(json_encode($sms), $routekey, 'momo_im');
                
                $this->send_response(200,$sms);
            }
            if($receiver=$data['receiver'][0]){
                if($receiver['id']){
                    $receiver_uid=$receiver['id'];
                }elseif($receiver['mobile']){
                    if($receiver['name']){
                        $result=User_Model::instance()->create_at(array(array('mobile'=>$receiver['mobile'],'name'=>$receiver['name'])), $this->getUid(), intval($data['client_id']));
                        if($result[0]['user_id']) $receiver_uid=$result[0]['user_id'];
                    }
                }
                
                if($receiver_uid){
                    $receiver['id']=$receiver_uid;
                    $sms=array(
        				'kind'=>'sms',
        				'data'=>array(
        					'id'=>$data['id'],
        					'sender'=>$data['sender'],
        					'receiver'=>array($receiver),
        					'timestamp'=>time(),
        					'content'=>$content,
        					'client_id'=>intval($data['client_id']),
                        )
                    );
                    
                    $routekey = $data['sender']['id'].'.'.$receiver_uid;
                    $feedModel = new Feed_Model();
                    $feedModel->mq_send(json_encode($sms), $routekey, 'momo_im');
                    
                    $this->send_response(200,$sms);
                }else{
                    $this->send_response(400,'','40002:接收用户不存在');
                }
            }
        }
        $this->send_response(400,'','40001:参数不完整');
    }
    
    public function send_read(){
        $data=$this->get_data();
        $uid=(int)$data['receiver'];
        $msgid=$data['id'];
        $client_id=(int)$data['client_id'];
        if(!$uid || !$msgid){
            $this->send_response(400,'','40001:参数不完整');
        }
        $sms=array(
            'kind'=>'roger',
            'data'=>array(
                'sender'=>$this->getUid(),
                'receiver'=>$uid,
                'timestamp'=>time(),
                'status'=>array('msg_read'=>array('id'=>$msgid)),
                'client_id'=>$client_id,
            )
        );
        $feedModel = new Feed_Model();
        $feedModel->mq_send(json_encode($sms), $uid, 'momo_im');
    }
    
    /**
     * 
     * 删除消息
     * POST
     * [msgid,...]
     */
    public function delete(){
        $data=(array)$this->get_data();
        $msgids=array_unique($data);
        if(! is_array($msgids) || ! $msgids){
            $this->send_response(400,'','40001:参数不合法');
        }
        if($this->imModel->delete($msgids)){
            $this->send_response(200);
        }else{
            $this->send_response(400,'','40002:操作失败');
        }
    }
    
    /**
     * 
     * 一键删除
     * GET
     * opid 对方id
     */
    public function delete_all(){
        $opid=$this->input->get('uid');
        if($this->imModel->delete_all($opid)){
            $this->send_response(200);
        }else{
            $this->send_response(400,'','40001:操作失败');
        }
    }
    
    /**
     * {
     *  sender:"发送者id",
     *  receiver:["手机号",...],//没有传 默认选择整个联系人
     *  msg:"短信内容"
     * }
     * Enter description here ...
     */
    public function sms_send_direct(){
        $data=$this->get_data();
        
        if(!isset($data['msg']) || trim((string)$data['msg'])=='' || !isset($data['sender']) || (int)$data['sender']==0){
            $this->send_response(400,'','短信内容不能为空');
        }
        $content=(string)$data['msg'];
        //@todo 截取短信内容
        
        $sender=(int)$data['sender'];
        $receiver_mobile_arr=isset($data['receiver'])?(array)$data['receiver']:array();
        
        $all_mobile=$r_mobile=array();
        if($receiver_mobile_arr){
            $all_mobile=$r_mobile=$receiver_mobile_arr;
        }else{
            if($contact=Contact_Model::instance()->get($sender,NULL,'',1)){
                foreach($contact as $row){
                    foreach ($row['tels'] as $telinfo){
                        $tel=(string)$telinfo['search'];
                        $all_mobile[]=$tel;
                        if(substr($tel, 0, 3)=='+86'){
                            $r_mobile[]=substr($tel, 3);
                        }
                    }
                }
            }
            
        }
        
        $r_mobile=array_unique($r_mobile);
        if(!$r_mobile) $this->send_response(400,'','没有找到接收者手机');
        
        $userModel=new User_Model();
        $err_mobile=$ok_mobile=array();
        foreach($r_mobile as $m){
            if(! $userModel->sms_global($m, $content)){
                $err_mobile[]=$m;
            }else{
                $ok_mobile[]=$m;
            }
        }
        sort($all_mobile);
        sort($ok_mobile);
        sort($err_mobile);
        $this->send_response('200',array('all'=>$all_mobile,'ok'=>$ok_mobile,'fail'=>$err_mobile));
    }
    
    public function map_history(){
        $page=(int)$this->input->get('page',1);
        $pagesize=(int)$this->input->get('pagesize',10);
        $keyword=(string)$this->input->get('keyword');
        $r=$this->imModel->get_map_history($page,$pagesize,$keyword);
        $this->send_response('200',$r);
    }
    
    public function get_groupsms(){
        $page=intval($this->input->get('page',1));
        $pagesize=intval($this->input->get('pagesize',20));
        $type=intval($this->input->get('type',3));//1审核通过，2审核未通过，3未审核
        $r=$this->imModel->get_groupsms($page,$pagesize,$type);
        $this->send_response('200',$r);
    }
    
    public function verify_groupsms(){
        //必须是小秘才能操作
        if($this->getUid() != Kohana::config('uap.xiaomo')){
            $this->send_response('403');
        }
        
        $id=intval($this->input->get('id'));
        $op=intval($this->input->get('verify'));
        
        if($this->imModel->verify_groupsms($id,$op)){
            $this->send_response('200');
        }
        $this->send_response('400');
    }

}
?>
