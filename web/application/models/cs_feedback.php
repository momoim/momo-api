<?php
class Cs_Feedback_Model extends Model {
	
	public $error_msg = '';
	public static $instances = null;	
	public $m;
	public $feedback;
	protected static $instance;
	
	/**
	 * 单例模式
	 * @return Callshow_Model
	 */
	public static function &instance()
	{
		if (! isset(self::$instance))
		{
			// Create a new instance
			self::$instance = new Cs_Feedback_Model();
		}
		return self::$instance;
	}
	
	public function __construct() {
		parent::__construct ();
		
		$mg_instance = new Mongo(Kohana::config('uap.mongodb'));
        $this->m = $mg_instance->selectDB(MONGO_DB_CALLSHOW);
        $this->feedback = $this->m->selectCollection('feedback');
	}

    public function create($uid, $client_id,$phone_os, $phone_model, $guid,$content,$version)
    {
    	$is_reg = $uid?1:0;
    	$user_id = $uid?"".$uid:$guid;
    	$mongo_id = api::uuid();
    	$create_date = time();
    	$state = 0;
    	$version = $version?$version:"";
    	
    	//将消息记录到mongo
    	$msg = array(
    					"_id"=> $mongo_id,
    	                "uid"=>$uid?$uid:0,
    					"guid"=>$guid?$guid:"",
    					"client_id"=>$client_id,
    					"phone_model"=>$phone_model,
    					"phone_os"=>$phone_os,
    					"version"=>$version,
    					"content"=>$content,
    					"create_date"=>$create_date,
    					"refresh_date"=>$create_date,
    					"state" =>$state
    				);
    	
    	$this->feedback->insert($msg);		
    				
    	$uid_t = $this->db->escape($user_id);
    	$phone_os_t = $this->db->escape($phone_os);
    	$phone_model_t = $this->db->escape($phone_model);
    	$version_t = $this->db->escape($version);
    	//消息记录到mysql
		$sql = "INSERT INTO cs_feedback (user_id, is_reg, client_id, phone_model, phone_os,version, mongo_id, create_date, refresh_date,state)
				VALUES ($uid_t,$is_reg,$client_id, $phone_model_t,$phone_os_t,$version_t,'$mongo_id',$create_date,$create_date, $state)";
		$query = $this->db->query($sql);
		$lastid = $query->insert_id();
    	if($lastid)
    	{
    		return array("result"=>200, "msg"=>array('id'=>$lastid));
    	}	
    	else
    	{
    		return array("result"=>500, "msg"=>"创建失败");
    	}		
    		
    }
    
    public function sms_feedback($mobile, $sms)
    {
    	//查找对应的反馈
    	$sql = "SELECT mongo_id FROM cs_feedback_sms WHERE mobile = '$mobile' AND zone_code = 86 LIMIT 1";
    	$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
    	
    	if($result)
    	{
    		$cur_time = time();
    		$sql = "UPDATE cs_feedback SET refresh_date = $cur_time WHERE mongo_id = '".$result[0]['mongo_id']."'";
    		$this->db->query($sql);

    		//记录到mongo中
			$this->feedback->update(array('_id'=>$result[0]['mongo_id']), array('$push'=>array("sms"=>array("sms"=>$sms, 'mobile'=>$mobile,'zone_code'=>'86',"sms_date"=>time(),"direction"=>"up")),'$set'=>array('refresh_date'=>$cur_time)),array("upsert"=>TRUE));
    	}
    	
    }
    
    public function getlist($uid, $guid)
    {
    	$user_id = $uid?"".$uid:$guid;
    	$uid_t = $this->db->escape($user_id);
    	
    	$sql = "SELECT mongo_id FROM cs_feedback WHERE user_id = $uid_t ORDER BY create_date DESC LIMIT 50";
		
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		$mongoid_arr = array();
		foreach ($result as $ret)
		{
			if($ret['mongo_id'])
			{
				$mongoid_arr[] = $ret['mongo_id'];
			}
		}
		
		$ret = array();
		if($mongoid_arr)
		{
			$content = $this->feedback->find(array('_id'=>array('$in'=>$mongoid_arr)));
			$user_model = User_Model::instance();
			$date_arr = array();
			foreach ($content as $tmp)
			{
				$uinfo = $user_model->get_user_info($tmp['uid']);
				$ret[] = array(
								"id"=>$tmp['_id'],
								"submit_time"=>	$tmp['create_date'],
								"content"=>$tmp['content'],
								"sms"=>$tmp['sms']?$tmp['sms']:array()
								);
				$date_arr[] = $tmp['create_date'];
			}
			
			array_multisort($date_arr, SORT_DESC, $ret);
		}
		
		return array("result"=>200,"msg"=>$ret);
    }
}