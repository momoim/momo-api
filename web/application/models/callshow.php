<?php
class Callshow_Model extends Model {
	
	protected static $instance;
	public $error_msg = '';
	public static $instances = null;
	
	/**
	 * 单例模式
	 * @return Callshow_Model
	 */
	public static function &instance()
	{
		if (! isset(self::$instance))
		{
			// Create a new instance
			self::$instance = new Callshow_Model();
		}
		return self::$instance;
	}
	
	public function __construct() {
		parent::__construct ();
		
		$mg_instance = new Mongo(Kohana::config('uap.mongodb'));
        $this->m = $mg_instance->selectDB(MONGO_DB_CALLSHOW);
        $this->sysmsg = $this->m->selectCollection('sysmsg');
	}
	
	//创建来电秀
	public function create($uid,$source, $ring, $image,$video, $label, $contact, $refid,$forwarded, $access_ctrl)
	{
		 
		$user_model = User_Model::instance();
		$creatorinfo = $user_model->get_user_info($uid);
		if(!$creatorinfo['nickname'])
		{
			if($user_model->update_name($uid, "",$creatorinfo['realname']))
			{
				$creatorinfo['nickname'] = $creatorinfo['realname'];
			}
		}
		
		$creator = array("uid"=>$uid,"realname"=>$creatorinfo['realname'],"nickname"=>$creatorinfo['nickname'],"phone"=>$creatorinfo['mobile'],"zone_code"=>$creatorinfo['zone_code'],"avatar"=>sns::getavatar($uid));
		
		$userinfo = array();
		//如果phone为空，则为自己创建来电秀
		if(empty($contact))
		{
			$userinfo["user_".$uid] = $creator;						
		}
		else
		{
			$user_arr = array();
			foreach ($contact['phone'] as $phone)
			{
				//检查号码的合法性
				$mobile = international::check_mobile($phone);
				if($mobile)
				{
					$user_arr[] = array("name"=>$contact['name'],"nickname"=>$contact['name'],"mobile"=>$mobile['mobile']);
				}
			}
			
			//有合法的手机号
			if($user_arr)
			{
				$ret = $user_model->create_at($user_arr, $uid, $source);
				foreach ($ret as $user)
				{
					if($user['user_id'] !== 0)
					{
						$userinfo["user_".$user['user_id']] = array("uid"=>(int)$user['user_id'],"realname"=>$user['name'],"nickname"=>$user['nickname'],"phone"=>$user['mobile'],"zone_code"=>'86',"avatar"=>sns::getavatar((int)$user['user_id']));						
					}
				}
			}
		}
		
		if(!$userinfo)
		{
			return array("result"=>400, "msg"=>"手机号不合法");
		}
		
		//设置来电秀
		$res = array();
		foreach($userinfo as $k => $v)
		{
			$ret = $this->_create_show($source,$ring,$image,$video,$label,$creator,$v,$refid,$forwarded,$access_ctrl);	
			if($ret)
			{
				$res[] = $ret;
			}
		}
	
		if($res)
		{	
			$cs_personalty_model = Cs_Personalty_Model::instance();
			//成功创建来电秀,将资源加入到创建者自己的资源库中
			if($image && (int)$image['refid'] == 0)
			{
				$cs_personalty_model->add_image($uid, $image['mime'], $image['url']);
			}
			
			if($video && (int)$video['refid'] == 0)
			{
				$cs_personalty_model->add_video($uid, $video['mime'], $video['url'],(int)$video['duration'], $video['snapshot']['mime'], $video['snapshot']['url'] );
			}
			
			if((int)$ring['refid'] == 0)
			{
				$cs_personalty_model->add_ring($uid, $ring['name'], $ring['mime'], $ring['url'], (int)$ring['duration']);
			}
			
			return array("result"=>200, "msg"=>$res); 
		}
		else
		{
			return array("result"=>400, "msg"=>"该手机号不允许他人设置来电秀");
		}
	}
	
