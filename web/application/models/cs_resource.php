<?php
class Cs_Resource_Model extends Model {
	
	public $error_msg = '';
	public static $instances = null;
	protected static $instance;
	
	/**
	 * 单例模式
	 * @return Cs_Resource_Model
	 */
	public static function &instance()
	{
		if (! isset(self::$instance))
		{
			// Create a new instance
			self::$instance = new Cs_Resource_Model();
		}
		return self::$instance;
	}
	
	public function __construct() {
		parent::__construct ();
	}
	
	//搜索图片
	public function search_image($key,$tag, $nice, $order, $pos, $size)
	{
		$sql = "";
		if($tag)
		{
			$tag_t = $this->db->escape($tag);
			$sql = "SELECT b.id,b.name, b.mime ,b.pix_x, b.pix_y, b.tag, b.nice, b.size, b.url, b.refcount,b.remark, b.approve_date 
					FROM cs_resource_image_tag_link a LEFT JOIN cs_resource_image b ON a.image_id = b.id WHERE a.tag_name = $tag_t ";
			
			if($nice !== NULl)
			{
				$sql .= "AND b.nice = $nice ";
			}
			
			if($key) //TODO:资源数较少，暂时使用like进行查询，若数据量大或者访问量大，后期将使用搜索引擎
			{
				$key_t = $this->db->escape('%'.$key.'%');
				$sql .= "AND b.name like $key_t ";
			}
			
			$sql .= "AND b.approve_stat = 2 ";
		}
		else
		{
			$sql = "SELECT id,name, mime ,pix_x, pix_y, tag, nice, size, url, refcount,remark, approve_date 
					FROM cs_resource_image WHERE ";
			
			$b_condition = false;
			if($nice !== NULl)
			{
				$sql .= " nice = $nice ";
				$b_condition = true;
			}
			
			if($key) //TODO:资源数较少，暂时使用like进行查询，若数据量大或者访问量大，后期将使用搜索引擎
			{
				if($b_condition)
				{
					$sql .= "AND ";
				}
				
				$key_t = $this->db->escape('%'.$key.'%');
				$sql .= "name like $key_t ";
				$b_condition = true;
			}
			
			if($b_condition)
			{
				$sql .= "AND ";
			}
			
			$sql .= "approve_stat = 2 ";
			$b_condition = true;
		}
		
		if($order == "hot")
		{
			$sql .= "ORDER BY refcount desc ";
		}
		else if($order == "latest")
		{
			$sql .= "ORDER BY approve_date desc ";
		}
			
		$sql .= "LIMIT $pos, $size;";
		
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		foreach ($result as &$ret)
		{
			$ret['resolution'] = array("pix_x"=>(int)$ret['pix_x'],"pix_y"=>(int)$ret['pix_y']);
			unset($ret['pix_x']);
			unset($ret['pix_y']);
			$ret['id'] = (int)$ret['id'];
			$ret['nice'] = (int)$ret['nice'];
			$ret['size'] = (int)$ret['size'];
			$ret['refcount'] = (int)$ret['refcount'];
			$ret['approve_date'] = (int)$ret['approve_date'];
		}
		return $result;
	}
	
