<?php
class Csbk_Resource_Model extends Model {
	
	public $error_msg = '';
	public static $instances = null;
	
	public function __construct() {
		parent::__construct ();
	}

	public function add_image($uid, $data_ary)
	{
		$ret = array();
		$tag_info = array();
		foreach ($data_ary as $data)
		{
			if(!$data['name'] || !$data['mime'] 
				|| !$data['resolution'] || !$data['resolution']['pix_x'] || !$data['resolution']['pix_y'] 
				|| !$data['size'] || !$data['url'] )
			{
				continue;		
			}
			
			$name = $data['name'];
			$mime = $data['mime'];
			$pix_x = $data['resolution']['pix_x'];
			$pix_y = $data['resolution']['pix_y'];
			$size = $data['size'];
			$url = $data['url'];
			$remark = $data['remark']?$data['remark']:"";
			$tag = $data['tag']?$data['tag']:""; 
			$newtagary = $this->_unique_tag($tag);
			
			$nice = $data['nice']?$data['nice']:0;
			
			$refcount = 0;
			$curtime = time();
			$approve_stat = 0;
			$approver = 0;
			$approve_date = 0;
			if($data['approve'])
			{
				$approve_stat = 2;
				$approver = $uid;
				$approve_date = $curtime;
			}
			
			$name_t = $this->db->escape($name);
			$mime_t = $this->db->escape($mime);
			$tag_t = $this->db->escape($tag);
			$url_t = $this->db->escape($url);
			$remark_t = $this->db->escape($remark);			
			$sql = "INSERT INTO cs_resource_image (name, mime, pix_x, pix_y, tag, nice, size, author, refcount, url, remark, create_date, refresh_date, approve_stat, approver, approve_date )  
					VALUES ($name_t, $mime_t, $pix_x, $pix_y, $tag_t, $nice, $size, $uid, $refcount, $url_t, $remark_t, $curtime, $curtime,$approve_stat, $approver, $approve_date)";
			$query = $this->db->query($sql);
			$lastid = $query->insert_id();
			
			if($lastid)
			{	
				//更新tag信息
				if($newtagary)
				{					
					foreach ($newtagary as $tmp)
					{
						if(!$tag_info[$tmp])
						{
							$tag_info[$tmp] = array('all'=>0,'approve'=>0);
						}
						if($data['approve'])
						{
							$tag_info[$tmp]['approve'] = $tag_info[$tmp]['approve']+1;
						}
						$tag_info[$tmp]['all'] = $tag_info[$tmp]['all']+1;
						
						$tmp_t = $this->db->escape($tmp);
						$sql = "INSERT INTO cs_resource_image_tag_link (tag_name, image_id) 
								VALUES ($tmp_t, $lastid)";
						$this->db->query($sql);
					}				
				}
				
				$tmp = $data;
				$tmp['id'] = $lastid;
				$tmp['author'] = array('uid'=>$uid);
				$tmp['tag'] = $tag;
				$tmp['refcount'] = $refcount;
				$tmp['create_date'] = $curtime;
				$tmp['refresh_date'] = $curtime;
				unset($tmp['approve']);
				if($data['approve'])
				{
					$tmp['approve_stat'] = $approve_stat;
					$tmp['approver'] = $approver;
					$tmp['approve_date'] = $approve_date;
				}

				$ret[] = $tmp;
			}
		}
		
		//修改tag计数
		$this->_add_tag_num($tag_info, 'cs_resource_image_tag');
		return $ret;
	}
	
	public function modi_image($uid, $data_ary)
	{
		$ret_id = array();
		$tag_info = array();
		foreach ($data_ary as $data)
		{
			if(!$data['id'])
			{
				continue;
			}
			
			$sql = "SELECT id, approve_stat FROM cs_resource_image WHERE id = ".$data['id'];
			$query = $this->db->query($sql);
			$result = $query->result_array(FALSE);
			if(!$result)
			{
				continue;
			}
			
			$cur_stat = (int)$result[0]['approve_stat'];
			
			$sql_subary = array();
			if($data['name'])
			{
				$sql_subary[] = " name = ".$this->db->escape($data['name']);
			}
			
			if($data['mime'])
			{
				$sql_subary[] = " mime = ".$this->db->escape($data['mime']);
			}

			if($data['resolution'])
			{
				if($data['resolution']['pix_x'])
				{
					$sql_subary[] = " pix_x = ".$data['resolution']['pix_x'];
				}

				if($data['resolution']['pix_y'])
				{
					$sql_subary[] = " pix_y = ".$data['resolution']['pix_y'];
				}				
			}
			
			if($data['size'])
			{
				$sql_subary[] = " size = ".$data['size'];
			}
			
			if($data['url'])
			{
				$sql_subary[] = " url = ".$this->db->escape($data['url']);
			}
			
			if(isset($data['remark']))
			{
				$sql_subary[] = " remark = ".$this->db->escape($data['remark']);
			}
			
			$newtagary = array();
			if(isset($data['tag']))
			{
				$tag = $data['tag'];
				$newtagary = $this->_unique_tag($tag);
				$sql_subary[] = " tag = ".$this->db->escape($tag);
			}
			
			if(isset($data['nice']))
			{
				$sql_subary[] = " nice = ".$data['nice'];
			}
			
			$curtime = time();
			//以上内容为更新项目
			if($sql_subary)
			{
				$sql_subary[] = " refresh_date = ".$curtime;
			}
			
			if($data['approve'])
			{
				$sql_subary[] = " approve_stat = 2";
				$sql_subary[] = " approver = ".$uid;
				$sql_subary[] = " approve_date = ".$curtime;
			}
			else
			{
				if($sql_subary || isset($data['approve']))
				{
					if($cur_stat == 2)
					{
						$sql_subary[] = " approve_stat = 1";
						$sql_subary[] = " approver = 0";
						$sql_subary[] = " approve_date = 0";
					}
				}
			}
			
			if($sql_subary)
			{
				$sql = "UPDATE cs_resource_image SET ".implode(',',$sql_subary)." WHERE id = ".$data['id'];
				$this->db->query($sql);
				
				$ret_id[] = $data['id'];
				if(isset($data['tag']))
				{
					$tmp_tagary = array();
					foreach ($newtagary as $tmp)
					{
						$tmp_tagary[$tmp] = 1;
						if(!$tag_info[$tmp])
						{
							$tag_info[$tmp] =  array('all'=>0,'approve'=>0);
						}
						if($data['approve'])
						{
							$tag_info[$tmp]['approve'] = $tag_info[$tmp]['approve']+1;	
						}
						$tag_info[$tmp]['all'] = $tag_info[$tmp]['all']+1;
					}
					//更新tag信息
					$sql = "SELECT tag_name, image_id FROM cs_resource_image_tag_link WHERE image_id = ".$data['id'];
					$query = $this->db->query($sql);
					$result = $query->result_array(FALSE);
					foreach ($result as $tmp)
					{
						if(!$tag_info[$tmp['tag_name']])
						{
							$tag_info[$tmp['tag_name']] =  array('all'=>0,'approve'=>0);
						}
						if($cur_stat == 2)
						{
							$tag_info[$tmp['tag_name']]['approve'] = $tag_info[$tmp['tag_name']]['approve']-1;	
						}
						$tag_info[$tmp['tag_name']]['all'] = $tag_info[$tmp['tag_name']]['all']-1;						
						
						if(!$tmp_tagary[$tmp['tag_name']])
						{
							$sql = "DELETE FROM cs_resource_image_tag_link WHERE tag_name = ".$this->db->escape($tmp['tag_name'])." AND image_id = ".$data['id'];
							$this->db->query($sql);
						}
						else
						{
							$tmp_tagary[$tmp['tag_name']] = 0;
						}
					}
					
					foreach ($tmp_tagary as $k => $v)
					{
						if($v)
						{
							$k_t = $this->db->escape($k);
							$sql = "INSERT INTO cs_resource_image_tag_link (tag_name, image_id) VALUES ($k_t, ".$data['id'].")";
							$this->db->query($sql);
						}
					}					
				}
				else 
				{
					$increment = 0;
					if($data['approve'] && $cur_stat !== 2 )
					{
						$increment = 1;
					}
					else if(!$data['approve'] && $cur_stat == 2)
					{
						$increment = -1;
					}
					
					if($increment)
					{
						$sql = "SELECT tag_name, image_id FROM cs_resource_image_tag_link WHERE image_id = ".$data['id'];
						$query = $this->db->query($sql);
						$result = $query->result_array(FALSE);
						foreach ($result as $tmp)
						{
							if(!$tag_info[$tmp['tag_name']])
							{
								$tag_info[$tmp['tag_name']] =  array('all'=>0,'approve'=>0);
							}
							$tag_info[$tmp['tag_name']]['approve'] = $tag_info[$tmp['tag_name']]['approve'] + $increment;	
						}	
					}
				}
			}
		}
		
		$this->_add_tag_num($tag_info, 'cs_resource_image_tag');
		
		$sql = "SELECT * FROM cs_resource_image WHERE id in (".implode(',',$ret_id).")";
		$ret = array();
		$this->_append_image_result($sql,$ret);
		return $ret;
	}
	
