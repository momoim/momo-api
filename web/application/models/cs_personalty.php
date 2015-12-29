<?php
class Cs_Personalty_Model extends Model {
	
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
			self::$instance = new Cs_Personalty_Model();
		}
		return self::$instance;
	}
	
	public function __construct() {
		parent::__construct ();
	}
	
	//搜索图片
	public function search_image($uid, $pos, $size)
	{
		$sql = "SELECT mime, url FROM cs_personalty_image WHERE uid = $uid ORDER BY create_time DESC LIMIT $pos,$size";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		return $result;
	}
	
	//搜索视频
	public function search_video($uid, $pos, $size)
	{
		$sql = "SELECT mime, url,duration, snap_mime,snap_url FROM cs_personalty_video WHERE uid = $uid ORDER BY create_time DESC LIMIT $pos,$size";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		foreach ($result as &$ret)
		{
			$ret['snapshot'] = array('mime'=>$ret['snap_mime'], 'url'=>$ret['snap_url']);
			$ret['duration'] = (int)$ret['duration'];
			unset($ret['snap_mime']);
			unset($ret['snap_url']);
		}
		
		return $result;
	}
	
	//搜索铃音
	public function search_ring($uid, $pos, $size)
	{
		$sql = "SELECT rid,name, mime, duration, url FROM cs_personalty_ring WHERE uid = $uid ORDER BY create_time DESC LIMIT $pos,$size";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		foreach ($result as &$ret)
		{
			$ret['rid'] = (int)$ret['rid'];
			$ret['duration'] = (int)$ret['duration'];
		}
		return $result;
	}
	
	//全部铃音
	public function all_ring($timestamp, $size)
	{
		$sql = "SELECT rid, uid, name, mime, duration, url,create_time FROM cs_personalty_ring WHERE create_time < $timestamp ORDER BY create_time DESC LIMIT $size";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		
		$user_model = User_Model::instance();
		$user_info = array();
		foreach ($result as &$ret)
		{
			$ret['rid'] = (int)$ret['rid'];
			$ret['duration'] = (int)$ret['duration'];
			$ret['create_time'] = (int)$ret['create_time'];
			if(!$user_info['user_'.$ret['uid']])
			{
				$info = $user_model->get_user_info($ret['uid']);
				$user_info['user_'.$ret['uid']] = array("uid"=>(int)$info['uid'],"nickname"=>$info['nickname'],"avatar"=>sns::getavatar((int)$ret['uid']));
			}
			$ret['owner'] = $user_info['user_'.$ret['uid']];
			unset($ret['uid']);
		}
		return $result;
	}
	
	//添加图片
	public function add_image($uid, $mime, $url, $create_time = NULL)
	{
		if($uid && $mime && $url)
		{
			$iden_code = $uid.'_'.md5($url);
			//判断该资源是否已经存在于资源库中
			$sql = "SELECT rid FROM cs_personalty_image WHERE identifier = '$iden_code'";
			$query = $this->db->query($sql);
			$result = $query->result_array(FALSE);	
			if(!$result)
			{
				if(!$create_time)
				{
					$create_time = time();
				}
				
				$mime_t = $this->db->escape($mime);
				$url_t = $this->db->escape($url);
				//添加到图片库中
				$sql = "INSERT INTO cs_personalty_image (identifier,uid,mime,url,create_time,update_time) VALUES ('$iden_code', $uid, $mime_t, $url_t, $create_time,$create_time)";	
				$this->db->query($sql);
			}
		}
	}
	
	//添加视频
	public function add_video($uid, $mime, $url, $duration, $snap_mime, $snap_url, $create_time = NULL)
	{
		if($uid && $mime && $url && $duration && $snap_mime && $snap_url)
		{
			$iden_code = $uid.'_'.md5($url);
			//判断该资源是否已经存在于资源库中
			$sql = "SELECT rid FROM cs_personalty_video WHERE identifier = '$iden_code'";
			$query = $this->db->query($sql);
			$result = $query->result_array(FALSE);	
			if(!$result)
			{
				if(!$create_time)
				{
					$create_time = time();
				}
				
				$mime_t = $this->db->escape($mime);
				$url_t = $this->db->escape($url);
				$snap_mime_t = $this->db->escape($snap_mime);
				$snap_url_t = $this->db->escape($snap_url);
				//添加到图片库中
				$sql = "INSERT INTO cs_personalty_video (identifier,uid,mime,url,duration, snap_mime, snap_url,create_time,update_time) VALUES ('$iden_code', $uid, $mime_t, $url_t,$duration, $snap_mime_t, $snap_url_t, $create_time,$create_time)";	
				$this->db->query($sql);
			}
		}
	}	
	
	//添加铃声
	public function add_ring($uid, $name, $mime, $url, $duration, $create_time = NULL)
	{
		if($uid && $name && $mime && $url && $duration)
		{
			$iden_code = $uid.'_'.md5($url);

			//判断该资源是否已经存在于资源库中
			$sql = "SELECT rid FROM cs_personalty_ring WHERE identifier = '$iden_code'";
			$query = $this->db->query($sql);
			$result = $query->result_array(FALSE);	
			if(!$result)
			{
				if(!$create_time)
				{
					$create_time = time();
				}
				
				$name_t = $this->db->escape($name);
				$mime_t = $this->db->escape($mime);
				$url_t = $this->db->escape($url);
				//添加到铃声库中
				$sql = "INSERT INTO cs_personalty_ring (identifier,uid,name,mime,duration,url,create_time,update_time) VALUES ('$iden_code', $uid, $name_t, $mime_t, $duration, $url_t, $create_time,$create_time)";	
				$this->db->query($sql);
			}
		}		
	}
	
	public function modi_ring($uid, $rid, $name, $mime, $duration, $url)
	{
		//判断能否修改该铃声
		$sql = "SELECT rid FROM cs_personalty_ring WHERE rid = $rid AND uid = $uid LIMIT 1";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		if(!$result)
		{
			return array("result"=>400, "msg"=>"铃声不存在或者不属于您");
		}
		
		$sql_arr = array();
		if($name)
		{
			$sql_arr[] = "name=".$this->db->escape($name);
		}
		
		if($mime)
		{
			$sql_arr[] = "mime=".$this->db->escape($mime);
		}
		
		if($duration)
		{
			$sql_arr[] = "duration=$duration";
		}
		
		if($url)
		{
			$sql_arr[] = "url=".$this->db->escape($url);
		}
		
		$sql_arr[] = "update_time=".time();
		
		$sql = "UPDATE cs_personalty_ring SET ".implode(',',$sql_arr)." WHERE rid = $rid";
		$this->db->query($sql);

		return array("result"=>200, "msg"=>"");
	}
	
	//重建用户资源库
	public function rebuild($pos, $size)
	{
		$sql = "SELECT * FROM cs_history WHERE id >$pos AND id <= ".($pos + $size);
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		foreach ($result as $ret)
		{
			//添加图片-当前只加入非系统的部分
			if((int)$ret['image_refid'] == 0)
			{
				$this->add_image((int)$ret['creator'], $ret['image_mime'], $ret['image_url'], (int)$ret['create_time'] );
			}
			
			//添加视频-当前只加入非系统部分
			if((int)$ret['video_refid'] == 0)
			{
				$this->add_video((int)$ret['creator'], $ret['video_mime'], $ret['video_url'], (int)$ret['video_duration'], $ret['video_snap_mime'], $ret['video_snap_url'], (int)$ret['create_time'] );
			}
			
			//添加铃声-当前只加入非系统的部分
			if((int)$ret['ring_refid'] == 0)
			{
				$this->add_ring((int)$ret['creator'], $ret['ring_name'], $ret['ring_mime'], $ret['ring_url'], (int)$ret['ring_duration'], (int)$ret['create_time'] );
			}
		}
		return "";
	}

}