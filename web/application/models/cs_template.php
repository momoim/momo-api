<?php
class Cs_Template_Model extends Model {
	
	public $error_msg = '';
	public static $instances = null;	
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
			self::$instance = new Cs_Template_Model();
		}
		return self::$instance;
	}
	
	public function __construct() {
		parent::__construct ();
	}

	public function tag()
	{
		$sql = "SELECT `name`, cover, num FROM cs_template_tag WHERE sequence < 65536 and num > 0 ORDER BY sequence ASC";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		foreach ($result as &$ret)
		{
			$ret['num'] = (int)$ret['num'];
		}
		return $result;
	}
	
	public function search($tag, $order, $pos, $size)
	{
		$sql = "";
		if($tag)
		{
			$tag_t = $this->db->escape($tag);
			$sql = "SELECT b.id, b.ring_name, b.ring_mime, b.ring_url,b.ring_duration,b.ring_refid, b.image_mime,b.image_url,b.image_refid,b.video_mime,b.video_url,b.video_duration,b.video_snap_mime,b.video_snap_url,b.video_refid,b.label,b.refid,b.creator,b.tag,b.approve_date,b.refcount,b.show_id  
					FROM cs_template_tag_link a LEFT JOIN cs_template b ON a.show_id = b.id WHERE a.tag_name = $tag_t ";
		}
		else
		{
			$sql = "SELECT id, ring_name, ring_mime, ring_url,ring_duration,ring_refid, image_mime,image_url,image_refid,video_mime,video_url,video_duration,video_snap_mime,video_snap_url,video_refid,label,refid,creator,tag,approve_date,refcount,show_id   
					FROM cs_template ";	
		}

		if($order == "hot")
		{
			$sql .= "ORDER BY refcount desc ";
		}
		else
		{
			$sql .= "ORDER BY approve_date desc ";
		}
		
		$sql .= "LIMIT $pos, $size;";
		
		$user_info = array();
		return $this->_format_template($user_info, $sql);
	}
	
	public function detail($id)
	{
		$sql = "SELECT id, ring_name, ring_mime, ring_url,ring_duration,ring_refid, image_mime,image_url,image_refid,video_mime,video_url,video_duration,video_snap_mime,video_snap_url,video_refid,label,refid,creator,tag,approve_date,refcount,show_id   
			FROM cs_template WHERE id = $id LIMIT 1";
		
		$user_info = array();
		$ret = $this->_format_template($user_info, $sql);
		if($ret)
		{
			return array("result"=>200,"msg"=>$ret[0]);
		}
		
		return array("result"=>404,"msg"=>"无法找到模板秀");
	}
	
	public function surprise($uid, $phone,$contact_name)
	{
		//从所有分享秀中随机获取一批来电秀
		$sql = "SELECT id, ring_name, ring_mime, ring_url,ring_duration,ring_refid, image_mime,image_url,image_refid,video_mime,video_url,video_duration,video_snap_mime,video_snap_url,video_refid,label,refid,creator,tag,approve_date,refcount,show_id   
			FROM cs_template ORDER BY RAND() LIMIT 1";
		
		$user_info = array();
		$ret = $this->_format_template($user_info, $sql);
		if($ret)
		{
			return array("result"=>200,"msg"=>$ret[0]);
		}
		
		return array("result"=>404,"msg"=>"无法找到模板秀");
	}
	
	public function add($uid,$ids)
	{
		$ret = array();
		$ret_ids = array();
		//获取合法的来电秀
		$cs_model = Callshow_Model::instance();
		$cs_info = $cs_model->inner_info_by_ids($ids);
		if($cs_info)
		{
			$cur_time = time();
			foreach ($cs_info as $show)
			{
				$image_url = $show['image_url']?$show['image_url']:"";
				$video_url = $show['video_url']?$show['video_url']:"";
				//校验指纹
				$fp = $this->get_fingerprint($video_url,$image_url);
				if(!$fp)
				{
					continue;
				}
				
				$fp_t = $this->db->escape($fp);
				//若指纹存在，则不再重复添加
				$sql = "SELECT id FROM cs_template WHERE fingerprint = $fp_t LIMIT 1";
				$query = $this->db->query($sql);
				$result = $query->result_array(FALSE);	
				if($result)
				{
					continue;
				}
				
				$ring_name_t = $this->db->escape($show['ring_name']?$show['ring_name']:"");
				$ring_mime_t = $this->db->escape($show['ring_mime']?$show['ring_mime']:"");
				$ring_url_t = $this->db->escape($show['ring_url']?$show['ring_url']:"");
				$image_mime_t = $this->db->escape($show['image_mime']?$show['image_mime']:"");
				$image_url_t = $this->db->escape($image_url);
				$video_mime_t = $this->db->escape($show['video_mime']?$show['video_mime']:"");
				$video_url_t = $this->db->escape($video_url);
				$video_snap_mime_t = $this->db->escape($show['video_snap_mime']?$show['video_snap_mime']:"");
				$video_snap_url_t = $this->db->escape($show['video_snap_url']?$show['video_snap_url']:"");
				$label_t = $this->db->escape($show['label']?$show['label']:"");
				$ring_duration = (int)$show['ring_duration'];
				$ring_refid = (int)$show['ring_refid'];
				$image_refid = (int)$show['image_refid'];
				$video_duration = (int)$show['video_duration'];
				$video_refid = (int)$show['video_refid'];
				$refid = (int)$show['refid'];
				$creator = (int)$show['creator'];
				$show_id = (int)$show['id'];
				
				//添加到模板库
				$sql = "INSERT INTO cs_template (fingerprint,ring_name,ring_mime,ring_url,ring_duration,ring_refid,image_mime,image_url,image_refid,video_mime,video_url,video_duration,video_snap_mime,video_snap_url,video_refid,label,refid,creator,tag,approve_date,approver,show_id,refcount) 
						VALUES ($fp_t,$ring_name_t,$ring_mime_t,$ring_url_t,$ring_duration,$ring_refid,$image_mime_t,$image_url_t,$image_refid,$video_mime_t,$video_url_t,$video_duration,$video_snap_mime_t,$video_snap_url_t,$video_refid,$label_t,$refid,$creator,'',$cur_time,$uid,$show_id,0)";
				
				$query = $this->db->query($sql);
				$lastid = $query->insert_id();
				
				//更新来电秀的模板秀信息
				if($lastid)
				{
					$cs_model->update_template_info($show_id, $lastid, 224);	
					$ret_ids[] = $lastid;
					
					$cs_model->add_sysmsg(353, 2,array(array('user_id'=>$creator, 'type'=>1)), "您的来电秀被选为模板秀,太厉害了!","您的来电秀被选为模板秀,太厉害了!", array('show'=>array("id"=>$show_id)));
				}
			}
		}
		
		//获取所有新增加的模板秀信息并返回
		if($ret_ids)
		{
			$sql = "SELECT id, ring_name, ring_mime, ring_url,ring_duration,ring_refid, image_mime,image_url,image_refid,video_mime,video_url,video_duration,video_snap_mime,video_snap_url,video_refid,label,refid,creator,tag,approve_date,refcount,show_id   
			FROM cs_template WHERE id in (".implode(',',$ret_ids).")";
			$user_info = array();
			$ret = $this->_format_template($user_info, $sql);
		}
		return $ret;
	}
	
	public function modi($uid,$infos)
	{
		$ret = array();
		$ret_ids = array();
		$tag_info = array();
		
		foreach ($infos as $info)
		{
			if(!$info['id'] || !isset($info['tag']))
			{
				continue;
			}
			
			$ret_ids[] = $info['id'];
			
			$id = $info['id'];
			$tag = $info['tag'];
			$newtag_dict = $this->_unique_tag_2_dict($tag);
			
			//更新模板秀本身存储的tag信息
			$tag_t = $this->db->escape($tag);
			$sql = "UPDATE cs_template SET tag = $tag_t WHERE id = $id";
			$this->db->query($sql);
			
			//更新tag和模板秀的关联信息
			$sql = "SELECT tag_name, show_id FROM cs_template_tag_link WHERE show_id = $id";
			$query = $this->db->query($sql);
			$result = $query->result_array(FALSE);
			foreach ($result as $tmp)
			{
				if(!$tag_info[$tmp['tag_name']])
				{
					$tag_info[$tmp['tag_name']] =  0;
				}
				$tag_info[$tmp['tag_name']] = $tag_info[$tmp['tag_name']] - 1;
				
				if(!isset($newtag_dict[$tmp['tag_name']]))
				{
					$tagname_t = $this->db->escape($tmp['tag_name']);
					$sql = "DELETE FROM cs_template_tag_link WHERE tag_name = $tagname_t AND show_id = $id";
					$this->db->query($sql);
				}
				else
				{
					$newtag_dict[$tmp['tag_name']] = "exist";
				}
			}
			
			foreach ($newtag_dict as $k=>$v)
			{
				if(!$tag_info[$k])
				{
					$tag_info[$k] =  0;
				}
				$tag_info[$k] = $tag_info[$k] + 1;
				
				if(!$v)
				{
					$v = 1;
				}
				
				if($v != "exist")
				{
					$tagname_t = $this->db->escape($k);
					$sql = "INSERT INTO cs_template_tag_link (tag_name,show_id) VALUES ($tagname_t,$id)";
					$this->db->query($sql);
				}
			}
		}
		
		$this->_add_tag_num($tag_info);
		
		//获取所有新增加的模板秀信息并返回
		if($ret_ids)
		{
			$sql = "SELECT id, ring_name, ring_mime, ring_url,ring_duration,ring_refid, image_mime,image_url,image_refid,video_mime,video_url,video_duration,video_snap_mime,video_snap_url,video_refid,label,refid,creator,tag,approve_date,refcount,show_id   
			FROM cs_template WHERE id in (".implode(',',$ret_ids).")";
		
			$user_info = array();
			$ret = $this->_format_template($user_info, $sql);
		}
		return $ret;
	}
	
	public function del($uid,$ids)
	{
		if($ids)
		{
			$sql = "SELECT show_id FROM cs_template WHERE id in (".implode(',',$ids).")";
			$query = $this->db->query($sql);
			$result = $query->result_array(FALSE);	
			if($result)
			{
				$cs_model = Callshow_Model::instance();
				foreach ($result as $tmp)
				{
					$cs_model->update_template_info($tmp['show_id'], 0, 0);
				}
			}
			
			//更新标签
			$tag_info = array();
			$sql = "SELECT tag_name, show_id FROM cs_template_tag_link WHERE show_id in (".implode(',',$ids).")";
			$query = $this->db->query($sql);
			$result = $query->result_array(FALSE);
			foreach ($result as $tmp)
			{
				if(!$tag_info[$tmp['tag_name']])
				{
					$tag_info[$tmp['tag_name']] =  0;
				}
				$tag_info[$tmp['tag_name']] = $tag_info[$tmp['tag_name']] - 1;
			}
			
			$this->_add_tag_num($tag_info);
			
			$sql = "DELETE FROM cs_template_tag_link WHERE show_id in (".implode(',',$ids).")";
			$this->db->query($sql);
			
			$sql = "DELETE FROM cs_template WHERE id in (".implode(',',$ids).")";
			$this->db->query($sql);
		}
	}
	
	public function get_similarity($temp_id,$video, $image, $ring)
	{
		$similarity = 0;
		if($temp_id)
		{
			$sql = "SELECT ring_url, image_url, video_url FROM cs_template WHERE id = $temp_id LIMIT 1";
			$query = $this->db->query($sql);
			$result = $query->result_array(FALSE);	
			if($result)
			{
				if($result[0]['ring_url'] == $ring)
				{
					$similarity += 32;
				}
				if($result[0]['image_url'] == $image)
				{
					$similarity += 64;
				}
				if($result[0]['video_url'] == $video)
				{
					$similarity += 128;
				}
			}
		}
		
		return $similarity;
	}
	
	public function update_ref_count($video_url, $image_url, $ring_url)
	{
		$t_id = 0;
		$similarity = 0;
		$fp = $this->get_fingerprint($video_url,$image_url);
		if($fp)
		{
			$sql = "SELECT id, ring_url, image_url, video_url FROM cs_template WHERE fingerprint = '$fp' LIMIT 1";
			$query = $this->db->query($sql);
			$result = $query->result_array(FALSE);	
			if($result)
			{
				$t_id = (int)$result[0]['id'];
				if($result[0]['ring_url'] == $ring)
				{
					$similarity += 32;
				}
				if($result[0]['image_url'] == $image)
				{
					$similarity += 64;
				}
				if($result[0]['video_url'] == $video)
				{
					$similarity += 128;
				}
				
				$sql = "UPDATE cs_template SET refcount = refcount+1 WHERE id = $t_id";
				$this->db->query($sql);
			}
		}
		
		return array("id"=>$t_id,"similarity"=>$similarity);
	}
	
	private function _format_template(&$user_info, $sql)
	{
		$ret = array();	
	
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);	
		if($result)
		{
			$user_model = User_Model::instance();
			
			foreach($result as $show)
			{
				if(!$user_info['user_'.$show['creator']])
				{
					$info = $user_model->get_user_info($show['creator']);
					$user_info['user_'.$show['creator']] = array("uid"=>(int)$info['uid'],"nickname"=>$info['nickname']);
				}
	
				$tmp = array(
								"id"=>(int)$show['id'],
								"ring"=>array("name"=>$show['ring_name'],"mime"=>$show['ring_mime'],"url"=>$show['ring_url'],"duration"=>(int)$show['ring_duration'],"refid"=>(int)$show['ring_refid']),
								"label"=>"",
								"refid"=>(int)$show['refid'],
								"creator"=>array("uid"=>(int)$show['creator'],"nickname"=>$user_info['user_'.$show['creator']]["nickname"],"avatar"=>sns::getavatar((int)$show['creator'])),
								"tag"=>$show['tag'],
								"refcount"=>(int)$show['refcount'],
								"approve_date"=>(int)$show['approve_date'],
								"show"=>array("id"=>(int)$show['show_id']),
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
				
				$ret[] = $tmp;
			}
		}	
		
		return $ret;
	}
	
	private function get_fingerprint($video_url,$image_url)
	{
		if($video_url)
		{
			return md5($video_url);
		}
		else if($image_url)
		{
			return md5($image_url);
		}
		
		return NULL;		
	}
	
	private function _unique_tag_2_dict(&$tag)
	{
		$newtagary = array();
		if($tag)
		{
			$tagary = explode(',',$tag);				
			$tagary = array_filter($tagary);
			$tagary = array_unique($tagary);											
			
			$tag = implode(',',$tagary);
			$newtagary = array_flip($tagary);
		}
		return $newtagary;
	}
	
	private function _add_tag_num($tag_info)
	{
		foreach ($tag_info as $k=>$v)
		{
			if($v)
			{
				$sql = "SELECT name FROM cs_template_tag WHERE name = '$k' LIMIT 1";
				$query = $this->db->query($sql);
				$result = $query->result_array(FALSE);
				if($result)
				{
					$sql = "UPDATE cs_template_tag SET num=num+$v WHERE name = '$k'";
					$this->db->query($sql);
				}
				else
				{
					$sql = "INSERT INTO cs_template_tag (name,cover, num)
							VALUES ('$k', '', $v)";
					$this->db->query($sql);
				}
			}
		}
	}
}