	public function del_image_force($uid, $data_ary)
	{
		//将未审核的删除
		$del_ids = $this->del_image($uid, $data_ary);
		$del_ids = $del_ids['ids'];
		
		//过滤出已审核的
		
		//更新标签
		
		
		//更新专题
		
		
	}
	
	public function del_image($uid, $data_ary)
	{
		$ret_ids = array();
		
		//查询哪些id符合删除条件
		$sql = "SELECT id FROM cs_resource_image WHERE id in (".implode(',',$data_ary).") AND approve_stat = 0";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		foreach ($result as $tmp)
		{
			$ret_ids[] = (int)$tmp['id'];
		}
				
		if($ret_ids)
		{
			//删除所有合法的id
			$sql = "DELETE FROM cs_resource_image WHERE id in (".implode(',',$ret_ids).")";
			$this->db->query($sql);
		
			$tag_info = array();
			//删除tag
			$sql = "SELECT tag_name, image_id FROM cs_resource_image_tag_link WHERE image_id in (".implode(',',$ret_ids).")";
			$query = $this->db->query($sql);
			$result = $query->result_array(FALSE);
			foreach ($result as $tmp)
			{
				if(!$tag_info[$tmp['tag_name']])
				{
					$tag_info[$tmp['tag_name']] = array('all'=>0,'approve'=>0);
				}

				$tag_info[$tmp['tag_name']]['all'] = $tag_info[$tmp['tag_name']]['all']-1;		
			}
			
			$sql = "DELETE FROM cs_resource_image_tag_link WHERE image_id in (".implode(',',$ret_ids).")";
			$this->db->query($sql);
			
			$this->_add_tag_num($tag_info, 'cs_resource_image_tag');
		}
		
		return array('ids'=>$ret_ids);
	}
	
	public function search_image($uid, $key,$tag, $notag, $gif,$nice,$order, $pos, $size, $approve_stat)
	{
		$ret = array();
		
		$sql_prefix = "";
		$sql_count_prefix = "";
		$sql_postfix = "";
		
		if($tag)
		{
			$sql_prefix = "SELECT b.id,b.name, b.mime ,b.pix_x, b.pix_y, b.tag, b.nice, b.size,b.author, b.url, b.refcount,b.remark,b.create_date, b.refresh_date,b.approve_stat,b.approver, b.approve_date 
							FROM cs_resource_image_tag_link a LEFT JOIN cs_resource_image b ON a.image_id = b.id ";
			$sql_count_prefix = "SELECT count(b.id) as t 
							FROM cs_resource_image_tag_link a LEFT JOIN cs_resource_image b ON a.image_id = b.id ";
			
			$tag_t = $this->db->escape($tag);
			$sql_postfix = "WHERE a.tag_name = $tag_t ";
			
			if($nice !== NULL)
			{
				$sql_postfix .= "AND b.nice = $nice ";
			}
			
			if($key) //TODO:资源数较少，暂时使用like进行查询，若数据量大或者访问量大，后期将使用搜索引擎
			{
				$key_t = $this->db->escape('%'.$key.'%');
				$sql_postfix .= "AND b.name like $key_t ";
			}
			
			if($approve_stat !== NULL)
			{
				$sql_postfix .= "AND b.approve_stat in ( $approve_stat ) ";
			}
			
			if($gif !== NULL)
			{
				if($gif)
				{
					$sql_postfix .= "AND b.mime = 'image/gif' ";
				}
				else
				{
					$sql_postfix .= "AND b.mime != 'image/gif' ";
				}
			}
		}
		else
		{
			$sql_prefix = "SELECT id,name, mime ,pix_x, pix_y, tag, nice, size,author, url, refcount,remark,create_date, refresh_date,approve_stat,approver, approve_date 
							FROM cs_resource_image ";
			$sql_count_prefix = "SELECT count(id) as t 
							FROM cs_resource_image ";
			
			if( $notag || $gif !== NULL || $nice !== NULL || $key || $approve_stat !== NULL)
			{
				$sql_postfix .= "WHERE ";	
			}
			
			$b_condition = false;
			if($nice !== NULL)
			{
				$sql_postfix .= " nice = $nice ";
				$b_condition = true;
			}
			
			if($notag)
			{
				if($b_condition)
				{
					$sql_postfix .= "AND ";
				}
				
				$sql_postfix .= "tag = '' ";
				$b_condition = true;
			}
			
			if($key) //TODO:资源数较少，暂时使用like进行查询，若数据量大或者访问量大，后期将使用搜索引擎
			{
				if($b_condition)
				{
					$sql_postfix .= "AND ";
				}
				
				$key_t = $this->db->escape('%'.$key.'%');
				$sql_postfix .= "name like $key_t ";
				$b_condition = true;
			}
			
			if($approve_stat !== NULL)
			{
				if($b_condition)
				{
					$sql_postfix .= "AND ";
				}
				
				$sql_postfix .= "approve_stat in ( $approve_stat ) ";
				$b_condition = true;
			}
			
			if($gif !== NULL)
			{
				if($b_condition)
				{
					$sql_postfix .= "AND ";
				}
				
				if($gif)
				{
					$sql_postfix .= "mime = 'image/gif' ";
				}
				else
				{
					$sql_postfix .= "mime != 'image/gif' ";
				}
				
				$b_condition = true;
			}
		}
		
		$sql = $sql_prefix.$sql_postfix;
		$sql_count = $sql_count_prefix.$sql_postfix;
		
		if($order == "hot")
		{
			$sql .= "ORDER BY refcount desc ";
		}
		else if($order == "latest")
		{
			$sql .= "ORDER BY create_date desc ";
		}
			
		$sql .= "LIMIT $pos, $size;";
		
		$this->_append_image_result($sql, $ret);
		
		$query = $this->db->query($sql_count);
		$result = $query->result_array(FALSE);
		
		return array("total"=>$result[0]['t'],"data"=>$ret);
	}
	
	public function tag_image()
	{
		$sql = "SELECT name, num FROM cs_resource_image_tag ORDER BY sequence";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		foreach ($result as &$ret)
		{
			$ret['num'] = (int)$ret['num'];
		}
		return $result;
	}
	
	public function del_tag_image($tag, $force)
	{
		$tag_t = $this->db->escape($tag);
		//tag是否存在
		$sql = "SELECT num FROM cs_resource_image_tag WHERE name = $tag_t";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		if(!$result)
		{
			return array("result"=>200, "msg"=>NULL);
		}
			
		if((int)$result[0]['num'] > 0)
		{
			if(!$force)
			{
				return array("result"=>400, "msg"=>"tag下还有文件");
			}
			else 
			{
				//整理相关文件的tag信息
				$sql = "SELECT tag_name, image_id FROM cs_resource_image_tag_link WHERE image_id IN 
						(SELECT image_id FROM cs_resource_image_tag_link WHERE tag_name = $tag_t)";
				$query = $this->db->query($sql);
				$result = $query->result_array(FALSE);
				$new_tag_info = array();
				foreach ($result as $tmp)
				{
					if(!$new_tag_info['i_'.$tmp['image_id']])
					{
						$new_tag_info['i_'.$tmp['image_id']] = array('id'=>(int)$tmp['image_id'],'tag'=>array());
					}
					
					if($tmp['tag_name'] == $tag)
					{
						continue;
					}
					
					$new_tag_info['i_'.$tmp['image_id']]['tag'][] = $tmp['tag_name'];
				}
				
				foreach ($new_tag_info as $tmp)
				{
					$sql = "UPDATE cs_resource_image SET tag = '".implode(',',$tmp['tag'])."' WHERE id = ".$tmp['id'];
					$this->db->query($sql);
				}
				
				//删除link信息
				$sql = "DELETE FROM cs_resource_image_tag_link WHERE tag_name = $tag_t";
				$this->db->query($sql);
			}
		}
		
		//清理tag记录
		$sql = "DELETE FROM cs_resource_image_tag WHERE name = $tag_t";
		$this->db->query($sql);
		
		return array("result"=>200, "msg"=>NULL);
	}
	
