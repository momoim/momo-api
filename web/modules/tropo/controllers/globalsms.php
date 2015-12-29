<?php defined('SYSPATH') or die('No direct access allowed.');
require_once MODPATH . 'tropo/tropo.class.php';

class Globalsms_Controller extends Controller{
    /**
     *
     * 国际短信接口
     */
    public function oversea_sms(){
        $tropo = new tropo\Tropo();
        $session = new tropo\Session();

        if ($session->getParameters("action") == "create") {
            $tropo->call($session->getParameters("to"),array("network" => "SMS"));
            $tropo->say($session->getParameters("msg"));
        } else {
            $msg = $session->getInitialText();
            $from = $session->getFrom();
            $mobi = $from["id"];
        }
        $tropo->renderJSON();
    }

    public function receive(){
        $data=$this->get_data();
        $SourceType = 1;			//系统类型
        $BusinessCode = 90038;		//业务编码
        $KeyValue = '0e7f2d2a-e6d5-4c9e-8be9-fd6681514303';		//系统分配的加密KEY

        $SerialNo = $data['SerialNo'];
        $Mobile = $data['Mobile'];
        $Content = urldecode($data['Content']);
        $RecvTime = $data['RecvTime'];
        $VerifyCode = $data['VerifyCode'];
        $AppendID = intval($data['AppendID']);

        $TheVerifyCode = md5($SourceType . $BusinessCode . $SerialNo . $Mobile . $Content . $RecvTime . $KeyValue);

        if($TheVerifyCode == $VerifyCode){
            echo '1$调用成功';
            if (function_exists('fastcgi_finish_request'))
            fastcgi_finish_request();
            
            $imModel=new IM_Model();
            $sms=iconv('GB2312', 'UTF-8//IGNORE', $Content);
            $imModel->log_smsreceive($Mobile, $AppendID, $sms);

            /*
             * 上行注册短信格式 [90038]1289838d621a.865adf5e45
             * 生成自 userModel.gen_smsregister()
             */
            if(preg_match('/^register_uniqid:\w{32}$/', $sms)){
                $this->doRegister($Mobile, $sms);
            }elseif(preg_match('/^register_imsi:.+/', $sms)){
                $this->doRegisterLink('imsi', $Mobile, $sms);
            }elseif(preg_match('/^register_guid:.+/', $sms)){
                $this->doRegisterLink('guid', $Mobile, $sms);
            }else{
                if($AppendID == 1){
                    //@todo 91来电秀
                    $this->doReceive_callshow($Mobile,$AppendID, $sms);
                }else{
                    $this->doReceive($Mobile, $AppendID, $sms);
                }
            }
        }else{
            echo '0$验证码错误';
        }

        exit();
    }
    
    public function receive_nv()
    {
        $data=$this->get_data();
        $SourceType = 2;			//系统类型
        $BusinessCode = 500019;		//音信分配的企业代码
        $KeyValue = 'f06b63d6-405e-42b6-9610-04ab9830b599';		//系统分配的加密KEY

        $AppendID = intval($data['callerno']);//短信尾号
        $Mobile = $data['p'];	//主叫手机号
        $Content = $data['smsbody'];	//消息体
        $RecvTime = $data['recvtime'];//用户回短信的时间       
        $VerifyCode = $data['verify_code'];	//验证码
        
        $TheVerifyCode = md5($SourceType . $BusinessCode . $AppendID . $Mobile . $Content . $RecvTime . $KeyValue);

        if($TheVerifyCode == $VerifyCode){
            echo '1$调用成功';
            if (function_exists('fastcgi_finish_request'))
            fastcgi_finish_request();
            	
            $imModel=new IM_Model();
            $imModel->log_smsreceive_nv($Mobile, $AppendID, $Content);

            //音信短信接口暂不开发注册功能
            $this->doReceive_nv($Mobile, $AppendID, $Content);
        }else{
            echo '0$验证码错误';
        }

        exit();    	
    }
   
    private function doReceive_nv($mobile,$channel,$sms)
    {
        $imModel=Im_Model::instance();
        $channelInfo = $imModel->get_channel_nv($mobile,$channel);
         
        if($channelInfo){
            $msgid = api::uuid();
            $content = array('text'=>$sms);
            $sender_uid=$channelInfo['sender_uid'];
            $receiver_uid=$channelInfo['receiver_uid'];
            
            //receiver->sender
            $receivers=array(array('id'=>$sender_uid,'name'=>sns::getrealname($sender_uid),'avatar'=>sns::getavatar($sender_uid)));
            $sender=array('id'=>$receiver_uid,'name'=>sns::getrealname($receiver_uid),'avatar'=>sns::getavatar($receiver_uid));

            $sms=array(
                'kind'=>'sms',
                'data'=>array(
                    'id'=>$msgid,
                    'sender'=>$sender,
                    'receiver'=>$receivers,
                    'timestamp'=>time(),
                    'content'=>$content,
                    'client_id'=>12 //短信客户端
            )
            );

            $routekey = $sender_uid.'.'.$receiver_uid;
             
            $userModel=new User_Model();
            $userInfo=$userModel->get_user_info($receiver_uid);
            if($userInfo['status']==1 && $userInfo['sms_count']==0){
                $userModel->present_sms($receiver_uid, 100, '感谢使用移动MOMO，赠送100免费短信给您');
            }
            
            
            $feedModel = new Feed_Model();
            $feedModel->mq_send(json_encode($sms), $routekey, 'momo_im');
        }    	
    }

