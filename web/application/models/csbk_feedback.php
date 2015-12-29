<?php
class Csbk_Feedback_Model extends Model {
	
	public $error_msg = '';
	public static $instances = null;	
	public $m;
	public $feedback;
	 
	public function __construct() {
		parent::__construct ();
		
		$mg_instance = new Mongo(Kohana::config('uap.mongodb'));
        $this->m = $mg_instance->selectDB(MONGO_DB_CALLSHOW);
        $this->feedback = $this->m->selectCollection('feedback');
	}

	public function search($state, $pos, $size)
	{
		$sql_count = "SELECT count(*) as num FROM cs_feedback ";
		$sql = "SELECT mongo_id FROM cs_feedback ";
		if($state !== NULL)
		{
			$sql_count .= "WHERE state = $state ";
			$sql .= "WHERE state = $state ";
		}		
		$sql .= "ORDER BY refresh_date DESC LIMIT $pos, $size";
		
		$count_query = $this->db->query($sql_count);
		$count_result = $count_query->result_array(FALSE);
		$count = 0;
		if($count_result)
		{
			$count = (int)$count_result[0]['num'];
		}
		
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
								"submitter"=>array('uid'=>$tmp['uid'],'guid'=>$tmp['guid'],'mobile'=>$uinfo['mobile'],'realname'=>$uinfo['realname'],'nickname'=>$uinfo['nickname']),
								"submit_time"=>	$tmp['create_date'],
				 				"client_id"=>(int)$tmp['client_id'],
								"phone_model"=>$tmp['phone_model'],
								"phone_os"=>$tmp['phone_os'],
								"version"=>$tmp['version']?$tmp['version']:"",
								"content"=>$tmp['content'],
								"state"=>$tmp['state'],
								"sms"=>$tmp['sms']?$tmp['sms']:array()
								);
				$date_arr[] = $tmp['refresh_date']?$tmp['refresh_date']:$tmp['create_date'];
			}
			
			array_multisort($date_arr, SORT_DESC, $ret);
		}
		
		return array("total"=>$count,"data"=>$ret);
	}
	
	public function send_sms($uid, $id, $sms, $phone)
	{
		$sms .= " [91来电秀团队]";
		$info = $this->feedback->findone(array('_id'=>$id));
		if(!$info)
		{
			return array("result"=>400, "msg"=>"反馈内容不存在");
		}
		
		$mobile = array();
		$user_model = User_Model::instance();
		if($phone)
		{
			$mobile = international::check_mobile($phone);
		}
		else if($info['uid'])
		{
			$uinfo = $user_model->get_user_info((int)$info['uid']);
			if($uinfo['mobile'])
			{
				$mobile = array(
					'country_code' => $uinfo['zone_code'],
					'mobile'       => $uinfo['mobile']
				);
			}
		}
		
		if($mobile)
		{
			//记录到mongo中
			$this->feedback->update(array('_id'=>$id), array('$push'=>array("sms"=>array("uid"=>$uid, "sms"=>$sms, 'mobile'=>$mobile['mobile'],'zone_code'=>$mobile['country_code'],"sms_date"=>time()))),array("upsert"=>TRUE));
			
			//记录到mysql中
			$sql = "REPLACE INTO cs_feedback_sms (mobile, zone_code, mongo_id) VALUES ('".$mobile['mobile']."',".$mobile['country_code'].",'".$id."')";
			$this->db->query($sql);
			//发送短信
			$user_model->sms_global($mobile['mobile'], $sms,$mobile['country_code'],1 );
		}
		else
		{
			//记录到mongo中
			$this->feedback->update(array('_id'=>$id), array('$push'=>array("sms"=>array("uid"=>$uid, "sms"=>$sms,"sms_date"=>time()))),array("upsert"=>TRUE));
		}
		
		//记录到事件中
		$callshow_model = Callshow_Model::instance();
		$t_uid = $info['uid']?$info['uid']:($info['guid']?$info['guid']:"");
		$callshow_model->add_sysmsg(353, 2,array(array('user_id'=>$t_uid, 'type'=>($info['uid']?1:0))), "谢谢您的反馈.我们将努力做得更好。",$sms, array('feedback'=>array("id"=>$id)));
		
		return array('result'=>200, "msg"=>NULL);
	}
	
	public function resend_sms($start, $end)
	{
		$ret_arr = array();
		$user_model = User_Model::instance();
		$ret = $this->search(NULL, 0, 500);
		if($ret && $ret['data'])
		{
			foreach ($ret['data'] as $tmp)
			{
				if(is_array($tmp['sms']) && count($tmp['sms']) > 0)
				{
					$tsms = $tmp['sms'][count($tmp['sms'])-1];
					if($tsms['sms_date'] > $start && $tsms['sms_date'] < $end && $tsms['mobile'] && $tsms['sms'] )
					{
						$user_model->sms_global($tsms['mobile'], $tsms['sms'],$mobile['zone_code'],1 );
						$ret_arr[] = $tsms;
					}
				}
			}
		}
		return array("result"=>200,"msg"=>$ret_arr);
	}
}