	public function add_ring($uid, $data_ary)
	{
		$ret = array();
		$tag_info = array();
		foreach ($data_ary as $data)
		{
			if(!$data['name'] || !$data['mime']|| !$data['duration']
				|| !$data['size']|| !$data['url'])
			{
				continue;
			}
			
			$name = $data['name'];
			$mime = $data['mime'];
			$duration = $data['duration'];
			$size = $data['size'];
			$url = $data['url'];
			$singer = $data['singer']?$data['singer']:"";
			$remark = $data['remark']?$data['remark']:"";
			$tag = $data['tag']?$data['tag']:"";
			$newtagary = $this->_unique_tag($tag);
			
			$nice = $data['nice']?$data['nice']:0;
			
			$refcount = 0;
			$curtime = time();
			$approve_stat = 0;
			$approver = 0;
			$approve_date = 0;
			if($data['approve'])
			{
				$approve_stat = 2;
				$approver = $uid;
				$approve_date = $curtime;
			}
			
			$name_t = $this->db->escape($name);
			$mime_t = $this->db->escape($mime);
			$tag_t = $this->db->escape($tag);
			$singer_t = $this->db->escape($singer);
			$url_t = $this->db->escape($url);
			$remark_t = $this->db->escape($remark);	
			$sql = "INSERT INTO cs_resource_ring (name, mime,tag, nice, duration, size, author,singer, refcount, url, remark, create_date, refresh_date,approve_stat ,approver, approve_date ) 
					VALUES ($name_t,$mime_t,$tag_t,$nice, $duration, $size, $uid,$singer_t ,$refcount, $url_t, $remark_t, $curtime, $curtime,$approve_stat ,$approver, $approve_date)";
			$query = $this->db->query($sql);
			$lastid = $query->insert_id();
			
			if($lastid)
			{
				//更新tag信息
				if($newtagary)
				{					
					foreach ($newtagary as $tmp)
					{
						if(!$tag_info[$tmp])
						{
							$tag_info[$tmp] = array('all'=>0,'approve'=>0);
						}
						if($data['approve'])
						{
							$tag_info[$tmp]['approve'] = $tag_info[$tmp]['approve']+1;
						}
						$tag_info[$tmp]['all'] = $tag_info[$tmp]['all']+1;
						
						$tmp_t = $this->db->escape($tmp);
						$sql = "INSERT INTO cs_resource_ring_tag_link (tag_name, ring_id) 
								VALUES ($tmp_t, $lastid)";
						$this->db->query($sql);
					}				
				}
				
				$tmp = $data;
				$tmp['id'] = $lastid;
				$tmp['author'] = array('uid'=>$uid);
				$tmp['tag'] = $tag;
				$tmp['refcount'] = $refcount;
				$tmp['create_date'] = $curtime;
				$tmp['refresh_date'] = $curtime;
				unset($tmp['approve']);
				if($data['approve'])
				{
					$tmp['approve_stat'] = $approve_stat;
					$tmp['approver'] = $approver;
					$tmp['approve_date'] = $approve_date;
				}

				$ret[] = $tmp;
			}
		}
		//修改tag计数
		$this->_add_tag_num($tag_info, 'cs_resource_ring_tag');
		return $ret;
	}
	
	public function modi_ring($uid, $data_ary)
	{
		$ret_id = array();
		$tag_info = array();
		foreach ($data_ary as $data)
		{
			if(!$data['id'])
			{
				continue;
			}
			
			$sql = "SELECT id, approve_stat FROM cs_resource_ring WHERE id = ".$data['id'];
			$query = $this->db->query($sql);
			$result = $query->result_array(FALSE);
			if(!$result)
			{
				continue;
			}
			
			$cur_stat = (int)$result[0]['approve_stat']; 
			
			$sql_subary = array();
			if($data['name'])
			{
				$sql_subary[] = " name = ".$this->db->escape($data['name']);
			}
			
			if($data['mime'])
			{
				$sql_subary[] = " mime = ".$this->db->escape($data['mime']);
			}
			
			if($data['duration'])
			{
				$sql_subary[] = " duration = ".$data['duration'];
			}
			
			if($data['size'])
			{
				$sql_subary[] = " size = ".$data['size'];
			}
			
			if($data['url'])
			{
				$sql_subary[] = " url = ".$this->db->escape($data['url']);
			}
			
			if(isset($data['singer']))
			{
				$sql_subary[] = " singer = ".$this->db->escape($data['singer']);
			}
			
			if(isset($data['remark']))
			{
				$sql_subary[] = " remark = ".$this->db->escape($data['remark']);
			}
			
			$newtagary = array();
			if(isset($data['tag']))
			{
				$tag = $data['tag'];
				$newtagary = $this->_unique_tag($tag);
				$sql_subary[] = " tag = ".$this->db->escape($tag);
			}
			
			if(isset($data['nice']))
			{
				$sql_subary[] = " nice = ".$data['nice'];
			}

			$curtime = time();
			//以上内容为更新项目
			if($sql_subary)
			{
				$sql_subary[] = " refresh_date = ".$curtime;
			}
			
			if($data['approve'])
			{
				$sql_subary[] = " approve_stat = 2";
				$sql_subary[] = " approver = ".$uid;
				$sql_subary[] = " approve_date = ".$curtime;
			}
			else
			{
				if($sql_subary || isset($data['approve']))
				{
					if($cur_stat == 2)
					{
						$sql_subary[] = " approve_stat = 1";
						$sql_subary[] = " approver = 0";
						$sql_subary[] = " approve_date = 0";
					}
				}
			}

			if($sql_subary)
			{
				$sql = "UPDATE cs_resource_ring SET ".implode(',',$sql_subary)." WHERE id = ".$data['id'];
				$this->db->query($sql);
				
				$ret_id[] = $data['id'];
				if(isset($data['tag']))
				{
					$tmp_tagary = array();
					foreach ($newtagary as $tmp)
					{
						$tmp_tagary[$tmp] = 1;
						if(!$tag_info[$tmp])
						{
							$tag_info[$tmp] =  array('all'=>0,'approve'=>0);
						}
						if($data['approve'])
						{
							$tag_info[$tmp]['approve'] = $tag_info[$tmp]['approve']+1;	
						}
						$tag_info[$tmp]['all'] = $tag_info[$tmp]['all']+1;
					}
					
					//更新tag信息
					$sql = "SELECT tag_name, ring_id FROM cs_resource_ring_tag_link WHERE ring_id = ".$data['id'];
					$query = $this->db->query($sql);
					$result = $query->result_array(FALSE);
					foreach ($result as $tmp)
					{
						if(!$tag_info[$tmp['tag_name']])
						{
							$tag_info[$tmp['tag_name']] =  array('all'=>0,'approve'=>0);
						}
						if($cur_stat == 2)
						{
							$tag_info[$tmp['tag_name']]['approve'] = $tag_info[$tmp['tag_name']]['approve']-1;	
						}
						$tag_info[$tmp['tag_name']]['all'] = $tag_info[$tmp['tag_name']]['all']-1;	
							
						if(!$tmp_tagary[$tmp['tag_name']])
						{
							$sql = "DELETE FROM cs_resource_ring_tag_link WHERE tag_name = ".$this->db->escape($tmp['tag_name'])." AND ring_id = ".$data['id'];
							$this->db->query($sql);
						}
						else
						{
							$tmp_tagary[$tmp['tag_name']] = 0;
						}
					}
					
					foreach ($tmp_tagary as $k => $v)
					{
						if($v)
						{
							$k_t = $this->db->escape($k);
							$sql = "INSERT INTO cs_resource_ring_tag_link (tag_name, ring_id) VALUES ($k_t, ".$data['id'].")";
							$this->db->query($sql);
						}
					}					
				}
				else 
				{
					$increment = 0;
					if($data['approve'] && $cur_stat !== 2 )
					{
						$increment = 1;
					}
					else if(!$data['approve'] && $cur_stat == 2)
					{
						$increment = -1;
					}
					
					if($increment)
					{
						$sql = "SELECT tag_name, ring_id FROM cs_resource_ring_tag_link WHERE ring_id = ".$data['id'];
						$query = $this->db->query($sql);
						$result = $query->result_array(FALSE);
						foreach ($result as $tmp)
						{
							if(!$tag_info[$tmp['tag_name']])
							{
								$tag_info[$tmp['tag_name']] =  array('all'=>0,'approve'=>0);
							}
							$tag_info[$tmp['tag_name']]['approve'] = $tag_info[$tmp['tag_name']]['approve'] + $increment;	
						}
					}
				}
			}			
		}
		
		$this->_add_tag_num($tag_info, 'cs_resource_ring_tag');
		
		$sql = "SELECT * FROM cs_resource_ring WHERE id in(".implode(',',$ret_id).")";
		$ret = array();
		$this->_append_ring_result($sql, $ret);
		return $ret;
	}
	
	public function format_ring_name()
	{
		$sql = "SELECT id, name FROM cs_resource_ring";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		foreach ($result as $tmp)
		{
			$str = $tmp['name'];
			$str = str_replace('(佚名)','',$str);
			$str = str_replace('.mp3','',$str);
			$str = str_replace('.MP3','',$str);
			if(substr($str,-1) == ")")
			{
				$str_end = strrchr($str,'(');
				$str = trim(str_replace($str_end,'',$str));
				$str_end = trim(substr($str_end,1,-1));
				if($str_end)
				{
					$str = $str_end.' - '.$str;	
				}
			}
			
			if($str !== $tmp['name'])
			{
				$str_t = $this->db->escape($str);
				$sql = "UPDATE cs_resource_ring SET name = $str_t WHERE id = ".$tmp['id'];
				$this->db->query($sql);
			}
		}
	}
	
