<?php
class Csbk_Callshow_Model extends Model {
	
	public $error_msg = '';
	public static $instances = null;
	
	public function __construct() {
		parent::__construct ();
	}
	
	public function del($show_id)
	{
		$sql = "SELECT id, creator, owner FROM cs_history WHERE id = $show_id AND is_deleted = 0 LIMIT 1";
		$query = $this->db->query($sql);
		$result2 = $query->result_array(FALSE);
		
		if(!$result2 )
		{
			return array("result"=>400, "msg"=>"来电秀不存在");
		}
		
		$sql = "SELECT owner, private_create, cur_show_id FROM cs_user WHERE owner = ".$result2[0]['owner']." LIMIT 1";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		if(!$result)
		{
			return array("result"=>400, "msg"=>"内部错误");
		}
		
		$callshow_model = Callshow_Model::instance();
		
		//将该来电秀标志为已删除
		$sql = "UPDATE cs_history SET is_deleted=1 WHERE id = $show_id";
		$this->db->query($sql);
		
		$callshow_model->del_mass_show($show_id);
		
		//若为当前秀，则选一个最新的秀代替
		if((int)$result[0]['cur_show_id'] == $show_id )
		{
			$new_id = 0;
			$sql = "SELECT id FROM cs_history WHERE owner = ".$result[0]['owner']." AND is_deleted = 0 ORDER BY update_time DESC LIMIT 1";
			$query = $this->db->query($sql);
			$tresult = $query->result_array(FALSE);
			if($tresult)
			{
				$new_id = (int)$tresult[0]['id'];
			}
			
			$sql = "UPDATE cs_user SET cur_show_id = $new_id, refresh_date = ".time()." WHERE owner = ".$result[0]['owner'];
			$this->db->query($sql);
		}
		
		$callshow_model->add_sysmsg(353, 2,array(array('user_id'=>$result2[0]['owner'], 'type'=>1)), "您的来电秀包含违规内容，已从分享移除，如需帮助，请点击反馈联系!","您的来电秀包含违规内容，已从分享移除，如需帮助，请点击反馈联系!", array('show'=>array("id"=>$show_id)));
		if($result2[0]['owner'] != $result2[0]['creator'])
		{
			$callshow_model->add_sysmsg(353, 2,array(array('user_id'=>$result2[0]['creator'], 'type'=>1)), "您发布的来电秀包含违规内容，已从分享移除，如需帮助，请点击反馈联系!","您发布的来电秀包含违规内容，已从分享移除，如需帮助，请点击反馈联系!", array('show'=>array("id"=>$show_id)));
		}
		
		return array("result"=>200, "msg"=>"");
	}

	public function nice($show_id, $nice_coefficient)
	{
		//获取来电秀的状态
		$sql = "SELECT id, creator, owner,nice_coefficient FROM cs_history WHERE id = $show_id AND is_deleted=0 LIMIT 1";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		
		//判断来电秀是否存在且有效
		if(!$result)
		{
			return array("result"=>400, "msg"=>"来电秀不存在");
		}
		
		$nice_time = $nice_coefficient?time():"update_time";
		$sql = "UPDATE cs_history SET nice_coefficient = $nice_coefficient, nice_time = $nice_time WHERE id = $show_id AND is_deleted = 0";
		$this->db->query($sql);
		
		if((int)$result[0]['nice_coefficient'] == 0 && $nice_coefficient)
		{
			$callshow_model = Callshow_Model::instance();
			$callshow_model->add_sysmsg(353, 2,array(array('user_id'=>$result[0]['owner'], 'type'=>1)), "您的来电秀被选为精选秀,太厉害了!","您的来电秀被选为精选秀,太厉害了!", array('show'=>array("id"=>$show_id)));
			
			$user_model = User_Model::instance();
			$ownerinfo = $user_model->get_user_info((int)$result[0]['owner']);
			if($ownerinfo['mobile'])
			{
				$this->mq_send(json_encode(array("kind"=>"register","data"=>array("appid"=>29,"receivers"=>array($ownerinfo['mobile']),"smsbody"=>"您的来电秀被选为精选秀,太厉害了![91来电秀]"))), 'queue_sms_app', 'amq.direct');
			}
			
			if($result[0]['owner'] != $result[0]['creator'])
			{
				$callshow_model->add_sysmsg(353, 2,array(array('user_id'=>$result[0]['creator'], 'type'=>1)), "您发布的来电秀被选为精选秀,太厉害了!","您发布的来电秀被选为精选秀,太厉害了!", array('show'=>array("id"=>$show_id)));
				$creatorinfo = $user_model->get_user_info((int)$result[0]['creator']);
				if($creatorinfo['mobile'])
				{
					$this->mq_send(json_encode(array("kind"=>"register","data"=>array("appid"=>29,"receivers"=>array($creatorinfo['mobile']),"smsbody"=>"您发布的来电秀被选为精选秀,太厉害了![91来电秀]"))), 'queue_sms_app', 'amq.direct');
				}
			}
		}
		
		return array("result"=>200, "msg"=>"");
	}
}