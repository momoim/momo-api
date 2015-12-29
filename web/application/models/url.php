<?php defined('SYSPATH') or die('No direct access allowed.');
/*
 * [UAP Portal] (C)1999-2009 ND Inc.
 * 短信短地址模型
 */

class Url_Model extends Model {

	public static $instances = null;
	
    public function __construct()
    {
        // 加载数据库类。以下可以使用 $this->db 操作数据库（如果不要求可以省略）
        parent::__construct();
    }

    /**
    * 
    * 单例模型
    * @return Url_Model
    */
    public static function &instance()
    {
        if (!is_object(Url_Model::$instances)) {
            // Create a new instance
            Url_Model::$instances = new Url_Model();
        }
        return Url_Model::$instances;
    }

    /**
     * 
     * 判断url code是否使用过
     * @param string $code
     */
    public function _code_not_used($code) {
    	$free = $this->db->where(array("url_code"=>$code))->count_records("members_url");
    	return $free > 0?false:true;
    }
    
    /**
     * 
     * 根据urlcode获取信息
     * @param unknown_type $code
     */
    public function get($code) {
		$query = $this->db->query("SELECT * FROM `members_url` WHERE `url_code` = '{$code}' LIMIT 1");
		$result = $query->result_array(FALSE);
		if($result[0]['id']) {
			return $result[0];
		}
		return FALSE;
    }
    
    /**
     * 
     * 更新短信url
     * @param string $url_code
     * @param string $receiver_uid
     * @param int $client_time
     * @param string $client_ip
     * @param string $browser_info
     * @param string $operateing_sys_info
     * @param string $user_agent
     * @param boolean $first_open
     */
    public function update($url_code, $receiver_uid, $client_time, $client_ip, $browser_info, $operateing_sys_info, $user_agent, $first_open,$password='') {
        $field = array("last_open_time" => $client_time, "last_open_ip" => $client_ip, "browser" => $browser_info, "os" => $operateing_sys_info, "user_agent" => $user_agent);
        
        if ($first_open) {
            $field["receive_time"] = $client_time;
            $field["receive_ip"] = $client_ip;
        }
    
        if ($password) {
            $field["password"] = $password;
        }
        
    	return $this->db->from('members_url')->set($field)->where(array('url_code' => $url_code))->update();
    }
    
    /**
     * 
     * 创建url短地址记录
     * @param string $type
     * @param string $url_code
     * @param int $sender_uid
     * @param string $sender_name
     * @param int $receiver_uid
     * @param string $receiver_name
     * @param int $status_id
     */
    public function create($type,$sender_uid,$sender_name,$receiver_uid,$receiver_name,$receiver_mobile,$receiver_zone_code='86',$status_id='',$msgid='',$content_type='',$appid=0) {
    	$code_not_used = false;
        $receiver_zone_code = empty($receiver_zone_code)?'86':$receiver_zone_code;
		do {
			$url_code = $this->_rand_str(3).$this->_rand_str(3);
			$code_not_used = $this->_code_not_used($url_code);
			if ( $code_not_used ) {
				$id = $this->db->insertData ( "members_url", array ('type' => $type,'appid' => $appid, 'url_code' => $url_code, 'sender_uid' => $sender_uid, 'sender_name' => $sender_name, 'receiver_uid' => $receiver_uid, 'receiver_name' => $receiver_name, 'receiver_mobile' => $receiver_mobile, 'receiver_zone_code' => $receiver_zone_code, 'status_id' => $status_id ,'send_time'=>time(),"msgid" => $msgid, "content_type" => $content_type) );
				if (empty ( $id )) {
					$error_body = 'sender_uid:' . $sender_uid . ' receiver_uid:' . $receiver_uid . '生成短地址失败';
					error_log ( '[' . date ( 'Y-m-d H:i:s' ) . '] : ' . $error_body . ".\n", 3, DOCROOT . 'application/logs/members_url_' . date ( 'Ymd' ) . '.log' );
					return false;
				}
				return $url_code;
			}
		} while (!$code_not_used);
		return false;
    }

    /**
     * 
     * 生成随机字符串
     * @param int $len
     */
	private function _rand_str($len=5) {
        $chars='1234567890abcdefghijklmnopqrstuvwxyz';
        mt_srand((double)microtime()*1000000*getmypid());
        $rand='';
        while(strlen($rand)<$len)
            $rand.=substr($chars,(mt_rand()%strlen($chars)),1);
        return $rand;
    }
    
    public function event($result){
        //$list=array(1,482,15);
        //if(!in_array($result['sender_uid'], $list)) return;
        
        if (empty($result["receive_time"])) {//第一次打开
            $start_time=strtotime('2011-12-31 00:00:00');
            $end_time=strtotime('2012-01-05 23:59:59');
            $now=time();
            if($now>$start_time && $now<$end_time){//活动期间
                if($result["content_type"]==1){//音频
                    $sender_uid=$result['sender_uid'];
                    
                    $query=$this->db->query("select * from tmp_event_20111225 where uid=".$sender_uid);
                    $r=$query->result_array(FALSE);
                    if($r){
                        
                    }else{//发送者还未获得奖励
                        $content="恭喜您成功通过MO短信发送语音信息，获得15张6寸彩照的免费冲印大礼，您的免费冲印优惠码：913030，在线冲印地址： http://t.momo.im/71guu （您可以通过电脑登录momo.im网站访问冲印链接），优惠码使用演示： http://ly.91.com/s";
                        User_Model::instance()->present_mo_notice(Kohana::config('uap.xiaomo'), $sender_uid, $content);
                        $this->db->query("insert into tmp_event_20111225 (uid,receiver_uid,open_time) values (".$sender_uid.",".$result['receiver_uid'].",".$now.")");
                    }
                }
            }
        }
    }
}
