<?php defined('SYSPATH') OR die('No direct access allowed.');

class Im_Model extends Model {
    
    protected $db = 'momo_im';

    public static $instances = null;

    public function __construct(){
        parent::__construct();
        $mg_instance = new MongoClient(Kohana::config('uap.mongodb'));
        $this->mongo = $mg_instance->selectDB(MONGO_DB_FEED);
        $this->mongo_msg = $this->mongo->selectCollection ('im_message');
    }

    public static function & instance(){
        if ( !is_object(Im_Model::$instances) ){
            Im_Model::$instances = new Im_Model;
        }
        return Im_Model::$instances;
    }

    public function table($table, $uid=0){
        if($uid === 0){
            $uid = $this->uid;
        }
        if($table == 'im_message'){
            $flag_num = (int)($uid / 10000000);
            $trunk = $uid % 100;
            
            if($flag_num < 1){
                $flag = 'a';
            }else{
                $flag = 'b';
            }
            
            return 'momo_im.' . $table .'_'. $flag .'_'. $trunk;
        }
        return $table;
    }

    /**
     *
     * 获取最近的私聊通知
     * @param int $num 条数
     * @param boolean $unread 是否只取未读
     */
    public function getNotify($num,$unread=0){
        if($unread){
            $sql="SELECT `ownerid`,`opid` FROM ". $this->table('im_notify') .
            " WHERE `ownerid`={$this->uid} AND `new_count` > 0 ORDER BY `timestamp` DESC LIMIT {$num}";
        }else{
            $num *= 2;
            $sql="SELECT `ownerid`,`opid` FROM ". $this->table('im_notify') .
            " WHERE `ownerid`={$this->uid} OR `opid`='{$this->uid}' ORDER BY `timestamp` DESC LIMIT {$num}";
        }

        $query=$this->db->query($sql);
        $result=$query->result_array();
        	
        $users=array();
        $uidset=array();
        if($result){
            foreach($result as $row){
                $row = (array) $row;
                if($row['ownerid'] == $this->uid){
                    $uid=$row['opid'];
                }else{
                    $uid=$row['ownerid'];
                }

                if(! in_array($uid, $uidset)){
                    $uidset[]=$uid;
                    $users[]=$this->_user($uid,48);
                }
            }
        }
        return array_slice($users, 0, 5);
    }

    /**
     *
     * 获取会话记录
     * @param int $uid 会话方的id
     * @param int $offset 获取的偏移量
     * @param int $size 条数
     * @param int $timestamp 以最后一条记录时间为偏移量
     * @param int $forward 往后取
     */
    public function getMore($uid,$offset=0,$size=20,$timestamp=0,$forward=0,$micro_timestamp=0){
        $timestamp=intval($timestamp);
        $cond = "";
        $limit = $offset . "," . $size;
        if($timestamp > 0){
            if($forward){
                $cond = "AND `s_stime` > $timestamp ";
            }else{
                $cond = "AND `s_stime` < $timestamp ";
            }
            $limit = $size;
        }elseif($micro_timestamp >0 ){
            if($forward){
                $cond = "AND `timestamp` > $micro_timestamp ";
            }else{
                $cond = "AND `timestamp` < $micro_timestamp ";
            }
            $limit = $size;
        }

        $sql="SELECT * FROM ". $this->table('im_message') .
        " WHERE `ownerid`={$this->uid} AND `opid`='{$uid}' {$cond}ORDER BY `timestamp` DESC LIMIT {$limit}";
        	
        if($this->uid == $uid) {
            $sql="SELECT * FROM ". $this->table('im_message') .
            " WHERE `ownerid`={$this->uid} AND `opid`='{$uid}' AND `box`=1 {$cond}ORDER BY `timestamp` DESC LIMIT {$limit}";
        }
        	
        $query=$this->db->query($sql);
        $result=$query->result_array();
        	
        return $this->_getResult($result);
    }

    /**
     *
     * 只获取最近未读的私聊列表
     * @param int $offset 偏移量
     * @param int $size 条数
     */
    public function getNew($offset=0,$size=20){
        $sql="SELECT SQL_CALC_FOUND_ROWS *,COUNT(`opid`) AS num FROM " .
                "(SELECT * FROM ". $this->table('im_message') ." WHERE `ownerid`={$this->uid} AND `r_rtime`=0 AND `box`=0 ORDER BY `timestamp` DESC) t " .
                "GROUP BY `opid` ORDER BY `timestamp` DESC LIMIT {$offset},{$size}";
        	
        $query=$this->db->query($sql);
        $result=$query->result_array();
        	
        $query=$this->db->query("SELECT FOUND_ROWS() AS total");
        $result2=$query->result_array(FALSE);
        $total=$result2[0]['total'];
        	
        return array(
                'count'=>$total,
                'data'=>$this->_getResult($result)
        );
    }