	//搜索铃音
	public function search_ring($key,$tag,$topic_id, $singer, $nice,$order, $pos, $size)
	{
		$sql = "";
		if($tag || $topic_id)
		{
			$sql = "";
			if($tag)
			{
				$tag_t = $this->db->escape($tag);
				$sql = "SELECT b.id,b.name,b.mime, b.tag,b.singer, b.nice,b.duration, b.size, b.url, b.refcount,b.remark, b.approve_date 
						FROM cs_resource_ring_tag_link a LEFT JOIN cs_resource_ring b ON a.ring_id = b.id WHERE a.tag_name = $tag_t ";
			}
			else 
			{
				$sql = "SELECT b.id,b.name,b.mime, b.tag,b.singer, b.nice,b.duration, b.size, b.url, b.refcount,b.remark, b.approve_date 
						FROM cs_resource_ring_topic_link a LEFT JOIN cs_resource_ring b ON a.ring_id = b.id WHERE a.topic_id = $topic_id ";
			}
			
			if($singer)
			{
				$sql .= "AND b.singer = '$singer' ";
			}
			
			if($nice !== NULl)
			{
				$sql .= "AND b.nice = $nice ";
			}
			
			if($key) //TODO:资源数较少，暂时使用like进行查询，若数据量大或者访问量大，后期将使用搜索引擎
			{
				$key_t = $this->db->escape('%'.$key.'%');
				$sql .= "AND b.name like $key_t ";
			}
			
			$sql .= "AND b.approve_stat = 2 ";			
		}
		else 
		{
			$sql = "SELECT id,name,mime, tag,singer, nice,duration, size, url, refcount,remark, approve_date 
					FROM cs_resource_ring WHERE ";
				
			$b_condition = false;
			if($singer)
			{
				$sql .= "singer = '$singer' ";
				$b_condition = true;
			}
			
			if($nice !== NULl)
			{
				if($b_condition)
				{
					$sql .= "AND ";
				}
				
				$sql .= "nice = $nice ";
				$b_condition = true;
			}

			if($key)
			{
				if($b_condition)
				{
					$sql .= "AND ";
				}
				
				$key_t = $this->db->escape('%'.$key.'%');
				$sql .= "name like $key_t ";
				$b_condition = true;
			}

			if($b_condition)
			{
				$sql .= "AND ";
			}
			
			$sql .= "approve_stat = 2 ";
			$b_condition = true;
		}

		if($order == "hot")
		{
			$sql .= "ORDER BY refcount desc ";
		}
		else if($order == "latest")
		{
			$sql .= "ORDER BY approve_date desc ";
		}
		
		$sql .= "LIMIT $pos, $size;";
		
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		foreach ($result as &$ret)
		{
			$ret['id'] = (int)$ret['id'];
			$ret['nice'] = (int)$ret['nice'];
			$ret['duration'] = (int)$ret['duration'];
			$ret['size'] = (int)$ret['size'];
			$ret['refcount'] = (int)$ret['refcount'];
			$ret['approve_date'] = (int)$ret['approve_date'];
		}
		return $result;		
	}
	
	//搜索来电秀
	public function search_show($key,$tag, $nice,$order, $pos, $size)
	{
		$sql = "";
		if($tag)
		{
			$tag_t = $this->db->escape($tag);
			$sql = "SELECT b.id,b.name, b.image_id, b.ring_id, b.label, b.tag, b.nice, b.refcount,b.remark, b.approve_date 
					FROM cs_resource_show_tag_link a LEFT JOIN cs_resource_show b ON a.show_id = b.id WHERE a.tag_name = $tag_t ";
			
			if($nice !== NULl)
			{
				$sql .= "AND b.nice = $nice ";
			}
			
			if($key) //TODO:资源数较少，暂时使用like进行查询，若数据量大或者访问量大，后期将使用搜索引擎
			{
				$key_t = $this->db->escape('%'.$key.'%');
				$sql .= "AND b.name like $key_t ";
			}
			
			$sql .= "AND b.approve_stat = 2 ";
		}
		else
		{
			$sql = "SELECT id,name, image_id, ring_id,label, tag, nice, refcount,remark, approve_date 
					FROM cs_resource_show WHERE ";
			
			$b_condition = false;
			if($nice !== NULl)
			{
				$sql .= " nice = $nice ";
				$b_condition = true;
			}
			
			if($key) //TODO:资源数较少，暂时使用like进行查询，若数据量大或者访问量大，后期将使用搜索引擎
			{
				if($b_condition)
				{
					$sql .= "AND ";
				}
				
				$key_t = $this->db->escape('%'.$key.'%');
				$sql .= "name like $key_t ";
				$b_condition = true;
			}
			
			if($b_condition)
			{
				$sql .= "AND ";
			}
			
			$sql .= "approve_stat = 2 ";
			$b_condition = true;		
		}

		if($order == "hot")
		{
			$sql .= "ORDER BY refcount desc ";
		}
		else if($order == "latest")
		{
			$sql .= "ORDER BY approve_date desc ";
		}
		
		$sql .= "LIMIT $pos, $size;";
		
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		$imgary = array();
		$ringary = array();
		foreach ($result as &$ret)
		{
			$imgary[] = $ret['image_id'];
			$ringary[] = $ret['ring_id'];
			$ret['id'] = (int)$ret['id'];
			$ret['nice'] = (int)$ret['nice'];
			$ret['refcount'] = (int)$ret['refcount'];
			$ret['approve_date'] = (int)$ret['approve_date'];
		}
		
		$imgary = array_unique($imgary);
		$ringary = array_unique($ringary);
		
		$details = array();
		if($imgary)
		{
			$sql = "SELECT id,mime, url from cs_resource_image where id in (".implode(',',$imgary) .")";
			$imgquery = $this->db->query($sql);
			$imgresult = $imgquery->result_array(FALSE);
			foreach ($imgresult as $img)
			{
				$details['img_'.$img['id']] = array("mime"=>$img['mime'],"url"=>$img['url'],"id"=>$img['id']);
			}
		}
		
		if($ringary)
		{
			$sql = "SELECT id, name,mime,url,duration from cs_resource_ring where id in (".implode(',',$ringary) .")";
			$ringquery = $this->db->query($sql);
			$ringresult = $ringquery->result_array(FALSE);
			foreach ($ringresult as $ring)
			{
				$details['ring_'.$ring['id']] = array("name"=>$ring['name'],"mime"=>$ring['mime'],"url"=>$ring['url'],"duration"=>(int)$ring['duration'],"id"=>$ring['id']);
			}			
		}

		foreach ($result as &$ret)
		{
			$ret['detail'] = array("image"=>$details['img_'.$ret['image_id']],"ring"=>$details['ring_'.$ret['ring_id']]);
			unset($ret['image_id']);
			unset($ret['ring_id']);
		}
		
		return $result;			
	}
	