    private function doReceive($mobile,$channel,$sms){
        $imModel=Im_Model::instance();
        $channelInfo = $imModel->get_channel($mobile,$channel);
         
        if($channelInfo){
            $msgid = api::uuid();
            $content = array('text'=>$sms);
            $sender_uid=$channelInfo['sender_uid'];
            $receiver_uid=$channelInfo['receiver_uid'];
            
            //receiver->sender
            $receivers=array(array('id'=>$sender_uid,'name'=>sns::getrealname($sender_uid),'avatar'=>sns::getavatar($sender_uid)));
            $sender=array('id'=>$receiver_uid,'name'=>sns::getrealname($receiver_uid),'avatar'=>sns::getavatar($receiver_uid));

            $sms=array(
                'kind'=>'sms',
                'data'=>array(
                    'id'=>$msgid,
                    'sender'=>$sender,
                    'receiver'=>$receivers,
                    'timestamp'=>time(),
                    'content'=>$content,
                    'client_id'=>12 //短信客户端
                )
            );

            $routekey = $sender_uid.'.'.$receiver_uid;
            
            $userModel=new User_Model();
            $userInfo=$userModel->get_user_info($receiver_uid);
            if($userInfo['status']==1 && $userInfo['sms_count']==0){
                $userModel->present_sms($receiver_uid, 100, '感谢使用移动MOMO，赠送100免费短信给您');
            }
            
            
            $feedModel = new Feed_Model();
            $feedModel->mq_send(json_encode($sms), $routekey, 'momo_im');

            $imModel->udp_channel($receiver_uid,$sender_uid,$channel);
        }
    }
    
    private function doReceive_callshow($mobile,$channel,$sms)
    {
    	Cs_Feedback_Model::instance()->sms_feedback($mobile, $sms);
    }

    private function doRegister($mobile,$sms){
        $imModel=new IM_Model();
        $guid=substr($sms, 16);

        $smsregister=$imModel->get_smsregister($guid);
        if($smsregister && $smsregister['status']==1){
            $userModel=new User_Model();
            $zone_code = 86;
            $install_id=$smsregister['install_id'];
            $phone_model=$smsregister['phone_model'];
            $os=$smsregister['os'];
            $source=$smsregister['client_id'];
            $device_id=$smsregister['device_id'];
            $client_ip=$mobile;
            $password='';
            //1未激活用户 2激活用户（手机经过urlcode或者上行短信验证过的） 3用户（设置了用户名密码的）
            //1和2区别不明显可作为一个状态
            $status=2;
            $realname='';
            $appid=(int)$smsregister['appid'];
            $nickname='';
            //create_account($mobile,$zone_code,$install_id,$phone_model,$os,$source,$device_id,$client_ip='',$password='',$status=NO_ACTIVED_USER,$realname='',$appid=0,$nickname='')
            $user=$userModel->create_account($mobile,$zone_code,$install_id,$phone_model,$os,$source,$device_id,$client_ip,$password,$status,$realname,$appid,$nickname);
            
            if($user){//存在status<3 或 新注册 的用户
                $imModel->upd_smsregister($guid, $mobile, $user['uid'], FALSE);
            }else{
                if(substr($userModel->get_return_msg(),0,6) == '400117'){//存在 status>=3 的用户
                    $imModel->upd_smsregister($guid, $mobile, 0, TRUE);
                }
            }
        }
    }
    
    public function doRegisterLink($type, $mobile, $sms){
        $sid=substr($sms, 14);

        if($sid){
            $imModel=new IM_Model();
            if($type=='imsi'){
                $imModel->del_imsi_link($sid, $mobile, '86');
                $imModel->gen_imsi_link($sid, $mobile, '86', 1);
            }elseif($type=='guid'){
                $sinfo = explode('$', $sid);
                if($sinfo[1]){
                    $appid = intval($sinfo[0]);
                    $link_guid = $imModel->get_guid_link($appid, $sinfo[1]);
                    $link_mobile = $imModel->get_guid_link_by_mobile($appid, $mobile, '86');
                    if($link_guid && $link_guid['verify']){
                        return;
                    }
                    if($link_mobile && $link_guid['verify']){
                        return;
                    }
                    $imModel->gen_guid_link($appid, $sinfo[1], $mobile, '86');
                }
            }
        }
    }
    
    public function inter_test_sms_gateway_non_use(){
        $data = $this->get_data();
        $to=$data['to'];
        $msg=$data['msg'];
        $zc=$data['zc'];
        if($to && $zc && $msg){
            $userModel=new User_Model();
            $userModel->sms_global($to, $msg, $zc);
        }else{
            $this->send_response(500);
        }
    }
    
}
?>