	public function modi($uid, $client_id,$show_id, $ring,$image,$video,$label,$refid,$access_ctrl,$be_cur_show, $action_in_use)
	{
		//获取来电秀的状态
		$sql = "SELECT id, ring_name, ring_mime, ring_url, ring_duration, ring_refid, image_mime, image_url, image_refid,video_mime,video_url,video_duration,video_snap_mime,video_snap_url,video_refid, label, refid, creator, owner, is_deleted, access_ctrl_range,nice_coefficient, update_time,template_id,template_similarity,forwarded_id FROM cs_history WHERE id = $show_id LIMIT 1";
		$query = $this->db->query($sql);
		$result_user = $query->result_array(FALSE);
		
		//判断来电秀是否存在且有效
		if(!$result_user || (int)$result_user[0]['is_deleted'])
		{
			return array("result"=>400, "msg"=>"来电秀不存在");
		}
		
		//获取拥有者的信息
		$sql = "SELECT private_create, cur_show_id, refresh_date FROM cs_user where owner = ".$result_user[0]['owner'];
		$query = $this->db->query($sql);
		$result_owner = $query->result_array(FALSE);
		
		if(!$result_owner)
		{
			return array("result"=>400, "msg"=>"来电秀信息错误");
		}

		$new_show_id = $show_id;
		$new_access_ctrl_range = (int)$result_user[0]['access_ctrl_range'];
		$new_self_create = false;
		$modi_ring = false;
		$modi_image = false;
		$modi_video = false;
		$modi_label = false;
		$cur_time = time();	
		
		$new_private_create = (int)$result_owner[0]['private_create'];
		//只有创建者或者拥有者可以修改该来电秀,且创建者只有当拥有者尚未认领该来电秀时可以修改该来电秀
		if($uid !== (int)$result_user[0]['owner'])
		{
			if($uid !== (int)$result_user[0]['creator'] || (int)$result_owner[0]['private_create'])
			{
				return array("result"=>405, "msg"=>"您无权修改该来电秀");
			}	
		}
		else
		{
			if(!(int)$result_owner[0]['private_create'])
			{
				$new_self_create = true;
			}
			$new_private_create = 1;
		}
		
		//获取需要修改的属性
		if($ring)
		{
			//铃声url存在且和系统库中不相等，则需要修改铃声
			if($ring['url'] && $ring['url'] !== $result_user[0]['ring_url'])
			{
				$modi_ring = true;
			}
		}
		
		if($image)
		{
			//图片url存在且和系统库中不相等，则需要修改图片
			if($image['url'] && $image['url'] !== $result_user[0]['image_url'])
			{
				$modi_image = true;
			}
		}
		
		if($video)
		{
			//视频url存在且和系统库中不相等，则需要修改视频
			if($video['url'] && $video['url'] !== $result_user[0]['video_url'])
			{
				$modi_video = true;
			}
		}
		
		$modi_arr = array();
		//设置访问控制状态
		if(isset($access_ctrl) && isset($access_ctrl['range']) && (int)$access_ctrl['range'] !== (int)$result_user[0]['access_ctrl_range'])
		{
			$modi_arr[] = "access_ctrl_range = ".$access_ctrl['range'];
			$new_access_ctrl_range = (int)$access_ctrl['range'];
			if(!$access_ctrl['range'])
			{
				$this->add_mass_show($result_user[0]['video_url'],$result_user[0]['image_url'],$show_id);
			}
			else
			{
				$this->del_mass_show($show_id);
			}
		}
		
		//判断是否需要设置为当前秀
		if((int)$be_cur_show && !($show_id == (int)$result_owner[0]['cur_show_id'] && (int)$action_in_use == 2)  )
		{
			$modi_arr[]= 'update_time='.$cur_time;
			$modi_arr[]= 'nice_time='.$cur_time;
		}
		
		//判断是否需要修改该秀
		if(($show_id !== (int)$result_owner[0]['cur_show_id']) || ((int)$action_in_use == 1) )
		{
			if($ring)
			{
				if($ring['name'])
				{
					$modi_arr[]= "ring_name=".$this->db->escape($ring['name']);
				}
				if($ring['mime'])
				{
					$modi_arr[]= "ring_mime=".$this->db->escape($ring['mime']);
				}
				if($ring['url'])
				{
					$modi_arr[]= "ring_url=".$this->db->escape($ring['url']);
				}
				if($ring['duration'])
				{
					$modi_arr[]= "ring_duration=".$ring['duration'];
				}				
				if($ring['refid'])
				{
					$modi_arr[]= "ring_refid=".$ring['refid'];
				}
				else if($modi_ring)
				{
					$modi_arr[]= "ring_refid=0";
				}
			} 

			if($image)
			{
				if($image['mime'])
				{
					$modi_arr[]= "image_mime=".$this->db->escape($image['mime']);
				}
				if($image['url'])
				{
					$modi_arr[]= "image_url=".$this->db->escape($image['url']);
				}
				if($image['refid'])
				{
					$modi_arr[]= "image_refid=".$image['refid'];
				}
				else if($modi_image)
				{
					$modi_arr[]= "image_refid=0";
				}

				$modi_arr[]= "video_mime=''";
				$modi_arr[]= "video_url=''";
				$modi_arr[]= "video_duration=0";
				$modi_arr[]= "video_snap_mime=''";
				$modi_arr[]= "video_snap_url=''";
				$modi_arr[]= "video_refid=0";
			}
			else if($video)
			{
				if($video['mime'])
				{
					$modi_arr[]= "video_mime=".$this->db->escape($video['mime']);
				}
				if($video['url'])
				{
					$modi_arr[]= "video_url=".$this->db->escape($video['url']);
				}
				if($video['duration'])
				{
					$modi_arr[]= "video_duration=".$video['duration'];
				}
				if($video['refid'])
				{
					$modi_arr[]= "video_refid=".$video['refid'];
				}
				else if($modi_video)
				{
					$modi_arr[]= "video_refid=0";
				}
				if($video['snapshot'])
				{
					if($video['snapshot']['mime'])
					{
						$modi_arr[]= "video_snap_mime=".$this->db->escape($video['snapshot']['mime']);
					}
					if($video['snapshot']['url'])
					{
						$modi_arr[]= "video_snap_url=".$this->db->escape($video['snapshot']['url']);
					}
				}
				
				$modi_arr[]= "image_mime=''";
				$modi_arr[]= "image_url=''";
				$modi_arr[]= "image_refid=0";
			}

			if($result_user[0]["template_id"] && ($modi_video || $modi_ring || $modi_image))
			{
				$video_url = $video && $video['url']?$video['url']:$result_user[0]['video_url'];
				$image_url = $image && $image['url']?$image['url']:$result_user[0]['image_url'];
				$ring_url = $ring && $ring['url']?$ring['url']:$result_user[0]['ring_url'];
				if($image)
				{
					$video_url = "";
				}
				else if($video)
				{
					$image_url = "";
				}
				$modi_arr[]= "template_similarity=".Cs_Template_Model::instance()->get_similarity($result_user[0]["template_id"],$video_url,$image_url,$ring_url);
			}
			
			
			if($modi_video||$modi_image)
			{
				$this->del_mass_show($show_id);
				if(!$new_access_ctrl_range)
				{
					$video_url = $modi_video?$video['url']:NULL;
					$image_url = $modi_image?$image['url']:NULL;
					$this->add_mass_show($video_url,$image_url,$show_id);
				}
			}
			
			if($refid)
			{
				$modi_arr[]= "refid=".$refid;
			}
			
			if(isset($label))
			{
				$modi_arr[]= "label=".$this->db->escape($label);
			}
			
			if(!(int)$be_cur_show)
			{
				$modi_arr[]= 'update_time='.$cur_time;
				$modi_arr[]= 'nice_time='.$cur_time;
			}		
		}
		
		if($modi_arr)
		{
			//修改该来电秀
			$sql = "UPDATE cs_history SET ".implode(',',$modi_arr)." WHERE id = $show_id";
			$this->db->query($sql);	
		}
		
		if($show_id == (int)$result_owner[0]['cur_show_id'] && (int)$action_in_use == 2)
		{	
			$ring_name = $ring && $ring['name']?$ring['name']:$result_user[0]['ring_name'];
			$ring_mime = $ring && $ring['mime']?$ring['mime']:$result_user[0]['ring_mime'];
			$ring_url = $ring && $ring['url']?$ring['url']:$result_user[0]['ring_url'];
			$ring_duration = $ring && $ring['duration']?$ring['duration']:(int)$result_user[0]['ring_duration'];
			$ring_refid = $ring && $ring['refid']?$ring['refid']:($modi_ring?0:(int)$result_user[0]['ring_refid']);
			$image_url = $image && $image['url']?$image['url']:$result_user[0]['image_url'];
			$image_mime = $image && $image['mime']?$image['mime']:$result_user[0]['image_mime'];
			$image_refid = $image && $image['refid']?$image['refid']:($modi_image?0:(int)$result_user[0]['image_refid']);
			$video_mime = $video && $video['mime']?$video['mime']:$result_user[0]['video_mime'];
			$video_url = $video && $video['url']?$video['url']:$result_user[0]['video_url'];
			$video_duration = $video && $video['duration']?$video['duration']:(int)$result_user[0]['video_duration'];
			$video_refid = $video && $video['refid']?$video['refid']:($modi_video?0:(int)$result_user[0]['video_refid']);
			$video_snap_mime = $video && $video['snapshot'] && $video['snapshot']['mime']?$video['snapshot']['mime']:$result_user[0]['video_snap_mime'];
			$video_snap_url = $video && $video['snapshot'] && $video['snapshot']['url']?$video['snapshot']['url']:$result_user[0]['video_snap_url'];
			
			$label = isset($label)?$label:$result_user[0]['label'];
			$trefid = isset($refid)?$refid:(int)$result_user[0]['refid'];

			$new_show_id = $this->_new_show($ring_name,$ring_mime,$ring_url,$ring_duration,$ring_refid,$image_mime,$image_url,$image_refid,$video_mime,$video_url,$video_duration,$video_snap_mime,$video_snap_url,$video_refid,$label,$trefid,$uid,(int)$result_user[0]['owner'], $client_id,$new_access_ctrl_range, $cur_time, 0, 0, 30 );
		}
			
		//更新资源引用计数信息及我的资源库信息	
		$refring_id = 0;
		$refimg_id = 0;
		$cs_personalty_model = Cs_Personalty_Model::instance();
		if($modi_ring)
		{
			if($ring['refid']) //更新资源引用计数
			{
				$refring_id = (int)$ring['refid'];
			}
			else //添加到我的资源库
			{
				if($ring['name'] && $ring['mime'] && $ring['url'] && $ring['duration'])
				{
					$cs_personalty_model->add_ring($uid, $ring['name'] , $ring['mime'], $ring['url'], (int)$ring['duration']);
				}
			}
		}
		
		if($modi_image)
		{
			if($image['refid']) //更新资源引用计数
			{
				$refimg_id = (int)$image['refid'];
			}
			else //添加到我的资源库
			{
				if($image['mime'] && $image['url'])
				{
					$cs_personalty_model->add_image($uid, $image['mime'], $image['url']);
				}
			}
		}
		
		if($modi_video)
		{
			if($video['refid']) //更新资源引用计数
			{
				//TODO:增加对视频资源的支持
			}
			else //添加到我的资源库
			{
				if($video['mime'] && $video['url'] && $video['duration'] && $video['snapshot'] && $video['snapshot']['mime'] && $video['snapshot']['url'])
				{
					$cs_personalty_model->add_video($uid, $video['mime'], $video['url'],(int)$video['duration'],$video['snapshot']['mime'], $video['snapshot']['url']);
				}
			}
		}
		
		Cs_Resource_Model::instance()->update_ref_count($refid, $refimg_id, $refring_id);
		
		//更新用户的当前来电秀信息
		$cs_user_arr = array();		
		if($new_private_create !== (int)$result_owner[0]['private_create'])
		{
			$cs_user_arr[] = "private_create = $new_private_create";
			if($new_private_create == 1)
			{
				$cs_user_arr[] = "reg_client_id = $client_id";
			}
		}
		
		if((int)$be_cur_show && $new_show_id !== (int)$result_owner[0]['cur_show_id'])
		{
			$cs_user_arr[] = "cur_show_id = $new_show_id";
		}
		
		//如果当前来电秀有更新，则修改refresh_date
		if((int)$be_cur_show || ($show_id == (int)$result_owner[0]['cur_show_id'] && (int)$action_in_use == 1) )
		{
			$cs_user_arr[] = "refresh_date = ".$cur_time;
		}
		
		if($cs_user_arr)
		{
			$sql = "UPDATE cs_user SET ".implode(',',$cs_user_arr)." WHERE owner = ".$result_user[0]['owner'];
			$this->db->query($sql);
		}
		
		//获取需返回的秀的详情
		$sql = "SELECT id, ring_name, ring_mime, ring_url, ring_duration,ring_refid, image_mime, image_url,image_refid,video_mime,video_url,video_duration,video_snap_mime,video_snap_url,video_refid, label,refid, create_time, creator,owner,gift_count,hot_score ,nice_coefficient,update_time,template_id,template_similarity,forwarded_id 
				FROM cs_history WHERE id = $new_show_id";
		$user_info = array();
		$ret = $this->_build_show_info($uid,$user_info , $sql,TRUE);
		
		if($ret)
		{
			$ret = $ret[0];
			$ret2 = $ret;
			$ret2['owner']['new_self_create'] = $new_self_create?1:0;
			$ret2['operator'] = $uid;
			$ret2['operate_show_id'] = $show_id;
			$this->mq_send(json_encode(array("kind"=>"callshow_modi","data"=>$ret2)), 'queue_callshow', 'amq.direct');
			return array("result"=>200, "msg"=>$ret);
		}
		
		return array("result"=>500, "msg"=>'内部错误');
	}
	
	public function del($uid, $show_id)
	{
		$ret = array();
		//只能删除自己创建的或者自己的秀
		$sql = "SELECT id, creator, owner FROM cs_history WHERE id = $show_id AND is_deleted = 0 LIMIT 1";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		
		if(!$result || ((int)$result[0]['owner'] !== $uid && (int)$result[0]['creator'] !== $uid))
		{
			return array("result"=>400, "msg"=>"来电秀不存在或者不属于您");
		}
		
		$sql = "SELECT owner, private_create, cur_show_id FROM cs_user WHERE owner = ".$result[0]['owner']." LIMIT 1";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		if(!$result)
		{
			return array("result"=>400, "msg"=>"内部错误");
		}
		
		if($uid !== (int)$result[0]['owner'] && (int)$result[0]['private_create'])
		{
			return array("result"=>405, "msg"=>"您无权删除该来电秀");	
		}
		
		//将该来电秀标志为已删除
		$sql = "UPDATE cs_history SET is_deleted=1 WHERE id = $show_id";
		$this->db->query($sql);
		
		//删除海选秀记录
		$this->del_mass_show($show_id);
		
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
			
			if($new_id)
			{
				//根据show id 获取详情
				$sql = "SELECT id, ring_name, ring_mime, ring_url, ring_duration,ring_refid, image_mime, image_url,image_refid,video_mime,video_url,video_duration,video_snap_mime,video_snap_url,video_refid, label,refid, create_time, creator,owner,gift_count,hot_score,nice_coefficient, update_time, is_deleted,template_id,template_similarity,forwarded_id 
						FROM cs_history WHERE id = $new_id LIMIT 1";
				
				$user_info = array();
				$tmp = $this->_build_show_info(NULL,$user_info, $sql, TRUE);
				if($tmp)
				{
					$ret = $tmp[0];
				}
			}
		}
		
