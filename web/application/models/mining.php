<?php
defined('SYSPATH') or die('No direct script access.');
/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * 联系人分组模型文件
 */
/**
 * 联系人分组模型器
 */
class Mining_Model extends Model {

	/**
	 * 实例
	 * @var Mining_Model
	 */
	protected static $instance;

	/**
	 * 单例模式
	 * @return Mining_Model
	 */
	public static function &instance()
	{
		if (! isset(self::$instance))
		{
			// Create a new instance
			self::$instance = new Mining_Model();
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
	 * 根据设备IMEI获取用户91ID
	 * @param string $imei
	 * @return bool
	 */
	public function get_imid_by_imei($imei)
	{
		$uid = $this->get_uid_by_imei($imei);
		if ($imei)
		{
			return $this->get_imid_by_uid($uid);
		}
		return 0;
	}

	/**
	 * 根据IMEI获取用户ID
	 * @param $imei
	 * @return int
	 */
	public function get_uid_by_imei($imei)
	{
		if ($imei)
		{
			$sql = sprintf('SELECT ost_usa_id_ref AS uid FROM oauth_server_token WHERE ost_device_id = %s LIMIT 1',
				$this->db->escape($imei));
			$query = $this->db->query($sql);
			if ($query->count())
			{
				$result = $query->result(FALSE);
				return isset($result[0]['uid']) ? $result[0]['uid'] : 0;
			}
		}
		return 0;
	}


	/**
	 * 根据用户ID获取91ID
	 * @param int $uid 用户ID
	 * @return int
	 */
	public function get_imid_by_uid($uid)
	{
		if ($uid)
		{
			$sql = sprintf('SELECT imid FROM members WHERE uid = %s LIMIT 1',
				$this->db->escape($uid));
			$query = $this->db->query($sql);
			if ($query->count())
			{
				$result = $query->result(FALSE);
				$imid = isset($result[0]['imid']) ? $result[0]['imid'] : 0;
				if ($imid)
				{
					return $imid;
				}
			}
		}
		return 0;
	}

	/**
	 * 根据用户ID获取设备ID
	 * @param $uid
	 * @return array
	 */
	public function get_imei_by_uid($uid)
	{
		if ($uid)
		{
			$sql = sprintf('SELECT ost_device_id AS device_id FROM oauth_server_token WHERE ost_usa_id_ref = %s',
				$this->db->escape($uid));
			$query = $this->db->query($sql);
			if ($query->count())
			{
				$result = $query->result(FALSE);
				$device_ids = array();
				foreach ($result as $val)
				{
					if (! empty($val['device_id']) AND
						is_numeric($val['device_id']) AND strlen($val['device_id']) == 15
					)
					{
						$device_ids[] = $val['device_id'];
					}
				}
				return $device_ids;
			}
		}
		return array();
	}

	/**
	 * 获取我的所有联系人
	 * @param int $uid 用户ID
	 * @param string $return_type 返回值类型
	 * @return mixed
	 */
	public function get_contact_by_uid($uid, $return_type = 'imid')
	{
		return $this->_get_contact_from_graph('uid', $uid, $return_type);
	}

	/**
	 * 获取我的所有联系人
	 * @param int $imid 91ID
	 * @param string $return_type 返回值类型
	 * @return array
	 */
	public function get_contact_by_imid($imid, $return_type = 'imid')
	{
		return $this->_get_contact_from_graph('imid', $imid, $return_type);
	}

	/**
	 * 根据91ID获取用户关系
	 * @param $from_imid
	 * @param $to_imid
	 * @return bool
	 */
	public function get_relationship_by_imid($from_imid, $to_imid)
	{
		return $this->_get_relationship('imid', $from_imid, $to_imid);
	}

	/**
	 * 根据IMEI获取关系
	 * @param $from_imei
	 * @param $to_imei
	 * @return bool
	 */
	public function get_relationship_by_imei($from_imei, $to_imei)
	{
		$from_uid = $this->get_uid_by_imei($from_imei);
		if ($from_uid)
		{
			$to_uid = $this->get_uid_by_imei($to_imei);
			if ($to_uid)
			{
				return $this->_get_relationship('uid', $from_uid, $to_uid);
			}
		}
		return FALSE;
	}

	/**
	 * 获取我的所有联系人手机号码和名字
	 * @param string $type ID类型 uid或imid
	 * @param mixed $from_id ID
	 * @param mixed $to_id 返回值类型
	 * @return array
	 */
	private function _get_relationship($type, $from_id, $to_id)
	{
		$count = 0;
		if (in_array($type, array('uid', 'imid'), TRUE) AND $from_id
			AND $to_id
		)
		{
			$queryTemplate =
				"start from=node:node_auto_index({$type}='{$from_id}'), to=node:node_auto_index({$type}='{$to_id}') " .
				"match from-[:KNOWS]->fof<-[:KNOWS]-to " .
				"return count(fof)";
			$result = $this->_graph_query($queryTemplate);
			if ($result)
			{
				$count = isset($result[0][0]) ? $result[0][0] : 0;
			}
		}
		return $count;
	}

	/**
	 * 获取我的所有联系人手机号码和名字
	 * @param string $type ID类型 uid或imid
	 * @param string $id ID
	 * @param string $return_type 返回值类型
	 * @return array
	 */
	private function _get_contact_from_graph($type, $id, $return_type = 'imid')
	{
		$contacts = array();
		if (in_array($type, array('uid', 'imid'), TRUE) AND $id
			AND in_array($return_type, array('imid', 'imei'), TRUE)
		)
		{
			$queryTemplate =
				"start user=node:node_auto_index({$type}='{$id}') " .
				"match user-[relationship:KNOWS]->contact " .
				"return contact";
			$result = $this->_graph_query($queryTemplate);
			if ($result)
			{
				foreach ($result as $row)
				{
					/** @var Everyman\Neo4j\PropertyContainer[] $row */

					if ($return_type == 'imid')
					{
						$imid = $row['contact']->getProperty('imid');
						if ($imid)
						{
							$contacts[] = array(
								'imid' => $imid,
							);
						}
					}
					elseif ($return_type == 'imei')
					{
						$uid = $row['contact']->getProperty('uid');
						if ($uid)
						{
							if ($imeis = $this->get_imei_by_uid($uid))
							{
								$imeis = array_unique($imeis);
								$imeis = array_filter($imeis,
									create_function('$v',
										'if($v == "000000000000000") { return false;} else {return true;}'));
								sort($imeis);
								$contacts[] = array(
									'imei' => $imeis
								);
							}
						}
					}
				}
			}
		}
		return $contacts;
	}

	/**
	 * 根据需求查询
	 * @param string $query 查询字符串
	 * @return array
	 */
	public function cypher($query)
	{
		$result = $this->_graph_query($query, 25);
		if ($result)
		{
			$cols = $result->getColumns();
			$return = array();
			foreach ($result as $i => $row)
			{
				/** @var Everyman\Neo4j\PropertyContainer[] $row */
				foreach ($cols as $col)
				{
					if ($row[$col] instanceof Everyman\Neo4j\Node)
					{
						/** @var Everyman\Neo4j\Node[] $row */
						$return[$i][$col]['meta']['id'] = $row[$col]->getId();
						$return[$i][$col]['property'] = $row[$col]->getProperties();
						if ($user_id = $row[$col]->getProperty('uid'))
						{
							$return[$i][$col]['property']['name'] = sns::getrealname($user_id);
							$return[$i][$col]['property']['avatar'] = sns::getavatar($user_id);
						}
					}
					elseif ($row[$col] instanceof Everyman\Neo4j\Relationship)
					{
						/** @var Everyman\Neo4j\Relationship[] $row */
						$return[$i][$col]['meta']['id'] = $row[$col]->getId();
						$return[$i][$col]['meta']['type'] = $row[$col]->getType();
						$return[$i][$col]['meta']['start_node'] = $row[$col]->getStartNode()->getId();
						$return[$i][$col]['meta']['end_node'] = $row[$col]->getEndNode()->getId();
						$return[$i][$col]['property'] = $row[$col]->getProperties();
					}
					elseif ($row[$col] instanceof Everyman\Neo4j\Query\Row)
					{
						foreach ($row[$col] as $j => $r)
						{
							if ($r instanceof Everyman\Neo4j\Node)
							{
								/** @var Everyman\Neo4j\Node[] $row */
								$return[$i][$col][$j]['meta']['id'] = $r->getId();
								$return[$i][$col][$j]['property'] = $r->getProperties();
								if ($user_id = $r->getProperty('uid'))
								{
									$return[$i][$col][$j]['property']['name'] = sns::getrealname($user_id);
									$return[$i][$col][$j]['property']['avatar'] = sns::getavatar($user_id);
								}
							}
							elseif ($r instanceof Everyman\Neo4j\Relationship)
							{
								/** @var Everyman\Neo4j\Relationship[] $row */
								$return[$i][$col][$j]['meta']['id'] = $row[$col]->getId();
								$return[$i][$col][$j]['meta']['type'] = $row[$col]->getType();
								$return[$i][$col][$j]['meta']['start_node'] = $row[$col]->getStartNode()->getId();
								$return[$i][$col][$j]['meta']['end_node'] = $row[$col]->getEndNode()->getId();

								$return[$i][$col][$j]['property'] = $r->getProperties();
							}
							else
							{
								$return[$i][$col][$j] = $r;
							}
						}
					}
					else
					{
						$return[$i][$col] = $row[$col];
					}
				}
			}
			return $return;
		}
		return false;
	}


	/**
	 * 查询
	 * @param $queryTemplate
	 * @param $timeout
	 * @return bool|Everyman\Neo4j\Query\Row|\Everyman\Neo4j\Query|\Everyman\Neo4j\Query\ResultSet
	 */
	private function _graph_query($queryTemplate, $timeout = 0)
	{
		try
		{
			require APPPATH . 'vendor/neo4jphp/bootstrap.php';
			$client = new Everyman\Neo4j\Client(Kohana::config('mining.host'), Kohana::config('mining.port'),
				array('timeout' => $timeout ? $timeout : Kohana::config('mining.timeout')));
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
}