    /**
     *
     * 获取所有的最近聊天列表
     * @param int $offset 偏移量
     * @param int $size 条数
     */
    public function getAll($offset=0,$size=20,$timestamp=0,$micro_timestamp=0){
        $cond = "";
        $limit = " LIMIT " . $offset . "," . $size;
        if($timestamp > 0){
            $cond = "AND `s_stime` > $timestamp ";
            $limit = "";
        }elseif($micro_timestamp > 0){
            $cond = "AND `timestamp` > $micro_timestamp ";
            $limit = "";
        }
        //收到的未读信息或者群发信息
        $sql="SELECT * FROM " .
                "(SELECT * FROM ". $this->table('im_message') ." WHERE `ownerid`={$this->uid} {$cond}GROUP BY `msgid` ORDER BY `timestamp` DESC) t " .
                "GROUP BY `opid` ORDER BY `timestamp` DESC{$limit}";
        //小秘的最近聊天列表过滤掉系统发送的消息
        if($this->uid == Kohana::config('uap.xiaomo')){
            $sql="SELECT * FROM " .
                    "(SELECT * FROM ". $this->table('im_message') ." WHERE `ownerid`={$this->uid} AND `msgtype`=0 {$cond}GROUP BY `msgid` ORDER BY `timestamp` DESC) t " .
                    "GROUP BY `opid` ORDER BY `timestamp` DESC{$limit}";
        }
        $query=$this->db->query($sql);
        $result = array();
        if($query->count() > 0) {
            $result=$query->result_array(FALSE);

            foreach($result as $row){
                $opids[]=$row['opid'];
            }
            $ids=implode("','", $opids);

            $sql="SELECT `new_count`,`opid` FROM ". $this->table('im_notify') ." WHERE `ownerid`={$this->uid} AND `opid` IN ('{$ids}')";

            $query=$this->db->query($sql);
            $result2=$query->result_array(FALSE);
            if(!$result2) $result2=array();

            for($i=0;$i<count($result);$i++){
                $new_count=0;
                foreach($result2 as $row){
                    if($result[$i]['opid'] == $row['opid']){
                        $new_count=$row['new_count'];
                        break;
                    }
                }

                $result[$i]['num']=$new_count;
            }
        }
        	
        return $this->_getResult($result);
    }

    /**
     *
     * 获取不分组的最近未读聊天历史记录
     */
    public function getAllNonGroup($offset=0,$size=20,$timestamp=0){
        $cond = "";
        $limit = " LIMIT " . $size;
        if($timestamp > 0){
            $cond = "AND `s_stime` > $timestamp ";
        }else{
            $cond = "AND `r_rtime` = 0 AND `box` = 0 ";
        }
        //收到的未读信息或者群发信息
        $sql="SELECT * FROM ". $this->table('im_message') ." WHERE `ownerid`={$this->uid} {$cond}ORDER BY `timestamp` DESC{$limit}";
        $query=$this->db->query($sql);
        $result = array();
        if($query->count() > 0) {
            $result=$query->result_array(FALSE);
        }

        return $this->_getResult($result);
    }

    /**
     *
     * 获取最近聊天历史总记录条数
     */
    public function getCount($uid=0, $unread=0, $appid=0) {
        if($appid>0){
            $app_message = new App_Message_Model($appid);
            $app_message->uid = $this->uid;
            return $app_message->getCount($uid);
        }
        
        if($unread){
            if($uid>0){
                $sql = "SELECT `new_count` AS total FROM ". $this->table('im_notify') ." WHERE `ownerid`={$this->uid} AND `opid`='{$uid}'";
            }else{
                $sql = "SELECT COUNT(0) AS total FROM ". $this->table('im_notify') ." WHERE `ownerid`={$this->uid} AND `new_count`>0";
            }
        }else{
            if($uid>0){
                if($this->uid == $uid){
                    $sql = "SELECT COUNT(0)/2 AS total FROM ". $this->table('im_message') ." WHERE `ownerid`={$this->uid} AND `opid`='{$uid}'";
                }else{
                    $sql = "SELECT COUNT(0) AS total FROM ". $this->table('im_message') ." WHERE `ownerid`={$this->uid} AND `opid`='{$uid}'";
                }
            }else{
                $sql="SELECT COUNT(0) as total FROM (SELECT 0 FROM " .
                        "(SELECT `msgid`,`opid`,`timestamp` FROM ". $this->table('im_message') ." WHERE `ownerid`={$this->uid} GROUP BY `msgid`) t " .
                        "GROUP BY `opid`) c";
                //小秘的最近聊天列表过滤掉系统发送的消息
                if($this->uid == Kohana::config('uap.xiaomo')){
                    $sql="DELETE FROM ". $this->table('im_message', NULL) ." WHERE `content_key`=''";
                    $this->db->query($sql);
                    	
                    $sql="SELECT COUNT(0) as total FROM (SELECT 0 FROM " .
                            "(SELECT `msgid`,`opid`,`timestamp` FROM ". $this->table('im_message') ." WHERE `ownerid`={$this->uid} AND `msgtype`=0 GROUP BY `msgid`) t " .
                            "GROUP BY `opid`) c";
                }
            }
        }
        $query=$this->db->query($sql);
        $result=$query->result_array(FALSE);
        if($result) {
            return (int)$result[0]['total'];
        }
        return 0;
    }