	public function del_ring($uid, $data_ary, $force)
	{
		$ret_ids = array();
		
		//查询哪些id符合删除条件
		$sql = "SELECT id FROM cs_resource_ring WHERE id in (".implode(',',$data_ary).") AND approve_stat = 0";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		foreach ($result as $tmp)
		{
			$ret_ids[] = (int)$tmp['id'];
		}
				
		if($ret_ids)
		{
			//删除所有合法的id
			$sql = "DELETE FROM cs_resource_ring WHERE id in (".implode(',',$ret_ids).")";
			$this->db->query($sql);
		
			$tag_info = array();
			//删除tag
			$sql = "SELECT tag_name, ring_id FROM cs_resource_ring_tag_link WHERE ring_id in (".implode(',',$ret_ids).")";
			$query = $this->db->query($sql);
			$result = $query->result_array(FALSE);
			foreach ($result as $tmp)
			{
				if(!$tag_info[$tmp['tag_name']])
				{
					$tag_info[$tmp['tag_name']] = array('all'=>0,'approve'=>0);
				}

				$tag_info[$tmp['tag_name']]['all'] = $tag_info[$tmp['tag_name']]['all']-1;		
			}
			
			$sql = "DELETE FROM cs_resource_ring_tag_link WHERE ring_id in (".implode(',',$ret_ids).")";
			$this->db->query($sql);
			
			$this->_add_tag_num($tag_info, 'cs_resource_ring_tag');
		}
		
		return array('ids'=>$ret_ids);	
	}
	
	public function search_ring($uid, $key,$tag, $notag,$topic_id, $singer,$nice,$order, $pos, $size, $approve_stat)
	{
		$ret = array();
		
		$sql_prefix = "";
		$sql_count_prefix = "";
		$sql_postfix = "";
		if($tag)
		{
			$sql_prefix = "SELECT b.id,b.name,b.mime, b.tag, b.nice,b.duration, b.size,b.author, b.singer, b.url, b.refcount,b.remark,b.create_date,b.refresh_date,b.approve_stat,b.approver, b.approve_date 
							FROM cs_resource_ring_tag_link a LEFT JOIN cs_resource_ring b ON a.ring_id = b.id ";
			$sql_count_prefix = "SELECT count(b.id) as t 
							FROM cs_resource_ring_tag_link a LEFT JOIN cs_resource_ring b ON a.ring_id = b.id ";
			$tag_t = $this->db->escape($tag);
			$sql_postfix = "WHERE a.tag_name = $tag_t ";
			
			if($singer)
			{
				$singer_t = $this->db->escape($singer);
				$sql_postfix .= "AND b.singer = $singer_t ";
			}
			
			if($nice !== NULL)
			{
				$sql_postfix .= "AND b.nice = $nice ";
			}
			
			if($key) //TODO:资源数较少，暂时使用like进行查询，若数据量大或者访问量大，后期将使用搜索引擎
			{
				$key_t = $this->db->escape('%'.$key.'%');
				$sql_postfix .= "AND b.name like $key_t ";
			}

			if($approve_stat !== NULL)
			{
				$sql_postfix .= "AND b.approve_stat in ( $approve_stat ) ";
			}
		}
		else if($topic_id)
		{
			$sql_prefix = "SELECT b.id,b.name,b.mime, b.tag, b.nice,b.duration, b.size,b.author, b.singer, b.url, b.refcount,b.remark,b.create_date,b.refresh_date,b.approve_stat,b.approver, b.approve_date 
							FROM cs_resource_ring_topic_link a LEFT JOIN cs_resource_ring b ON a.ring_id = b.id ";
			$sql_count_prefix = "SELECT count(b.id) as t 
							FROM cs_resource_ring_topic_link a LEFT JOIN cs_resource_ring b ON a.ring_id = b.id ";
			$sql_postfix = "WHERE a.topic_id = $topic_id ";
			
			if($singer)
			{
				$singer_t = $this->db->escape($singer);
				$sql_postfix .= "AND b.singer = $singer_t ";
			}
			
			if($nice !== NULL)
			{
				$sql_postfix .= "AND b.nice = $nice ";
			}
			
			if($key) //TODO:资源数较少，暂时使用like进行查询，若数据量大或者访问量大，后期将使用搜索引擎
			{
				$key_t = $this->db->escape('%'.$key.'%');
				$sql_postfix .= "AND b.name like $key_t ";
			}

			if($approve_stat !== NULL)
			{
				$sql_postfix .= "AND b.approve_stat in ( $approve_stat ) ";
			}
		}
		else 
		{
			$sql_prefix = "SELECT id,name,mime, tag, nice,duration, size,author, singer, url, refcount,remark,create_date,refresh_date,approve_stat,approver, approve_date 
							FROM cs_resource_ring ";
			$sql_count_prefix = "SELECT count(id) as t 
							FROM cs_resource_ring ";
			
			if($notag || $singer || $nice !== NULL || $key || $approve_stat !== NULL)
			{
				$sql_postfix .= "WHERE ";
				
				$b_condition = false;
				if($singer)
				{
					$singer_t = $this->db->escape($singer);
					$sql_postfix .= "singer = $singer_t ";
					$b_condition = true;
				}
				
				if($nice !== NULL)
				{
					if($b_condition)
					{
						$sql_postfix .= "AND ";
					}
					
					$sql_postfix .= "nice = $nice ";
					$b_condition = true;
				}
				
				if($notag)
				{
					if($b_condition)
					{
						$sql_postfix .= "AND ";
					}
				
					$sql_postfix .= "tag = '' ";
					$b_condition = true;
				}

				if($key)
				{
					if($b_condition)
					{
						$sql_postfix .= "AND ";
					}
					
					$key_t = $this->db->escape('%'.$key.'%');
					$sql_postfix .= "name like $key_t ";
					$b_condition = true;
				}
				
				if($approve_stat !== NULL)
				{
					if($b_condition)
					{
						$sql_postfix .= "AND ";
					}
					
					$sql_postfix .= "approve_stat in ( $approve_stat ) ";
					$b_condition = true;
				}
			}		
		}
		
		$sql = $sql_prefix.$sql_postfix;
		$sql_count = $sql_count_prefix.$sql_postfix;
		
		if($order == "hot")
		{
			$sql .= "ORDER BY refcount desc ";
		}
		else if($order == "latest")
		{
			$sql .= "ORDER BY create_date desc ";
		}
		
		$sql .= "LIMIT $pos, $size;";
		
		$this->_append_ring_result($sql, $ret);
		$query = $this->db->query($sql_count);
		$result = $query->result_array(FALSE);
		
		return array("total"=>$result[0]['t'],"data"=>$ret);		
	}

	public function tag_ring()
	{
		$sql = "SELECT name,cover, num FROM cs_resource_ring_tag ORDER BY sequence";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		foreach ($result as &$ret)
		{
			$ret['num'] = (int)$ret['num'];
		}
		return $result;
	}
	
	public function modi_tag_ring($tag, $new_tag, $cover, $merge)
	{
		$tag_t = $this->db->escape($tag);
		
		//先修改封面
		if(isset($cover))
		{
			$cover_t = $this->db->escape($cover);
			$sql = "UPDATE cs_resource_ring_tag set cover = $cover_t WHERE name = $tag_t";
			$this->db->query($sql);
		}
		
		if($new_tag)
		{
			$new_tag_t = $this->db->escape($new_tag);
			$tag_info = array();
			$new_tag_info = array();
			
			$sql = "SELECT id, name,cover, sequence, num, approved_num FROM cs_resource_ring_tag WHERE name IN ($tag_t, $new_tag_t)";
			$query = $this->db->query($sql);
			$result = $query->result_array(FALSE);
			foreach ($result as $tmp)
			{
				if($tmp['name'] == $tag)
				{
					$tag_info = $tmp;
				}
				else if($tmp['name'] == $new_tag)
				{
					$new_tag_info = $tmp;
				}
			}
			
			if(!$tag_info)
			{
				return array("result"=>400,"msg"=>"tag不存在");
			}
			
			if(!$new_tag_info) //新tag不存在，直接替换该tag
			{
				$sql = "UPDATE cs_resource_ring_tag set name = $new_tag_t WHERE name = $tag_t";
				$this->db->query($sql);
				
				$sql = "UPDATE cs_resource_ring_tag_link SET tag_name = $new_tag_t WHERE tag_name = $tag_t";
				$this->db->query($sql);
				
				$sql = "SELECT b.id, b.tag FROM cs_resource_ring_tag_link a LEFT JOIN cs_resource_ring b on a.ring_id = b.id WHERE a.tag_name = $new_tag_t";
				$query = $this->db->query($sql);
				$result = $query->result_array(FALSE);
				foreach ($result as $tmp)
				{
					$tmp['tag'] = $tmp['tag'].",";
					$tmp_str = substr(str_replace($tag.',', $new_tag.',', $tmp['tag']),0,-1);
					
					$tmp_str_t = $this->db->escape($tmp_str);
					$sql = "UPDATE cs_resource_ring SET tag = $tmp_str_t WHERE id = ".$tmp['id'];
					$this->db->query($sql);
				}
			}
			else 
			{
				if(!$merge)
				{
					return array("result"=>400,"msg"=>"新tag已经存在");
				}
				
				$sql = "DELETE FROM cs_resource_ring_tag WHERE name = $tag_t";
				$this->db->query($sql);
				
				$sql = "UPDATE cs_resource_ring_tag_link SET tag_name = $new_tag_t WHERE tag_name = $tag_t";
				$this->db->query($sql);
				
				$sql = "SELECT b.id, b.tag,b.approve_stat FROM cs_resource_ring_tag_link a LEFT JOIN cs_resource_ring b on a.ring_id = b.id WHERE a.tag_name = $new_tag_t";
				$query = $this->db->query($sql);
				$result = $query->result_array(FALSE);
				$num = count($result);
				$approved_num = 0;
				foreach ($result as $tmp)
				{
					if((int)$tmp['approve_stat'] == 2)
					{
						$approved_num++;
					}
					
					$tmp['tag'] = $tmp['tag'].",";
					$tmp['tag'] = str_replace($tag.',', '', $tmp['tag']);
					$tmp['tag'] = str_replace($new_tag.',', '', $tmp['tag']);
					$tmp['tag'].= $new_tag;
	
					$tmp_str_t = $this->db->escape($tmp['tag']);
					$sql = "UPDATE cs_resource_ring SET tag = $tmp_str_t WHERE id = ".$tmp['id'];
					$this->db->query($sql);
				}
				
				$sql = "UPDATE cs_resource_ring_tag SET num = $num, approved_num = $approved_num WHERE name = $new_tag_t";
				$this->db->query($sql);
			}			
		}
		
		return array("result"=>200,"msg"=>NULL);
	}
	
