<?php
defined('SYSPATH') or die('No direct access allowed.');
/*
 * [MOMO API] (C)1999-2012 ND Inc.
 * 联系人数据映射库文件
 */
/**
 * 联系人数据映射类
 */
class Contact_Mapper implements Contact_Interface {

	/**
	 * 数据库连接
	 * @var DataBase
	 */
	protected $db;

	/**
	 * 数据库连接
	 * @var DataBase[]
	 */
	protected $db_instances = array();

	/**
	 * 实例
	 * @var Contact_Mapper
	 */
	protected static $instance;

	/**
	 * @var array 分表信息
	 */
	protected $divide_table = array();

	/**
	 * 单例模式
	 * @return Contact_Mapper 返回实例对象
	 */
	public static function &instance()
	{
		if (! isset(Contact_Mapper::$instance))
		{
			// Create a new instance
			Contact_Mapper::$instance = new Contact_Mapper();
		}
		return Contact_Mapper::$instance;
	}

	/**
	 * 构造方法
	 */
	public function __construct()
	{
	}

	/**
	 * 根据联系人ID查找联系人
	 * @param int $user_id 用户ID
	 * @param int $id      联系人ID
	 * @return Contact|bool 返回联系人对象
	 */
	public function find_by_id($user_id, $id)
	{
		//查询联系人主表
		$sql = sprintf("SELECT * FROM %s WHERE cid = %d LIMIT 1", $this->get_table($user_id, 'contacts'), $id);
		$query = $this->db->query($sql);
		if (! $query->count())
		{
			return FALSE;
		}
		else
		{
			$result = $query->result_array(FALSE);
			$row = $result[0];

			//@todo 待数据迁移后去除判断
			if (Kohana::config('contact.from_category_table'))
			{
				$row['category'] = $this->get_category_name_by_cid($user_id, $row['cid']);
			}

			//填充数据
			foreach (array_keys(Contact::$allow_cols) as $val)
			{
				$row[$val] = $this->get_info_list($user_id, $id, $val);
			}

			$contact = new Contact($row);
			return $contact;
		}
	}

	/**
	 * 根据时间戳和联系人ID查找联系人
	 * @param int $user_id 用户ID
	 * @param int $id      联系人ID
	 * @param int $dateline 快照ID
	 * @return Contact|bool 返回联系人对象
	 */
	public function find_by_dateline_id($user_id, $id, $dateline)
	{
		//查询联系人主表
		$sql = sprintf("SELECT * FROM %s WHERE uid = %d AND snapshot_id = %d AND cid = %d LIMIT 1",
			$this->get_table($user_id, 'contacts_snapshot', $dateline), $user_id, $dateline, $id);
		$query = $this->db->query($sql);
		if (! $query->count())
		{
			return FALSE;
		}
		else
		{
			$result = $query->result_array(FALSE);
			$row = $result[0];

			if (Kohana::config('contact.from_category_table'))
			{
				$row['category'] = $this->get_snapshot_category_name_by_cid($user_id, $row['cid'], $dateline);
			}

			//填充数据
			foreach (array_keys(Contact::$allow_cols) as $val)
			{
				$row[$val] = $this->get_snapshot_info_list($user_id, $id, $dateline, $val);
			}

			$contact = new Contact($row);
			return $contact;
		}
	}

	/**
	 * 根据用户ID查找联系人
	 * @param int $user_id 用户ID
	 * @return array 联系人信息数组
	 */
	public function find_by_user_id($user_id)
	{
		$table = $this->get_table($user_id, 'contacts');
		$multi_values = array('tels', 'emails');
		$cols = array_merge(Contact::get_list_fields(), $multi_values);
		$sql = sprintf("SELECT %s FROM %s WHERE uid = %d ORDER BY phonetic ASC", implode(',',
				Contact::get_list_fields(TRUE)), $table,
			$user_id);
		$query = $this->db->query($sql);
		$result = array();
		if ($query->count())
		{
			$rows = $query->result_array(FALSE);

			//@todo 待数据迁移后去除判断
			if (Kohana::config('contact.from_category_table'))
			{
				$contact_category_list = $this->get_contact_category_list($user_id);
				$category_list = $this->get_category_list($user_id);
			}
			foreach ($rows as $row)
			{
				$data = array();
				foreach ($cols as $col)
				{
					switch ($col)
					{
						case 'id':
							$data['id'] = (int) $row['cid'];
							break;
						case 'modified_at':
							$data['modified_at'] = $row['modified'] ? (int) $row['modified'] : (int) $row['created'];
							break;
						case 'source':
							$data['source'] = is_numeric($row['source']) ? api::get_source_name(
								$row['source']
							) : $row['source'];
							break;
						case 'tels':
						case 'emails':
							$data[$col] = array();
							break;
						default:
							$data[$col] = $row[$col];
							break;
					}
				}
				//@todo 待数据迁移后去除判断
				if (Kohana::config('contact.from_category_table'))
				{
					if (! empty($contact_category_list[$row['cid']]))
					{
						$category_names = array();
						foreach ($contact_category_list[$row['cid']] as $category_id)
						{
							$category_names[] = $category_list[$category_id];
						}
						$data['category'] = implode(',', $category_names);
					}
					else
					{
						$data['category'] = '';
					}
				}
				$result[(int) $row['cid']] = $data;
			}
		}
		return $result;
	}

	/**
	 * 根据用户ID获取回收站联系人列表
	 * @param int $user_id 用户ID
	 * @param int $offset  开始记录数
	 * @param int $limit   获取记录数
	 * @return array
	 */
	public function get_recycled_list($user_id, $offset = 0, $limit = 5000)
	{
		$cols = Contact::get_list_fields(FALSE, TRUE);
		$table = $this->get_table($user_id, 'contacts_recycled');

		$sql = sprintf("SELECT %s FROM %s WHERE uid = %d ORDER BY modified DESC LIMIT %d,%d",
			implode(',', Contact::get_list_fields(TRUE, TRUE)), $table,
			$user_id, $offset, $limit);
		$query = $this->db->query($sql);
		$result = array();
		if ($query->count())
		{
			//获取电话数据
			$tels = $this->get_info_list($user_id, NULL, 'tels', FALSE, TRUE,
				array('recycled_id') + Contact::$allow_cols['tels']);
			$tels_data = array();
			foreach ($tels as $tel)
			{
				$recycled_id = $tel['recycled_id'];
				unset($tel['recycled_id']);
				$tels_data[$recycled_id][] = $tel;
			}

			$rows = $query->result_array(FALSE);
			foreach ($rows as $row)
			{
				$data = array();
				foreach ($cols as $col)
				{
					switch ($col)
					{
						case 'id':
							$data['id'] = (int) $row['recycled_id'];
							break;
						case 'modified_at':
							$data['modified_at'] = $row['modified'] ? (int) $row['modified'] : (int) $row['created'];
							break;
						case 'source':
							$data['source'] = is_numeric($row['source']) ? api::get_source_name(
								$row['source']
							) : $row['source'];
							break;
						case 'tels':
							$data['tels'] = isset($tels_data[$row['recycled_id']]) ? $tels_data[$row['recycled_id']]
								: array();
							break;
						default:
							$data[$col] = $row[$col];
							break;
					}
				}
				$result[(int) $row['recycled_id']] = $data;
			}
		}
		return $result;
	}

	/**
	 * 获取系统分组联系人数
	 * @param int $user_id 用户ID
	 * @param string $type    系统分组名
	 * @return int
	 */
	public function get_count($user_id, $type = 'all')
	{
		$num = 0;
		$table = '';
		$append_sql = '';
		switch ($type)
		{
			case 'all':
				$table = $this->get_table($user_id, 'contacts');
				break;
			case 'recycled':
				$table = $this->get_table($user_id, 'contacts_recycled');
				break;
			case 'no_category':
				$table = $this->get_table($user_id, 'contacts');
				$append_sql = " AND category = ''";
				break;
		}
		if ($table)
		{
			$sql = sprintf("SELECT COUNT(0) AS num FROM %s WHERE uid = %d  %s LIMIT 1", $table, $user_id, $append_sql);
			$query = $this->db->query($sql);
			$result = $query->result_array(FALSE);
			$num = isset($result[0]['num']) ? (int) $result[0]['num'] : 0;
		}
		return $num;
	}

	/**
	 * 根据标签查找联系人
	 * @param Contact $contact 联系人对象
	 * @param bool $is_no_name
	 * @return array
	 */
	public function find_by_tags(Contact $contact, $is_no_name = FALSE)
	{
		//组织SQL
		$keys = array_filter($contact->get_main_info());
		if ($is_no_name === TRUE)
		{
			$keys['formatted_name'] = '';
		}
		$table = $this->get_table($contact->get_user_id(), 'contacts');
		$query = $this->db->getwhere($table, $keys);
		$result = array();
		if ($query->count())
		{
			$rows = $query->result_array(FALSE);
			foreach ($rows as $row)
			{
				$result[] = (int) $row['cid'];
			}
		}
		return $result;
	}