    /**
     *
     * 获取好友的昵称
     * @param int $fid
     public function getNicks($fid){
     $sql="SELECT formatted_name FROM contacts WHERE uid={$this->uid} AND fid={$fid}";
     $query=$this->db->query($sql);
     $rowset=$query->result_array(FALSE);
     if($rowset){
     $r=array();

     foreach($rowset as $row){
     $r[]=$row['formatted_name'];
     }

     return $r;
     }
      
     return array();
     }
     */

    //获取用户信息
    private $_cache_users=array();
    private function _user($uid,$avatar_size=130){
        if(!$uid){
            return array(
                    'id'=> 0,
                    'name'=>'',
                    'avatar'=>'',
                    'nick'=>array(),
                    'mobile'=>'',
                    'zone_code'=>'',
                    'role'=>0
            );
        }
        if(! $this->_cache_users[$uid]){
            $user_info=sns::getuser($uid);
            //          $nicks=$this->getNicks($uid);

            //报500错
            $nicks=Friend_Model::instance()->get_contact_formatted_name($this->uid, $user_info['mobile'], $user_info['zone_code']);

            if($nicks){
                $mobile=$user_info['mobile'];
            }else{
                $mobile='';
            }

            $this->_cache_users[$uid]=array(
                    'id'=> (int) $uid,
                    'name'=>$user_info['realname'],
                    'avatar'=>sns::getavatar($uid, $avatar_size),
                    'nick'=>$nicks,
                    'mobile'=>$mobile,
                    'zone_code'=>$user_info['zone_code'],
                    'role'=>(int)$user_info['role']
            );
        }
        return $this->_cache_users[$uid];
    }
    //获取群组用户信息
    private $_cache_group=array();
    private function _group($group_id){
        if(! $this->_cache_group[$group_id]){
            $sql="SELECT * FROM ". $this->table('im_group') ." WHERE `listmd5`='{$group_id}' LIMIT 1";
            $query=$this->db->query($sql);
            $op=array();
            if($query->count()){
                $result=$query->result_array(FALSE);
                foreach(explode(',',$result[0]['list']) as $uid){
                    $op[]=$this->_user($uid);
                }
            }
            $this->_cache_group[$group_id]=$op;
        }
        	
        return $this->_cache_group[$group_id];
    }

    private function _getResult($result){
        $r = $cnt_keys = array();
        if(! $result) return $r;

        //timer 30ms
        foreach($result as $row){
            $row=(array)$row;
            $data['_id']=$row['id'];
            $data['id']=$row['msgid'];
            $owner=$this->_user($row['ownerid']);

            $group_id=0;

            if($row['box']){//发件箱
                $op=array();
                if($row['optype']==1){
                    $op=$this->_group($row['opid']);
                    $group_id = $row['opid'];
                }else{
                    $op[]=$this->_user($row['opid']);
                }
                $data['sender']=$owner;
                $data['receiver']=$op;
            }else{
                $data['sender']=$this->_user($row['opid']);
                $data['receiver']=array($owner);
            }

            $data['group_id']=$group_id;
            $data['timestamp']=(int) $row['s_stime'];
            $data['client_id']=(int) $row['s_appid'];
            $data['content']=$row['content_key'];
            $data['status']=array(
                    'msg_read'=>$row['r_rtime']?1:0,
                    'sms_send'=>$row['s_sms']?1:0
            );
            if(isset($row['num'])){
                $data['unread_num'] = $row['num'];
            }

            $r[]=$data;
            $cnt_keys[]=new MongoID($row['content_key']);
        }
        	
        //timer 20ms
        $cols=$this->mongo_msg->find(array('_id'=>array('$in'=>$cnt_keys)));
        $cnt=array();
        $opt=array();
        if($cols){
            foreach($cols as $row){
                $cnt_key=(string) $row['_id'];
                if(isset($row['content']['text']) && is_array($row['content']['text'])){
                    $text=$row['content']['text'];
                    if(count($text)>1){
                        $row['content'] = array('text_long' => $text[0]);
                    }else{
                        $row['content'] = array('text' => $text[0]);
                    }
                }
                $cnt[$cnt_key]=$row['content'];
                if(isset($row['opt'])){
                    $opt[$cnt_key]=$row['opt'];
                }
            }
        }
        $unsetkey=array();
        for($i=0;$i<count($r);$i++){
            $cnt_key=$r[$i]['content'];
            $new_content=$this->_format_content($cnt[$cnt_key]);

            if($new_content){
                $r[$i]['content']=$new_content;
                if(isset($opt[$cnt_key])){
                    $r[$i]['opt']=$opt[$cnt_key];
                }
            }else{
                $unsetkey[]=$i;
            }
        }
        $missing_ids = array();
        foreach($unsetkey as $i){
            $missing_ids[] = $r[$i]['_id'];
            unset($r[$i]);
        }
        $this->delete_missing($missing_ids);
        return array_values($r);
    }