	public function del_tag_ring($tag, $force)
	{
		$tag_t = $this->db->escape($tag);
		//tag是否存在
		$sql = "SELECT num FROM cs_resource_ring_tag WHERE name = $tag_t";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		if(!$result)
		{
			return array("result"=>200, "msg"=>NULL);
		}
			
		if((int)$result[0]['num'] > 0)
		{
			if(!$force)
			{
				return array("result"=>400, "msg"=>"tag下还有文件");
			}
			else 
			{
				//整理相关文件的tag信息
				$sql = "SELECT tag_name, ring_id FROM cs_resource_ring_tag_link WHERE ring_id IN 
						(SELECT ring_id FROM cs_resource_ring_tag_link WHERE tag_name = $tag_t)";
				$query = $this->db->query($sql);
				$result = $query->result_array(FALSE);
				$new_tag_info = array();
				foreach ($result as $tmp)
				{
					if(!$new_tag_info['i_'.$tmp['ring_id']])
					{
						$new_tag_info['i_'.$tmp['ring_id']] = array('id'=>(int)$tmp['ring_id'],'tag'=>array());
					}
					
					if($tmp['tag_name'] == $tag)
					{
						continue;
					}
					
					$new_tag_info['i_'.$tmp['ring_id']]['tag'][] = $tmp['tag_name'];
				}
				
				foreach ($new_tag_info as $tmp)
				{
					$sql = "UPDATE cs_resource_ring SET tag = '".implode(',',$tmp['tag'])."' WHERE id = ".$tmp['id'];
					$this->db->query($sql);
				}
				
				//删除link信息
				$sql = "DELETE FROM cs_resource_ring_tag_link WHERE tag_name = $tag_t";
				$this->db->query($sql);
			}
		}
		
		//清理tag记录
		$sql = "DELETE FROM cs_resource_ring_tag WHERE name = $tag_t";
		$this->db->query($sql);
		
		return array("result"=>200, "msg"=>NULL);
	}	
	
	public function add_ring_topic($name, $cover, $desc, $sequence)
	{
		$name_t = $this->db->escape($name);
		$cover_t = $this->db->escape($cover);
		$desc_t = $this->db->escape($desc);
		$sql = "INSERT INTO cs_resource_ring_topic (`name`, cover, `desc`, num, sequence) VALUES ($name_t, $cover_t, $desc_t,0,$sequence)";
		
		$query = $this->db->query($sql);
		$lastid = $query->insert_id();
		
		return array('id'=>$lastid, "name"=>$name, "cover"=>$cover, "desc"=>$desc,"num"=>0, "sequence"=>$sequence);
	}
	
	public function modi_ring_topic($id, $name, $cover, $desc, $sequence)
	{
		$sql = "SELECT * FROM cs_resource_ring_topic WHERE id = $id";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		
		if(!$result)
		{
			return array("result"=>400, "msg"=>"专题不存在");
		}
		
		$modi_arr = array();
		if($name)
		{
			$modi_arr[] = "`name` = ".$this->db->escape($name);
		}
		else 
		{
			$name = $result[0]['name'];
		}		
		
		if($cover)
		{
			$modi_arr[] = "cover = ".$this->db->escape($cover);
		}
		else
		{
			$cover = $result[0]['cover'];
		}
		
		if(isset($desc))
		{
			$modi_arr[] = "`desc` = ".$this->db->escape($desc);
		}
		else
		{
			$desc = $result[0]['desc'];
		}
		
		if($sequence)
		{
			$modi_arr[] = "sequence = $sequence";
		}
		else
		{
			$sequence = (int)$result[0]['sequence'];
		}
		
		if($modi_arr)
		{
			$sql = "UPDATE cs_resource_ring_topic SET ".implode(',',$modi_arr)." WHERE id = $id";
			$this->db->query($sql);
		}
		
		return array("result"=>200, "msg"=>array("id"=>$id, "name"=>$name, "cover"=>$cover, "desc"=>$desc,"num"=>(int)$result[0]['num'], "sequence"=>$sequence));
	}
	
	public function del_ring_topic($id, $force)
	{
		//如果不为强制删除,则先判断该专题下是否有内容
		if(!$force)
		{
			$sql = "SELECT ring_id FROM cs_resource_ring_topic_link WHERE topic_id = $id LIMIT 1";
			$query = $this->db->query($sql);
			$result = $query->result_array(FALSE);
			
			if($result)
			{
				return array("result"=>400, "msg"=>"专题下还有内容，不能删除");
			}
		}
		else 
		{
			$sql = "DELETE FROM cs_resource_ring_topic_link WHERE topic_id = $id";
			$this->db->query($sql);
		}
		
		//删除当前专题
		$sql = "DELETE FROM cs_resource_ring_topic WHERE id = $id";
		$this->db->query($sql);
		
		return array("result"=>200, "msg"=>NULL);
	}
	
	public function list_ring_topic()
	{
		$sql = "SELECT id, `name`, cover, `desc`, num, sequence FROM cs_resource_ring_topic ORDER BY sequence ASC";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		foreach ($result as &$ret)
		{
			$ret['id'] = (int)$ret['id'];
			$ret['num'] = (int)$ret['num'];
			$ret['sequence'] = (int)$ret['sequence'];
		}
		return $result;	
	}
	
	public function add_topic_rings($id, $ring_arr)
	{
		//判断专题是否存在
		$sql = "SELECT id FROM cs_resource_ring_topic WHERE id = $id LIMIT 1";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
			
		if(!$result)
		{
			return array("result"=>400, "msg"=>"专题不存在");
		}
		
		//获取能够添加的ring_id, ring_id必须已审核且没有被添加过
		$sql = "SELECT id FROM cs_resource_ring WHERE id IN (".implode(',',$ring_arr).") AND approve_stat = 2";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		if(!$result)
		{
			return array("result"=>400, "msg"=>"没有符合条件的铃声");
		}
		
		$id_arr = array();
		foreach ($result as $tmp)
		{
			$id_arr[] = (int)$tmp['id'];
			$sql = "REPLACE INTO cs_resource_ring_topic_link (topic_id, ring_id) VALUES ($id, ".$tmp['id'].")";
			$this->db->query($sql);
		}
		
		//更新专题铃声计数
		$this->_refresh_topic_rings_num($id);
		
		$id_arr = array_unique($id_arr);
		return array("result"=>200, "msg"=>array('id'=>$id, "ring_ids"=>$id_arr));
	}
	
	public function del_topic_rings($id, $ring_arr)
	{
		$sql = "DELETE FROM cs_resource_ring_topic_link WHERE topic_id = $id AND ring_id in (".implode(',',$ring_arr).")";
		$this->db->query($sql);
		$this->_refresh_topic_rings_num($id);
		return array("result"=>200, "msg"=>NULL);
	}
	
