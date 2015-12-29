<?php defined('SYSPATH') OR die('No direct access allowed.');

class App_Message_Model extends Model {
    
    public $appid = 0;
    
    public $is_app_standalone = FALSE;
    
    //jieyu.me is standalone
    public $standalone_apps = array(91);
    
    protected static $instance = NULL;
    
    public static function instance($appid=0){
        if(! self::$instance){
            self::$instance = new App_Message_Model($appid);
        }
    
        return self::$instance;
    }
    
    public function __construct($appid=0){
        parent::__construct();
        $mg_instance = new Mongo(Kohana::config('uap.mongodb'));
        $this->mongo_msg = $mg_instance->selectDB('app_message')->selectCollection ('im_message');
        $this->appid = $appid;
        if (in_array((int)$this->appid, $this->standalone_apps)){
            $this->is_app_standalone = TRUE;
        }
    }
    
    public function table($table, $uid=0){
        if($uid === 0){
            $uid = $this->uid;
        }
        return $table;
    }
    
    //获取用户信息
    private $_cache_users=array();
    private function _user($uid,$avatar_size=130){
        //外部应用不需获取用户信息
        if($this->is_app_standalone){
            return array(
                    'id'=>$uid,
                    'name'=>'',
                    'avatar'=>'',
            );
        }
        if(!$uid){
            return array(
                    'id'=> 0,
                    'name'=>'',
                    'avatar'=>'',
                    'nick'=>array(),
                    'mobile'=>'',
                    'zone_code'=>'',
            );
        }
        if(! $this->_cache_users[$uid]){
            $user_info=sns::getuser($uid);
            
            $nicks=Friend_Model::instance()->get_contact_formatted_name($this->uid, $user_info['mobile'], $user_info['zone_code']);
    
            $mobile=$user_info['mobile'];
    
            $this->_cache_users[$uid]=array(
                    'id'=> (int) $uid,
                    'name'=>$user_info['realname'],
                    'avatar'=>sns::getavatar($uid, $avatar_size),
                    'nick'=>$nicks,
                    'mobile'=>$mobile,
                    'zone_code'=>$user_info['zone_code']
            );
        }
        return $this->_cache_users[$uid];
    }
    //获取群组用户信息
    private $_cache_group=array();
    private function _group($group_id){
        if(! $this->_cache_group[$group_id]){
            $sql="SELECT * FROM ". $this->table('app_message_group') ." WHERE `listmd5`='{$group_id}' LIMIT 1";
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
            //群发id
            $data['group_id']=$group_id;
            //发送时间
            $data['timestamp']=(int) ($row['timestamp']/1000);
            //发送客户端
            $data['client_id']=(int) $row['s_client'];
            $data['content']=$row['content_key'];
            //消息反馈
            $data['status']=array(
                    'msg_read'=>$row['r_time']?1:0,
                    'sms_send'=>in_array(3, explode(',', $row['r_roger']))?1:0
            );
            //相同会话还剩未读条数
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
                if(isset($row['opt']) && $row['opt']){
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
        foreach($unsetkey as $i){
            unset($r[$i]);
        }
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
    
    
    /**
     *
     * 获取最近的私聊通知
     * @param int $num 条数
     * @param boolean $unread 是否只取未读
     */
    public function getNotify($num,$unread=0){
        if($unread){
            $sql="SELECT `ownerid`,`opid` FROM ". $this->table('app_message_notify') .
            " WHERE `app`={$this->appid} AND `ownerid`={$this->uid} AND `r_new_count` > 0 ORDER BY `r_last_time` DESC LIMIT {$num}";
        }else{
            $num *= 2;
            $sql="SELECT `ownerid`,`opid` FROM ". $this->table('app_message_notify') .
            " WHERE `app`={$this->appid} AND `ownerid`={$this->uid} OR `opid`='{$this->uid}' ORDER BY `r_last_time` DESC LIMIT {$num}";
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
    
    public function getIMMessageIndex($msgid){
        $sql="SELECT * FROM ". $this->table('app_message') ." WHERE msgid='$msgid' LIMIT 1";
        $query=$this->db->query($sql);
        $result=$query->result_array();
        if($result){
            return (array) $result[0];
        }else{
            return NULL;
        }
    }
    
    public function getIMMessage($msgid,$sender_uid,$receiver_uid){
        $sql="SELECT * FROM ". $this->table('app_message') ." WHERE msgid='$msgid' AND `app`={$this->appid} AND ownerid=$receiver_uid AND opid='$sender_uid' LIMIT 1";
        $query=$this->db->query($sql);
        $result=$query->result_array();
        if($result){
            if($all=$this->_getResult($result))
                return $all[0];
        }
    
        return array();
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
    public function getMore($uid,$offset=0,$size=20,$forward=0,$micro_timestamp=0){
        $micro_timestamp = sprintf('%-013.0f', $micro_timestamp);
        $cond = "";
        $limit = $offset . "," . $size;
        if($micro_timestamp > 0 ){
            if($forward){
                $cond = "AND `timestamp` > $micro_timestamp ";
            }else{
                $cond = "AND `timestamp` < $micro_timestamp ";
            }
            $limit = $size;
        }
    
        $sql="SELECT SQL_CALC_FOUND_ROWS * FROM ". $this->table('app_message') .
        " WHERE `app`={$this->appid} AND `ownerid`={$this->uid} AND `opid`='{$uid}' {$cond}ORDER BY `timestamp` DESC LIMIT {$limit}";
         
        if($this->uid == $uid) {
            $sql="SELECT SQL_CALC_FOUND_ROWS * FROM ". $this->table('app_message') .
            " WHERE `app`={$this->appid} AND `ownerid`={$this->uid} AND `opid`='{$uid}' AND `box`=1 {$cond}ORDER BY `timestamp` DESC LIMIT {$limit}";
        }
         
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
     * 获取分组消息
     * @param int $offset 偏移量
     * @param int $size 条数
     */
    public function getAll($offset=0,$size=20,$micro_timestamp=0){
        $micro_timestamp = sprintf('%-013.0f', $micro_timestamp);
        $cond = "";
        $limit = " LIMIT " . $offset . "," . $size;
        if($micro_timestamp > 0){
            $cond = "AND `timestamp` > $micro_timestamp ";
            $limit = "";
        }
        //收到的未读信息或者群发信息
        $sql="SELECT SQL_CALC_FOUND_ROWS * FROM " .
                "(SELECT * FROM ". $this->table('app_message') ." WHERE `app`={$this->appid} AND `ownerid`={$this->uid} {$cond}ORDER BY `timestamp` DESC) t " .
                "GROUP BY `opid` ORDER BY `timestamp` DESC{$limit}";

        $query=$this->db->query($sql);
        $total = 0;
        $result = array();
        if($query->count() > 0) {
            $result=$query->result_array(FALSE);
            
            $query=$this->db->query("SELECT FOUND_ROWS() AS total");
            $result2=$query->result_array(FALSE);
            $total=$result2[0]['total'];
    
            foreach($result as $row){
                $opids[]=$row['opid'];
            }
            $ids=implode("','", $opids);
    
            $sql="SELECT `r_new_count`,`opid` FROM ". $this->table('app_message_notify') ." WHERE `app`={$this->appid} AND `ownerid`={$this->uid} AND `opid` IN ('{$ids}')";
    
            $query=$this->db->query($sql);
            $result2=$query->result_array(FALSE);
            if(!$result2) $result2=array();
    
            for($i=0;$i<count($result);$i++){
                $new_count=0;
                foreach($result2 as $row){
                    if($result[$i]['opid'] == $row['opid']){
                        $new_count=$row['r_new_count'];
                        break;
                    }
                }
    
                $result[$i]['num']=$new_count;
            }
        }
         
        return array(
                'count'=>$total,
                'data'=>$this->_getResult($result)
               );
    }
    
    /**
     *
     * 获取不分组的未读消息
     */
    public function getAllNonGroup($offset=0,$size=20,$micro_timestamp=0){
        $micro_timestamp = sprintf('%-013.0f', $micro_timestamp);
        $cond = "";
        $limit = " LIMIT " . $size;
        if($micro_timestamp > 0){
            $cond = "AND `timestamp` > $micro_timestamp ";
        }else{
            $cond = "AND `r_time` = 0 AND `box` = 0 ";
        }
        //收到的未读信息或者群发信息
        $sql="SELECT SQL_CALC_FOUND_ROWS * FROM ". $this->table('app_message') ." WHERE `app`={$this->appid} AND `ownerid`={$this->uid} {$cond}ORDER BY `timestamp` DESC{$limit}";
        $query=$this->db->query($sql);
        $result = array();
        if($query->count() > 0) {
            $result=$query->result_array(FALSE);
        }
        
        $query=$this->db->query("SELECT FOUND_ROWS() AS total");
        $result2=$query->result_array(FALSE);
        $total=$result2[0]['total'];
    
        return array(
                'count'=>$total,
                'data'=>$this->_getResult($result)
               );
    }
    
    public function getAllReceived($offset=0,$size=20,$micro_timestamp=0){
        $micro_timestamp = sprintf('%-013.0f', $micro_timestamp);
        $cond = "";
        $limit = " LIMIT " . $offset . "," . $size;
        if($micro_timestamp > 0){
            $cond = "AND `timestamp` > $micro_timestamp ";
            $limit = "";
        }
        //收到的未读信息或者群发信息
        $sql="SELECT SQL_CALC_FOUND_ROWS * FROM ". $this->table('app_message') ." WHERE `app`={$this->appid} AND `ownerid`={$this->uid} AND `box`=0 {$cond}ORDER BY `timestamp` DESC{$limit}";
        
        $query=$this->db->query($sql);
        $result=$query->result_array(FALSE);
        
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
     * 只获取分组的未读消息
     * @param int $offset 偏移量
     * @param int $size 条数
     */
    public function getNew($offset=0,$size=20){
        $sql="SELECT SQL_CALC_FOUND_ROWS *,r_new_count AS num FROM ". $this->table('app_message_notify') .
                " WHERE `app`={$this->appid} AND `ownerid`={$this->uid} AND `r_new_count`>0 ORDER BY `r_last_time` DESC LIMIT {$offset},{$size}";
         
        $query=$this->db->query($sql);
        $rowset=$query->result_array(FALSE);
         
        $query=$this->db->query("SELECT FOUND_ROWS() AS total");
        $result2=$query->result_array(FALSE);
        $total=$result2[0]['total'];
        
        $result = $msgids = array();
        foreach($rowset as $row){
            $msgids[$row['r_last_msgid']] = $row['num'];
        }
        if($msgids){
            $sql = "SELECT * FROM ". $this->table('app_message') ." WHERE `ownerid`={$this->uid} AND `msgid` IN ('". implode("','", array_keys($msgids)) ."')";
            $query=$this->db->query($sql);
            $result=$query->result_array(FALSE);
        }
        
        for($i=0;$i<count($result);$i++){
            $msgid = $result[$i]['msgid'];
            $result[$i]['num'] = $msgids[$msgid];
        }
         
        return array(
                'count'=>$total,
                'data'=>$this->_getResult($result)
               );
    }
    
    public function getCount($uid=0){
        if($uid>0){
            $sql = "SELECT `new_count` AS total FROM ". $this->table('app_message_notify') ." WHERE `app`={$this->appid} AND `ownerid`={$this->uid} AND `opid`='{$uid}'";
        }else{
            $sql = "SELECT COUNT(0) AS total FROM ". $this->table('app_message_notify') ." WHERE `app`={$this->appid} AND `ownerid`={$this->uid} AND `r_new_count`>0";
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
     * 删除消息
     * @param array $msgids
     */
    public function delete($msgids){
        foreach($msgids as $msgid){
            $msgids_escape[] = $this->db->escape($msgid);
        }
        $msgidstr=implode(',', $msgids_escape);
    
        $cond="`msgid` IN (".$msgidstr.")";
        return $this->_delete($cond);
    }
    
    public function delete_all($opid){
        $opid=$this->db->escape((string)$opid);
    
        $cond="`opid`=".$opid;
        return $this->_delete($cond);
    }
    
    private function _delete($cond){
        return $this->db->query("DELETE FROM ". $this->table('app_message') ." WHERE `app`={$this->appid} AND `ownerid`=".$this->uid." AND ".$cond);
    }
    
    public function mq_exchange(){
        $applist=array(
            27 => 'momo_flea',
            22 => 'momo_event',
            91 => 'jieyu'
        );
        
        return $applist[$this->appid];
    }
    
    public function mq_send($message, $routekey){
        $conn = new AMQPConnect(Kohana::config('uap.rabbitmq'));
        $exchange = new AMQPExchange($conn, $this->mq_exchange());
        $exchange->publish($message, $routekey, AMQP_MANDATORY, array('app_id'=>(string)$this->appid, 'user_id'=>(string)$this->uid));
    }
}