    /**
     *
     * 格式化消息体
     * @param array $cnt 消息体
     */
    public function _format_content($cnt){
        //如果是空记录
        if(!$cnt) return FALSE;

        //如果content不是数组
        if(!is_array($cnt)){
            $cnt=array('text'=>$cnt);
        }
        //如果content键大于一个只取第一个
        $im_keys=array_keys($cnt);
        $im_type=$im_keys[0];

        $allow_im_type=array(
                'text','text_long','picture','audio','file','location','sender_card','contact',
                'register_remind','message_reward','mobile_modify','birthday_remind'
        );
        //如果符合支持的类型
        if(in_array($im_type, $allow_im_type)){
            $im_body=$cnt[$im_type];

            if($im_type == 'text' || $im_type == 'text_long'){
                $im_body = $im_body . '';
            }
            return array($im_type=>$im_body);
        }

        return FALSE;
    }

    public function get_format_content($row){
        if(isset($row['content']['text']) && is_array($row['content']['text'])){
            $text=$row['content']['text'];
            if(count($text)>1){
                $row['content'] = array('text_long' => $text[0]);
            }else{
                $row['content'] = array('text' => $text[0]);
            }
        }
        $row['_id']=(string)$row['_id'];
        $row['content']=$this->_format_content($row['content']);

        return $row;
    }

    /**
     *
     * 把文本切分为长文本格式
     * @param string $sourcestr 文本
     * @param int $length 文本长度
     */
    public function long_text_array($sourcestr, $length = 1500){
        $text_long = array();

        $str_length = strlen($sourcestr);
        if($str_length <= $length){
            $text_long[]=$sourcestr;
            return $text_long;
        }

        $page = 1;
        $offset = 0;
        while($offset <= $str_length){
            $returnstr = '';
            while($offset < $length*$page){
                $temp_str = substr($sourcestr, $offset, 1);
                $ascnum = ord($temp_str);
                //11110xxx 10xxxxxx 10xxxxxx 10xxxxxx 4字节
                if($ascnum >= 224){ //1110xxxx 10xxxxxx 10xxxxxx 3字节
                    $returnstr = $returnstr.substr($sourcestr, $offset, 3);
                    $offset = $offset + 3;
                }elseif($ascnum >= 192){ //110xxxxx 10xxxxxx 双字节
                    $returnstr = $returnstr.substr($sourcestr, $offset, 2);
                    $offset = $offset + 2;
                    //			}elseif($ascnum>=65 && $ascnum<=90){
                    //				$returnstr=$returnstr.substr($sourcestr,$i,1); //如果是大写字母
                    //				$i=$i+1;
                    //				$fontwidth=$fontwidth+1;
                }else{ //0xxxxxxx 单字节
                    $returnstr = $returnstr.substr($sourcestr, $offset, 1);
                    $offset = $offset + 1;
                }
            }
            $text_long[] = $returnstr;
            $page++;
        }

        return $text_long;
    }

    /**
     *
     * 存储消息
     * @param array $message 存储在mongo的消息体
     */
    public function putMessage($message){
        $res=$this->mongo_msg->insert($message,array('safe'=>TRUE));
        if($res['ok']) return (string) $message['_id'];
        else return '';
    }

    /**
     *
     * 获取长文本
     * @param string $msgid
     * @param int $page
     */
    public function getMessage($msgid,$page=0){
        $r=array('chunks'=>0,'text'=>'');
        $idx=$page-1;
        $cols=$this->mongo_msg->findOne(array('msgid'=>$msgid));
        if(isset($cols['content']['text']) && is_array($cols['content']['text'])){
            if($idx==-1){
                $r['text']=implode('', $cols['content']['text']);
            }else{
                $r['text']=$cols['content']['text'][$idx];
            }
            $r['chunks']=count($cols['content']['text']);
        }
        	
        return $r;
    }

    public function getIMMessageIndex($msgid){
        $sql="SELECT * FROM ". $this->table('im_message') ." WHERE msgid='$msgid' LIMIT 1";
        $query=$this->db->query($sql);
        $result=$query->result_array();
        if($result){
            return (array) $result[0];
        }else{
            return NULL;
        }
    }