	public function add_show($uid, $name, $tag, $nice, $image_id, $ring_id,$label, $remark,$approve)
	{
		//判断ring_id 和 image_id是否存在且已审核
		$sql = "SELECT id,mime,url FROM cs_resource_image WHERE id = $image_id AND approve_stat = 2";
		$query = $this->db->query($sql);
		$img_result = $query->result_array(FALSE);
		if(!$img_result)
		{
			return array("result"=>400,"msg"=>"image id 不合法");
		}
		
		$sql = "SELECT id,name,mime,url, duration FROM cs_resource_ring WHERE id = $ring_id AND approve_stat = 2";
		$query = $this->db->query($sql);
		$ring_result = $query->result_array(FALSE);
		if(!$ring_result)
		{
			return array("result"=>400,"msg"=>"ring id 不合法");
		}
		
		$tag_info = array();
		//添加来电秀
		$tag = $tag?$tag:"";
		$newtagary = $this->_unique_tag($tag);	
		$nice = $nice?$nice:0;
		$label = $label?$label:"";
		$remark = $remark?$remark:"";

		$refcount = 0;
		$curtime = time();
		$approve_stat = 0;
		$approver = 0;
		$approve_date = 0;
		$xiaomi_show_id = 0;
		if($approve)
		{
			$approve_stat = 2;
			$approver = $uid;
			$approve_date = $curtime;
		}
		
		$name_t = $this->db->escape($name);
		$tag_t = $this->db->escape($tag);
		$label_t = $this->db->escape($label);
		$remark_t = $this->db->escape($remark);	
		$sql = "INSERT INTO cs_resource_show (name,tag, nice, image_id, ring_id,label, author, refcount, remark, create_date, refresh_date, approve_stat, approver, approve_date,xiaomi_show_id) 
				VALUES ($name_t,$tag_t,$nice, $image_id, $ring_id,$label_t, $uid, $refcount, $remark_t, $curtime, $curtime, $approve_stat, $approver, $approve_date,$xiaomi_show_id)";		
		$query = $this->db->query($sql);
		$lastid = $query->insert_id();
			
		if($lastid)
		{
			//更新tag信息
			if($newtagary)
			{					
				foreach ($newtagary as $tmp)
				{
					if(!$tag_info[$tmp])
					{
						$tag_info[$tmp] = array('all'=>0,'approve'=>0);
					}
					if($approve)
					{
						$tag_info[$tmp]['approve'] = $tag_info[$tmp]['approve']+1;
					}
					$tag_info[$tmp]['all'] = $tag_info[$tmp]['all']+1;
					
					$tmp_t = $this->db->escape($tmp);
					$sql = "INSERT INTO cs_resource_show_tag_link (tag_name, show_id) 
							VALUES ($tmp_t, $lastid)";
					$this->db->query($sql);
				}				
			}
			
			//修改tag计数
			$this->_add_tag_num($tag_info, 'cs_resource_show_tag');
			
			//若为精品秀且已审核,则发布到推荐秀中
			if($nice && $approve)
			{
				$xiaomo_id = Kohana::config('uap.xiaomo');
				$tret = Callshow_Model::instance()->create(
														$xiaomo_id,
														0,
														array('refid'=>$ring_id,'name'=>$ring_result[0]['name'],'mime'=>$ring_result[0]['mime'],'url'=>$ring_result[0]['url'],'duration'=>(int)$ring_result[0]['duration']),
														array('refid'=>$image_id, 'mime'=>$img_result[0]['mime'], 'url'=>$img_result[0]['url']),
														$label,
														NULL,
														$lastid
													);
				if($tret['result'] == 200)
				{
					Callshow_Model::instance()->recommend($xiaomo_id,$tret['msg'][0]['id']);
					$xiaomi_show_id = (int)$tret['msg'][0]['id'];
					$sql = "UPDATE cs_resource_show SET xiaomi_show_id = ".$xiaomi_show_id." WHERE id = $lastid";
					$this->db->query($sql);
				}
			}
			
			return array("result"=>200,"msg"=>array(
														'id'=>$lastid,
														'name'=>$name,
														'tag'=>$tag,
														'nice'=>$nice,
														'ring'=>array('id'=>$ring_id,'name'=>$ring_result[0]['name'],'mime'=>$ring_result[0]['mime'],'url'=>$ring_result[0]['url'],'duration'=>(int)$ring_result[0]['duration']),
														'image'=>array('id'=>$image_id, 'mime'=>$img_result[0]['mime'], 'url'=>$img_result[0]['url']),
														'label'=>$label,
														'author'=>array('uid'=>$uid),
														'remark'=>$remark,
														'refcount'=>$refcount,
														'create_date'=>$curtime,
														'refresh_date'=>$curtime,
														'approve_stat'=>$approve_stat,
														'approver'=>$approver,
														'approve_date'=>$approve_date,
														'xiaomi_show_id'=>$xiaomi_show_id
													));
		}
		else
		{
			return array("result"=>500,"msg"=>"新增失败");
		}		
	}
	
	public function modi_show($uid, $data_ary)
	{
		$ret_id = array();
		$tag_info = array();
		$image_dict = array();
		$ring_dict = array();
		foreach ($data_ary as $data)
		{
			if(!$data['id'])
			{
				continue;
			}
			
			$sql = "SELECT id,approve_stat FROM cs_resource_show WHERE id = ".$data['id'];
			$query = $this->db->query($sql);
			$result = $query->result_array(FALSE);
			if(!$result)
			{
				continue;
			}
			$cur_stat = (int)$result[0]['approve_stat']; 
						
			$sql_subary = array();
			if($data['name'])
			{
				$sql_subary[] = " name = ".$this->db->escape($data['name']);
			}
			
			$newtagary = array();
			if(isset($data['tag']))
			{
				$tag = $data['tag'];
				$newtagary = $this->_unique_tag($tag);
				$sql_subary[] = " tag = ".$this->db->escape($tag);
			}
			
			if(isset($data['nice']))
			{
				$sql_subary[] = " nice = ".$data['nice'];
			}
			
			if($data['image_id'])
			{
				$img_result = array();
				if(isset($image_dict['i_'.$data['image_id']]))
				{
					$img_result = $image_dict['i_'.$data['image_id']];
				}
				else 
				{
					//判断image_id是否合法
					$sql = "SELECT id,mime,url FROM cs_resource_image WHERE id = ".$data['image_id']." AND approve_stat = 2";
					$query = $this->db->query($sql);
					$img_result = $query->result_array(FALSE);
					if(!$img_result)
					{
						$img_result = array();
					}
					else 
					{
						$img_result = $img_result[0];
					}
					$image_dict['i_'.$data['image_id']] = $img_result;
				}
				
				if(!$img_result)
				{
					continue;
				}
				
				$sql_subary[] = " image_id = ".$data['image_id'];
			}

			if($data['ring_id'])
			{
				$ring_result = array();
				if(isset($ring_dict['r_'.$data['ring_id']]))
				{
					$ring_result = $ring_dict['r_'.$data['ring_id']];
				}
				else 
				{
					//判断ring_id是否合法
					$sql = "SELECT id,name,mime,url,duration FROM cs_resource_ring WHERE id = ".$data['ring_id']." AND approve_stat = 2";
					$query = $this->db->query($sql);
					$ring_result = $query->result_array(FALSE);
					if(!$ring_result)
					{
						$ring_result = array();
					}
					else 
					{
						$ring_result = $ring_result[0];
					}
					$ring_dict['r_'.$data['ring_id']] = $ring_result;
				}
				
				if(!$ring_result)
				{
					continue;
				}
				
				$sql_subary[] = " ring_id = ".$data['ring_id'];
			}
			
			if(isset($data['remark']))
			{
				$sql_subary[] = " remark = ".$this->db->escape($data['remark']);
			}
			
			if(isset($data['label']))
			{
				$sql_subary[] = " label = ".$this->db->escape($data['label']);
			}
			
			$curtime = time();
			//以上内容为更新项目
			if($sql_subary)
			{
				$sql_subary[] = " refresh_date = ".$curtime;
			}
			
			if($data['approve'])
			{
				$sql_subary[] = " approve_stat = 2";
				$sql_subary[] = " approver = ".$uid;
				$sql_subary[] = " approve_date = ".$curtime;
			}
			else
			{
				if($sql_subary || isset($data['approve']))
				{
					if($cur_stat == 2)
					{
						$sql_subary[] = " approve_stat = 1";
						$sql_subary[] = " approver = 0";
						$sql_subary[] = " approve_date = 0";
					}
				}
			}
			
			if($sql_subary)
			{
				$sql = "UPDATE cs_resource_show SET ".implode(',',$sql_subary)." WHERE id = ".$data['id'];
				$this->db->query($sql);
				
				$ret_id[] = $data['id'];
				
				if(isset($data['tag']))
				{
					$tmp_tagary = array();
					foreach ($newtagary as $tmp)
					{
						$tmp_tagary[$tmp] = 1;
						if(!$tag_info[$tmp])
						{
							$tag_info[$tmp] =  array('all'=>0,'approve'=>0);
						}
						if($data['approve'])
						{
							$tag_info[$tmp]['approve'] = $tag_info[$tmp]['approve']+1;	
						}
						$tag_info[$tmp]['all'] = $tag_info[$tmp]['all']+1;
					}
					
					//更新tag信息
					$sql = "SELECT tag_name, show_id FROM cs_resource_show_tag_link WHERE show_id = ".$data['id'];
					$query = $this->db->query($sql);
					$result = $query->result_array(FALSE);
					foreach ($result as $tmp)
					{
						if(!$tag_info[$tmp['tag_name']])
						{
							$tag_info[$tmp['tag_name']] =  array('all'=>0,'approve'=>0);
						}
						if($cur_stat == 2)
						{
							$tag_info[$tmp['tag_name']]['approve'] = $tag_info[$tmp['tag_name']]['approve']-1;	
						}
						$tag_info[$tmp['tag_name']]['all'] = $tag_info[$tmp['tag_name']]['all']-1;	
							
						if(!$tmp_tagary[$tmp['tag_name']])
						{
							$sql = "DELETE FROM cs_resource_show_tag_link WHERE tag_name = ".$this->db->escape($tmp['tag_name'])." AND show_id = ".$data['id'];
							$this->db->query($sql);
						}
						else
						{
							$tmp_tagary[$tmp['tag_name']] = 0;
						}
					}
					
					foreach ($tmp_tagary as $k => $v)
					{
						if($v)
						{
							$k_t = $this->db->escape($k);
							$sql = "INSERT INTO cs_resource_show_tag_link (tag_name, show_id) VALUES ($k_t, ".$data['id'].")";
							$this->db->query($sql);
						}
					}					
				}
				else 
				{
					$increment = 0;
					if($data['approve'] && $cur_stat !== 2 )
					{
						$increment = 1;
					}
					else if(!$data['approve'] && $cur_stat == 2)
					{
						$increment = -1;
					}
					
					if($increment)
					{
						$sql = "SELECT tag_name, show_id FROM cs_resource_show_tag_link WHERE show_id = ".$data['id'];
						$query = $this->db->query($sql);
						$result = $query->result_array(FALSE);
						foreach ($result as $tmp)
						{
							if(!$tag_info[$tmp['tag_name']])
							{
								$tag_info[$tmp['tag_name']] =  array('all'=>0,'approve'=>0);
							}
							$tag_info[$tmp['tag_name']]['approve'] = $tag_info[$tmp['tag_name']]['approve'] + $increment;	
						}
					}
				}
			}
		}
		
		$this->_add_tag_num($tag_info, 'cs_resource_show_tag');
		
		//更新ring信息和image信息
		$sql = "SELECT * FROM cs_resource_show WHERE id in (".implode(',',$ret_id).")";
		$ret = array();
		$this->_append_show_result($sql, $ret, $image_dict, $ring_dict);
		
		$xiaomo_id = Kohana::config('uap.xiaomo');
		//更新来电秀推荐列表
		foreach ($ret as &$tmp)
		{
			if($tmp['xiaomi_show_id'])
			{
				Callshow_Model::instance()->cancel_recommend($xiaomo_id,$tmp['xiaomi_show_id']);
				
				$sql = "UPDATE cs_resource_show SET xiaomi_show_id = 0 WHERE id = ".$tmp['id'];
				$this->db->query($sql);
				
				Callshow_Model::instance()->del($xiaomo_id,$tmp['xiaomi_show_id']);
			}
			
			if((int)$tmp['approve_stat'] == 2 && $tmp['nice'])
			{
				$img = $tmp['image'];
				$img['refid'] = $img['id'];
				$ring = $tmp['ring'];
				$ring['refid'] = $ring['id'];
				//发布来电秀
				$tret = Callshow_Model::instance()->create($xiaomo_id,	0, $ring, $img,	$tmp['label'], NULL, $tmp['id']	);
				if($tret['result'] == 200)
				{
					$xiaomi_show_id = (int)$tret['msg'][0]['id'];
					$sql = "UPDATE cs_resource_show SET xiaomi_show_id = ".$xiaomi_show_id." WHERE id = ".$tmp['id'];
					$this->db->query($sql);
					$tmp['xiaomi_show_id'] = $xiaomi_show_id;
					
					Callshow_Model::instance()->recommend($xiaomo_id,$tmp['xiaomi_show_id']);	
				}
			}
		}
		
		return $ret;
	}
	
