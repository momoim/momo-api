<?php defined('SYSPATH') OR die('No direct access allowed.');

class Im_controller extends Controller {

    public function __construct() {
        parent::__construct();
        if(! $this->appid){
            $this->send_response(509,NULL,'必须带有appid');
        }
        $this->uid  = $this->getUid();
        $this->imModel = new App_Message_Model($this->appid);
    }
    
    /**
     *
     * 私聊发送给某个指定手机号码接口
     * POST
     * {
     'id':"消息id",
     'sender':{id:"int64 用户id", name:"用户名", avatar:"头像地址"},
     'receiver':[{id:"int64 用户id", name:"用户名", avatar:"头像地址"}], 
     'content':消息体对象,
     'client_id':"客户端类型",
     }
     */
    public function send_message(){
        $data=$this->get_data();
        
        if($data['id'] && $data['sender'] && $data['sender']['id'] && $data['receiver'] && $data['content']){
            $content=$this->imModel->_format_content($data['content']);
            if(!$content){
                $this->send_response(400,'','40003:不支持的消息格式');
            }
            if(count($data['receiver'])>1){
                //群发
                $kind = 'group_sms';
                $routekey = 'sys';
            }else{
                $kind = 'sms';
                $routekey = $data['sender']['id'].'.'.$data['receiver'][0]['id'];
            }
            
            $sms=array(
                'kind'=>$kind,
                'data'=>array(
                    'id'=>$data['id'],
                    'sender'=>$data['sender'],
                    'receiver'=>$data['receiver'],
                    'timestamp'=>time(),
                    'content'=>$content,
                    'client_id'=>intval($data['client_id']),
                )
            );
            
            $this->imModel->mq_send(json_encode($sms), $routekey);
            
            $this->send_response(200,$sms);
        }
        $this->send_response(400,'','40001:参数不完整');
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
     * GET more/:uid.json?page={页数}&pagesize={每页条数}&lasttime={以最后一条记录的时间为偏移量来取}&forward={往前取}&offset={}
     */
    public function more($uid){
        $page=$this->input->get('page',1);
        $pagesize=$this->input->get('pagesize',20);
        $timestamp=$this->input->get('lasttime',0);//精确到毫秒
        $forward=$this->input->get('forward',0);
        $msg_id=$this->input->get('msg_id',0);
        
        if($msg_id){
            $msgidx=$this->imModel->getIMMessageIndex($msg_id);
            if($msgidx) $timestamp=$msgidx['timestamp'];
        }

        if(isset($_GET['offset'])){
            $offset=intval($_GET['offset']);
        }else{
            $offset=($page-1)*$pagesize;
        }
        
        $r=$this->imModel->getMore($uid,$offset,$pagesize,$forward,$timestamp);
        
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
             
            $this->imModel->mq_send(json_encode($sms), $uid);
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
        $timestamp=$this->input->get('lasttime',0);//精确到毫秒
        $forward=$this->input->get('forward',0);

        $offset=($page-1)*$pagesize;
        $r=$this->imModel->getMore($gid,$offset,$pagesize,$forward,$timestamp);
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
        $timestamp=$this->input->get('lasttime',0);//精确到毫秒
        $msg_id=$this->input->get('msg_id',0);
        
        if($msg_id){
            $msgidx=$this->imModel->getIMMessageIndex($msg_id);
            if($msgidx) $timestamp=$msgidx['timestamp'];
        }
        
        if(isset($_GET['offset'])){
            $offset=intval($_GET['offset']);
        }else{
            $offset=($page-1)*$pagesize;
        }
        if($this->get_source()==1 || $this->get_source()==2 || $this->get_source()==7){//如果是android，iphone或webos
            $r=$this->imModel->getAllNonGroup($offset,$pagesize,$timestamp);
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
                foreach($r['data'] as $row){
                    $uid=$row['receiver'][0]['id'];
                    $sms['data']['receiver']=$uid;
                    $sms['data']['status']['msg_receive']['id']=$row['id'];
                    $this->imModel->mq_send(json_encode($sms), $uid);
                }
            }
            $this->send_response(200, $r);
        }
        if(! $new){//有对内容分组
            $r=$this->imModel->getAll($offset,$pagesize,$timestamp);
            $this->send_response(200, $r);
        }else{//3g触屏版需要
            $r=$this->imModel->getNew($offset,$pagesize);
            $this->send_response(200, $r);
        }
    }
    
    function all_received(){
        $page=$this->input->get('page',1);
        $pagesize=$this->input->get('pagesize',20);
        $timestamp=$this->input->get('lasttime',0);//精确到毫秒
        
        if(isset($_GET['offset'])){
            $offset=intval($_GET['offset']);
        }else{
            $offset=($page-1)*$pagesize;
        }
        
        $r=$this->imModel->getAllReceived($offset,$pagesize,$timestamp);
        
        if($r){
            $sms=array(
                    'kind'=>'roger',
                    'data'=>array(
                            'sender'=>$this->getUid(),
                            'receiver'=>0,
                            'timestamp'=>time(),
                            'status'=>array('msg_read'=>array()),
                            'client_id'=>$this->get_source(),
                    )
            );
            foreach($r['data'] as $row){
                $sms['data']['receiver']=$row['sender']['id'];
                $this->imModel->mq_send(json_encode($sms), $uid);
            }
        }
        $this->send_response(200, $r);
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
    
}
?>
