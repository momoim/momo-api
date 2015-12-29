<?php
defined('SYSPATH') or die('No direct script access.');
/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * 联系人分组模型文件
 */
/**
 * 联系人分组模型器
 */
class Graph_Model extends Model {

	/**
	 * 实例
	 * @var Graph_Model
	 */
	protected static $instance;

	protected $result = FALSE;

	protected $user_id = 0;

	protected $guid = '';

	protected $url;

	/**
	 * 单例模式
	 * @return Graph_Model
	 */
	public static function &instance()
	{
		if (! isset(self::$instance))
		{
			// Create a new instance
			self::$instance = new Graph_Model();
		}
		return self::$instance;
	}

	/**
	 * 构造函数,
	 * 为了避免循环实例化，请尽量调用单例模式
	 */
	public function __construct()
	{
		//必须继承父类控制器
		parent::__construct();
	}

	/**
	 * 上传文件
	 * @param string $file
	 * @return bool
	 */
	public function upload($file)
	{
		$res = $this->upload_file_total($file);
		if ($res['result'] == 200)
		{
			return $res['md5'];
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * 增加设备上传文件历史
	 * @param string $md5 MD5
	 * @param string $guid GUID
	 * @param int $client_id 来源
	 * @return Database_Result
	 */
	public function add_device_history($md5, $guid, $client_id)
	{
		$this->url = url::base(FALSE) . 'graph/download/' . $md5;
		$this->guid = $guid;
		$this->result = $this->db->insert('cs_contact_device_history',
			array('file_md5'  => $md5,
			      'guid'      => $guid,
			      'client_id' => $client_id,
			      'dateline'  => time()
			));
		return $this->result;
	}

	/**
	 * 检查设备GUID是否存在
	 * @param string $guid
	 * @return bool
	 */
	public function check_device($guid)
	{
		if ($guid)
		{
			$sql = sprintf('SELECT id FROM cs_contact_device_history WHERE guid = %s LIMIT 1',
				$this->db->escape($guid));
			$query = $this->db->query($sql);
			if ($query->count())
			{
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * 解压缩文件
	 * @param $data
	 * @return string
	 */
	/*
	function gzdecode($data)
	{
		$flags = ord(substr($data, 3, 1));
		$header_len = 10;
		if ($flags & 4)
		{
			$extra_len = unpack('v', substr($data, 10, 2));
			$extra_len = $extra_len[1];
			$header_len += 2 + $extra_len;
		}
		if ($flags & 8) // Filename
		{
			$header_len = strpos($data, chr(0), $header_len) + 1;
		}
		if ($flags & 16) // Comment
		{
			$header_len = strpos($data, chr(0), $header_len) + 1;
		}
		if ($flags & 2) // CRC at end of file
		{
			$header_len += 2;
		}
		$unpacked = @gzinflate(substr($data, $header_len));
		if ($unpacked === FALSE)
		{
			$unpacked = $data;
		}
		return $unpacked;
	}
	*/

	/**
	 * 增加联系人数据
	 * @param string $file
	 * @param string $guid
	 * @return bool|int
	 */
	/*
	public function add_contact_data($file, $guid)
	{
		$uncompressed_data = $this->gzdecode(file_get_contents($file));
		$contacts = array();
		if ($uncompressed_data)
		{
			$arr = json_decode($uncompressed_data, TRUE);
			$data = ! empty($arr['data']) ? $arr['data'] : '';
			if (is_array($data))
			{
				foreach ($data as $val)
				{
					$family_name = isset($val['family_name']) ? $val['family_name'] : '';
					$given_name = isset($val['given_name']) ? $val['given_name'] : '';
					$middle_name = isset($val['middle_name']) ? $val['middle_name'] : '';
					$prefix = isset($val['prefix']) ? $val['prefix'] : '';
					$suffix = isset($val['suffix']) ? $val['suffix'] : '';

					$formatted_name = Contact_Helper::name_to_formatted_name($family_name,
						$given_name,
						$prefix, $middle_name, $suffix
					);
					foreach ($val['tels'] as $tel)
					{
						if ($valid = international::check_mobile($tel['value'], '86'))
						{
							$contacts[$valid['country_code'] . $valid['mobile']] = $formatted_name;
						}
					}
				}
			}
		}
		if ($contacts)
		{
			$time = time();
			$sql = sprintf('REPLACE INTO cs_contact_device (guid, dateline, data) VALUES (%s, %s, %s)',
				$this->db->escape($guid),
				$time,
				$this->db->escape(json_encode($contacts))
			);
			if($this->db->query($sql)) {
				return $time;
			}
			return FALSE;
		}
		return FALSE;
	}
	*/

	/**
	 * 上传手机号码
	 * @param int $user_id 用户ID
	 * @param string $url 备份URL
	 * @param string $reason 备份原因
	 * @param int $appid 应用ID
	 * @param string $device_id 设备ID
	 * @param int $client_id 来源
	 * @param string $guid GUID
	 * @return bool
	 */
	public function update($user_id, $url, $reason, $appid = 0, $device_id = '', $client_id = 0, $guid = '')
	{
		$this->user_id = $user_id;
		$this->guid = $guid;
		$this->url = $url;
		$this->result = $this->db->insert('cs_contact_history',
			array('uid'       => $user_id,
			      'appid'     => $appid,
			      'device_id' => $device_id,
			      'url'       => $url,
			      'reason'    => $reason,
			      'client_id' => $client_id,
			      'guid'      => $guid,
			      'dateline'  => time()
			));
		return $this->result;
	}

	/**
	 * 析构同时发送MQ到后端
	 */
	public function __destruct()
	{
		if ($this->result)
		{
			$mq_msg = array(
				'kind' => 'callshow_contact',
				'data' => array('uid' => $this->user_id, 'url' => $this->url, 'guid' => $this->guid)
			);

			$this->mq_send(json_encode($mq_msg), 'queue_callshow_contact', 'amq.direct');
		}
	}

	/**
	 * 获取我的所有联系人手机号码和名字
	 * @param int $uid 用户ID
	 * @param int|array $show_status 来电秀状态 0 未开通 1 已开通 2 被开通 3 体验用户 多种状态用数组例如 array(1,2)、空数组所有状态
	 * @return mixed
	 */
	public function get_contact_by_uid($uid, $show_status = array())
	{
		return $this->_get_contact_from_graph('uid', $uid, $show_status);
	}

	/**
	 * 获取我的所有联系人手机号码和名字
	 * @param string $guid 唯一ID
	 * @param int|array $show_status 来电秀状态 0 未开通 1 已开通 2 被开通 3 体验用户 多种状态用数组例如 array(1,2)、空数组所有状态
	 * @return array
	 */
	public function get_contact_by_device($guid, $show_status = array())
	{
		return $this->_get_contact_from_graph('guid', $guid, $show_status);
	}

	/**
	 * 获取我的所有联系人手机号码和名字
	 * @param string $type ID类型 uid或guid
	 * @param string $id ID
	 * @param int|array $show_status 来电秀状态 0 未开通 1 已开通 2 被开通 3 体验用户 多种状态用数组例如 array(1,2)、空数组所有状态
	 * @return array
	 */
	private function _get_contact_from_graph($type, $id, $show_status = array())
	{
		$contacts = array();
		if (in_array($type, array('uid', 'guid'), TRUE) AND $id)
		{
			$queryTemplate =
				"start user=node:node_auto_index({$type}='{$id}') " .
					"match user-[relationship:Knows]->contact ";

			$where = array();
			if (! is_array($show_status) OR ! empty($show_status))
			{
				$statuses = (array) $show_status;
				foreach ($statuses as $status)
				{
					$where[] = " contact.show = {$status} ";
				}
			}
			$queryTemplate .= 'where relationship.block? = 0 ';
			if ($where)
			{
				$queryTemplate .= 'and (' . implode('or', $where) . ') ';
			}
			$queryTemplate .= "return relationship,contact";
			$result = $this->_graph_query($queryTemplate);
			if ($result)
			{
				foreach ($result as $row)
				{
					/** @var Everyman\Neo4j\PropertyContainer[] $row */
					$contacts[] = array(
						'uid'    => $row['contact']->getProperty('uid'),
						'mobile' => $row['contact']->getProperty('mobile'),
						'name'   => $row['relationship']->getProperty('name'),
						'show'   => $row['contact']->getProperty('show'),
						'time'   => (int) $row['relationship']->getProperty('time'),
					);
				}
			}
		}
		return $contacts;
	}

	/**
	 * 设置黑名单
	 * @param int $id ID
	 * @param string $mobile 手机号码
	 * @param int $status 状态
	 * @param string $id_type ID类型
	 * @return int
	 */
	public function set_block($id, $mobile, $status, $id_type = 'uid')
	{
		$queryTemplate =
			"start user=node:node_auto_index({$id_type}='{$id}'), friend=node:node_auto_index(mobile='{$mobile}') " .
				"match user-[relationship:Knows]->friend " .
				"set relationship.block = {$status} " .
				"return relationship";

		$result = $this->_graph_query($queryTemplate);

		if ($result)
		{
			if (isset($result[0]['relationship']) && $result[0]['relationship'] !== NULL)
			{
				return 1;
			}
			else
			{
				return 0;
			}
		}
		else
		{
			return -1;
		}
//		$queryTemplate = "
//		start user=node:node_auto_index('uid:{$uid}'),friend=node:node_auto_index('mobile:{$mobile}')
//		return user,friend";
//
//		$result = $this->_graph_query($queryTemplate);
//		if ($result)
//		{
//			$log = 'user graph id = '. (isset($result[0]['user']) ? $result[0]['user']->getId(): '');
//			$log .= 'friend graph id = '. (isset($result[0]['friend']) ? $result[0]['friend']->getId(): '');
//			api::log('error', $log, 'graph');
//
//			if (isset($result[0]['user']) AND $result[0]['user'] instanceof Everyman\Neo4j\Node
//				AND isset($result[0]['friend']) AND $result[0]['friend'] instanceof Everyman\Neo4j\Node
//			)
//				/** @var Everyman\Neo4j\Node[] $result[0] */
//			{
//				$relationships = $result[0]['user']->getRelationships();
//				foreach ($relationships as $r)
//				{
//					if ($r->getEndNode()->getId() == $result[0]['friend']->getId())
//					{
//						$r->setProperty('block', $status)->save();
//						api::log('error', 'set block '. $status .' ok', 'graph');
//						return true;
//					}
//				}
//				api::log('error', 'no in relationship!'. 'set block '. $status .' ok', 'graph');
//				return false;
//			}
//			else
//			{
//				return false;
//			}
//		}
//		else
//		{
//			return null;
//		}
	}

	/**
	 * 获取黑名单
	 * @param int $id ID
	 * @param string $id_type ID类型
	 * @return array
	 */
	public function block_list($id, $id_type)
	{
		$queryTemplate =
			"start user=node:node_auto_index({$id_type}='{$id}')
				match user-[relationship:Knows]->contact
				where relationship.block! = 1
				return relationship,contact";
		$result = $this->_graph_query($queryTemplate);
		$contacts = array();
		if ($result)
		{
			foreach ($result as $row)
			{
				/** @var Everyman\Neo4j\PropertyContainer[] $row */
				$contacts[] = $row['contact']->getProperty('mobile');
//				$contacts[] = array(
//					'uid'    => $row['contact']->getProperty('uid'),
//					'mobile' => $row['contact']->getProperty('mobile'),
//					'name'   => $row['relationship']->getProperty('name'),
//					'show'   => $row['contact']->getProperty('show'),
//					'time'   => (int) $row['relationship']->getProperty('time'),
//				);
			}
		}
		return $contacts;
	}

	private function _graph_query($queryTemplate)
	{
		try
		{
			require APPPATH . 'vendor/neo4jphp/bootstrap.php';
			$client = new Everyman\Neo4j\Client(Kohana::config('graph.host'), Kohana::config('graph.port'),
				array('timeout' => Kohana::config('graph.timeout')));
			$query = new Everyman\Neo4j\Cypher\Query($client, $queryTemplate);
			$result = $query->getResultSet();
			return $result;
		} catch (Everyman\Neo4j\Exception $e)
		{
			//索引不存在或者出现其他异常
			api::log('error', 'query ' . $queryTemplate . ' error! ' . $e->getTraceAsString(), 'graph');
		}
		return false;
	}

	/**
	 * 获取我的所有联系人手机号码和名字
	 * @param string $guid
	 * @return mixed
	 */
	public function get_file_by_device($guid)
	{
		$sql = sprintf("SELECT file_md5 FROM cs_contact_device_history
		WHERE guid = %s ORDER BY id DESC LIMIT 1"
			, $this->db->escape($guid));
		$query = $this->db->query($sql);
		if ($query->count())
		{
			$result = $query->result_array(FALSE);
			$this->get_file_by_md5($result[0]['file_md5']);
		}
	}

	/**
	 * 根据MD5返回文件
	 * @param $md5
	 */
	public function get_file_by_md5($md5)
	{
		$info = $this->get_info_by_md5($md5);
		$this->down($info['ndfs_id'], 'contact.gz', $md5);
	}

	/**
	 * 根据md5查询文件记录
	 * @param string $md5 文件md5
	 * @return array 文件记录信息
	 */
	public function get_info_by_md5($md5)
	{
		$sql = "SELECT id, cflag, ndfs_id FROM cs_contact_file WHERE md5= '$md5' LIMIT 1";
		$query = $this->db->query($sql);
		if ($query->count())
		{
			$result = $query->result_array(FALSE);
			return $result[0];
		}
		return array();
	}

	//新增文件信息
	public function add_file_info($md5, $size, $ndfs_id, $cflag)
	{
		$refcount = 1;
		$sql = "INSERT INTO cs_contact_file(md5, size, ndfs_id, cflag, refcount, create_time, finish_time)
		VALUE('$md5', '$size', '$ndfs_id', '$cflag', '$refcount', now(),now())";
		return $this->db->query($sql);
	}

	//修改文件信息
	public function update_file_info($md5, $cflag, $refcount_sum)
	{
		$sql = "UPDATE cs_contact_file SET cflag = $cflag, refcount = refcount + $refcount_sum WHERE md5 = '$md5'";
		return $this->db->query($sql);
	}

	//文件上传完成
	public function file_finish($md5)
	{
		$sql = "UPDATE cs_contact_file SET cflag = 1 WHERE md5 = '$md5'";
		return $this->db->query($sql);
	}

	public function upload_file_total($path)
	{
		$file_md5 = md5_file($path);
		$file_size = abs(filesize($path));
		$ndfs_id = '';
		$cflag = 0;

		//根据MD5判断文件是否已存在
		$result = $this->get_info_by_md5($file_md5);
		if ($result)
		{
			$ndfs_id = $result['ndfs_id'];
			$cflag = $result['cflag'];
		}

		//写文件
		if ($cflag == 0) //文件不存在或者未上传完
		{
			$cflag = 1;
			//写文件
			$ret = $this->write($ndfs_id, $path, 0);

			if ($ret['result'] !== 200)
			{
				Kohana::log("error", $ret['msg']);
				return $ret;
			}

			if (! $result)
			{
				$this->add_file_info($file_md5, $file_size, $ret['ndfs_id'], $cflag);
			}
			else
			{
				$this->update_file_info($file_md5, $cflag, 1);
			}
		}
		else
		{
			$this->update_file_info($file_md5, $cflag, 1);
		}
		return array("result" => 200, 'md5' => $file_md5);
	}

	/**
	 * 写文件
	 * @param string $ndfs_id 文件在ndfs中的id
	 * @param string $file 文件
	 * @param int $offset 偏移
	 * @return array
	 */
	public function write($ndfs_id, $file, $offset = 0)
	{
		$inh = fopen($file, 'rb');
		if (! $inh)
		{
			return array("result" => "500", "msg" => "can not open tmp file");
		}

		$result = $this->_ndfs_open($ndfs_id, NDFS_ACCESS_WRITE);
		if ($result['result'] !== 200)
		{
			return $result;
		}

		$session = $result['session'];
		$fh = $result['fd'];
		$ndfs_id = $result['info']['id'];

		$ndfs_config = Kohana::config('graph.ndfs');
		$write_once_size = $ndfs_config['write_once_size'];

		while (! feof($inh))
		{
			$data = fread($inh, $write_once_size);
			if (strlen($data) !== 0)
			{
				$r = ndfs_write_file($session, $fh, $offset, strlen($data), $data);
				if ($r['status'] !== 0)
				{
					fclose($inh);
					return array("result" => 500,
					             "msg"    =>
					             'ndfs_write_file: ' . $r['status'] . ', offset = ' . $offset . ', fh =' . $fh .
						             ', session = ' . $session . ',ndfs_id=' . $ndfs_id
					);
				}
			}

			$offset += strlen($data);
		}

		fclose($inh);

		$r = ndfs_query_file_info($session, $fh);
		if ($r['status'] !== 0)
		{
			return array("result" => 500, "msg" => 'ndfs_query_file_info: ' . $r['status']);
		}

		ndfs_close_file($session, $fh);
		ndfs_close_session($session);

		return array("result" => 200, 'ndfs_id' => $ndfs_id, 'info' => $r);
	}

	/**
	 * 下载文件
	 * @param int $ndfs_id 文件在ndfs中的id
	 * @param $file_name
	 * @param $file_md5
	 * @return array
	 */
	public function down($ndfs_id, $file_name, $file_md5)
	{
		$result = $this->_ndfs_open($ndfs_id, NDFS_ACCESS_READ);
		if ($result['result'] !== 200)
		{
			return $result;
		}

		$session = $result['session'];
		$fh = $result['fd'];
		$file_size = $result['info']['size'];

		$off = 0;
		$end = $file_size - 1;

		header("Accept-Ranges: bytes");
		header("Content-Type: " . $this->get_mime_type($file_name));
		header("Content-Disposition: attachment; filename=" . $file_name . ";md5=" . $file_md5);

		//断点续传支持
		if (! isset($_SERVER['HTTP_RANGE']))
		{
			header("Content-Range: bytes 0-$end/$file_size");
			header("Content-length: $file_size");
		}
		// 断点续传支持
		else
		{
			$range = $_SERVER['HTTP_RANGE'];
			if (empty($range))
			{
				header("HTTP/1.1 400 Bad Request");
				exit();
			}
			else
			{
				// 计算下载位置
				$pos = strpos($range, '=');
				if ($pos)
				{
					$range = substr($range, $pos + 1);
				}
				list($off, $end) = explode('-', $range);
				if ($end == '')
				{
					$end = - 1;
				}
				if ($off == '')
				{
					$off = - 1;
				}
				$off = floatval($off);
				$end = floatval($end);

				if ($end < 0 || $end >= $file_size)
				{
					$end = $file_size - 1;
				}
				if ($off >= $file_size || $off < 0)
				{
					$off = 0;
				}

				// 部分下载
				header("HTTP/1.1 206 Partial Content");
				header("Content-Range: bytes $off-$end/$file_size");
				$size = $end - $off + 1;
				header("Content-length: $size");
			}
		}

		while (TRUE)
		{
			if ($end <= $off)
			{
				break;
			}

			$r = ndfs_read_file($session, $fh, $off, $end - $off + 1);
			if ($r['status'] !== 0)
			{ //错误终断
				return array("result" => 500, "msg" => 'ndfs_read_file: ' . $r['status']);
			}

			$length = strlen($r['read_content']);

			if ($length > 0)
			{
				$off += $length;
				echo $r['read_content'];
				@ob_flush();
				@flush();
			}
			else
			{
				break;
			}
		}

		ndfs_close_file($session, $fh);
		ndfs_close_session($session);
		exit();
	}

	/**
	 * 将一个文件读到本地文件系统上
	 * @param int $ndfs_id
	 * @param string $path
	 * @return array
	 */
	public function read_2_local_fs($ndfs_id, $path)
	{
		$result = $this->_ndfs_open($ndfs_id, NDFS_ACCESS_READ);
		if ($result['result'] !== 200)
		{
			return $result;
		}

		$session = $result['session'];
		$fh = $result['fd'];
		$file_size = $result['info']['size'];

		$off = 0;
		$end = $file_size - 1;

		$lfile = fopen($path, "wb");

		while (TRUE)
		{
			if ($end <= $off)
			{
				break;
			}

			$r = ndfs_read_file($session, $fh, $off, $end - $off + 1);
			if ($r['status'] !== 0)
			{ //错误终断
				return array("result" => 500, "msg" => 'ndfs_read_file: ' . $r['status']);
			}

			$length = strlen($r['read_content']);

			if ($length > 0)
			{
				$off += $length;
				fwrite($lfile, $r['read_content']);

			}
			else
			{
				break;
			}
		}

		fclose($lfile);
		ndfs_close_file($session, $fh);
		ndfs_close_session($session);

		return array("result" => 200, "msg" => "");
	}

	/**
	 * 创建连接并打开ndfs上的文件
	 * @param int $id 新创建时id为0
	 * @param string $mode 方式
	 * @return array
	 */
	private function _ndfs_open($id, $mode)
	{
		if ($id < 0)
		{
			return array("result" => 404, "msg" => 'id < 0 ');
		}

		$ndfs_config = Kohana::config('graph.ndfs');

		//创建ndfs会话
		$r = ndfs_create_session($ndfs_config['hostname'], $ndfs_config['port']);
		if ($r['status'] != 0)
		{
			return array("result" => 500, "msg" => 'ndfs_create_session: ' . $r['status']);
		}
		$session = $r["session"];

		$r = ndfs_login($session, $ndfs_config['username'], $ndfs_config['password']);
		if ($r['status'] != 0)
		{
			ndfs_close_session($session);
			return array("result" => 500, "msg" => 'ndfs_login: ' . $r['status']);
		}

		$r = null;
		if ($id == 0)
		{
			$r = ndfs_open_file_by_id($session, 0, $mode, 7, NDFS_FILE_CREATE, 0);
		}
		else
		{
			$r = ndfs_open_file_by_id($session, $id, $mode, 7, NDFS_FILE_OPEN, 0);
		}

		if ($r['status'] != 0)
		{
			return array("result" => 500, "msg" => 'ndfs_open_by_id(w): ' . $r['status']);
		}
		$fd = $r['fd'];

		$r = ndfs_query_file_info($session, $fd);
		if ($r['status'] !== 0)
		{
			return array("result" => 500, "msg" => 'ndfs_query_file_info: ' . $r['status']);
		}

		return array("result" => 200, "fd" => $fd, "session" => $session, "info" => $r);
	}

	/**
	 * 获取文件MIME
	 * @param string $file_name 文件名
	 * @return string
	 */
	public static function get_mime_type($file_name)
	{
		$file_ext = strtolower(substr($file_name, strrpos($file_name, '.') + 1));
		if ($file_ext == 'gz')
		{
			return 'application/x-gzip';
		}
		return 'application/octet-stream';
	}
}