	public function del_show($uid, $data_ary)
	{
		$ret_ids = array();
		
		//查询哪些id符合删除条件
		$sql = "SELECT id FROM cs_resource_show WHERE id in (".implode(',',$data_ary).") AND approve_stat = 0";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		foreach ($result as $tmp)
		{
			$ret_ids[] = (int)$tmp['id'];
		}
				
		if($ret_ids)
		{
			//删除所有合法的id
			$sql = "DELETE FROM cs_resource_show WHERE id in (".implode(',',$ret_ids).")";
			$this->db->query($sql);
		
			$tag_info = array();
			//删除tag
			$sql = "SELECT tag_name, show_id FROM cs_resource_show_tag_link WHERE show_id in (".implode(',',$ret_ids).")";
			$query = $this->db->query($sql);
			$result = $query->result_array(FALSE);
			foreach ($result as $tmp)
			{
				if(!$tag_info[$tmp['tag_name']])
				{
					$tag_info[$tmp['tag_name']] = array('all'=>0,'approve'=>0);
				}

				$tag_info[$tmp['tag_name']]['all'] = $tag_info[$tmp['tag_name']]['all']-1;		
			}
			
			$sql = "DELETE FROM cs_resource_show_tag_link WHERE show_id in (".implode(',',$ret_ids).")";
			$this->db->query($sql);
			
			$this->_add_tag_num($tag_info, 'cs_resource_show_tag');
		}
		
		return array('ids'=>$ret_ids);			
	}
	
	public function search_show($uid, $key,$tag, $notag,$nice,$order, $pos, $size, $approve_stat)
	{
		$ret = array();
		$image_dict = array();
		$ring_dict = array();
		
		$sql_prefix = "";
		$sql_count_prefix = "";
		$sql_postfix = "";
		if($tag)
		{
			$sql_prefix = "SELECT b.id,b.name, b.tag, b.nice,b.image_id, b.ring_id,b.label,b.author, b.refcount,b.remark,b.create_date,b.refresh_date,b.approve_stat,b.approver, b.approve_date,b.xiaomi_show_id 
							FROM cs_resource_show_tag_link a LEFT JOIN cs_resource_show b ON a.show_id = b.id ";
			$sql_count_prefix = "SELECT count(b.id) as t 
							FROM cs_resource_show_tag_link a LEFT JOIN cs_resource_show b ON a.show_id = b.id ";
			$tag_t = $this->db->escape($tag);
			$sql_postfix = "WHERE a.tag_name = $tag_t ";
			
			if($nice !== NULL)
			{
				$sql_postfix .= "AND b.nice = $nice ";
			}
			
			if($key) //TODO:资源数较少，暂时使用like进行查询，若数据量大或者访问量大，后期将使用搜索引擎
			{
				$key_t = $this->db->escape('%'.$key.'%');
				$sql_postfix .= "AND b.name like $key_t ";
			}

			if($approve_stat !== NULL)
			{
				$sql_postfix .= "AND b.approve_stat in ( $approve_stat ) ";
			}
		}
		else 
		{
			$sql_prefix = "SELECT id,name, tag, nice,image_id, ring_id,label,author, refcount,remark,create_date,refresh_date,approve_stat,approver, approve_date,xiaomi_show_id 
							FROM cs_resource_show ";
			$sql_count_prefix = "SELECT count(id) as t 
							FROM cs_resource_show ";
			
			if($notag || $nice !== NULL || $key || $approve_stat !== NULL)
			{
				$sql_postfix .= "WHERE ";
				
				$b_condition = false;
				if($nice !== NULL)
				{
					if($b_condition)
					{
						$sql_postfix .= "AND ";
					}
					
					$sql_postfix .= "nice = $nice ";
					$b_condition = true;
				}
				
				if($notag)
				{
					if($b_condition)
					{
						$sql_postfix .= "AND ";
					}
				
					$sql_postfix .= "tag = '' ";
					$b_condition = true;
				}

				if($key)
				{
					if($b_condition)
					{
						$sql_postfix .= "AND ";
					}
					
					$key_t = $this->db->escape('%'.$key.'%');
					$sql_postfix .= "name like $key_t ";
					$b_condition = true;
				}
				
				if($approve_stat !== NULL)
				{
					if($b_condition)
					{
						$sql_postfix .= "AND ";
					}
					
					$sql_postfix .= "approve_stat in ( $approve_stat ) ";
					$b_condition = true;
				}
			}		
		}
		
		$sql = $sql_prefix.$sql_postfix;
		$sql_count = $sql_count_prefix.$sql_postfix;
		
		if($order == "hot")
		{
			$sql .= "ORDER BY refcount desc ";
		}
		else if($order == "latest")
		{
			$sql .= "ORDER BY create_date desc ";
		}
		
		$sql .= "LIMIT $pos, $size;";
		
		$this->_append_show_result($sql, $ret, $image_dict, $ring_dict);
		$query = $this->db->query($sql_count);
		$result = $query->result_array(FALSE);
		
		return array("total"=>$result[0]['t'],"data"=>$ret);		
	}
	