    public function get_msg_by_msgid($msgid){
        $cols=$this->mongo_msg->findOne(array('msgid'=>$msgid));
        if($cols){
            return $cols;
        }
        return array();
    }

    public function getIMMessage($msgid,$sender_uid,$receiver_uid){
        $sql="SELECT * FROM ". $this->table('im_message', $receiver_uid) ." WHERE msgid='$msgid' AND ownerid=$receiver_uid AND opid='$sender_uid' LIMIT 1";
        $query=$this->db->query($sql);
        $result=$query->result_array();
        if($result){
            if($all=$this->_getResult($result))
                return $all[0];
        }

        return array();
    }

    public function get_channel($receiver_mobile,$channel){
        $sql="SELECT * FROM ". $this->table('im_channel') ." WHERE receiver_mobile='$receiver_mobile' AND channel=$channel LIMIT 1";
        $query=$this->db->query($sql);
        $result=$query->result_array(FALSE);
        if($result){
            return (array) $result[0];
        }

        return array();
    }

    //获取双方的uid
    public function get_channel_nv($receiver_mobile,$channel){
        $sql="SELECT * FROM ". $this->table('im_channel_nv') ." WHERE receiver_mobile='$receiver_mobile' AND channel=$channel LIMIT 1";

        $query=$this->db->query($sql);
        $result=$query->result_array(FALSE);

        if($result){
        	$sql = "SELECT uid, mobile FROM ". $this->table('members') ." WHERE mobile in ('". $result[0]['sender_mobile'] ."','". $result[0]['receiver_mobile'] ."')";

        	$query=$this->db->query($sql);
        	$result=$query->result_array(FALSE);
        	if($result){
        		$ret = array();
				foreach($result as $info)
				{
				   		if($info["mobile"] == $receiver_mobile)
				   		{
				   			$ret["receiver_mobile"] = $info["mobile"];
				   			$ret['receiver_uid'] = $info[uid];
				   		}
				   		else
				   		{
				   			$ret["sender_mobile"] = $info["mobile"];
				   			$ret['sender_uid'] = $info[uid];
				   		}
				}
				return $ret;    		
        	}
        }

        return array();
    }
    
    public function udp_channel($receiver_uid,$sender_uid,$channel){
        $timestamp=time();
        $sql="UPDATE ". $this->table('im_channel') ." SET act_count=act_count+1,act_time=$timestamp WHERE receiver_uid=$receiver_uid AND sender_uid=$sender_uid AND channel=$channel LIMIT 1";
        return $this->db->query($sql);
    }

    public function log_smsreceive($Mobile,$AppendID,$Content){
        $timestamp=intval(microtime(TRUE)*1000);
        $set=array(
                'from'=>$Mobile,
                'channel'=>$AppendID,
                'content'=>$Content,
                'timestamp'=>$timestamp,
        		'source_type'=>0,
        );
        $this->db->insert($this->table('im_pushlog'),$set);
    }
    
    public function log_smsreceive_nv($Mobile,$AppendID,$Content){
        $timestamp=intval(microtime(TRUE)*1000);
        $set=array(
                'from'=>$Mobile,
                'channel'=>$AppendID,
                'content'=>$Content,
                'timestamp'=>$timestamp,
        		'source_type'=>1,
        );
        $this->db->insert($this->table('im_pushlog'),$set);
    }    
    
    //注册成功更新
    public function upd_smsregister($guid,$mobile,$uid,$exist){
        if(!$exist){
            $sql = "UPDATE ". $this->table('im_register') ." SET mobile='$mobile',uid=$uid,status=2 WHERE guid='$guid' LIMIT 1";
        }else{
            $sql = "UPDATE ". $this->table('im_register') ." SET mobile='$mobile',uid=$uid,status=3 WHERE guid='$guid' LIMIT 1";
        }
        $this->db->query($sql);
    }

    public function gen_smsregister($install_id,$phone_model,$os,$device_id,$client_id,$appid=0,$imsi=''){
        $uniqid = md5(uniqid ( '', TRUE ));

        $set=array(
                'guid'=>$uniqid,
                'mobile'=>'',
                'uid'=>0,
                'status'=>1,
                'install_id'=>$install_id,
                'phone_model'=>$phone_model,
                'os'=>$os,
                'device_id'=>$device_id,
                'client_id'=>$client_id,
                'appid'=>$appid,
                'imsi'=>$imsi,
                'ctime'=>time(),
        );
        if($this->db->insert($this->table('im_register'),$set)){
            $mobile = array(
                    'china_mobile' => '10657500005339929',
                    'china_unicom' => '10655010535539929',
                    'china_telecom' => '10690195319929',
            );
            return array ('uniqid' => $uniqid, 'mobile' => $mobile, 'message' => 'register_uniqid:'.$uniqid );
        }

        return array();
    }
    