	/**
	 * 根据联系人对象插入联系人
	 * @param Contact $contact 联系人对象
	 * @throws Exception
	 * @return bool
	 */
	public function insert($contact)
	{
		$setters = $contact->get_main_info();
		$user_id = $contact->get_user_id();
		$details = $contact->get_more_info();
		$setters['created'] = $setters['modified'] = api::get_now_time();
		$table = $this->get_table($user_id, 'contacts');
		$this->db->begin();
		$query = $this->db->insert($table, $setters);
		if (! $query)
		{
			$this->db->rollback();
		}
		$id = $query->insert_id();
		if ($id)
		{
			$sqls = array();

			$this->set_contact_category_by_name($user_id, $id, $contact->get_category());

			foreach ($details as $type => $value)
			{
				$sqls = array_merge(
					$sqls,
					$this->_edit_info($user_id, $id, $type, $value, TRUE)
				);
			}
			foreach ($sqls as $sql)
			{
				$query = $this->db->query($sql);
				if (! $query)
				{
					$this->db->rollback();
				}
			}
			if ($this->db->commit())
			{
				$contact->set_id($id);
				$contact->set_created_at($setters['created']);
				$contact->set_modified_at($setters['modified']);
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * 修改联系人信息
	 * @param Contact $contact 联系人信息
	 * @param string $mode    模式 overwrite\special
	 * @return int SUCCESS 成功 FAIL 失败
	 */
	public function update($contact, $mode = 'overwrite')
	{
		$id = $contact->get_id();
		$user_id = $contact->get_user_id();
		$setters = $contact->get_main_info();

		if ($contact->get_avatar_exist() == FALSE)
		{
			unset($setters['avatar']);
		}
		$details = $contact->get_more_info();
		switch ($mode)
		{
			case 'overwrite':
				$setters['modified'] = api::get_now_time();
				$sqls = array();
				foreach ($details as $type => $value)
				{
					$sqls = array_merge($sqls, $this->_edit_info($user_id, $id, $type, $value));
				}
				//更新数据库
				$table = $this->get_table(
					$user_id, 'contacts'
				);
				$this->db->begin();
				$query = $this->db->update(
					$table, $setters,
					array(
					     'cid' => $id
					)
				);
				if (! $query)
				{
					$this->db->rollback();
				}
				$this->set_contact_category_by_name($user_id, $id, $contact->get_category());
				foreach ($sqls as $sql)
				{
					$query = $this->db->query($sql);
					if (! $query)
					{
						$this->db->rollback();
					}
				}
				if ($this->db->commit())
				{
					$contact->set_modified_at($setters['modified']);
					return SUCCESS;
				}
				return FAIL;
				break;
			case 'special':
				$sqls = array();
				foreach ($details as $type => $value)
				{
					$sqls = array_merge($sqls, $this->_edit_info($user_id, $id, $type, $value));
				}
				//更新数据库
				if (! empty($setters) or ! empty($sqls))
				{
					$setters['modified'] = api::get_now_time();
					$table = $this->get_table(
						$user_id,
						'contacts'
					);
					$this->db->begin();
					$query = $this->db->update(
						$table, $setters,
						array(
						     'cid' => $id
						)
					);
					$this->set_contact_category_by_name($user_id, $id, $contact->get_category());
					if (! $query)
					{
						$this->db->rollback();
					}
					foreach ($sqls as $sql)
					{
						$query = $this->db->query($sql);
						if (! $query)
						{
							$this->db->rollback();
						}
					}
					if ($this->db->commit())
					{
						$contact->set_modified_at($setters['modified']);
						return SUCCESS;
					}
					return FAIL;
				}
				return SUCCESS;
				break;
		}
		return FAIL;
	}

	/**
	 * 把多个联系人移到回收站
	 * @param int $user_id 用户ID
	 * @param array $ids     联系人ID数组
	 * @param int $source  删除来源
	 * @return array|bool
	 */
	public function move_contact_to_recycle($user_id, $ids, $source)
	{
		return $this->move_data($user_id, $ids, $source, FALSE);
	}

	/**
	 * 从回收站恢复多个联系人
	 * @param int $user_id 用户ID
	 * @param array $ids     联系人ID数组
	 * @param int $source  还原来源
	 * @return array|bool
	 */
	public function move_recycle_to_contact($user_id, $ids, $source)
	{
		return $this->move_data($user_id, $ids, $source, TRUE);
	}

	/**
	 * 移动联系人数据到回收站或移动回收站数据到联系人
	 * 根据数据是否在回收站判断操作
	 * @param int $user_id     用户ID
	 * @param array $ids         联系人ID
	 * @param int $source      数据来源
	 * @param bool $is_recycled 是否在回收站
	 * @return array|bool
	 */
	public function move_data($user_id, $ids, $source, $is_recycled)
	{
		$move_sqls = $this->build_move_sql($user_id, $source, $is_recycled);
		$delete_sqls = $this->build_delete_sql($user_id, $ids, $is_recycled);

		$this->db->begin();
		$new_ids = array();
		foreach ($ids as $id)
		{
			$new_id = 0;
			foreach ($move_sqls as $key => $sql)
			{
				//更新主表生成回收站ID
				if ($key == 0)
				{
					$query = $this->db->query(sprintf($sql, $id));
					if (! $query)
					{
						$this->db->rollback();
					}
					else
					{
						$new_id = $query->insert_id();
					}
				}
				else
				{
					$query = $this->db->query(sprintf($sql, $new_id, $id));
					if (! $query)
					{
						$this->db->rollback();
					}
				}
			}
			$new_ids[] = $new_id;
		}
		foreach ($delete_sqls as $sql)
		{
			$query = $this->db->query($sql);
			if (! $query)
			{
				$this->db->rollback();
			}
		}
		if ($this->db->commit())
		{
			return $new_ids;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * 移动更新的联系人到回收站
	 * @param $user_id 用户ID
	 * @param $id      联系人ID
	 * @param $source  数据来源
	 * @return bool
	 */
	public function move_update_contact_to_recycle($user_id, $id, $source)
	{
		$sqls = $this->build_move_sql($user_id, $source, FALSE, 'update');
		$new_id = 0;
		$this->db->begin();
		foreach ($sqls as $key => $sql)
		{
			//更新主表生成回收站ID
			if ($key == 0)
			{
				$query = $this->db->query(sprintf($sql, $id));
				if (! $query)
				{
					$this->db->rollback();
				}
				else
				{
					$new_id = $query->insert_id();
				}
			}
			else
			{
				$query = $this->db->query(sprintf($sql, $new_id, $id));
				if (! $query)
				{
					$this->db->rollback();
				}
			}
		}
		return $this->db->commit();
	}

	/**
	 * 构建移动联系人到回收站或回收站到联系人的SQL，根据是否在回收站判断
	 * @param int $user_id     用户ID
	 * @param int $source      数据来源
	 * @param bool $is_recycled 是否在回收站
	 * @param string $operation   操作类型
	 * @return array
	 */
	public function build_move_sql($user_id, $source, $is_recycled, $operation = 'delete')
	{
		$cols = $new_cols = array();
		$contact_tables = array('contacts');
		$new_fields = $fields = Contact::get_main_fields();
		$now = api::get_now_time();
		foreach (Contact::$allow_cols as $key => $val)
		{
			$contact_tables[] = 'contact_' . $key;
			$cols['contact_' . $key] = array_merge(
				array(
				     'id',
				     'uid',
				     'cid'
				), $val
			);
		}
		$sqls = array();

		//更新字段
		$key = array_search('created', $new_fields);
		if ($key !== FALSE)
		{
			$new_fields[$key] = $now;
		}

		$key = array_search('modified', $new_fields);
		if ($key !== FALSE)
		{
			$new_fields[$key] = $now;
		}

		$key = array_search('source', $new_fields);
		if ($key !== FALSE)
		{
			$new_fields[$key] = $this->db->escape($source);
		}

		foreach ($contact_tables as $table)
		{
			if ($is_recycled === TRUE)
			{
				$from_table = $table . '_recycled';
				$to_table = $table;
				$id_name = 'recycled_id';
				if ($table == 'contacts')
				{
					//更新联系人ID
					$key = array_search('cid', $new_fields);
					if ($key !== FALSE)
					{
						$new_fields[$key] = 0;
					}
				}
				else
				{
					//设置自增字段
					$new_cols[$table] = $cols[$table];
					$key = array_search('id', $new_cols[$table], TRUE);
					if ($key !== FALSE)
					{
						$new_cols[$table][$key] = 0;
					}
					//设置联系人ID
					$key = array_search('cid', $new_cols[$table], TRUE);
					if ($key !== FALSE)
					{
						$new_cols[$table][$key] = '%d';
					}
				}
			}
			else
			{
				$from_table = $table;
				$to_table = $table . '_recycled';
				$id_name = 'cid';
				//增加回收站数据操作类型
				if ($table == 'contacts')
				{
					$fields[] = 'operation';
					$new_fields[] = $this->db->escape($operation);
				}
				else
				{
					$new_cols[$table] = $cols[$table];
					array_unshift($cols[$table], 'recycled_id');
					array_unshift($new_cols[$table], '%d');
				}
			}
			if ($table == 'contacts')
			{
				$sqls[] = "INSERT INTO " . $this->get_table(
						$user_id,
						$to_table
					) . " (" . implode(',', $fields) . ") SELECT " .
					implode(',', $new_fields) . " FROM " .
					$this->get_table($user_id, $from_table) . " WHERE $id_name = %d AND uid = $user_id";
			}
			else
			{
				$sqls[] = "INSERT INTO " . $this->get_table(
						$user_id,
						$to_table
					) . " (" . implode(',', $cols[$table]) . ") SELECT " .
					implode(',', $new_cols[$table]) . " FROM " .
					$this->get_table($user_id, $from_table) . " WHERE $id_name = %d AND uid = $user_id";
			}
		}
		return $sqls;
	}

	/**
	 * 构建从回收站或联系人中删除多个联系人的SQL
	 * @param int $user_id     用户ID
	 * @param array $ids         联系人ID数组
	 * @param bool $is_recycled 是否在回收站
	 * @return array
	 */
	public function build_delete_sql($user_id, $ids = array(), $is_recycled = TRUE)
	{
		$sqls = array();
		$id_name = $is_recycled === TRUE ? 'recycled_id' : 'cid';
		if ($ids)
		{
			$append_sql = " AND $id_name IN (" . implode(',', $ids) . ");";
		}
		else
		{
			$append_sql = ';';
		}
		if ($is_recycled == FALSE)
		{
			$tables = array(
				'contacts',
				'contact_tels',
				'contact_emails',
				'contact_urls',
				'contact_addresses',
				'contact_ims',
				'contact_events',
				'contact_relations',
				'contact_customs',
				'contact_classes'
			);
			if (empty($ids))
			{
				array_push($tables, 'contact_categories');
			}
		}
		else
		{
			$tables = array(
				'contacts',
				'contact_tels',
				'contact_emails',
				'contact_urls',
				'contact_addresses',
				'contact_ims',
				'contact_events',
				'contact_relations',
				'contact_customs'
			);
		}

		foreach ($tables as $table)
		{
			$from_table = $is_recycled ? $table . '_recycled' : $table;
			$sqls[] = "DELETE FROM " . $this->get_table($user_id, $from_table) .
				" WHERE uid = $user_id" . $append_sql;
		}
		return $sqls;
	}

	/**
	 * 删除回收站联系人
	 * @param int $user_id 用户ID
	 * @param array $ids     回收站联系人ID
	 * @return bool
	 */
	public function delete($user_id, $ids = array())
	{
		$sqls = $this->build_delete_sql($user_id, $ids, TRUE);
		$this->db->begin();
		foreach ($sqls as $sql)
		{
			$query = $this->db->query($sql);
			if (! $query)
			{
				$this->db->rollback();
			}
		}
		return $this->db->commit();
	}

	/**
	 * 修改电话、邮箱、网址、纪念日、关系、即时通讯、关系信息
	 * @param int $user_id 用户ID
	 * @param int $id      联系人ID
	 * @param string $type    类型
	 * @param array $values  信息
	 * @param bool $is_add  是否新增
	 * @return array
	 */
	private function _edit_info(
		$user_id, $id, $type = 'emails', $values = array(),
		$is_add = FALSE
	)
	{
		$new_info_tmp = $old_info_tmp = $sqls = $ids = array();
		if ($values)
		{
			foreach ($values as $val)
			{
				switch ($type)
				{
					case 'tels':
						if (! empty($val['value']))
						{
							$key = md5($id . strtolower($val['type']) . $val['value']);
							$new_info_tmp[$key]['cid'] = $id;
							$new_info_tmp[$key]['type'] = $val['type'];
							$new_info_tmp[$key]['value'] = $val['value'];
							$new_info_tmp[$key]['pref'] = $val['pref'];
							$new_info_tmp[$key]['city'] = $val['city'];
							$new_info_tmp[$key]['search'] = $val['search'];
						}
						break;
					case 'ims':
						if (! empty($val['value']))
						{
							$key = md5(
								$id . strtolower($val['protocol']) . strtolower($val['type']) . $val['value']
							);
							$new_info_tmp[$key]['cid'] = $id;
							$new_info_tmp[$key]['protocol'] = $val['protocol'];
							$new_info_tmp[$key]['type'] = $val['type'];
							$new_info_tmp[$key]['value'] = $val['value'];
						}
						break;
					case 'addresses':
						if (! empty($val['country']) || ! empty($val['region'])
							|| ! empty(
							$val['city'])
							|| ! empty($val['street'])
							|| ! empty($val['postal'])
						)
						{
							$key = md5(
								$id . strtolower($val['type']) . $val['country'] . $val['region'] .
								$val['city'] . $val['street'] . $val['street']
							);
							$new_info_tmp[$key]['cid'] = $id;
							$new_info_tmp[$key]['type'] = $val['type'];
							$new_info_tmp[$key]['country'] = $val['country'];
							$new_info_tmp[$key]['region'] = $val['region'];
							$new_info_tmp[$key]['city'] = $val['city'];
							$new_info_tmp[$key]['street'] = $val['street'];
							$new_info_tmp[$key]['postal'] = $val['postal'];
						}
						break;
					default:
						if (! empty($val['value']))
						{
							$key = md5($id . strtolower($val['type']) . $val['value']);
							$new_info_tmp[$key]['cid'] = $id;
							$new_info_tmp[$key]['type'] = $val['type'];
							$new_info_tmp[$key]['value'] = $val['value'];
						}
						break;
				}
			}
		}
		if ($is_add === FALSE)
		{
			$old_info = $this->get_info_list($user_id, $id, $type, TRUE);
			if ($old_info)
			{
				foreach ($old_info as $val)
				{
					switch ($type)
					{
						case 'ims':
							if (! empty($val['value']))
							{
								$key = md5(
									$id . strtolower($val['protocol']) . strtolower($val['type']) .
									$val['value']
								);
								if (array_key_exists($key, $old_info_tmp))
								{
									$ids[] = $val['id'];
								}
								else
								{
									$old_info_tmp[$key]['id'] = $val['id'];
									$old_info_tmp[$key]['cid'] = $id;
									$old_info_tmp[$key]['protocol'] = $val['protocol'];
									$old_info_tmp[$key]['type'] = $val['type'];
									$old_info_tmp[$key]['value'] = $val['value'];
								}
							}
							break;
						case 'addresses':
							if (! empty($val['country'])
								|| ! empty(
								$val['region'])
								|| ! empty($val['city'])
								|| ! empty($val['street'])
								|| ! empty($val['postal'])
							)
							{
								$key = md5(
									$id . strtolower($val['type']) . $val['country'] .
									$val['region'] . $val['city'] . $val['street'] .
									$val['street']
								);
								if (array_key_exists($key, $old_info_tmp))
								{
									$ids[] = $val['id'];
								}
								else
								{
									$old_info_tmp[$key]['id'] = $val['id'];
									$old_info_tmp[$key]['cid'] = $id;
									$old_info_tmp[$key]['type'] = $val['type'];
									$old_info_tmp[$key]['country'] = $val['country'];
									$old_info_tmp[$key]['region'] = $val['region'];
									$old_info_tmp[$key]['city'] = $val['city'];
									$old_info_tmp[$key]['street'] = $val['street'];
									$old_info_tmp[$key]['postal'] = $val['postal'];
								}
							}
							break;
						default:
							if (! empty($val['value']))
							{
								$key = md5($id . strtolower($val['type']) . $val['value']);
								if (array_key_exists($key, $old_info_tmp))
								{
									$ids[] = $val['id'];
								}
								else
								{
									$old_info_tmp[$key]['id'] = $val['id'];
									$old_info_tmp[$key]['cid'] = $id;
									$old_info_tmp[$key]['type'] = $val['type'];
									$old_info_tmp[$key]['value'] = $val['value'];
								}
							}
							break;
					}
				}
			}
		}
		$diff = array_diff_key($old_info_tmp, $new_info_tmp);
		if ($diff)
		{
			foreach ($diff as $val)
			{
				$ids[] = $val['id'];
			}
		}
		if ($ids)
		{
			$sqls[] = "DELETE FROM " .
				$this->get_table($user_id, 'contact_' . $type) .
				" WHERE `uid` = $user_id AND `cid` = $id AND id IN (" .
				implode(',', $ids) . ");";
		}
		$diff = array_diff_key($new_info_tmp, $old_info_tmp);
		if ($diff)
		{
			switch ($type)
			{
				case 'tels':
					$sql = "INSERT INTO " .
						$this->get_table($user_id, 'contact_' . $type) .
						" (`uid`, `cid`, `type`, `value`, `pref`, `city`, `search`) VALUES ";
					foreach ($diff as $val)
					{
						$val['type'] = $this->db->escape($val['type']);
						$val['value'] = $this->db->escape($val['value']);
						$val['city'] = $this->db->escape($val['city']);
						$val['search'] = $this->db->escape($val['search']);
						$sql .= sprintf(
							"(%d, %d, %s, %s, %d, %s, %s),", $user_id,
							$id, $val['type'], $val['value'], $val['pref'],
							$val['city'], $val['search']
						);
					}
					$sqls[] = rtrim($sql, ',') . ';';
					break;
				case 'addresses':
					$sql = "INSERT INTO " .
						$this->get_table($user_id, 'contact_' . $type) .
						" (`uid`, `cid`, `type`, `country`," .
						" `postal`, `region`, `city`, `street`) VALUES ";
					foreach ($diff as $val)
					{
						$val['country'] = $this->db->escape($val['country']);
						$val['region'] = $this->db->escape($val['region']);
						$val['city'] = $this->db->escape($val['city']);
						$val['street'] = $this->db->escape($val['street']);
						$val['postal'] = $this->db->escape($val['postal']);
						$val['type'] = $this->db->escape($val['type']);
						$sql .= sprintf(
							"(%d, %d, %s, %s, %s, %s, %s, %s),",
							$user_id, $id, $val['type'], $val['country'],
							$val['postal'], $val['region'], $val['city'],
							$val['street']
						);
					}
					$sqls[] = rtrim($sql, ',') . ';';
					break;
				case 'ims':
					$sql = "INSERT INTO " .
						$this->get_table($user_id, 'contact_' . $type) .
						" (`uid`, `cid`, `protocol`, `type`, `value`) VALUES ";
					foreach ($diff as $val)
					{
						$val['type'] = $this->db->escape($val['type']);
						$val['value'] = $this->db->escape($val['value']);
						$val['protocol'] = $this->db->escape($val['protocol']);
						$sql .= sprintf(
							"(%d, %d, %s, %s, %s),", $user_id, $id,
							$val['protocol'], $val['type'], $val['value']
						);
					}
					$sqls[] = rtrim($sql, ',') . ';';
					break;
				default:
					$sql = "INSERT INTO " .
						$this->get_table($user_id, 'contact_' . $type) .
						" (`uid`, `cid`, `type`, `value`) VALUES ";
					foreach ($diff as $val)
					{
						$val['type'] = $this->db->escape($val['type']);
						$val['value'] = $this->db->escape($val['value']);
						$sql .= sprintf(
							"(%d, %d, %s, %s),", $user_id, $id,
							$val['type'], $val['value']
						);
					}
					$sqls[] = rtrim($sql, ',') . ';';
					break;
			}
		}
		return $sqls;
	}

	/**
	 * 获取联系信息列表
	 * @param int $user_id      用户ID
	 * @param int $id           联系人ID
	 * @param string $type         联系方式 emails\tels\ims\urls\addresses\events\relations\customs
	 * @param bool $is_return_id 是否返回ID
	 * @param bool $is_recycled  是否回收站
	 * @param array $row 返回记录列名
	 * @return array
	 */
	public function get_info_list(
		$user_id, $id = NULL, $type = 'emails',
		$is_return_id = FALSE, $is_recycled = FALSE, $row = array()
	)
	{
		$row = $row ? implode(',', $row) : implode(',', Contact::$allow_cols[$type]);

		if ($is_recycled === TRUE)
		{
			$table = $this->get_table($user_id, 'contact_' . $type . '_recycled');
			$id_name = 'recycled_id';
		}
		else
		{
			$table = $this->get_table($user_id, 'contact_' . $type);
			$id_name = 'cid';
		}
		if ($is_return_id === TRUE)
		{
			$row .= ', `id`';
		}

		if (! empty($id))
		{
			$append_sql = "{$id_name} = {$id} AND ";
		}
		else
		{
			$append_sql = '';
		}
		$sql = sprintf("SELECT %s FROM %s WHERE %s uid = %d", $row, $table, $append_sql, $user_id);
		$query = $this->db->query($sql);
		return $query->result_array(FALSE);
	}

	/**
	 * 获取快照联系信息列表
	 * @param int $user_id      用户ID
	 * @param int $id           联系人ID
	 * @param int $dateline     时间戳
	 * @param string $type      联系方式 emails\tels\ims\urls\addresses\events\relations\customs
	 * @return array
	 */
	public function get_snapshot_info_list($user_id, $id, $dateline, $type = 'emails')
	{
		$row = implode(',', Contact::$allow_cols[$type]);
		$table = $this->get_table($user_id, 'contact_' . $type . '_snapshot', $dateline);
		$sql = sprintf("SELECT %s FROM %s WHERE uid = %d AND snapshot_id = %d AND cid = %d", $row, $table
			, $user_id, $dateline, $id);
		$query = $this->db->query($sql);
		return $query->result_array(FALSE);
	}

	/**
	 * 根据联系方式获取联系人ID
	 * @param int $user_id 用户ID
	 * @param string $type    表
	 * @param array $info    联系方式
	 * @return array
	 */
	public function get_id_by_info($user_id, $type, $info)
	{
		$value = $return = array();
		foreach ($info as $val)
		{
			$value[] = $this->db->escape($val['value']);
		}
		$sql = sprintf("SELECT cid FROM %s WHERE uid = %d AND VALUE IN (%s) ORDER BY cid ASC",
			$this->get_table($user_id, 'contact_' . $type), $user_id, implode(',', $value));

		$query = $this->db->query($sql);
		if ($query->count())
		{
			$result = $query->result_array(FALSE);
			foreach ($result as $res)
			{
				$return[] = $res['cid'];
			}
		}
		return $return;
	}

	/**
	 * 根据电话查询字段获取联系人ID
	 * @param int $user_id 用户ID
	 * @param string $search    查询内容
	 * @return array
	 */
	public function get_id_by_tel($user_id, $search)
	{
		$return = array();
		$table = $this->get_table($user_id, 'contact_tels');
		$search = $this->db->escape($search);
		if ($search)
		{
			$sql = sprintf("SELECT cid FROM %s WHERE uid = %d AND search = %s ORDER BY cid ASC",
				$table, $user_id, $search);

			$query = $this->db->query($sql);
			if ($query->count())
			{
				$result = $query->result_array(FALSE);
				foreach ($result as $res)
				{
					$return[] = $res['cid'];
				}
			}
		}
		return $return;
	}

	/**
	 * 获取分表名
	 * @param int $user_id    用户ID
	 * @param string $base_table 基本表名
	 * @param int $time 分表时间
	 * @return string
	 */
	public function get_table($user_id, $base_table, $time = 0)
	{
		static $tables = array();
		// 已迁移数据到新库
		if (Kohana::config('contact.new_db_rule'))
		{
			// 使用cobar
			if (Kohana::config('contact.use_cobar'))
			{
				$db_group = 'default';
			}
			else
			{
				$db_group = 'contact_' . ($user_id % Kohana::config('contact.db_count'));
			}
		}
		else
		{
			//未迁移数据仍到旧库
			$db_group = 'contact';
		}

		$this->db = isset($this->db_instances[$db_group]) ? $this->db_instances[$db_group] : NULL;

		$key = md5($user_id . '|' . $base_table . '|' . $time);
		if (empty($tables[$key]))
		{
			// 分库前旧数据连接旧数据库
			if (Kohana::config('contact.new_db_rule'))
			{
				$divide_table = Kohana::config('contact.new_divide_table');
			}
			else
			{
				$divide_table = Kohana::config('contact.old_divide_table');
			}
			// 获取数据库连接
			if (! isset($this->db_instances[$db_group]))
			{
				$this->db_instances[$db_group] = Database::instance($db_group);
			}
			$this->db = $this->db_instances[$db_group];
			// 根据不同分表规则分表
			if (isset($divide_table[$base_table]))
			{
				$table = $base_table . '_' . ($user_id % $divide_table[$base_table]);
			}
			else
			{
				$table_key = '*' . substr($base_table, strrpos($base_table, '_'));
				if (isset($divide_table[$table_key]))
				{
					$time = $time == 0 ? api::get_now_time() : $time;
					$table = $base_table . '_' . date($divide_table[$table_key], $time);
				}
				else
				{
					$table = $base_table;
				}
			}

			if (Kohana::config('contact.new_db_rule'))
			{
				$table .= '_' . $user_id % Kohana::config('contact.db_count');
			}
			$tables[$key] = $table;
		}
		return $tables[$key];
	}

	/**
	 * 增加联系人修改历史记录
	 * @param int $user_id     用户ID
	 * @param int $appid       应用ID
	 * @param int $source      操作来源
	 * @param int $device_id      设备ID
	 * @param int $phone_model      手机型号
	 * @param string $operation   操作说明
	 * @param array $history     上次变更历史
	 * @param bool $is_history  是否保存快照
	 * @param array $added_ids   新增联系人ID
	 * @param array $updated_ids 修改联系人ID
	 * @param array $deleted_ids 删除联系人ID
	 * @param int $count 联系人数
	 * @param int $category_count 分组数
	 * @return bool
	 */
	public function add_history(
		$user_id, $appid, $source, $device_id, $phone_model, $operation, $history, $is_history,
		$added_ids = array(), $updated_ids = array(), $deleted_ids = array(),
		$count = 0, $category_count = 0
	)
	{
		if ($history)
		{
			if ($is_history === FALSE)
			{
				$added_ids = serialize(
					array_merge(unserialize($history['added_ids']), $added_ids)
				);
				$updated_ids = serialize(
					array_unique(array_merge(unserialize($history['updated_ids']), $updated_ids))
				);
				$deleted_ids = serialize(
					array_merge(unserialize($history['deleted_ids']), $deleted_ids)
				);
				$setters = array(
					'added_ids'   => $added_ids,
					'updated_ids' => $updated_ids,
					'deleted_ids' => $deleted_ids,
				);
				$result = $this->update_history($user_id, $history['id'], $setters);
			}
			else
			{
				return $this->insert_history($user_id, $appid, $source, $device_id, $phone_model, $operation,
					$added_ids, $updated_ids, $deleted_ids, $count, $category_count);
			}
		}
		else
		{
			return $this->insert_history($user_id, $appid, $source, $device_id, $phone_model, $operation,
				$added_ids, $updated_ids, $deleted_ids, $count, $category_count);
		}
		return $result;
	}

	/**
	 * 写入联系人修改历史记录
	 * @param int $user_id     用户ID
	 * @param int $appid       应用ID
	 * @param int $source      操作来源
	 * @param int $device_id      设备ID
	 * @param int $phone_model      手机型号
	 * @param string $operation   操作说明
	 * @param array $added_ids   新增联系人ID
	 * @param array $updated_ids 修改联系人ID
	 * @param array $deleted_ids 删除联系人ID
	 * @param int $count 联系人数
	 * @param int $category_count 分组数
	 * @return bool
	 */
	public function insert_history($user_id, $appid, $source, $device_id, $phone_model, $operation,
	                               $added_ids, $updated_ids, $deleted_ids, $count, $category_count)
	{
		$setters = array(
			'uid'            => $user_id,
			'appid'          => $appid,
			'source'         => $source,
			'device_id'      => $device_id,
			'phone_model'    => $phone_model,
			'operation'      => $operation,
			'added_ids'      => serialize($added_ids),
			'updated_ids'    => serialize($updated_ids),
			'deleted_ids'    => serialize($deleted_ids),
			'dateline'       => api::get_now_time(),
			'count'          => $count,
			'category_count' => $category_count,
		);
		$this->db->begin();
		$query = $this->db->insert($this->get_table($user_id, 'contact_history'), $setters);
		if (! $query)
		{
			$this->db->rollback();
		}
		return $this->db->commit();
	}

	/**
	 * 更新快照信息
	 * @param int $user_id 用户ID
	 * @param int $history_id 快照ID
	 * @param array $setters 更新内容
	 * @return bool
	 */
	public function update_history($user_id, $history_id, $setters)
	{

		$this->db->begin();
		$query = $this->db->update(
			$this->get_table($user_id, 'contact_history'), $setters,
			array(
			     'id' => $history_id
			)
		);
		if (! $query)
		{
			$this->db->rollback();
		}
		return $this->db->commit();
	}

	/**
	 * 执行保存快照
	 * @param int $user_id 用户ID
	 * @return bool
	 */
	public function save_snapshot($user_id)
	{
		$sqls = $this->_do_snapshot($user_id, TRUE);
		$this->db->begin();
		foreach ($sqls as $sql)
		{
			$query = $this->db->query($sql);
			if (! $query)
			{
				$this->db->rollback();
			}
		}
		return $this->db->commit();
	}

	/**
	 * 还原快照操作
	 * @param int $user_id     用户ID
	 * @param int $snapshot_id 快照ID
	 * @return bool
	 */
	public function recover_snapshot($user_id, $snapshot_id)
	{
		$delete_sqls = $this->build_delete_sql($user_id, array(), FALSE);
		$move_sqls = $this->_do_snapshot($user_id, FALSE, $snapshot_id);
		$sqls = array_merge($delete_sqls, $move_sqls);
		$this->db->begin();
		foreach ($sqls as $sql)
		{
			$query = $this->db->query($sql);
			if (! $query)
			{
				$this->db->rollback();
			}
		}
		return $this->db->commit();
	}

	/**
	 * 执行快照操作,返回需要执行的SQL
	 * @param int $user_id     用户ID
	 * @param bool $is_save     是否保存快照
	 * @param int $snapshot_id 快照ID，只有在还原时使用
	 * @return array
	 */
	private function _do_snapshot($user_id, $is_save = TRUE, $snapshot_id = 0)
	{
		if ($is_save)
		{
			$snapshot_id = api::get_now_time();
		}
		$cols = array();
		$contact_tables = array(
			'contacts',
			'contact_categories',
			'contact_classes'
		);
		$fields = Contact::get_main_fields();
		foreach (Contact::$allow_cols as $key => $val)
		{
			$contact_tables[] = 'contact_' . $key;
			$cols['contact_' . $key] = array_merge(
				array(
				     'id',
				     'uid',
				     'cid'
				), $val
			);
		}
		$sqls = array();
		foreach ($contact_tables as $table)
		{
			//是否备份操作
			if ($is_save)
			{
				$from_table = $table;
				$to_table = $table . '_snapshot';
				$append_sql = ';';
				if ($table == 'contacts')
				{
					$to_fields = array_merge(
						array(
						     'snapshot_id'
						), $fields
					);
					$from_fields = array_merge(
						array(
						     $snapshot_id
						), $fields
					);
				}
				elseif (in_array($table, array(
				                              'contact_categories',
				                              'contact_classes'
				                         ))
				)
				{
					$to_fields = array_merge(
						array(
						     'snapshot_id'
						), call_user_func(array('Contact', 'get_' . $table . '_fields'))
					);
					$from_fields = array_merge(
						array(
						     $snapshot_id
						), call_user_func(array('Contact', 'get_' . $table . '_fields'))
					);
				}
				else
				{
					$to_cols = array_merge(
						array(
						     'snapshot_id'
						), $cols[$table]
					);
					$from_cols = array_merge(
						array(
						     $snapshot_id
						), $cols[$table]
					);
				}
			}
			else
			{
				//还原操作
				$to_table = $table;
				$from_table = $table . '_snapshot';
				//只复制必要数据
				if ($table == 'contacts')
				{
					$from_fields = $to_fields = $fields;
				}
				elseif (in_array($table, array(
				                              'contact_categories',
				                              'contact_classes'
				                         ))
				)
				{
					$from_fields = $to_fields = call_user_func(array('Contact', 'get_' . $table . '_fields'));
				}
				else
				{
					$from_cols = $to_cols = $cols[$table];
				}
				$append_sql = " AND snapshot_id = {$snapshot_id};";
			}
			if ($table == 'contacts')
			{
				$sqls[] = "INSERT INTO " . $this->get_table(
						$user_id,
						$to_table,
						$snapshot_id
					) . " (" . implode(',', $to_fields) . ") SELECT " .
					implode(',', $from_fields) . " FROM " .
					$this->get_table($user_id, $from_table, $snapshot_id) .
					" WHERE uid = {$user_id}" . $append_sql;
			}
			elseif (in_array($table, array(
			                              'contact_categories',
			                              'contact_classes'
			                         ))
			)
			{
				$sqls[] = "INSERT INTO " . $this->get_table(
						$user_id,
						$to_table,
						$snapshot_id
					) . " (" . implode(',', $to_fields) . ") SELECT " .
					implode(',', $from_fields) . " FROM " .
					$this->get_table($user_id, $from_table, $snapshot_id) .
					" WHERE uid = {$user_id}" . $append_sql;
			}
			else
			{
				$sqls[] = "INSERT INTO " . $this->get_table(
						$user_id,
						$to_table,
						$snapshot_id
					) . " (" . implode(',', $to_cols) . ") SELECT " .
					implode(',', $from_cols) . " FROM " .
					$this->get_table($user_id, $from_table, $snapshot_id) .
					" WHERE uid = {$user_id}" . $append_sql;
			}
		}
		return $sqls;
	}

	/**
	 * 检验快照
	 * @param int $user_id     用户ID
	 * @param int $snapshot_id 快照ID
	 * @return bool
	 */
	public function check_snapshot($user_id, $snapshot_id)
	{
		$table = $this->get_table($user_id, 'contacts_snapshot', $snapshot_id);
		$sql = sprintf("SELECT snapshot_id FROM %s WHERE uid = %d AND snapshot_id = %d LIMIT 1",
			$table, $user_id, $snapshot_id);
		$query = $this->db->query($sql);
		if ($query->count())
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * 获取快照
	 * @param int $user_id     用户ID
	 * @param int $snapshot_id 快照ID
	 * @return array
	 */
	public function get_snapshot_list($user_id, $snapshot_id)
	{
		$table = $this->get_table($user_id, 'contacts_snapshot', $snapshot_id);
		$sql = sprintf("SELECT cid AS id,formatted_name,modified AS modified_at,category,avatar FROM %s WHERE uid = %d AND snapshot_id = %d",
			$table, $user_id, $snapshot_id);
		$query = $this->db->query($sql);
		if ($query->count())
		{
			return $query->result_array(FALSE);
		}
		else
		{
			return array();
		}
	}

	/**
	 * 获取修改历史
	 * @param int $user_id 用户ID
	 * @return array|bool
	 */
	public function get_last_history($user_id)
	{
		$sql = sprintf("SELECT * FROM %s WHERE uid = %d ORDER BY id DESC LIMIT 1", $this->get_table($user_id,
			'contact_history'), $user_id);
		$query = $this->db->query($sql);
		if ($query->count())
		{
			$result = $query->result_array(FALSE);
			return $result[0];
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * 获取联系人变更历史
	 * @param int $user_id 用户ID
	 * @param int $start   起始记录数
	 * @param int $pos     获取记录数
	 * @return array
	 */
	public function get_history($user_id, $start, $pos)
	{
		$sql = sprintf("SELECT * FROM %s WHERE uid = %d ORDER BY id DESC LIMIT %d, %d",
			$this->get_table($user_id, 'contact_history'), $user_id, $start, $pos);
		$query = $this->db->query($sql);
		if ($query->count())
		{
			return $query->result_array(FALSE);
		}
		else
		{
			return array();
		}
	}

	/**
	 * 获取联系人快照分组数
	 * @param int $user_id 用户ID
	 * @param int $dateline 时间
	 * @return array
	 */
	public function get_snapshot_category_count($user_id, $dateline)
	{
		if (Kohana::config('contact.from_category_table'))
		{
			$sql = sprintf("SELECT COUNT(DISTINCT(category_id)) AS cnt FROM %s WHERE uid = %d AND snapshot_id = %d LIMIT 1;",
				$this->get_table($user_id, 'contact_classes_snapshot'), $user_id, $dateline);
			$query = $this->db->query($sql);
			$result = $query->result_array(FALSE);
			$category_count = isset($result[0]['cnt']) ? (int) $result[0]['cnt'] : 0;
			return $category_count;
		}
		else
		{
			$sql = sprintf("SELECT DISTINCT(category) FROM %s WHERE uid = %d AND snapshot_id = %d AND category != ''",
				$this->get_table($user_id, 'contacts_snapshot'), $user_id, $dateline);
			$query = $this->db->query($sql);
			$result = array();
			if ($query->count())
			{
				$res = $query->result_array(FALSE);
				foreach ($res as $val)
				{
					foreach (explode(',', $val['category']) as $category)
					{
						$result[] = $category;
					}
				}
			}
			return count($result);
		}
	}

	/**
	 * 获取联系人快照联系人数和分组数，同时更新历史表
	 * @notice 用于兼容旧数据
	 * @param int $user_id 用户ID
	 * @param int $dateline 时间
	 * @return array
	 * @todo fix
	 */
	public function get_snapshot_count($user_id, $dateline)
	{
		if (Kohana::config('contact.from_category_table'))
		{
			$sql = sprintf("SELECT COUNT(0) AS cnt FROM %s WHERE uid = %d AND snapshot_id = %d LIMIT 1",
				$this->get_table($user_id, 'contacts_snapshot', $dateline), $user_id, $dateline);
			$query = $this->db->query($sql);
			$result = $query->result_array(FALSE);
			$count = isset($result[0]['cnt']) ? (int) $result[0]['cnt'] : 0;

			$sql = sprintf("SELECT COUNT(DISTINCT(category_id)) AS cnt FROM %s WHERE uid = %d AND snapshot_id = %d LIMIT 1;",
				$this->get_table($user_id, 'contact_classes_snapshot'), $user_id, $dateline);
			$query = $this->db->query($sql);
			$result = $query->result_array(FALSE);
			$category_count = isset($result[0]['cnt']) ? (int) $result[0]['cnt'] : 0;
		}
		else
		{
			$sql = sprintf("SELECT cid,category FROM %s WHERE uid = %d AND snapshot_id = %d",
				$this->get_table($user_id, 'contacts_snapshot', $dateline), $user_id, $dateline);
			$query = $this->db->query($sql);
			$result = array();
			$count = $query->count();
			if ($count)
			{
				$res = $query->result_array(FALSE);
				foreach ($res as $val)
				{
					if ($val['category'])
					{
						foreach (explode(',', $val['category']) as $category)
						{
							$result[$category][] = $val['cid'];
						}
					}
				}
			}
			$category_count = count($result);
		}
		$sql = sprintf("UPDATE %s SET `count` = %d, category_count = %d WHERE uid = %d AND dateline = %d LIMIT 1",
			$this->get_table($user_id, 'contact_history'), $count, $category_count, $user_id, $dateline);
		$this->db->query($sql);
		return array('count' => $count, 'category_count' => $category_count);
	}

	/**
	 * 根据时间获取变更历史
	 * @param int $user_id  用户ID
	 * @param int $dateline 时间
	 * @return array
	 */
	public function get_history_by_dateline($user_id, $dateline)
	{
		$sql = sprintf("SELECT * FROM %s WHERE uid = %d AND dateline = %d LIMIT 1",
			$this->get_table($user_id, 'contact_history'), $user_id, $dateline);
		$query = $this->db->query($sql);
		if ($query->count())
		{
			$result = $query->result_array(FALSE);
			return $result[0];
		}
		else
		{
			return array();
		}
	}

	/**
	 * 获取有效的回收站ID
	 * @param $user_id 用户ID
	 * @param $ids 回收站ID
	 * @return array有效回收站ID
	 */
	public function get_valid_recycled_ids($user_id, $ids = array())
	{
		if ($ids)
		{
			$append_sql = ' AND recycled_id IN (' . implode(',', $ids) . ');';
		}
		else
		{
			$append_sql = ';';
		}
		$sql = sprintf("SELECT recycled_id FROM %s WHERE uid = %d %s",
			$this->get_table($user_id, 'contacts_recycled'), $user_id, $append_sql);
		$query = $this->db->query($sql);
		$result = array();
		if ($query->count())
		{
			$res = $query->result_array(FALSE);
			foreach ($res as $val)
			{
				$result[] = $val['recycled_id'];
			}
		}
		return $result;
	}

	/**
	 * 获取分组联系人
	 * @param int $user_id 用户ID
	 * @param int $category_id 分组ID
	 * @return array
	 */
	public function get_category_contact($user_id, $category_id)
	{
		$sql = sprintf("SELECT cid FROM %s WHERE uid = %d AND category_id = %d",
			$this->get_table($user_id, 'contact_classes'), $user_id, $category_id);
		$query = $this->db->query($sql);
		$result = array();
		if ($query->count())
		{
			$res = $query->result_array(FALSE);
			foreach ($res as $val)
			{
				$result[] = $val['cid'];
			}
		}
		return $result;
	}

	/**
	 * 获取分组联系人列表
	 * @param int $user_id 用户ID
	 * @return array
	 */
	public function get_category_contact_list($user_id)
	{
		$sql = sprintf("SELECT category_id,cid FROM %s WHERE uid = %d",
			$this->get_table($user_id, 'contact_classes'), $user_id);
		$query = $this->db->query($sql);
		$result = array();
		if ($query->count())
		{
			$res = $query->result_array(FALSE);
			foreach ($res as $val)
			{
				$result[$val['category_id']][] = $val['cid'];
			}
		}
		return $result;
	}

	/**
	 * 获取分组联系人列表
	 * @param int $user_id 用户ID
	 * @return array
	 */
	public function get_contact_category_list($user_id)
	{
		$sql = sprintf("SELECT category_id,cid FROM %s WHERE uid = %d",
			$this->get_table($user_id, 'contact_classes'), $user_id);
		$query = $this->db->query($sql);
		$result = array();
		if ($query->count())
		{
			$res = $query->result_array(FALSE);
			foreach ($res as $val)
			{
				$result[$val['cid']][] = $val['category_id'];
			}
		}
		return $result;
	}

	/**
	 * 批量设置联系人分组
	 * @param int $user_id 用户ID
	 * @param array $add_ids 需增加联系人与分组ID对应关系
	 * @param array $delete_ids 需删除联系人与分组ID对应关系
	 * @return bool
	 */
	public function set_contact_category($user_id, $add_ids, $delete_ids)
	{
		$add_sql = '';
		$add_confirm = FALSE;
		$delete_sqls = array();
		if ($add_ids)
		{
			$add_sql = sprintf("INSERT INTO %s (uid, category_id, cid) VALUES ",
				$this->get_table($user_id, 'contact_classes'));
			foreach ($add_ids as $cid => $category_ids)
			{
				if ($category_ids)
				{
					foreach ($category_ids as $category_id)
					{
						$add_sql .= sprintf('(%d, %d, %d),', $user_id, $category_id, $cid);
					}
					$add_confirm = TRUE;
				}
			}
			$add_sql = rtrim($add_sql, ',') . ';';
		}

		if ($delete_ids)
		{
			foreach ($delete_ids as $cid => $category_ids)
			{
				if ($category_ids)
				{
					$delete_sqls[] = sprintf("DELETE FROM %s WHERE uid = %d AND cid = %d AND category_id IN (%s);"
						, $this->get_table($user_id, 'contact_classes'), $user_id, $cid, implode(',', $category_ids));
				}
			}
		}
		if ($add_confirm)
		{
			$sqls = array_merge(array($add_sql), $delete_sqls);
		}
		else
		{
			$sqls = $delete_sqls;
		}
		if ($sqls)
		{
			$in_trans = $this->db->in_trans();
			//判断是否有外围事务，如果无则使用事务
			if (! $in_trans)
			{
				$this->db->begin();
			}
			foreach ($sqls as $sql)
			{
				$query = $this->db->query($sql);
				if (! $query)
				{
					$this->db->rollback();
				}
			}
			//判断是否有外围事务，如果无则提交事务，有则返回由外围提交事务
			if (! $in_trans)
			{
				if ($this->db->commit())
				{
					return TRUE;
				}
				else
				{
					return FALSE;
				}
			}
		}
		return TRUE;
	}

	/**
	 * 获取所有分组
	 * @param int $user_id 用户ID
	 * @return array
	 */
	public function get_category_list($user_id)
	{
		$sql = sprintf("SELECT id,category_name FROM %s WHERE uid = %d ORDER BY order_by",
			$this->get_table($user_id, 'contact_categories'), $user_id);
		$query = $this->db->query($sql);

		if ($query->count())
		{
			$list = array();
			$res = $query->result_array(FALSE);
			foreach ($res as $category)
			{
				$list[$category['id']] = $category['category_name'];
			}
			return $list;
		}
		return array();
	}

	/**
	 * 获取所有分组
	 * @param int $user_id 用户ID
	 * @param int $dateline 时间戳
	 * @return array
	 */
	public function get_snapshot_category_list($user_id, $dateline)
	{
		$sql = sprintf("SELECT id,category_name FROM %s WHERE uid = %d AND snapshot_id = %d ORDER BY order_by",
			$this->get_table($user_id, 'contact_categories_snapshot', $dateline), $user_id, $dateline);
		$query = $this->db->query($sql);

		if ($query->count())
		{
			$list = array();
			$res = $query->result_array(FALSE);
			foreach ($res as $category)
			{
				$list[$category['id']] = $category['category_name'];
			}
			return $list;
		}
		return array();
	}

	/**
	 * 修改联系人分组名
	 * @param int $user_id 用户ID
	 * @param int $id 分组ID
	 * @param array $ids 联系人ID
	 * @return bool
	 */
	public function add_contact_category($user_id, $id, $ids)
	{
		$sql = sprintf("INSERT INTO %s (uid, category_id, cid) VALUES ", $this->get_table($user_id, 'contact_classes'));
		foreach ($ids as $cid)
		{
			$sql .= sprintf('(%d, %d, %d),', $user_id, $id, $cid);
		}
		$sql = rtrim($sql, ',') . ';';
		$query = $this->db->query($sql);
		if ($query)
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * 删除联系人所有分组
	 * @param int $user_id 用户ID
	 * @param int $id 分组ID
	 * @param array $ids 联系人ID
	 * @return bool
	 */
	public function delete_contact_category($user_id, $ids, $id = NULL)
	{
		if ($id)
		{
			$append_sql = ' AND category_id = ' . $id;
		}
		else
		{
			$append_sql = '';
		}
		$sql = sprintf("DELETE FROM %s WHERE uid = %d %s AND cid IN (%s)",
			$this->get_table($user_id, 'contact_classes'), $user_id, $append_sql, implode(',', $ids));
		$query = $this->db->query($sql);
		if ($query)
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * 更新联系人修改时间
	 * @param int $user_id 用户ID
	 * @param array $ids 联系人ID
	 * @param string $source 来源
	 * @return bool
	 */
	public function update_contact_modified($user_id, $ids, $source)
	{
		$sql = sprintf("UPDATE %s SET modified = %d, SOURCE = %s WHERE uid = %d AND cid IN (%s)",
			$this->get_table($user_id, 'contacts'), api::get_now_time(), $this->db->escape($source),
			$user_id,
			implode(',', $ids));
		$query = $this->db->query($sql);
		if ($query)
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * 获取分组中存在的联系人ID
	 * @param int $user_id 用户ID
	 * @param array $ids 联系人ID
	 * @return array
	 */
	public function get_valid_category_contact_ids($user_id, $ids)
	{
		$sql = sprintf("SELECT cid FROM %s WHERE uid = %d AND cid IN (%s)",
			$this->get_table($user_id, 'contact_classes'), $user_id, implode(',', $ids));
		$query = $this->db->query($sql);
		$cids = array();
		if ($query->count())
		{
			$result = $query->result_array(FALSE);
			foreach ($result as $res)
			{
				$cids[] = $res['cid'];
			}
		}
		return $cids;
	}

	/**
	 * 更新分组排序
	 * @param int $user_id 用户ID
	 * @param array $ids 分组排序ID
	 * @return bool
	 */
	public function update_category_order_by($user_id, $ids)
	{
		$table = $this->get_table($user_id, 'contact_categories');
		$this->db->begin();
		foreach ($ids as $order_by => $id)
		{
			$sql = sprintf("UPDATE %s SET order_by = %d WHERE id = %d AND uid = %d", $table, $order_by, $id, $user_id);
			$query = $this->db->query($sql);
			if (! $query)
			{
				$this->db->rollback();
			}
		}
		if ($this->db->commit())
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * 获取分组名
	 * @param int $user_id 用户ID
	 * @param int $id 分组ID
	 * @return string
	 */
	public function get_category_name($user_id, $id)
	{
		$sql = sprintf("SELECT category_name FROM %s WHERE id = %d AND uid = %d LIMIT 1",
			$this->get_table($user_id, 'contact_categories'), $id, $user_id);
		$query = $this->db->query($sql);
		if ($query->count())
		{
			$result = $query->result_array(FALSE);
			return $result['0']['category_name'];
		}
		return '';
	}

	/**
	 * 获取分组联系人数
	 * @param int $user_id 用户ID
	 * @param int $category_id 分组ID
	 * @return string
	 */
	public function get_category_contact_count($user_id, $category_id)
	{
		$sql = sprintf("SELECT COUNT(*) AS cnt FROM %s WHERE category_id = %d AND uid = %d LIMIT 1",
			$this->get_table($user_id, 'contact_classes'), $category_id, $user_id);
		$query = $this->db->query($sql);
		if ($query->count())
		{
			$result = $query->result_array(FALSE);
			return $result['0']['cnt'];
		}
		return 0;
	}

	/**
	 * 获取分组数
	 * @param int $user_id 用户ID
	 * @return int
	 */
	public function get_category_count($user_id)
	{
		if (Kohana::config('contact.from_category_table'))
		{
			$sql = sprintf("SELECT COUNT(DISTINCT(category_id)) AS cnt FROM %s WHERE uid = %d LIMIT 1",
				$this->get_table($user_id, 'contact_classes'), $user_id);
			$query = $this->db->query($sql);
			if ($query->count())
			{
				$result = $query->result_array(FALSE);
				return $result['0']['cnt'];
			}
			return 0;
		}
		else
		{
			$sql = sprintf("SELECT DISTINCT(category) FROM %s WHERE uid = %d AND category != ''",
				$this->get_table($user_id, 'contacts'), $user_id);
			$query = $this->db->query($sql);
			$result = array();
			if ($query->count())
			{
				$res = $query->result_array(FALSE);
				foreach ($res as $val)
				{
					foreach (explode(',', $val['category']) as $category)
					{
						$result[] = $category;
					}
				}
			}
			return count($result);
		}
	}

	/**
	 * 获取分组数
	 * @param int $user_id 用户ID
	 * @return int
	 */
	public function get_all_category_contact_count($user_id)
	{
		$sql = sprintf("SELECT COUNT(DISTINCT(cid)) AS cnt FROM %s WHERE uid = %d LIMIT 1",
			$this->get_table($user_id, 'contact_classes'), $user_id);
		$query = $this->db->query($sql);
		if ($query->count())
		{
			$result = $query->result_array(FALSE);
			return $result['0']['cnt'];
		}
		return 0;
	}

	/**
	 * 删除分组
	 * @param int $user_id 用户ID
	 * @param int $id 分组ID
	 * @return bool
	 */
	public function delete_category($user_id, $id)
	{
		$sqls = array();
		$sqls[] = sprintf("DELETE FROM %s WHERE id = %d AND uid = %d",
			$this->get_table($user_id, 'contact_categories'), $id, $user_id);

		$sqls[] = sprintf("DELETE FROM %s WHERE category_id = %d AND uid = %d",
			$this->get_table($user_id, 'contact_classes'), $id, $user_id);

		$this->db->begin();
		foreach ($sqls as $sql)
		{
			$query = $this->db->query($sql);
			if (! $query)
			{
				$this->db->rollback();
			}
		}
		if ($this->db->commit())
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * 新增分组
	 * @param $user_id 用户ID
	 * @param $name 分组名
	 * @return int|mixed
	 */
	public function add_category($user_id, $name)
	{
		$sql = sprintf("INSERT INTO %s (uid, category_name) VALUES (%d, %s)",
			$this->get_table($user_id, 'contact_categories'), $user_id, $this->db->escape($name));
		$query = $this->db->query($sql);
		if ($query->count())
		{
			return $query->insert_id();
		}
		return 0;
	}

	/**
	 * 修改分组
	 * @param int $user_id 用户ID
	 * @param int $id 分组ID
	 * @param string $name 分组名
	 * @return bool
	 */
	public function update_category($user_id, $id, $name)
	{
		$sql = sprintf("UPDATE %s SET category_name = %s WHERE id = %d AND uid = %d",
			$this->get_table($user_id, 'contact_categories'), $this->db->escape($name), $id, $user_id
		);
		$query = $this->db->query($sql);
		if ($query)
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * 检查分组名是否存在
	 * @param $user_id 联系人ID
	 * @param $name 分组名
	 * @param $id 分组ID
	 * @return bool
	 */
	public function check_category_name($user_id, $name, $id)
	{
		$filter = '';
		if (! empty($id))
		{
			$filter = "AND id != $id";
		}
		$sql = sprintf("SELECT id FROM %s WHERE uid = %d AND category_name = %s %s LIMIT 1",
			$this->get_table($user_id, 'contact_categories'), $user_id, $this->db->escape($name), $filter);
		$query = $this->db->query($sql);
		if ($query->count())
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * 根据分组名设置联系人分组
	 * @param int $user_id 用户ID
	 * @param int $id 联系人ID
	 * @param string $names 分组名
	 * @return bool
	 */
	public function set_contact_category_by_name($user_id, $id, $names)
	{
		if ($names)
		{
			$names = explode(',', $names);
			$categories = $this->get_category_list($user_id);
			//分组名不存在，创建分组
			if ($diffs = array_diff($names, $categories))
			{
				foreach ($diffs as $name)
				{
					$category_id = $this->add_category($user_id, $name);
					$categories[$category_id] = $name;
				}
			}
			$category_ids = array_keys(array_intersect($categories, $names));

			$old_category_ids = $this->get_category_by_cid($user_id, $id);

			$add_ids = array_diff($category_ids, $old_category_ids);
			$delete_ids = array_diff($old_category_ids, $category_ids);
			if ($add_ids OR $delete_ids)
			{
				return $this->set_contact_category($user_id, array($id => $add_ids), array($id => $delete_ids));
			}
		}
		return TRUE;
	}

	/**
	 * 根据联系人ID获取分组名
	 * @param int $user_id 用户ID
	 * @param int $cid 联系人ID
	 * @return string
	 */
	public function get_category_name_by_cid($user_id, $cid)
	{
		$category_ids = $this->get_category_by_cid($user_id, $cid);
		if ($category_ids)
		{
			return implode(',', array_intersect_key($this->get_category_list($user_id), array_flip($category_ids)));
		}
		return '';
	}

	/**
	 * 根据时间戳和联系人ID获取快照分组名
	 * @param int $user_id 用户ID
	 * @param int $cid 联系人ID
	 * @param int $dateline 时间戳
	 * @return string
	 */
	public function get_snapshot_category_name_by_cid($user_id, $cid, $dateline)
	{
		$category_ids = $this->get_snapshot_category_by_cid($user_id, $cid, $dateline);
		if ($category_ids)
		{
			return implode(',',
				array_intersect_key($this->get_snapshot_category_list($user_id, $dateline), array_flip($category_ids)));
		}
		return '';
	}

	/**
	 * 根据联系人ID获取分组名
	 * @param int $user_id 用户ID
	 * @param int $cid 联系人ID
	 * @return string
	 */
	public function get_category_by_cid($user_id, $cid)
	{
		$sql = sprintf("SELECT category_id FROM %s WHERE uid = %d AND cid = %d",
			$this->get_table($user_id, 'contact_classes'),
			$user_id, $cid
		);
		$query = $this->db->query($sql);
		if ($query->count())
		{
			$result = array();
			$res = $query->result_array(FALSE);
			foreach ($res as $val)
			{
				$result[] = $val['category_id'];
			}
			return $result;
		}
		return array();
	}

	/**
	 * 根据时间戳和联系人ID获取快照分组名
	 * @param int $user_id 用户ID
	 * @param int $cid 联系人ID
	 * @param int $dateline 时间戳
	 * @return string
	 */
	public function get_snapshot_category_by_cid($user_id, $cid, $dateline)
	{
		$sql = sprintf("SELECT category_id FROM %s WHERE uid = %d AND snapshot_id = %d AND cid = %d",
			$this->get_table($user_id, 'contact_classes_snapshot'),
			$user_id, $dateline, $cid
		);
		$query = $this->db->query($sql);
		if ($query->count())
		{
			$result = array();
			$res = $query->result_array(FALSE);
			foreach ($res as $val)
			{
				$result[] = $val['category_id'];
			}
			return $result;
		}
		return array();
	}

	/**
	 * 是否有同步历史
	 * @param int $user_id 用户ID
	 * @param int $app_id 应用ID
	 * @param string $device_id 设备ID
	 * @return bool
	 */
	public function has_history($user_id, $app_id, $device_id = '')
	{
		// 分库前旧数据连接旧数据库
		if (Kohana::config('contact.new_db_rule'))
		{
			$divide_table = Kohana::config('contact.new_divide_table');
			$db_group = 'contact_slave_' . ($user_id % Kohana::config('contact.db_count'));
		}
		else
		{
			$divide_table = Kohana::config('contact.old_divide_table');
			$db_group = 'contact_slave_' . rand(0, 1);
		}

		$db = Database::instance($db_group);

		$base_table = 'contact_history';
		// 根据不同分表规则分表
		if (isset($divide_table[$base_table]))
		{
			$table = $base_table . '_' . ($user_id % $divide_table[$base_table]);
		}
		else
		{
			$table = $base_table;
		}

		if (Kohana::config('contact.new_db_rule'))
		{
			$table .= '_' . $user_id % Kohana::config('contact.db_count');
		}

		$sql = sprintf("SELECT id FROM %s WHERE uid = %d AND appid = %d %s LIMIT 1;",
			$table, $user_id, $app_id,
			($device_id ? 'AND device_id = ' . $db->escape($device_id) : ''));

		$query = $db->query($sql);
		if ($query->count())
		{
			return TRUE;
		}
		return FALSE;
	}
}