		//通知后台异步更新相关数据
		$this->mq_send(json_encode(array("kind"=>"callshow_del","data"=>array("id"=>$show_id))), 'queue_callshow', 'amq.direct');

		return array("result"=>200, "msg"=>$ret);
	}
	
	//拉取最新来电秀
	public function latest($uid, $phones,$guid, $since)
	{
		$ret = array();
		$user_info = array();
		$ids = array();

		if($phones)
		{
			$this->_get_info_by_phone($phones, $user_info, $ids);
		}
		else if($guid)
		{
			$this->_get_info_by_device($guid, $user_info, $ids);
		}
		else
		{
			$this->_get_relation_info($uid, $user_info,$ids);
		}

		$ret_before = array();
		if($since > 0)
		{
			$ids_before = array();
			foreach ($user_info as $k=>$v)
			{
				if($v['relation_time'] >= $since)
				{
					$ids_before[] = $v['uid'];
				}
			}
			
			if($ids_before)
			{
				//TODO:若删除秀的逻辑改为可以删除当前秀，则此处的查询语句需要相应修改
				$sql = "SELECT b.id,b.ring_name,b.ring_mime, b.ring_url, b.ring_duration,b.ring_refid, b.image_mime, b.image_url,b.image_refid,b.video_mime,b.video_url,b.video_duration,b.video_snap_mime,b.video_snap_url,b.video_refid, b.label,b.refid, b.create_time,b.creator, b.owner,b.gift_count,b.hot_score,b.nice_coefficient,b.update_time,b.template_id,b.template_similarity,b.forwarded_id, a.reg_date 
						FROM cs_user a left join cs_history b on a.cur_show_id = b.id where a.owner in (".implode(',',$ids_before).") and a.cur_show_id > 0 and a.refresh_date <= $since ORDER BY a.refresh_date DESC";	
				$ret_before = $this->_build_show_info($uid, $user_info, $sql, TRUE, TRUE, $since);
			}
		}
		
		if($ids)
		{
			//TODO:若删除秀的逻辑改为可以删除当前秀，则此处的查询语句需要相应修改
			$sql = "SELECT b.id,b.ring_name,b.ring_mime, b.ring_url, b.ring_duration,b.ring_refid, b.image_mime, b.image_url,b.image_refid,b.video_mime,b.video_url,b.video_duration,b.video_snap_mime,b.video_snap_url,b.video_refid, b.label,b.refid, b.create_time,b.creator, b.owner,b.gift_count,b.hot_score,b.nice_coefficient,b.update_time,b.template_id,b.template_similarity,b.forwarded_id, a.reg_date 
			FROM cs_user a left join cs_history b on a.cur_show_id = b.id where a.owner in (".implode(',',$ids).") and a.cur_show_id > 0 and a.refresh_date > $since ORDER BY a.refresh_date DESC";			
			
			$ret = $this->_build_show_info($uid, $user_info, $sql, TRUE, TRUE, $since);
		}

		$ret = array_merge($ret, $ret_before);
		return $ret;
	}
	
	public function list_mass($start_timestamp,$limit)
	{
		if(!$start_timestamp)
		{
			$start_timestamp = 4000000000;
		}
		$sql = "SELECT b.id,b.ring_name,b.ring_mime, b.ring_url, b.ring_duration,b.ring_refid, b.image_mime, b.image_url,b.image_refid,b.video_mime,b.video_url,b.video_duration,b.video_snap_mime,b.video_snap_url,b.video_refid, b.label,b.refid, b.create_time,b.creator, b.owner,b.gift_count,b.hot_score,b.nice_coefficient,a.create_time as update_time,b.template_id,b.template_similarity,b.forwarded_id 
				FROM cs_mass_log a LEFT JOIN cs_history b on a.show_id = b.id WHERE a.create_time < $start_timestamp AND a.status = 1 AND b.is_deleted=0 AND b.access_ctrl_range = 0 ORDER BY a.create_time DESC LIMIT $limit";
		$user_info = array();
		return $this->_build_show_info($uid,$user_info, $sql);
	}
	
	//拉取所有公开的来电秀时间线
	public function history_global($uid,$nice, $forwarded, $start_timestamp, $end_timestamp, $limit)
	{
		$time_type = $nice?"nice_time":"update_time";
		$start = $start_timestamp>$end_timestamp?$end_timestamp:$start_timestamp;
		$end = $start_timestamp<$end_timestamp?$end_timestamp:$start_timestamp;
		$sql = "SELECT id, ring_name, ring_mime, ring_url, ring_duration,ring_refid, image_mime, image_url,image_refid,video_mime,video_url,video_duration,video_snap_mime,video_snap_url,video_refid, label,refid, create_time, creator,owner,gift_count,hot_score,nice_coefficient,$time_type as update_time,template_id,template_similarity,forwarded_id 
				FROM cs_history WHERE $time_type > $start AND $time_type < $end AND is_deleted=0 AND access_ctrl_range = 0 ";
		
		if(isset($nice))
		{
			if($nice)
			{
				$sql .= "AND nice_coefficient > 0 ";
			}
			else 
			{
				$sql .= "AND nice_coefficient = 0 ";
			}
		}
		
		if(isset($forwarded))
		{
			if($forwarded)
			{
				$sql .= "AND forwarded_id > 0 ";
			}
			else 
			{
				$sql .= "AND forwarded_id = 0 ";
			}
		}
		
		$sql .= "ORDER BY $time_type DESC LIMIT $limit";
		
		$user_info = array();
		return $this->_build_show_info($uid,$user_info, $sql);
	}
	
	//拉取创造者或归属者是自己或联系人的秀 + 精选的秀
	public function history_all($uid,$nice, $guid, $start_timestamp, $end_timestamp, $limit)
	{
		if(!$start_timestamp)
		{
			$start_timestamp = 4000000000;
		}
		$sql = "SELECT b.id,b.ring_name,b.ring_mime, b.ring_url, b.ring_duration,b.ring_refid, b.image_mime, b.image_url,b.image_refid,b.video_mime,b.video_url,b.video_duration,b.video_snap_mime,b.video_snap_url,b.video_refid, b.label,b.refid, b.create_time,b.creator, b.owner,b.gift_count,b.hot_score,b.nice_coefficient,a.create_time as update_time,b.template_id,b.template_similarity,b.forwarded_id 
				FROM cs_mass_log a LEFT JOIN cs_history b on a.show_id = b.id WHERE a.create_time < $start_timestamp AND a.status = 1 AND b.is_deleted=0 AND b.access_ctrl_range = 0 ORDER BY a.create_time DESC LIMIT $limit";
		$user_info = array();
		return $this->_build_show_info($uid,$user_info, $sql);
	}
	
	//拉取创造者或归属者是自己或联系人的秀 + 精选的秀
	public function history_all_old($uid,$nice, $guid, $start_timestamp, $end_timestamp, $limit)
	{
		$ret = array();
		$user_info = array();
		$ids = array();
		if($uid)
		{
			$this->_get_relation_info($uid, $user_info,$ids);
			$ids[] = $uid;
		}
		else if($guid)
		{
			$this->_get_info_by_device($guid, $user_info, $ids);
		}
		
		$start = $start_timestamp>$end_timestamp?$end_timestamp:$start_timestamp;
		$end = $start_timestamp<$end_timestamp?$end_timestamp:$start_timestamp;
			
		if($ids)
		{
			$sql = "SELECT id, ring_name, ring_mime, ring_url, ring_duration,ring_refid, image_mime, image_url,image_refid,video_mime,video_url,video_duration,video_snap_mime,video_snap_url,video_refid, label,refid, create_time, creator,owner,gift_count,hot_score,nice_coefficient, nice_time as update_time,template_id,template_similarity,forwarded_id  
				FROM cs_history WHERE nice_time > $start AND nice_time < $end AND is_deleted=0 AND access_ctrl_range = 0 AND (creator in (".implode(',',$ids).") OR owner in (".implode(',',$ids).") OR nice_coefficient > 0) ORDER BY nice_time DESC LIMIT $limit";
			$ret = $this->_build_show_info($uid, $user_info, $sql);
		}
		else 
		{
			$sql = "SELECT id, ring_name, ring_mime, ring_url, ring_duration,ring_refid, image_mime, image_url,image_refid,video_mime,video_url,video_duration,video_snap_mime,video_snap_url,video_refid, label,refid, create_time, creator,owner,gift_count,hot_score,nice_coefficient, nice_time as update_time,template_id,template_similarity,forwarded_id  
				FROM cs_history WHERE nice_time > $start AND nice_time < $end AND is_deleted=0 AND access_ctrl_range = 0 AND nice_coefficient > 0 ORDER BY nice_time DESC LIMIT $limit";
			$ret = $this->_build_show_info($uid, $user_info, $sql);
		}
		return $ret;
	}
	
	//拉取关系网的来电秀时间线
	public function history_relation($uid,$nice, $guid, $start_timestamp, $end_timestamp, $limit)
	{
		$ret = array();
		$user_info = array();
		$ids = array();
		if($uid)
		{
			$this->_get_relation_info($uid, $user_info,$ids);
			$ids[] = $uid;
		}
		else if($guid)
		{
			$this->_get_info_by_device($guid, $user_info, $ids);
		}
		
		if($ids)
		{
			$time_type = $nice?"nice_time":"update_time";
			$start = $start_timestamp>$end_timestamp?$end_timestamp:$start_timestamp;
			$end = $start_timestamp<$end_timestamp?$end_timestamp:$start_timestamp;
			$sql = "SELECT id, ring_name, ring_mime, ring_url, ring_duration,ring_refid, image_mime, image_url,image_refid,video_mime,video_url,video_duration,video_snap_mime,video_snap_url,video_refid, label,refid, create_time, creator,owner,gift_count,hot_score,nice_coefficient, $time_type as update_time,template_id,template_similarity,forwarded_id  
				FROM cs_history WHERE $time_type > $start AND $time_type < $end AND is_deleted=0 AND access_ctrl_range = 0 AND (creator in (".implode(',',$ids).") OR owner in (".implode(',',$ids).")) ";
			
			if(isset($nice))
			{
				if($nice)
				{
					$sql .= "AND nice_coefficient > 0 ";
				}
				else 
				{
					$sql .= "AND nice_coefficient = 0 ";
				}
			}
			
			$sql .= "ORDER BY $time_type DESC LIMIT $limit";
			
			$ret = $this->_build_show_info($uid, $user_info, $sql);
		}
		return $ret;
	}
	
	//拉取指定用户的来电秀时间线
	public function history_user($uid,$nice, $phones,$duid, $start_timestamp, $end_timestamp, $limit)
	{
		//有phones字段的话，就拉去phones字段的，否则，拉取uid的
		$ret = array();
		$user_info = array();
		$ids = array();
		$bself = false;
		
		if(!$phones)
		{
			if($duid)
			{
				$ids[] = $duid;
			}
			else
			{
				$ids[] = $uid;
				$bself = true;
			}
		}
		else
		{

			$this->_get_info_by_phone($phones, $user_info, $ids);
		}
		
		if($ids)
		{
			$time_type = $nice?"nice_time":"update_time";
			$start = $start_timestamp>$end_timestamp?$end_timestamp:$start_timestamp;
			$end = $start_timestamp<$end_timestamp?$end_timestamp:$start_timestamp;
			$sql = "SELECT id, ring_name, ring_mime, ring_url, ring_duration,ring_refid, image_mime, image_url,image_refid,video_mime,video_url,video_duration,video_snap_mime,video_snap_url,video_refid, label,refid, create_time, creator,owner,gift_count,hot_score ,nice_coefficient,$time_type as update_time,template_id,template_similarity,forwarded_id 
				FROM cs_history WHERE owner in (".implode(',',$ids).") AND $time_type > $start AND $time_type < $end AND is_deleted=0 ";
			
			if(isset($nice))
			{
				if($nice)
				{
					$sql .= "AND nice_coefficient > 0 ";
				}
				else 
				{
					$sql .= "AND nice_coefficient = 0 ";
				}
			}
			
			if(!$bself)
			{
				$sql .="AND access_ctrl_range = 0 ";
			}
			
			$sql .= "ORDER BY $time_type DESC LIMIT $limit";
		
			$ret = $this->_build_show_info($uid, $user_info, $sql);
		}
		return $ret;
	}
	
	public function hot($uid, $min_score, $max_score, $limit)
	{
		$sql = "SELECT id, ring_name, ring_mime, ring_url, ring_duration,ring_refid, image_mime, image_url,image_refid,video_mime,video_url,video_duration,video_snap_mime,video_snap_url,video_refid, label,refid, create_time, creator,owner,gift_count,hot_score,nice_coefficient, update_time,template_id,template_similarity,forwarded_id 
				FROM cs_history WHERE hot_score BETWEEN $min_score AND $max_score AND is_deleted=0 AND access_ctrl_range = 0 ORDER BY hot_score DESC LIMIT $limit";
		$user_info = array();
		return $this->_build_show_info($uid,$user_info , $sql);
	}
	
	public function surprise($uid, $phone, $include_latest, $limit)
	{
		$latest = array();
		if($include_latest)
		{
			$latest = $this->latest($uid,$phone,NULL,0);
		}
		
		$surprise = array();
		$limit = $limit - count($latest);
		if($limit > 0)
		{
			//从所有分享秀中随机获取一批来电秀
			$sql = "SELECT b.id,b.ring_name,b.ring_mime, b.ring_url, b.ring_duration,b.ring_refid, b.image_mime, b.image_url,b.image_refid,b.video_mime,b.video_url,b.video_duration,b.video_snap_mime,b.video_snap_url,b.video_refid, b.label,b.refid, b.create_time,b.creator, b.owner,b.gift_count,b.hot_score,b.nice_coefficient,b.update_time,b.template_id,b.template_similarity,b.forwarded_id 
					FROM cs_recommend a LEFT JOIN cs_history b ON a.show_id = b.id WHERE a.is_sys = 1 AND b.is_deleted=0 ORDER BY RAND() LIMIT $limit";
			$user_info = array();
			$surprise = $this->_build_show_info($uid,$user_info , $sql);	
		}
		$surprise = array_merge($latest, $surprise);
		return $surprise;
	}
	
	public function detail($code)
	{
		//根据code获取show_id
		$sql = "SELECT show_id FROM cs_share WHERE guid = ".$this->db->escape($code)." LIMIT 1";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		if(!$result)
		{
			return array("result"=>400, "msg"=>"来电秀不存在"); 
		}
		
		$show_id = (int)$result[0]['show_id'];
		//根据show id 获取详情
		$sql = "SELECT id, ring_name, ring_mime, ring_url, ring_duration,ring_refid, image_mime, image_url,image_refid,video_mime,video_url,video_duration,video_snap_mime,video_snap_url,video_refid, label,refid, create_time, creator,owner,gift_count,hot_score, nice_coefficient,update_time, is_deleted,template_id,template_similarity,forwarded_id 
				FROM cs_history WHERE id = $show_id LIMIT 1";
		
		$user_info = array();
		$ret = $this->_build_show_info(NULL,$user_info, $sql, TRUE);
		
		if($ret)
		{
			return array("result"=>200,"msg"=>$ret[0]);
		}
		else
		{
			return array("result"=>404,"msg"=>"来电秀不存在");
		}
	}
	
	//送礼物
	public function give_gift($uid, $show_id, $gift_arr)
	{
		//show id是否存在
		$sql = "SELECT id,creator, owner FROM cs_history WHERE id=$show_id AND is_deleted=0 LIMIT 1";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		if(!$result)
		{
			return array("result"=>400, "msg"=>"来电秀不存在"); 
		}
		
		$owner_id = (int)$result[0]['owner'];
		$creator_id = (int)$result[0]['creator'];
		
		//用户没有向该来电秀赠送过礼物
		$sql = "SELECT id FROM cs_gift_history WHERE show_id = $show_id AND uid = $uid LIMIT 1";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		if($result)
		{
			return array("result"=>400, "msg"=>"您已经赠送过了"); 
		}

		$event_date = time();
		$new_gift_count = 0;		
		foreach($gift_arr as $gift)
		{
			$gift_id = $gift['id'];
			$gift_num = $gift['num'];
			
			//gift id是否合法
			$sql = "SELECT id FROM cs_resource_gift WHERE id = $gift_id LIMIT 1";
			$query = $this->db->query($sql);
			$result = $query->result_array(FALSE);
			if(!$result)
			{
				continue;
			}
			
			//写到赠送历史中
			$sql = "INSERT INTO cs_gift_history (uid, show_id, gift_id, gift_num, event_date)
			VALUES ($uid,$show_id,$gift_id,$gift_num,$event_date)";
			$query = $this->db->query($sql);
			$lastid = $query->insert_id();
			if($lastid)
			{
				$new_gift_count += $gift_num;
				
				//写到新事件中--TODO:后期改为异步写入，且此时将一次送礼事件改为了多次，写了多个事件记录，若以后式样变更为允许一次送多种礼物，则事件相关接口需要改造
				$this->_add_event($uid,$owner_id,1,2,$lastid );
				if($creator_id !== $owner_id)
				{
					$this->_add_event($uid,$creator_id,1,2,$lastid );
				}
			}
		}
		
		if($new_gift_count)
		{
			//更新来电秀礼物总数及热度
			$sql = "UPDATE cs_history SET gift_count = gift_count+$new_gift_count, hot_score=hot_score+$new_gift_count 
					WHERE id=$show_id";
			$this->db->query($sql);
			return array("result"=>200, "msg"=>NULL);
		}
		else
		{
			return array("result"=>400, "msg"=>"无可送的礼物"); 
		}
	}
	
	//查询新事件计数
	public function new_events_count($uid, $guid)
	{
		$ret = array();
		$types = Kohana::config('callshow.event.type');
		
		foreach ($types as $k=>$v)
		{
			$sql = "";
			if($uid)
			{
				$sql = "SELECT count(1) as num FROM cs_event WHERE recipient = $uid and type = ".$v['id']." and readed = 0 and outdated=0 and initiator != $uid";
			}
			else if($guid)
			{
				$guid_t = $this->db->escape($guid);
				$sql = "SELECT count(1) as num FROM cs_event_guid WHERE recipient = $guid_t and type = ".$v['id']." and readed = 0 and outdated=0";
			}
			
			if($sql)
			{
				$query = $this->db->query($sql);
				$result = $query->result_array(FALSE);
			
				$ret[$k] = (int)$result[0]['num'];
			}
			else 
			{
				$ret[$k] = 0;
			}			
		}
		return $ret;
	}
	
	//查询新事件内容
	public function events($uid, $guid, $type_str, $new, $pos, $size, $readed)
	{
		$ret = array();
		
		//获取时间分类查询条件
		$type_arr = array();
		$typeid_dict = array();
		$types = Kohana::config('callshow.event.type');	
		if($type_str)
		{
			$type_str_ary = explode(',',$type_str);
			foreach ($type_str_ary as $type)
			{
				if($types[$type])
				{
					$type_arr[] = $types[$type]["id"];
					$ret[$type] = array();
					$typeid_dict["d_".$types[$type]["id"]] = $type;
				}
			}
		}
		else
		{
			foreach ($types as $k => $v)
			{
				$type_arr[] = $v['id'];
				$ret[$k] = array();
				$typeid_dict["d_".$v['id']] = $k;
			}
		}
		
		if($type_arr)
		{
			$sql = "";
			if($uid)
			{
				$sql = "SELECT id, type, initiator, recipient, event_time, detail_id FROM cs_event WHERE recipient = $uid AND ";
			}
			else 
			{
				$guid_t = $this->db->escape($guid);
				$sql = "SELECT id, type, initiator, recipient, event_time, detail_id FROM cs_event_guid WHERE recipient = $guid_t AND ";
			}
			
			if(count($type_arr) == 1)
			{
				$sql .= "type=".$type_arr[0]." ";
			}
			else 
			{
				$sql .= "type in (".implode(',',$type_arr).") ";
			}
			
			if(isset($new))
			{
				$isreaded = $new?0:1;
				$sql .= "AND readed = $isreaded ";
			}
			
			if($uid)
			{
				$sql .= "AND outdated=0 AND initiator != $uid ORDER BY event_time DESC LIMIT $pos, $size;";
			}
			else 
			{
				$sql .= "AND outdated=0 ORDER BY event_time DESC LIMIT $pos, $size;";
			}
			
			$query = $this->db->query($sql);
			$result = $query->result_array(FALSE);
			
			if($result)
			{
				//分门别类事件，并获取事件明细
				$gift_event_id_arr = array();
				$gift_event_dict = array();
				$show_event_id_arr = array();
				$show_event_dict = array();
				$sys_event_arr = array();
				$sys_event_dict = array();
				foreach ($result as $tmp)
				{
					if((int)$tmp['type'] == 2)
					{
						$gift_event_id_arr[] = (int)$tmp['detail_id'];
					}
					else if((int)$tmp['type'] == 1 || (int)$tmp['type'] == 4 || (int)$tmp['type'] == 5)
					{
						$show_event_id_arr[] = (int)$tmp['detail_id'];
					}
					else if((int)$tmp['type'] == 3)
					{
						$sys_event_arr[] = (int)$tmp['detail_id'];
					}
				}
				
				//获取礼物相关信息
				$gift_event_id_arr = array_unique($gift_event_id_arr);
				if($gift_event_id_arr)
				{
					$sql = "SELECT id,uid, show_id, gift_id, gift_num FROM cs_gift_history WHERE id in (".implode(',',$gift_event_id_arr).")";
					$gquery = $this->db->query($sql);
					$gresult = $gquery->result_array(FALSE);
					if($gresult)
					{
						foreach ($gresult as $tmp)
						{
							$gift_event_dict['g_'.$tmp['id']] = $tmp;
							$show_event_id_arr[] = (int)$tmp['show_id'];
						}
					}
				}
				
				//获取来电秀相关信息
				$user_info = array();
				$show_event_id_arr = array_unique($show_event_id_arr);
				if($show_event_id_arr)
				{
					$sql = "SELECT id, ring_name, ring_mime, ring_url, ring_duration,ring_refid, image_mime, image_url,image_refid, video_mime,video_url,video_duration,video_snap_mime,video_snap_url,video_refid,label,refid, create_time, creator,owner,gift_count,hot_score,nice_coefficient,update_time,template_id,template_similarity,forwarded_id  
							FROM cs_history WHERE id in (".implode(',',$show_event_id_arr).")";
					
					$show_ret = $this->_build_show_info($uid,$user_info, $sql);
					foreach ($show_ret as $tmp)
					{
						$show_event_dict['s_'.$tmp['id']] = $tmp;
					}
				}
				
				//获取系统消息
				$sys_event_arr = array_unique($sys_event_arr);
				$sys_event_dict = $this->get_sysmsgs($sys_event_arr);
				
				//组装响应包
				$user_model = User_Model::instance();
				$id_arr = array();
				foreach ($result as $tmp)
				{
					$id_arr[] = $tmp['id'];
					$content = array(
					                  'id'=>(int)$tmp['id'],
					                  'initiator'=>array('uid'=>(int)$tmp['initiator']),
									  'event_time'=>(int)$tmp['event_time'],
									  'title'=>'您有一条系统消息',
									  'detail'=>array()
									);
					if((int)$tmp['type'] == 1)
					{
						if($show_event_dict['s_'.$tmp['detail_id']])
						{
							$content['initiator'] = $show_event_dict['s_'.$tmp['detail_id']]['creator'];
							$content['title'] = $show_event_dict['s_'.$tmp['detail_id']]['creator']['nickname'] . "为您设置了来电秀.";	
							$content['detail']['show'] = $show_event_dict['s_'.$tmp['detail_id']];
						}
					}
					else if((int)$tmp['type'] == 2)
					{
						$str = "";
						if($show_event_dict['s_'.$gift_event_dict['g_'.$tmp['detail_id']]['show_id']])
						{
							if((int)$tmp['recipient'] !== $show_event_dict['s_'.$gift_event_dict['g_'.$tmp['detail_id']]['show_id']]['owner']['uid'] 
							 && (int)$tmp['recipient'] == $show_event_dict['s_'.$gift_event_dict['g_'.$tmp['detail_id']]['show_id']]['creator']['uid'])
							 {
							 	$str = "制作的秀";
							 }
							$content['detail']['show'] = $show_event_dict['s_'.$gift_event_dict['g_'.$tmp['detail_id']]['show_id']];
						}
						$info = $user_model->get_user_info((int)$gift_event_dict['g_'.$tmp['detail_id']]['uid']);
						$content['initiator'] = array("uid"=>(int)$info['uid'],"nickname"=>$info['nickname'],"avatar"=>sns::getavatar((int)$gift_event_dict['g_'.$tmp['detail_id']]['uid']));
						$content['title'] = $info['nickname']."给您".$str."送了".$this->_format_gift($gift_event_dict['g_'.$tmp['detail_id']]['gift_id'],$gift_event_dict['g_'.$tmp['detail_id']]['gift_num']);
						$content['detail']['gift']= array(array('id'=>(int)$gift_event_dict['g_'.$tmp['detail_id']]['gift_id'],'num'=>(int)$gift_event_dict['g_'.$tmp['detail_id']]['gift_num']));
					}
					else if((int)$tmp['type'] == 3)
					{
						$content['initiator'] = array("uid"=>353,"nickname"=>"小秘秀秀");
						if($sys_event_dict['sys_'.$tmp['detail_id']])
						{
							if($sys_event_dict['sys_'.$tmp['detail_id']]['title'])
							{
								$content['title'] = $sys_event_dict['sys_'.$tmp['detail_id']]['title'];
							}
							
							if($sys_event_dict['sys_'.$tmp['detail_id']]['detail'])
							{
								$content['detail'] = $sys_event_dict['sys_'.$tmp['detail_id']]['detail'];
							}
							
							if($sys_event_dict['sys_'.$tmp['detail_id']]['text'])
							{
								$content['detail']['text'] = $sys_event_dict['sys_'.$tmp['detail_id']]['text'];
							}
						}
					}
					else if((int)$tmp['type'] == 4)
					{
						if($show_event_dict['s_'.$tmp['detail_id']])
						{
							$content['initiator'] = $show_event_dict['s_'.$tmp['detail_id']]['owner'];
							$content['title'] = $show_event_dict['s_'.$tmp['detail_id']]['owner']['nickname'] . "加入了来电秀.";	
							$content['detail']['show'] = $show_event_dict['s_'.$tmp['detail_id']];
						}
					}
					else if((int)$tmp['type'] == 5)
					{
						if($show_event_dict['s_'.$tmp['detail_id']])
						{
							$content['initiator'] = $show_event_dict['s_'.$tmp['detail_id']]['owner'];
							$content['title'] = $show_event_dict['s_'.$tmp['detail_id']]['owner']['nickname'] . "发布了新的秀.";	
							$content['detail']['show'] = $show_event_dict['s_'.$tmp['detail_id']];
						}
					}
	
					$ret[$typeid_dict["d_".$tmp['type']]][]	= $content;
				}
				
				//将记录置为已读
				$event_time = time();
				if(!$readed)
				{
					$sql = "";
					if($uid)
					{
						$sql = "UPDATE cs_event SET readed=1,read_time=$event_time WHERE recipient = $uid";
					}
					else 
					{
						$guid_t = $this->db->escape($guid);
						$sql = "UPDATE cs_event_guid SET readed=1,read_time=$event_time WHERE recipient = $guid_t";
					}
					
					$this->db->query($sql);
				}
				else if($readed == 1)
				{
					$sql = "";
					if($uid)
					{
						$sql = "UPDATE cs_event SET readed=1,read_time=$event_time WHERE id in (".implode(',',$id_arr).")";
					}
					else 
					{
						$sql = "UPDATE cs_event_guid SET readed=1,read_time=$event_time WHERE id in (".implode(',',$id_arr).")";
					}

					$this->db->query($sql);
				}
			}
		}
		
		return $ret;
	}
	
	public function share($uid, $show_id, $sites, $sms, $timeline)
	{
		$user_model = User_Model::instance();
		//判断用户是否有权限进行分享
		$show_info = $this->_get_show_by_id($show_id);
		if(!$show_info || $show_info['is_deleted'])
		{
			return array("result"=>400,"msg"=>"来电秀不存在");
		}
		
		$user_info = $user_model->get_user_info($show_info['owner']);
		
		if($uid !== $show_info['owner'])
		{
			if($uid !== $show_info['creator'])
			{
				return array("result"=>400,"msg"=>"您无权分享该来电秀");
			}
			else 
			{
				if($user_info['private_create'])
				{
					return array("result"=>400,"msg"=>"您无权分享该来电秀");
				}
			}
		}
		
		$ret = array('id'=>$show_id);
		
		
		$creatorinfo = $user_model->get_user_info($show_info['creator']);
		//网站
		if($sites && is_array($sites))
		{
			$ret['sites'] = array();
			$bindModel = Bind_Model::instance();
			foreach ($sites as $site)
			{
				$method_name = "{$site}_share";
				if(method_exists($bindModel, $method_name))
				{
					$url = "http://show.91.com/show/share/".$this->_generate_guid($uid, $show_id, $site);
					$share_info = array(
						'title'=>$creatorinfo['nickname'].'创作的来电秀',
	                	'text'=>'我刚用#91来电秀#制作了一个秀，照片配上自己DIY的铃声，好友来电时都能看到，点开播放来电秀 '.$url,
						'summary'=>$show_info['label'],
	               		'url'=>$url,
					);
					if($show_info['image_url'])
					{
						$share_info['images'] = array($show_info['image_url']);
					}
					
					if($show_info['video_url'] && $show_info['video_snap_url'])
					{
					    $share_info['text'] = '我刚用#91来电秀#制作了一个秀，视频配上自己DIY的铃声，好友来电时都能看到，点开播放视频秀 '.$url;
						$share_info['video'] = array("url"=>$show_info['video_url'], "snap_url"=> $show_info['video_snap_url']);
					}

	                $ret['sites'][$site] = $bindModel->{$method_name}($share_info, 29);
	            }
			}
			
			if(!$ret['sites'])
			{
				unset($ret['sites']);
			}
		}
		
		//短信
		if($sms)
		{
			$ret['sms'] = array('code'=>200,"text"=>"给你设了个来电秀，以后你给我打电话，有专属的来电画面与来电铃声啦，点开看看 ".MO_SMS_JUMP.Url_Model::instance()->create('callshow',(int)$creatorinfo['uid'],$creatorinfo['nickname'],(int)$user_info['uid'],$user_info['nickname'],$user_info['mobile'],$user_info['zone_code'],'',$this->_generate_guid($uid, $show_id, 'sms'),0,29)." [91来电秀]");
		}
		
		//时间线
		if($timeline)
		{
			if($show_info['access_ctrl_range'])
			{
				$sql = "UPDATE cs_history SET access_ctrl_range = 0 WHERE id = $show_id";
				$this->db->query($sql);
				
				$this->add_mass_show($show_info['video_url'],$show_info['image_url'],$show_id);
			}
			$ret['timeline'] = array('code'=>200);
		}
		
		return array("result"=>200, "msg"=>$ret);
	}
	
	public function recommend($uid, $show_id)
	{
		$xiaomo_id = Kohana::config('uap.xiaomo');
		//判断show_id是否存在且有效
		$sql = "SELECT id,owner from cs_history WHERE id = $show_id AND is_deleted = 0 LIMIT 1";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		if(!$result || ($uid !== $xiaomo_id && $uid !== (int)$result[0]['owner']))
		{
			return false;
		}
		
		$is_sys = 0;
		if($uid == $xiaomo_id)
		{
			$is_sys = 1;
		}
		$curdate = time();
		
		$sql = "REPLACE INTO cs_recommend (show_id, is_sys, share_date) VALUES ($show_id, $is_sys, $curdate) ";
		$this->db->query($sql);
		return true;
	}

	public function cancel_recommend($uid, $show_id)
	{
		$xiaomo_id = Kohana::config('uap.xiaomo');
		//判断show_id是否存在
		$sql = "SELECT id,owner from cs_history WHERE id = $show_id LIMIT 1";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		if($result && $uid !== $xiaomo_id && $uid !== (int)$result[0]['owner'])
		{
			return false;
		}
		
		$sql = "DELETE FROM cs_recommend WHERE show_id = $show_id";
		$this->db->query($sql);
		return true;
	}
	
	public function add_sysmsg($creator, $receiver_type, $receivers, $title, $text, $detail)
	{
		$mongo_id = api::uuid();
		$create_date = time();
		
		$sysmsg = array(
							"_id"=> $mongo_id,
							"creator"=>$creator,
							"receiver_type"=>$receiver_type,
							"receiver"=>$receivers,
							"title"=>$title,
							"text"=>$text,
							"detail"=>$detail,
							"create_date"=>$create_date
						);
						
		$this->sysmsg->insert($sysmsg);	
		
		$sql = "INSERT INTO cs_sysmsg (creator, receiver_type, mongo_id, create_date) VALUES ($creator, $receiver_type, '$mongo_id', $create_date)";
		$query = $this->db->query($sql);
		$lastid = $query->insert_id();
		
		foreach ($receivers as $receiver)
		{
			$this->_add_event(353, $receiver['user_id'], $receiver['type'], 3, $lastid);
		}
	}
	
	public function get_sysmsgs($id_arr)
	{
		$ret_dict = array();
		if($id_arr)
		{
			$sql = "SELECT id, mongo_id FROM cs_sysmsg WHERE id in (".implode(',',$id_arr).")";
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
			
			if($mongoid_arr)
			{
				$msg_dict = array();
				$content = $this->sysmsg->find(array('_id'=>array('$in'=>$mongoid_arr)));
				foreach ($content as $tmp)
				{
					$msg_dict[$tmp['_id']] = array(
									"id"=>$tmp['_id'],
									"title"=>$tmp['title'],
									"text"=>$tmp['text'],
									"detail"=>$tmp['detail']
									);
				}
				
				foreach ($result as $ret)
				{
					if($msg_dict[$ret['mongo_id']])
					{
						$ret_dict["sys_".$ret['id']] = $msg_dict[$ret['mongo_id']];
					}
				}
			}
		}
		
		return $ret_dict;
	}
	
	public function inner_info_by_ids($ids)
	{
		$ret = array();
		if($ids)
		{
			$sql = "SELECT id, ring_name, ring_mime, ring_url, ring_duration,ring_refid, image_mime, image_url,image_refid,video_mime,video_url,video_duration,video_snap_mime,video_snap_url,video_refid, label,refid, create_time, creator,owner,gift_count,hot_score,nice_coefficient, update_time,template_id,template_similarity,forwarded_id  
				FROM cs_history WHERE id in (".implode(',',$ids).") AND is_deleted=0";
			$query = $this->db->query($sql);
			$ret = $query->result_array(FALSE);
		}
		
		return $ret;
	}
	
	public function update_template_info($show_id, $template_id, $similarity)
	{
		$sql = "UPDATE cs_history SET template_id = $template_id, template_similarity = $similarity WHERE id = $show_id";
		$this->db->query($sql);
	}

	private function _add_event($initator, $recipient,$id_type, $type, $detail_id)
	{
		$event_time = time();
		$sql = "";
		if($id_type == 1) //uid
		{
			$sql = "INSERT INTO cs_event (initiator,recipient, type, detail_id, event_time, readed, read_time, outdated)
				VALUES ($initator, $recipient, $type, $detail_id, $event_time, 0, 0, 0)";
		}
		else
		{
			$recipient_t = $this->db->escape($recipient);
			$sql = "INSERT INTO cs_event_guid (initiator,recipient, type, detail_id, event_time, readed, read_time, outdated)
				VALUES ($initator, $recipient_t, $type, $detail_id, $event_time, 0, 0, 0)";
		}
		
		$this->db->query($sql);
	}

	private function _create_show($source,$ring,$image,$video,$plabel,$pcreator,$powner,$refid,$forwarded,$access_ctrl)
	{
		$ring_name = $ring['name']?$ring['name']:"";
		$ring_mime = $ring['mime']?$ring['mime']:"";
		$ring_url = $ring['url']?$ring['url']:"";
		$ring_duration = $ring['duration']?(int)$ring['duration']:0;
		$ring_refid = $ring['refid']?(int)$ring['refid']:0;
		
		$image_mime = "";
		$image_url = "";
		$image_refid = 0;
		if($image)
		{
			$image_mime = $image['mime']?$image['mime']:"";
			$image_url = $image['url']?$image['url']:"";
			$image_refid = $image['refid']?(int)$image['refid']:0;			
		}
		
		$video_mime = "";
		$video_url = "";
		$video_duration = 0;
		$video_snap_mime = "";
		$video_snap_url = "";
		$video_refid = 0;
		if($video)
		{
			$video_mime = $video['mime']?$video['mime']:"";
			$video_url = $video['url']?$video['url']:"";
			$video_duration = $video['duration']?(int)$video['duration']:0;
			$video_refid = $video['refid']?(int)$video['refid']:0;
			if($video['snapshot'])
			{
				$video_snap_mime = $video['snapshot']['mime']?$video['snapshot']['mime']:"";
				$video_snap_url = $video['snapshot']['url']?$video['snapshot']['url']:"";
			}
		}
		
		$label = $plabel?$plabel:"";
		$refid = $refid?$refid:0;
		$forwarded_id = ($forwarded && $forwarded['id'])?$forwarded['id']:0;
		$create_time = time();
		$creator = $pcreator['uid']?$pcreator['uid']:0;
		$owner = $powner['uid']?$powner['uid']:0;
		$gift_count = 0;
		$nice_coefficient = 0;
		$hot_score = 30;//默认给30的热度
		$access_ctrl_range = $access_ctrl && $access_ctrl['range']?$access_ctrl['range']:0;
		
		//判断用户能否被创建来电秀
		$sql = "SELECT owner,private_create FROM cs_user where owner = $owner limit 1";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		
		$isnew = false; //是否新用户
		$private_create = 0; 
		if($result)
		{ 
			if((int)$result[0]['private_create'] && $creator !== $owner)
			{
				return NULL;
			}
			$private_create = ($creator == $owner)?1:(int)$result[0]['private_create'];			
		}
		else
		{
			$private_create = ($creator == $owner)?1:0;	
			$isnew = true;
		}
		
		$new_self_create = false;
		if($creator == $owner && !($result && $result[0]['private_create']))
		{
			$new_self_create = true;
		}
		
		Cs_Resource_Model::instance()->update_ref_count($refid,$image_refid,$ring_refid);		
		$tmplate_info = Cs_Template_Model::instance()->update_ref_count($video_url,$image_url,$ring_url);
		
		$lastid = $this->_new_show($ring_name,$ring_mime,$ring_url,$ring_duration,$ring_refid,$image_mime,$image_url,$image_refid,
								$video_mime,$video_url,$video_duration, $video_snap_mime,$video_snap_url,$video_refid,$label,$refid,
								$creator,$owner, $source,$access_ctrl_range, $create_time, $gift_count, $nice_coefficient, $hot_score,0,0,$forwarded_id );
		
		//更新用户最新的来电秀信息		
		if(!$isnew)
		{
			$sql = "UPDATE cs_user SET cur_show_id = $lastid, refresh_date = $create_time, private_create = $private_create WHERE owner = $owner";
			$this->db->query($sql);
		}
		else
		{
			$sql = "INSERT INTO cs_user (owner,private_create,cur_show_id, refresh_date, reg_date,reg_client_id) 
			VALUES ($owner,$private_create, $lastid, $create_time, $create_time,$source)";
			$this->db->query($sql);
		}

		$ret = array(
						'id'=>$lastid,
						'ring'=>array('name'=>$ring_name,'mime'=>$ring_mime,'url'=>$ring_url,'duration'=>$ring_duration,'refid'=>$ring_refid),
						'label'=>$label,
						'refid'=>$refid,
						'nice_coefficient'=>$nice_coefficient,
						'create_time'=>$create_time,
						'update_time'=>$create_time,
						'creator'=>array("uid"=>$pcreator['uid'],"nickname"=>$pcreator['nickname'],"avatar"=>$pcreator['avatar']),
						'owner'=>array("uid"=>$powner['uid'],"nickname"=>$powner['nickname'],"phone"=>$powner['phone'],"avatar"=>$powner['avatar']),
						'gift'=>array("count"=>$gift_count),
						'hot_score'=>$hot_score,
						'template'=>array('id'=>0,"similarity"=>0),
						'forwarded'=>array('id'=>(int)$forwarded_id)
					);
		if($video)
		{
			$ret['video'] = array('mime'=>$video_mime,'url'=>$video_url,'duration'=>$video_duration,'refid'=>$video_refid,'snapshot'=>array('mime'=>$video_snap_mime,'url'=>$video_snap_url));
		}
		
		if($image)
		{
			$ret['image'] = array('mime'=>$image_mime,'url'=>$image_url,'refid'=>$image_refid);
		}
		else
		{
			$ret['image'] = array('mime'=>$video_snap_mime,'url'=>$video_snap_url,'refid'=>0);
		}
		
		//发送mq消息
		$ret2 = $ret;
		$ret2['creator']= $pcreator;
		$ret2['owner']= $powner;
		$ret2['owner']['new_user'] = $isnew?1:0;
		$ret2['owner']['new_self_create'] = $new_self_create?1:0;
		$this->mq_send(json_encode(array("kind"=>"callshow","data"=>$ret2)), 'queue_callshow', 'amq.direct');
		return $ret;
	}
	
	private function _new_show($ring_name,$ring_mime,$ring_url,$ring_duration,$ring_refid,$image_mime,$image_url,$image_refid,$video_mime,$video_url,$video_duration,$video_snap_mime,$video_snap_url,$video_refid,$label,$refid,$creator,$owner, $source,$access_ctrl_range, $create_time = NULL, $gift_count = 0,$nice_coefficient = 0, $hot_score = 30,$template_id = 0,$template_similarity = 0,$forwarded_id = 0 )
	{
		$ring_name_t = $this->db->escape($ring_name);
		$ring_mime_t = $this->db->escape($ring_mime);
		$ring_url_t = $this->db->escape($ring_url);
		$image_mime_t = $this->db->escape($image_mime);
		$image_url_t = $this->db->escape($image_url);
		$video_mime_t = $this->db->escape($video_mime);
		$video_url_t = $this->db->escape($video_url);
		$video_snap_mime_t = $this->db->escape($video_snap_mime);
		$video_snap_url_t = $this->db->escape($video_snap_url);
		$label_t = $this->db->escape($label);
		//添加到来电秀历史中，并获取id
		$sql = "INSERT INTO cs_history (ring_name,ring_mime, ring_url, ring_duration,ring_refid, image_mime, image_url, image_refid,video_mime,video_url,video_duration, video_snap_mime,video_snap_url,video_refid, label, refid,create_time,creator, owner,gift_count,nice_coefficient,hot_score,client_id, is_deleted, access_ctrl_range,update_time,nice_time,template_id, template_similarity, forwarded_id) 
		VALUES ($ring_name_t,$ring_mime_t,$ring_url_t,$ring_duration,$ring_refid,$image_mime_t,$image_url_t,$image_refid,$video_mime_t,$video_url_t,$video_duration,$video_snap_mime_t, $video_snap_url_t, $video_refid, $label_t,$refid,$create_time,$creator,$owner,$gift_count, $nice_coefficient, $hot_score, $source,0,$access_ctrl_range,$create_time,$create_time,$template_id,$template_similarity,$forwarded_id)";
		$query = $this->db->query($sql);
		$lastid = $query->insert_id();
		
		//加入海选秀
		if(!$access_ctrl_range)
		{
			$this->add_mass_show($video_url,$image_url,$lastid);
		}
		
		return $lastid;
	}
	
	public function add_mass_show($video_url, $image_url, $show_id, $curtime = 0)
	{
		$fingerprint = $video_url?md5($video_url):($image_url?md5($image_url):"");
		if($fingerprint)
		{
			$sql = "SELECT id FROM cs_mass_log WHERE fingerprint = '$fingerprint' LIMIT 1";
			$query = $this->db->query($sql);
			$result = $query->result_array(FALSE);
			if(!$result)
			{
				if(!$curtime)
				{
					$curtime = time();
				}
				
				$sql = "INSERT INTO cs_mass_log (fingerprint,show_id,status,create_time) 
				VALUES ('$fingerprint',$show_id,1,$curtime)";
				$query = $this->db->query($sql);
			}
		}
	}
	
	public function del_mass_show($show_id)
	{
		$sql = "UPDATE cs_mass_log SET status = 0 WHERE show_id = $show_id";
		$query = $this->db->query($sql);
	}
	
	public function rebuild_mass_show()
	{
		for($i = 2440; $i< 3000; $i++)
		{
			$start = $i*100 + 1;
			$end = $i*100 + 100;
			$sql = "SELECT id, video_url,image_url,update_time FROM cs_history WHERE id between $start and $end and is_deleted=0 and access_ctrl_range=0 order by id asc";
			$query = $this->db->query($sql);
			$result = $query->result_array(FALSE);
			if($result)
			{
				foreach ($result as $show)
				{
					$this->add_mass_show($show["video_url"],$show['image_url'],(int)$show['id'],(int)$show['udpate_time']);
				}
			}
		}
	}
	
	//获取用户的关系网资料及uid列表
	private function _get_relation_info($uid, &$user_info, &$idarray )
	{		
		$info_list = Graph_Model::instance()->get_contact_by_uid($uid,array(1,2));
		$user_model = User_Model::instance();
		foreach ($info_list as $tmp)
		{
			if($tmp['mobile'])
			{
				$uinfo = array();
				if($tmp['uid'])
				{
					$uinfo = $user_model->get_user_info($tmp['uid']);
				}
				else 
				{
					$mobile	= international::check_mobile($tmp['mobile'], 86, FALSE);
					$uinfo = $user_model->get_user_info_by_mobile($mobile['mobile'], $mobile['country_code']);	
				}
					
				if($uinfo AND !empty($uinfo['uid'])) 
				{
					$idarray[] = $uinfo['uid'];
					$user_info['user_'.$uinfo['uid']] = array('uid'=>(int)$uinfo['uid'],"nickname"=>$uinfo['nickname'],"realname"=>$uinfo['realname'],"phone"=>$tmp['mobile'],"contact_name"=>$tmp['name'],"relation_time"=>$tmp['time']);
				}
			}
		}
		$idarray = array_unique($idarray);
	}
	
	//根据guid获取用户资料列表
	private function _get_info_by_device($guid, &$user_info, &$idarray )
	{
		$info_list = Graph_Model::instance()->get_contact_by_device($guid,array(1,2));
		$user_model = User_Model::instance();
		foreach ($info_list as $tmp)
		{
			if($tmp['mobile'])
			{
				$uinfo = array();
				if($tmp['uid'])
				{
					$uinfo = $user_model->get_user_info($tmp['uid']);
				}
				else 
				{
					$mobile	= international::check_mobile($tmp['mobile'], 86, FALSE);
					$uinfo = $user_model->get_user_info_by_mobile($mobile['mobile'], $mobile['country_code']);	
				}
					
				if($uinfo AND !empty($uinfo['uid'])) 
				{
					$idarray[] = $uinfo['uid'];
					$user_info['user_'.$uinfo['uid']] = array('uid'=>(int)$uinfo['uid'],"nickname"=>$uinfo['nickname'],"realname"=>$uinfo['realname'],"phone"=>$tmp['mobile'],"contact_name"=>$tmp['name'],"relation_time"=>$tmp['time']);
				}
			}
		}
		$idarray = array_unique($idarray);		
	}
	
	//根据手机号获取用户资料列表
	private function _get_info_by_phone($phones, &$user_info, &$idarray )
	{
		$phone_ary = explode(',',$phones);		
		$user_model = User_Model::instance();
		foreach ($phone_ary as $phone)
		{	
			$mobile	= international::check_mobile($phone, 86, FALSE);

			$uinfo = $user_model->get_user_info_by_mobile($mobile['mobile'], $mobile['country_code']);
			if($uinfo AND !empty($uinfo['uid'])) 
			{
				$idarray[] = $uinfo['uid'];
				$user_info['user_'.$uinfo['uid']] = array('uid'=>(int)$uinfo['uid'],"nickname"=>$uinfo['nickname'],"realname"=>$uinfo['realname'],"phone"=>$phone);
			}
		}
		$idarray = array_unique($idarray);			
	}
	
	//构造来电秀信息
	private function _build_show_info($uid,&$user_info, $sql,$display_phone = FALSE, $new_user_check = FALSE,$since = 0)
	{
		$ret = array();	
		$ret_newuser = array();	
		$user_model = User_Model::instance();
		
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);		
		foreach($result as $show)
		{
			if(!$user_info['user_'.$show['creator']])
			{
				$info = $user_model->get_user_info($show['creator']);
				$user_info['user_'.$show['creator']] = array("uid"=>(int)$info['uid'],"nickname"=>$info['nickname'],"realname"=>$info['realname'],"phone"=>$info['mobile']);
			}
			
			if(!$user_info['user_'.$show['owner']])
			{
				$info = $user_model->get_user_info($show['owner']);
				$user_info['user_'.$show['owner']] = array("uid"=>(int)$info['uid'],"nickname"=>$info['nickname'],"realname"=>$info['realname'],"phone"=>$info['mobile']);
			}

			$tmp = array(
							"id"=>(int)$show['id'],
							"ring"=>array("name"=>$show['ring_name'],"mime"=>$show['ring_mime'],"url"=>$show['ring_url'],"duration"=>(int)$show['ring_duration'],"refid"=>(int)$show['ring_refid']),
							"label"=>$show['label'],
							"refid"=>(int)$show['refid'],
							"nice_coefficient"=>(int)$show['nice_coefficient'],
							"create_time"=>(int)$show['create_time'],
							"update_time"=>(int)$show['update_time'],
							"creator"=>array("uid"=>(int)$show['creator'],"nickname"=>$user_info['user_'.$show['creator']]["nickname"],"avatar"=>sns::getavatar((int)$show['creator'])),
							"owner"=>array("uid"=>(int)$show['owner'],"nickname"=>$user_info['user_'.$show['owner']]['nickname'],"avatar"=>sns::getavatar((int)$show['owner'])),
							"gift"=>array("count"=>(int)$show['gift_count']),
							"hot_score"=>(int)$show['hot_score'],
							"template"=>array('id'=>(int)$show['template_id'],"similarity"=>(int)$show['template_similarity']),
							"forwarded"=>array('id'=>(int)$show['forwarded_id'])
						);
			
			if( $show['video_url'] && $show['video_snap_url'])
			{
				$tmp['video'] =  array('mime'=>$show['video_mime'],'url'=>$show['video_url'],'duration'=>(int)$show['video_duration'],'refid'=>(int)$show['video_refid'],'snapshot'=>array('mime'=>$show['video_snap_mime'],'url'=>$show['video_snap_url']));
			}
			
			if( $show['image_url'])
			{
				$tmp['image'] = array("mime"=>$show['image_mime'],"url"=>$show['image_url'],"refid"=>(int)$show['image_refid']);
			}
			else
			{
				$tmp['image'] = array("mime"=>$show['video_snap_mime'],"url"=>$show['video_snap_url'],"refid"=>0);
			}

			if($user_info['user_'.$show['owner']]['contact_name'])
			{
				$tmp['owner']["contact_name"] = $user_info['user_'.$show['owner']]['contact_name'];
			}
			
			if($display_phone)
			{
				$tmp['creator']["phone"] = $user_info['user_'.$show['creator']]['phone'];
				$tmp['owner']["phone"] = $user_info['user_'.$show['owner']]['phone'];
			}
			
			//查询当前用户给该来电秀送礼物的情况,TODO：当前查询所有历史再total，后期需改为在赠送礼物时实时统计
			if($uid)
			{
				$sql = "SELECT SUM(gift_num) AS total_num FROM cs_gift_history WHERE show_id = ".$show['id']." AND uid = $uid";
				$tquery = $this->db->query($sql);
				$tresult = $tquery->result_array(FALSE);
				if($tresult)
				{
					$tmp['gift']['mine'] = array("count"=>(int)$tresult[0]['total_num']);
				}
			}
			if($new_user_check)
			{
				$tmp['owner']["new_user"]=(int)$show['reg_date']>$since?1:0;
				if($tmp['owner']["new_user"])
				{
					$ret_newuser[] = $tmp;
				}
				else 
				{
					$ret[] = $tmp;
				}
			}
			else
			{
				$ret[] = $tmp;
			}
		}
		
		$ret = array_merge($ret_newuser, $ret);
		return $ret;
	}
	
	private function _format_gift($gift_id, $gift_num)
	{
		if($gift_id == 1)
		{
			return "".$gift_num."朵小红花";
		}
		else 
		{
			return "".$gift_num."份礼物";
		}
	}
	
	private function _get_show_by_id($show_id, $char = FALSE)
	{
		$sql = "SELECT id, ring_name, ring_mime, ring_url, ring_duration, ring_refid, image_mime, image_url, image_refid,video_mime,video_url,video_duration,video_snap_mime,video_snap_url,video_refid, label, refid, create_time, creator, owner, gift_count, nice_coefficient, hot_score, client_id, is_deleted, access_ctrl_range, update_time 
				FROM cs_history WHERE id = $show_id";
		
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		
		if($result)
		{
			$result = $result[0];
			if(!$char)
			{
				$result['id'] = (int)$result['id'];
				$result['ring_duration'] = (int)$result['ring_duration'];
				$result['ring_refid'] = (int)$result['ring_refid'];
				$result['image_refid'] = (int)$result['image_refid'];
				$result['video_duration'] = (int)$result['video_duration'];
				$result['video_refid'] = (int)$result['video_refid'];
				$result['refid'] = (int)$result['refid'];
				$result['create_time'] = (int)$result['create_time'];
				$result['creator'] = (int)$result['creator'];
				$result['owner'] = (int)$result['owner'];
				$result['gift_count'] = (int)$result['gift_count'];
				$result['nice_coefficient'] = (int)$result['nice_coefficient'];
				$result['hot_score'] = (int)$result['hot_score'];
				$result['client_id'] = (int)$result['client_id'];
				$result['is_deleted'] = (int)$result['is_deleted'];
				$result['access_ctrl_range'] = (int)$result['access_ctrl_range'];
				$result['update_time'] = (int)$result['update_time'];
			}
			
		}
		return $result;
	}

	private function _get_user_info($uid, $char = FALSE)
	{
		$sql = "SELECT owner, private_create, cur_show_id, refresh_date, reg_date, reg_client_id 
				FROM cs_user WHERE owner = $uid";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		
		if($result)
		{
			$result = $result[0];
			if(!$char)
			{
				$result['owner'] = (int)$result['owner'];
				$result['private_create'] = (int)$result['private_create'];
				$result['cur_show_id'] = (int)$result['cur_show_id'];
				$result['refresh_date'] = (int)$result['refresh_date'];
				$result['reg_date'] = (int)$result['reg_date'];
				$result['reg_client_id'] = (int)$result['reg_client_id'];
			}
		}
		return $result;
	}
	
	private function _generate_guid($uid, $show_id, $type)
	{
		$cur_time = time();
		$guid = api::uuid();
		
		$sql = "INSERT INTO cs_share (show_id, type, share_uid, guid, create_date) 
				VALUES ($show_id, '$type', $uid, '$guid', $cur_time)";
		
		$this->db->query($sql);
		
		return $guid;
	}
}