    /**
     *
     * 获取上行短信注册信息
     * @param $guid
     */
    public function get_smsregister($guid){
        $sql="SELECT * FROM ". $this->table('im_register') ." WHERE guid='{$guid}'";
        $query = $this->db->query($sql);

        if($query->count()){
            $result = $query->result_array(FALSE);
            $r=$result[0];
            return $r;
        }

        return NULL;
    }
    
    public static function PushDebug($uid, $msg){
        $msgid = api::uuid();
        $content = array('text'=>$msg);
    
        $sender=array('id'=>353,'name'=>'','avatar'=>'');
        $receivers=array(array('id'=>$uid,'name'=>'','avatar'=>''));
    
        $sms=array(
                'kind'=>'sms',
                'data'=>array(
                        'id'=>$msgid,
                        'sender'=>$sender,
                        'receiver'=>$receivers,
                        'timestamp'=>time(),
                        'content'=>$content,
                        'client_id'=>0
                )
        );
    
        $feedModel = new Feed_Model();
        $feedModel->mq_send(json_encode($sms), '353.' . $uid, 'momo_im');
    }
    
    /**
     * 建立imsi和手机号的关联
     * @param string $imsi
     * @param string $mobile
     * @param string $zone_code
     * @param int $type
     */
    public function gen_imsi_link($imsi, $mobile, $zone_code='86', $type=0){
        $set = array(
                'imsi'=>$imsi,
                'type'=>$type,
                'mobile'=>$mobile,
                'zone_code'=>$zone_code,
                'ctime'=>time()
        );

        $this->db->insert('im_register_imsi',$set);
    }
    
    /**
     * 根据imsi获取关联记录
     * @param string $imsi
     * @return array:
     */
    public function get_imsi_link($imsi){
        $sql="SELECT * FROM im_register_imsi WHERE imsi=" . $this->db->escape($imsi);
        $query = $this->db->query($sql);
        
        if($query->count()){
            $result = $query->result_array(FALSE);
            $r=$result[0];
            return $r;
        }
        
        return array();
    }
    
    /**
     * 根据手机号获取关联记录
     * @param string $mobile
     * @param string $zone_code
     * @return array:
     */
    public function get_imsi_link_by_mobile($mobile, $zone_code='86'){
        $sql="SELECT * FROM im_register_imsi WHERE mobile=" . $this->db->escape($mobile) . " AND zone_code=" . $this->db->escape($zone_code);
        $query = $this->db->query($sql);
    
        if($query->count()){
            $result = $query->result_array(FALSE);
            $r=$result[0];
            return $r;
        }
    
        return array();
    }
    
    /**
     * 删除吻合imsi或者手机号的所有关联记录
     * @param string $imsi
     * @param string $mobile
     * @param string $zone_code
     * @return boolean
     */
    public function del_imsi_link($imsi='', $mobile='', $zone_code='86'){
        $cond1 = $cond2 = '';
        if($imsi){
            $cond1 = '(imsi='.$this->db->escape($imsi).')';
        }
        if($mobile && $zone_code){
            $cond2 = '(mobile='.$this->db->escape($mobile).' AND zone_code='.$this->db->escape($zone_code).')';
        }
        
        if($cond1 && $cond2){
            $cond = "($cond1 OR $cond2)";
        }elseif ($cond1){
            $cond = $cond1;
        }elseif ($cond2){
            $cond = $cond2;
        }else{
            return FALSE;
        }
        
        return $this->db->query("DELETE FROM im_register_imsi WHERE $cond");
    }
    
    public function gen_guid_link($appid, $guid, $mobile, $zone_code='86'){
        $set = array(
                'appid'=>$appid,
                'guid'=>$guid,
                'mobile'=>$mobile,
                'zone_code'=>$zone_code,
                'ctime'=>time()
        );
        
        $this->db->insert('im_register_guid',$set);
    }
    
    public function get_guid_link($appid, $guid){
        $sql="SELECT * FROM im_register_guid WHERE appid={$appid} AND guid=" . $this->db->escape($guid);
        $query = $this->db->query($sql);
        
        if($query->count()){
            $result = $query->result_array(FALSE);
            $r=$result[0];
            return $r;
        }
        
        return array();
    }
    
    public function get_guid_link_by_mobile($appid, $mobile, $zone_code='86'){
        $sql="SELECT * FROM im_register_guid WHERE appid={$appid} AND mobile=" . $this->db->escape($mobile) . " AND zone_code=" . $this->db->escape($zone_code);
        $query = $this->db->query($sql);
    
        if($query->count()){
            $result = $query->result_array(FALSE);
            $r=$result[0];
            return $r;
        }
    
        return array();
    }
    