	public function tag_show()
	{
		$sql = "SELECT name, num FROM cs_resource_show_tag ORDER BY sequence";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		foreach ($result as &$ret)
		{
			$ret['num'] = (int)$ret['num'];
		}
		return $result;
	}
	
	public function del_tag_show($tag, $force)
	{
		//tag是否存在
		$sql = "SELECT num FROM cs_resource_show_tag WHERE name = '$tag'";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		if(!$result)
		{
			return array("result"=>200, "msg"=>NULL);
		}
			
		if((int)$result[0]['num'] > 0)
		{
			if(!$force)
			{
				return array("result"=>400, "msg"=>"tag下还有文件");
			}
			else 
			{
				//整理相关文件的tag信息
				$sql = "SELECT tag_name, show_id FROM cs_resource_show_tag_link WHERE show_id IN 
						(SELECT show_id FROM cs_resource_show_tag_link WHERE tag_name = '$tag')";
				$query = $this->db->query($sql);
				$result = $query->result_array(FALSE);
				$new_tag_info = array();
				foreach ($result as $tmp)
				{
					if(!$new_tag_info['i_'.$tmp['show_id']])
					{
						$new_tag_info['i_'.$tmp['show_id']] = array('id'=>(int)$tmp['show_id'],'tag'=>array());
					}
					
					if($tmp['tag_name'] == $tag)
					{
						continue;
					}
					
					$new_tag_info['i_'.$tmp['show_id']]['tag'][] = $tmp['tag_name'];
				}
				
				foreach ($new_tag_info as $tmp)
				{
					$sql = "UPDATE cs_resource_show SET tag = '".implode(',',$tmp['tag'])."' WHERE id = ".$tmp['id'];
					$this->db->query($sql);
				}
				
				//删除link信息
				$sql = "DELETE FROM cs_resource_show_tag_link WHERE tag_name = '$tag'";
				$this->db->query($sql);
			}
		}
		
		//清理tag记录
		$sql = "DELETE FROM cs_resource_show_tag WHERE name = '$tag'";
		$this->db->query($sql);
		
		return array("result"=>200, "msg"=>NULL);
	}	
	
	private function _unique_tag(&$tag)
	{
		$newtagary = array();
		if($tag)
		{
			$tagary = explode(',',$tag);				
			foreach ($tagary as $tmp)
			{
				$tmp = trim($tmp);
				if($tmp)
				{
					$newtagary[] = $tmp;
				}
			}
			if($newtagary)
			{
				$newtagary = array_unique($newtagary);											
			}
			
			$tag = implode(',',$newtagary);	
		}
		return $newtagary;
	}
	
	private function _add_tag_num($tag_info, $table_name)
	{
		foreach ($tag_info as $k=>$v)
		{
			if($v['all'] || $v['approve'])
			{
				$sql = "SELECT name FROM $table_name WHERE name = '$k' LIMIT 1";
				$query = $this->db->query($sql);
				$result = $query->result_array(FALSE);
				if($result)
				{
					$sql = "UPDATE $table_name SET num=num+".$v['all'].",approved_num=approved_num+".$v['approve']." WHERE name = '$k'";
					$this->db->query($sql);
				}
				else
				{
					$sql = "INSERT INTO $table_name (name, num,approved_num)
							VALUES ('$k', ".$v['all'].", ".$v['approve'].")";
					$this->db->query($sql);
				}
			}
		}
	}
	
	private function _append_image_result($sql, &$ret)
	{
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		foreach ($result as $tmp)
		{
			$tmp['resolution'] = array("pix_x"=>(int)$tmp['pix_x'],"pix_y"=>(int)$tmp['pix_y']);
			unset($tmp['pix_x']);
			unset($tmp['pix_y']);
			$tmp['author'] = array('uid'=>(int)$tmp['author']);
			unset($tmp['author']);
			$tmp['id'] = (int)$tmp['id'];
			$tmp['nice'] = (int)$tmp['nice'];
			$tmp['size'] = (int)$tmp['size'];
			$tmp['refcount'] = (int)$tmp['refcount'];
			$tmp['create_date'] = (int)$tmp['create_date'];
			$tmp['refresh_date'] = (int)$tmp['refresh_date'];
			$tmp['approve_stat'] = (int)$tmp['approve_stat'];
			$tmp['approver'] = (int)$tmp['approver'];
			$tmp['approve_date'] = (int)$tmp['approve_date'];
			$ret[] = $tmp;
		}		
	}
	
	private function _append_ring_result($sql, &$ret)
	{
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		foreach ($result as $tmp)
		{
			$tmp['author'] = array('uid'=>(int)$tmp['author']);
			unset($tmp['author']);
			$tmp['id'] = (int)$tmp['id'];
			$tmp['duration'] = (int)$tmp['duration'];
			$tmp['nice'] = (int)$tmp['nice'];
			$tmp['size'] = (int)$tmp['size'];
			$tmp['refcount'] = (int)$tmp['refcount'];
			$tmp['create_date'] = (int)$tmp['create_date'];
			$tmp['refresh_date'] = (int)$tmp['refresh_date'];
			$tmp['approve_stat'] = (int)$tmp['approve_stat'];
			$tmp['approver'] = (int)$tmp['approver'];
			$tmp['approve_date'] = (int)$tmp['approve_date'];
			$ret[] = $tmp;
		}
	}
	
	private function _append_show_result($sql, &$ret, &$image_dict, &$ring_dict)
	{
		$img_ary = array();
		$ring_ary = array();
		
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		foreach ($result as $tmp)
		{
			if(!isset($image_dict['i_'.$tmp['image_id']]))
			{
				$img_ary[] = (int)$tmp['image_id'];
			}
			if(!isset($ring_dict['r_'.$tmp['ring_id']]))
			{
				$ring_ary[] = (int)$tmp['ring_id'];
			}
		}
		
		//获取残缺的image 信息
		if($img_ary)
		{
			$sql = "SELECT id,mime,url FROM cs_resource_image WHERE id in (".implode(',',$img_ary).")";
			$tquery = $this->db->query($sql);
			$tresult = $tquery->result_array(FALSE);
			foreach ($tresult as $tmp)
			{
				$image_dict['i_'.$tmp['id']] = $tmp;
			}
		}
		
		//获取残缺的ring 信息
		if($ring_ary)
		{
			$sql = "SELECT id,name,mime,url,duration FROM cs_resource_ring WHERE id in (".implode(',',$ring_ary).")";
			$tquery = $this->db->query($sql);
			$tresult = $tquery->result_array(FALSE);
			foreach ($tresult as $tmp)
			{
				$ring_dict['r_'.$tmp['id']] = $tmp;
			}
		}
		
		foreach ($result as $tmp)
		{
			if(!$image_dict['i_'.$tmp['image_id']])
			{
				continue; 
			}
			if(!$ring_dict['r_'.$tmp['ring_id']])
			{
				continue; 
			}
			$tmp['image'] = array('id'=>(int)$image_dict['i_'.$tmp['image_id']]['id'], 'mime'=>$image_dict['i_'.$tmp['image_id']]['mime'],'url'=>$image_dict['i_'.$tmp['image_id']]['url']);
			$tmp['ring'] = array('id'=>(int)$ring_dict['r_'.$tmp['ring_id']]['id'],'mime'=>$ring_dict['r_'.$tmp['ring_id']]['mime'],'name'=>$ring_dict['r_'.$tmp['ring_id']]['name'],'url'=>$ring_dict['r_'.$tmp['ring_id']]['url'],'duration'=>(int)$ring_dict['r_'.$tmp['ring_id']]['duration']);
			unset($tmp['image_id']);
			unset($tmp['ring_id']);
			$tmp['author'] = array('uid'=>(int)$tmp['author']);
			unset($tmp['author']);
			$tmp['id'] = (int)$tmp['id'];
			$tmp['nice'] = (int)$tmp['nice'];
			$tmp['refcount'] = (int)$tmp['refcount'];
			$tmp['create_date'] = (int)$tmp['create_date'];
			$tmp['refresh_date'] = (int)$tmp['refresh_date'];
			$tmp['approve_stat'] = (int)$tmp['approve_stat'];
			$tmp['approver'] = (int)$tmp['approver'];
			$tmp['approve_date'] = (int)$tmp['approve_date'];
			$tmp['xiaomi_show_id'] = (int)$tmp['xiaomi_show_id'];
			$ret[] = $tmp;
		}
	}
	
	private function _refresh_topic_rings_num($id)
	{
		$sql = "SELECT count(1) AS total FROM cs_resource_ring_topic_link WHERE topic_id = $id";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		
		if($result)
		{
			$sql = "UPDATE cs_resource_ring_topic SET num = ".$result[0]['total']." WHERE id = $id";
			$this->db->query($sql);
			return (int)$result[0]['total'];
		}
		return 0;
	}
}