	public function tag_image()
	{
		$sql = "SELECT name, approved_num as num FROM cs_resource_image_tag WHERE approved_num > 0 ORDER BY sequence";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		foreach ($result as &$ret)
		{
			$ret['num'] = (int)$ret['num'];
		}
		return $result;
	}
	
	public function tag_ring()
	{
		$sql = "SELECT name,cover, approved_num as num FROM cs_resource_ring_tag WHERE approved_num > 0 ORDER BY sequence";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		foreach ($result as &$ret)
		{
			$ret['num'] = (int)$ret['num'];
		}
		return $result;		
	}
	
	public function tag_show()
	{
		$sql = "SELECT name, approved_num as num FROM cs_resource_show_tag WHERE approved_num > 0 ORDER BY sequence";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		foreach ($result as &$ret)
		{
			$ret['num'] = (int)$ret['num'];
		}
		return $result;			
	}
	
	public function topic_ring($uid)
	{
		$sql = "SELECT id, `name`, cover, `desc`, num FROM cs_resource_ring_topic WHERE num > 0 ORDER BY sequence ASC";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		foreach ($result as &$ret)
		{
			$ret['id'] = (int)$ret['id'];
			$ret['num'] = (int)$ret['num'];
		}
		return $result;	
	}
	
	//增加引用计数
	public function update_ref_count($refid, $refimg_id, $refring_id)
	{
		//更新模板引用次数
		if($refid)
		{
			$sql = "UPDATE cs_resource_show set refcount = refcount+1 WHERE id = $refid";
			$this->db->query($sql);
			
			$sql = "SELECT image_id, ring_id FROM cs_resource_show where id = $refid LIMIT 1";
			$query = $this->db->query($sql);
			$result = $query->result_array(FALSE);
			$refimg_id = $refimg_id?$refimg_id:$result[0]['image_id']?(int)$result[0]['image_id']:0;
			$refring_id = $refring_id?$refring_id:$result[0]['ring_id']?(int)$result[0]['ring_id']:0;
		}
		
		if($refimg_id)
		{
			$sql = "UPDATE cs_resource_image set refcount = refcount+1 WHERE id = $refimg_id";
			$this->db->query($sql);
		}
		
		if($refring_id)
		{
			$sql = "UPDATE cs_resource_ring set refcount = refcount+1 WHERE id = $refring_id";
			$this->db->query($sql);
		}		
	}
}