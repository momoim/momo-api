<?php
class Cs_Device_Model extends Model {
	
	public $error_msg = '';
	public static $instances = null;
	
	public function __construct() {
		parent::__construct ();
	}

	public function bind($uid, $client_id, $phone_model, $os,$mac, $imei, $imsi, $guid, $phone, $sign)
	{
		$local_sign = md5($client_id."|".$phone_model."|".$os."|".$mac."|".$imei."|".$imsi."|".$guid."|".$phone."|9c57fea62d1ef356260aab9baf93bd7d");

		if($local_sign !== $sign)
		{
//			return array("result"=>400,"msg"=>"sign check fail");
		}
		
		$ret = array();
		$user_model = User_Model::instance();	
		$im_model = Im_Model::instance();
		
		//若未登录，则尝试使用imsi或guid登录
		if(!$uid) 
		{
			$skip = false;
			//若imsi或者 guid和mobile同时存在，则尝试绑定，当前仅限定在iphone客户端下
			if($client_id == 2 && $phone && ($imsi || $guid))
			{
				$mobile = international::check_mobile($phone);
				if($mobile)
				{

					if($imsi)
					{
						if(!$im_model->get_imsi_link($imsi) && !$im_model->get_imsi_link_by_mobile($mobile['mobile'],$mobile['country_code']))
						{
							$im_model->gen_imsi_link($imsi, $mobile['mobile'],$mobile['country_code'], 0);
						}
					}
					else if($guid)
					{
						if(!$im_model->get_guid_link(29, $guid) && !$im_model->get_guid_link_by_mobile(29,$mobile['mobile'],$mobile['country_code'] ))
						{
							$im_model->gen_guid_link(29,$guid, $mobile['mobile'],$mobile['country_code']);
						}
					}
					
					if($mobile['mobile'] == "15606912630" ||$mobile['mobile'] == "15060013031" ||$mobile['mobile'] == "13960850039" )
					{
						$skip = true;
					}
				}
						
			}
			
			
			$device_id = $imei?$imei:$imsi?$imsi:$mac?$mac:api::uuid();
			if(!$skip)
			{
				$result = $user_model->login_by_imsi($imsi, 29, $client_id, $device_id, $phone_model, $os);
				if($result)
				{
					$ret = $result;
				}
				else
				{
					$result = $user_model->login_by_guid($guid, 29, $client_id, $device_id, $phone_model, $os);
					if($result)
					{
						$ret = $result;
					}
				}				
			}
		}

		else 
		{
			if($imsi)
			{
				$uinfo = $user_model->get_user_info($uid);
				if(!$uinfo['country_code'])
				{
					$uinfo['country_code'] = "86";
				}
				$imsiinfo = $im_model->get_imsi_link($imsi);
				$imsimobiinfo = $im_model->get_imsi_link_by_mobile($uinfo['mobile'],$uinfo['country_code']);
				
				if($imsiinfo && !(int)$imsiinfo['type'])
				{
					$im_model->del_imsi_link($imsi);
				}
				
				if($imsimobiinfo && !(int)$imsimobiinfo['type'] && $imsimobiinfo['imsi'])
				{
					$im_model->del_imsi_link($imsimobiinfo['imsi']);
				}
				
				if(!$imsiinfo || !(int)$imsiinfo['type'])
				{
					$im_model->gen_imsi_link($imsi, $uinfo['mobile'],$uinfo['country_code'], 0);
				}
			}
		}
		
		//获取guid
		$tuid = $uid?$uid:($ret['uid']?$ret['uid']:0);
		$ret['guid'] = $this->get_guid($tuid, $client_id, $phone_model, $os,$mac, $imei, $imsi, $guid);
		return array("result"=>200,"msg"=>$ret);
	}
	
	//根据硬件信息获取
	private function get_guid($uid, $client_id, $phone_model, $os,$mac, $imei, $imsi, $guid_alternative)
	{	
		$iden_code = "c_".$client_id."_m_".($mac?md5($mac):"")."_e_".($imei?md5($imei):"")."_s_".($imsi?md5(imsi):"");
		$type = 0;
		//必须存在imsi，而且 mac 和imei也至少有一个，才能保证设备唯一
		if(($imsi && ($mac || $imei))||($client_id == 2 && ($mac || $imei || $imsi)))
		{
			$sql = "SELECT id, guid, last_uid FROM cs_device WHERE identifier = '$iden_code' LIMIT 1";
			$query = $this->db->query($sql);
			$result = $query->result_array(FALSE);

			if($result)
			{
				if($uid && $uid !== (int)$result[0]['last_uid'])
				{
					$sql = "UPDATE cs_device SET last_uid = $uid WHERE id = " .$result[0]['id'];
					$this->db->query($sql);
				}
				return $result[0]['guid'];
			}
			
			//先做严格绑定，以后再考虑同设备智能识别的问题
			if($mac && $imei){
				$type = 3;
			}
			else if($mac && !$imei){
				$type = 2;
			}
			else{
				$type = 1;
			}
		}
		else
		{
			$iden_code = "rand_".api::uuid();
		}
		
		$guid = md5($iden_code);
		
		if($guid_alternative)
		{
			$sql = "SELECT distinct(type) FROM cs_device WHERE guid = '$guid_alternative'";
			$query = $this->db->query($sql);
			$result = $query->result_array(FALSE);
			if(!$result)
			{
				$guid = $guid_alternative;
			}
		}
		
		if($guid !== $guid_alternative)
		{
			$sql = "SELECT identifier FROM cs_device WHERE guid = '$guid'";
			$query = $this->db->query($sql);
			$result = $query->result_array(FALSE);
			if($result)
			{
				$guid = api::uuid();
			}
		}
		
		$phone_model_t = $this->db->escape($phone_model);
		$os_t = $this->db->escape($os);
		$mac_t = $this->db->escape($mac);
		$imei_t = $this->db->escape($imei);
		$imsi_t = $this->db->escape($imsi);
		$cur_time = time();
		
		$sql = "INSERT INTO cs_device (identifier, type, guid, mac, imei, imsi, client_id, phone_model, os, bind_date, create_uid, last_uid)
				VALUES ('$iden_code', $type, '$guid', $mac_t, $imei_t, $imsi_t, $client_id,$phone_model_t,$os_t, $cur_time, $uid, 0)";
		$this->db->query($sql);
		return $guid;
	}

}