    public function verify_guid_link($appid, $guid){
        $sql="UPDATE im_register_guid SET verify=1 WHERE verify=0 AND appid={$appid} AND guid=" . $this->db->escape($guid);
        return $this->db->query($sql);
    }
    
    public function del_guid_link($appid, $guid='', $mobile='', $zone_code='86'){
        $cond1 = $cond2 = '';
        if($guid){
            $cond1 = '(imsi='.$this->db->escape($guid).')';
        }
        if($mobile && $zone_code){
            $cond2 = '(mobile='.$this->db->escape($mobile).' AND zone_code='.$this->db->escape($zone_code).')';
        }
        
        if($cond1 && $cond2){
            $cond = "($cond1 OR $cond2)";
        }elseif ($cond1){
            $cond = $cond1;
        }elseif ($cond2){
            $cond = $cond2;
        }else{
            return FALSE;
        }
        
        return $this->db->query("DELETE FROM im_register_guid WHERE appid=$appid AND $cond");
    }
    
    public function clear_smsregister_link(){
        $sql="DELETE FROM im_register_imsi";
        $this->db->query($sql);
        $sql="DELETE FROM im_register_guid";
        $this->db->query($sql);
    }

    /**
     *
     * 发送名片消息
     * @param $sender_uid 发送者id
     * @param $receiver_uid 接收者id
     * @param $client_id 客户端编号
     */
    public function send_card($sender_uid, $receiver_uid, $client_id=0){
        if(! $sender_uid || ! $receiver_uid) return FALSE;

        $sender=array('id'=>$sender_uid,'name'=>sns::getrealname($sender_uid),'avatar'=>sns::getavatar($sender_uid));
        $receiver[]=array('id'=>$receiver_uid,'name'=>sns::getrealname($receiver_uid),'avatar'=>sns::getavatar($receiver_uid));

        $content['sender_card'] = array('id'=>$sender_uid);
        $msgid = api::uuid();
        $sms = array(
                'kind'=>'sms',
                'data'=>array(
                        'id'=>$msgid,
                        'sender'=>$sender,
                        'receiver'=>$receiver,
                        'timestamp'=>time(),
                        'content'=>$content,
                        'client_id'=>intval($client_id),
                )
        );
        $feedModel = new Feed_Model();
        $feedModel->mq_send(json_encode($sms), $sender_uid .'.'. $receiver_uid, 'momo_im');
        return TRUE;
    }

    /**
     *
     * 删除消息
     * @param array $msgids
     */
    public function delete($msgids){
        foreach($msgids as $msgid){
            $msgids_escape[] = $this->db->escape($msgid);
        }
        $msgidstr=implode(',', $msgids_escape);

        $cond="msgid IN (".$msgidstr.")";
        return $this->_delete($cond);
    }

    public function delete_all($opid){
        $opid=$this->db->escape((string)$opid);

        $cond="opid=".$opid;
        return $this->_delete($cond);
    }
    
    public function delete_missing($missing_ids){
        if($missing_ids){
            $cond="id IN (".implode(',', $missing_ids).")";
            return $this->_delete($cond, TRUE);
        }
    }

    private function _delete($cond, $is_missing=FALSE){
        $query=$this->db->query("SELECT * FROM ". $this->table('im_message') ." WHERE ownerid=".$this->uid." AND ".$cond);
        $result=$query->result_array(NULL, MYSQL_NUM);
        if($result){
            $sql_bak="INSERT INTO ". $this->table('im_message_del') ." VALUES ";
            foreach ($result as $row){
                $newrow=array();
                foreach($row as $field){
                    $newrow[]=$this->db->escape($field);
                }
                $newrow[] = (int)$is_missing;
                $sql_bak.="(".implode(',', $newrow)."),";
            }
            if($this->db->query(rtrim($sql_bak,','))){
                return $this->db->query("DELETE FROM ". $this->table('im_message') ." WHERE ownerid=".$this->uid." AND ".$cond);
            }
        }else{
            //没有数据直接返回成功
            return TRUE;
        }

        return FALSE;
    }

    /**
     *
     * @param unknown_type $addr 地理位置名称
     * @param unknown_type $latitude 经度
     * @param unknown_type $longitude 纬度
     */
    public function add_map_history($addr,$latitude,$longitude,$is_correct){
        $latitude=(float)$latitude;
        $longitude=(float)$longitude;
        $is_correct=intval($is_correct);
        if(trim($addr)=='' || !$latitude || !$longitude){
            return FALSE;
        }
        $addr=$this->db->escape($addr);

        $sql = "REPLACE INTO ". $this->table('im_geohistory') ." (`address`,`uid`,`longitude`,`latitude`,`is_correct`,`ctime`) VALUES ({$addr},{$this->uid},$longitude,$latitude,$is_correct," . time() . ")";
        return $this->db->query($sql);
    }

    public function get_map_history($page,$pagesize,$keyword){
        $limit=(($page-1)*$pagesize).','.$pagesize;

        if($keyword) $cond = " AND `address` LIKE '%".mysql_escape_string($keyword)."%'";
        else $cond = "";

        $sql="SELECT SQL_CALC_FOUND_ROWS * FROM ". $this->table('im_geohistory') ." WHERE `uid`={$this->uid}{$cond} ORDER BY `ctime` DESC LIMIT {$limit}";
        $query = $this->db->query($sql);

        if($query->count()){
            $result = $query->result_array(FALSE);

            $query=$this->db->query("SELECT FOUND_ROWS() AS total");
            $result2=$query->result_array(FALSE);
            $total=$result2[0]['total'];

            return array("data"=>$result,"count"=>$total);
        }

        return array("data"=>array(),"count"=>0);
    }

    public function get_groupsms($page,$pagesize,$type){
        $limit=(($page-1)*$pagesize).','.$pagesize;
        if($type==1){
            $where='verify>0';
        }elseif($type==2){
            $where='verify=-1';
        }else{
            $where='verify=0';
        }
        $sql="SELECT * FROM ". $this->table('im_grpsms_verify') ." WHERE {$where} ORDER BY id DESC LIMIT {$limit}";
        $query = $this->db->query($sql);
        $list=array();
        if($query->count()){
            $result = $query->result_array(FALSE);
            foreach($result as $row){
                $r=array();
                $sender_info=$this->_user($row['sender_uid']);
                $r['sender']=$sender_info;
                $r['id']=$row['id'];
                $r['receiver_count']=$row['receiver_count'];
                $r['ctime']=$row['ctime'];

                $cols=$this->mongo_msg->findOne(array('_id' => new MongoID($row['content_key'])));
                if($cols){
                    $cols=$this->get_format_content($cols);
                    $r['content']=$cols['content'];
                    $list[]=$r;
                }
            }
        }
        return $list;
    }

    public function verify_groupsms($id, $op=TRUE){
        if(!$op){
            $sql="UPDATE ". $this->table('im_grpsms_verify') ." SET `verify`=-1 WHERE `id`=".intval($id);
            return $this->db->query($sql);
        }
        $sql="SELECT * FROM ". $this->table('im_grpsms_verify') ." WHERE `id`=".intval($id);
        $query = $this->db->query($sql);

        if($query->count()){
            $result = $query->result_array(FALSE);
            $row=$result[0];
            if($row['verify'] != 0)
                return FALSE;

            $sender_uid=$row['sender_uid'];
            $msgid=$row['msgid'];
            $exchange='amq.direct';
            $routing_key='queue_auto_mobisms';

            $group_id=$row['group_id'];
            $content_key=$row['content_key'];

            $sql="SELECT * FROM ". $this->table('im_group') ." WHERE listmd5='{$group_id}'";
            $query = $this->db->query($sql);
            if($query->count()<1){
                return FALSE;
            }
            $result = $query->result_array(FALSE);
            $receiver_uids_str=$result[0]['list'];
            $receiver_uids=explode(',', $receiver_uids_str);
            $receiver_uids=array_unique($receiver_uids);
            if(count($receiver_uids)<1){
                return FALSE;
            }

            $cols=$this->mongo_msg->findOne(array('_id' => new MongoID($content_key)));
            if(!$cols){
                return FALSE;
            }else{
                $cols=$this->get_format_content($cols);
            }
            $content=$cols['content'];
            if(!$content){
                return FALSE;
            }
            /**
             * {
             *  "kind":"mobile_sms",
             *  "data":{
             *      "sender":{"id":sender_id, "name":""},
             *      "receiver":[receiver_id],
             *      "timestamp":timestamp,
             *      "content":{"message":{"id":msgid, "content":content}}
             *  }
             * }
             */
            $feedModel = new Feed_Model();
            foreach ($receiver_uids as $receiver_uid){
                if($receiver_uid==$sender_uid)
                    continue;

                $sms=array(
                        'kind'=>'mobile_sms',
                        'data'=>array(
                                'params'=>array('sms_max_limit'=>0),
                                'sender'=>array('id'=>$sender_uid,'name'=>''),
                                'receiver'=>array($receiver_uid),
                                'timestamp'=>time(),
                                'content'=>array(
                                        'message'=>array('id'=>$msgid,'content'=>$content)
                                )
                        )
                );
                $feedModel->mq_send(json_encode($sms), $routing_key, $exchange);
            }

            $sql="UPDATE ". $this->table('im_grpsms_verify') ." SET `verify`=".time()." WHERE `id`=".intval($id);
            $this->db->query($sql);

            return TRUE;
        }

        return FALSE;
    }

}