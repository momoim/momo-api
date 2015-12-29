<?php
defined('SYSPATH') or die('No direct script access.');
/*
 * [MOMO API] (C)1999-2012 ND Inc.
 * 联系人模型文件
 */
/**
 * 联系人模型
 */
class Contact_Model extends Model implements Contact_Interface {

	/**
	 * 联系人数据映射
	 * @var Contact_Mapper
	 */
	public $contact_mapper;

	/**
	 * 缓存
	 * @var Cache
	 */
	protected $cache;

	/**
	 * 客户端来源
	 * @var string
	 */
	public $source = '';

	/**
	 * 客户端设备ID
	 * @var string
	 */
	public $device_id;

	/**
	 * 手机型号
	 * @var string
	 */
	public $phone_model;

	/**
	 * 应用ID
	 * @var int
	 */
	public $appid = 0;

	/**
	 * 缓存前缀
	 * @var string
	 */
	protected $cache_pre;

	/**
	 * 实例
	 * @var Contact_Model
	 */
	protected static $instance;

	/**
	 * 联系人变更历史
	 * @var array
	 */
	protected $history = array();

	/**
	 * 操作说明
	 * @var string
	 */
	protected $operation = '';

	/**
	 * 是否保存快照
	 * @var bool
	 */
	protected $is_snapshot = FALSE;

	/**
	 * 是否新建历史
	 * @var bool
	 */
	protected $is_history = FALSE;

	/**
	 * 联系人总数
	 * @var int
	 */
	protected $count;

	/**
	 * 分组数
	 * @var int
	 */
	protected $category_count;

	/**
	 * 当前用户国家码
	 * @var int
	 */
	protected $country_code = 0;

	/**
	 * 任务数据
	 * @var array
	 */
	protected $task_data = array();

	/**
	 * 本批次已经修改的联系人ID
	 * @var array
	 */
	protected $changed_ids = array();

	/**
	 * 单例模式
	 * @return Contact_Model
	 */
	public static function &instance()
	{
		if (! isset(self::$instance))
		{
			// Create a new instance
			self::$instance = new Contact_Model();
		}
		return self::$instance;
	}

	/**
	 * 构造函数,
	 * 为了避免循环实例化，请调用单例模式
	 */
	public function __construct()
	{
		parent::__construct();
		$this->cache = Cache::instance('contact');
		$this->cache_pre = CACHE_PRE . 'contact_';
		$this->contact_mapper = Contact_Mapper::instance();
	}

	/**
	 * 从缓存获取联系人列表或联系人详情
	 * @param int $user_id  用户ID
	 * @param int $id       联系人ID
	 * @param string $callback id为NULL时，允许设置回调方法
	 * @param int $info     无实际用途，仅兼容原方法
	 * @return array|Contact|bool
	 */
	public function get($user_id, $id = NULL, $callback = '', $info = 1)
	{
		$callback = $callback == '' ? 'get_list' : $callback;
		if ($id !== NULL)
		{
			$result = $this->cache->get($this->cache_pre . 'find_by_id_' . $user_id . '_' . $id);
		}
		else
		{
			$result = $this->cache->get($this->cache_pre . $callback . '_' . $user_id);
		}

		//重新生成缓存
		/*
		if ($id === NULL AND ! empty($result) AND ! isset($result[key($result)]['tels']))
		{
			$result = NULL;
		}
		*/
		if ($id === NULL AND $result)
		{
			foreach ($result as $value)
			{
				if (! isset($value['id']))
				{
					$result = NULL;
					break;
				}
			}
		}

		if (empty($result))
		{
			if ($id !== NULL)
			{
				$result = call_user_func(
					array(
					     $this,
					     'find_by_id'
					), $user_id, $id
				);
				$this->cache->set($this->cache_pre . 'find_by_id_' . $user_id . '_' . $id, $result);
			}
			else
			{
				$result = call_user_func(
					array(
					     $this,
					     $callback
					), $user_id
				);
				if ($callback == 'get_list')
				{
					//获取电话信息
					$tels = $this->contact_mapper->get_info_list(
						$user_id, NULL, 'tels', FALSE, FALSE, array_merge(array('cid'), Contact::$allow_cols['tels'])
					);
					//获取邮箱信息
					$emails = $this->contact_mapper->get_info_list(
						$user_id, NULL, 'emails', FALSE, FALSE,
						array_merge(array('cid'), Contact::$allow_cols['emails'])
					);
					foreach ($tels as $value)
					{
						$cid = $value['cid'];
						unset($value['cid']);
						if (in_array($cid, array_keys($result)))
						{
							$result[$cid]['tels'][] = $value;
						}
					}
					foreach ($emails as $value)
					{
						$cid = $value['cid'];
						unset($value['cid']);
						if (in_array($cid, array_keys($result)))
						{
							$result[$cid]['emails'][] = $value;
						}
					}
				}
				$this->cache->set($this->cache_pre . $callback . '_' . $user_id, $result);
			}
		}
		return $result;
	}

	/**
	 * 根据联系人ID获取联系人
	 * @param int $user_id 用户ID
	 * @param int $id      联系人ID
	 * @return Contact|bool
	 */
	public function find_by_id($user_id, $id)
	{
		$contact = $this->contact_mapper->find_by_id($user_id, $id);
		return $contact;
	}

	/**
	 * 根据时间戳和联系人ID获取快照联系人
	 * @param int $user_id 用户ID
	 * @param int $id      联系人ID
	 * @param int $dateline 快照ID
	 * @return Contact|bool
	 */
	public function find_by_dateline_id($user_id, $id, $dateline)
	{
		$contact = $this->contact_mapper->find_by_dateline_id($user_id, $id, $dateline);
		return $contact;
	}

	/**
	 * 获取联系人列表
	 * @param int $user_id 用户ID
	 * @return array
	 */
	public function get_list($user_id)
	{
		return $this->contact_mapper->find_by_user_id($user_id);
	}

	/**
	 * 获取回收站联系人列表
	 * @param int $user_id 用户ID
	 * @param int $offset  开始位置
	 * @param int $limit   获取数量
	 * @return array
	 */
	public function get_recycled_list($user_id, $offset = 0, $limit = 5000)
	{
		$key = $this->cache_pre . 'recycled_list_' . $user_id . '_' . $offset . '_' . $limit;
		$update_key = $this->cache_pre . 'recycled_list_update_' . $user_id;
		$list = $this->cache->get($key);
		$update_time = $this->cache->get($update_key);
		if (empty($list) OR (float) $list['update'] < (float) $update_time)
		{
			$recycled_list = $this->contact_mapper->get_recycled_list($user_id, $offset, $limit);
			$list = array(
				'data'   => $recycled_list,
				'update' => microtime(TRUE)
			);
			$this->cache->set($key, $list);
		}
		return ! empty($list) ? $list['data'] : array();
	}

	/**
	 * 更新缓存
	 * @param int $user_id 用户ID
	 * @param string $update_key 缓存key
	 * @return void
	 */
	public function update_cache($user_id, $update_key)
	{
		$update_key = $this->cache_pre . $update_key . '_' . $user_id;
		$this->cache->set($update_key, microtime(TRUE), NULL, 0);
	}

	/**
	 * 获取回收站联系人数
	 * @param $user_id 用户ID
	 * @return int
	 */
	public function get_recycled_count($user_id)
	{
		return $this->contact_mapper->get_count($user_id, 'recycled');
	}

	/**
	 * 获取系统分组联系人数
	 * @param int $user_id 用户ID
	 * @return array
	 */
	public function get_count($user_id)
	{
		$count = array(
			'all_count'         => $this->contact_mapper->get_count($user_id),
			'category_count'    => $this->contact_mapper->get_category_count($user_id),
			'recycled_count'    => $this->contact_mapper->get_count($user_id, 'recycled'),
			'no_category_count' => 0,
		);

		//@todo 待数据迁移后去除判断
		if (Kohana::config('contact.from_category_table'))
		{
			$count['no_category_count'] =
				$count['all_count'] - $this->contact_mapper->get_all_category_contact_count($user_id);
		}
		else
		{
			$count['no_category_count'] = $this->contact_mapper->get_count($user_id, 'no_category');
		}

		return $count;
	}

	/**
	 * 移动联系人到回收站
	 * @param int $user_id 用户ID
	 * @param array $ids     联系人ID
	 * @return array 结果数组
	 */
	public function move_contact_to_recycle($user_id, $ids)
	{
		$list = $this->get($user_id);
		$contact_ids = array_keys($list);
		$deleted_ids = $statuses = array();
		$now = api::get_now_time();
		foreach ($ids as $id)
		{
			if (in_array($id, $contact_ids))
			{
				$deleted_ids[] = (int) $id;
				$statuses[$id] = array(
					'status'      => 200,
					'id'          => (int) $id,
					'modified_at' => $now
				);
			}
			else
			{
				$statuses[$id] = array(
					'status'      => 404,
					'id'          => (int) $id,
					'modified_at' => 0
				);
			}
		}
		if (! empty($deleted_ids))
		{
			//修改前保存快照
			if ($this->save_snapshot($user_id))
			{
				$result = $this->contact_mapper->move_contact_to_recycle(
					$user_id, $deleted_ids, $this->get_source_name()
				);
				if ($result)
				{
					$this->prepare_task($user_id, array(), array(), $deleted_ids);
				}
				else
				{
					foreach ($deleted_ids as $id)
					{
						$statuses[$id]['status'] = 500;
					}
				}
			}
			else
			{
				foreach ($deleted_ids as $id)
				{
					$statuses[$id]['status'] = 500;
				}
			}
		}
		return array_values($statuses);
	}

	/**
	 * 移动回收站联系人到联系人
	 * @param int $user_id 用户ID
	 * @param array $ids     联系人ID
	 * @return array 结果数组
	 */
	public function move_recycle_to_contact($user_id, $ids)
	{
		$contact_ids = $this->contact_mapper->get_valid_recycled_ids($user_id, $ids);
		$recovered_ids = $statuses = array();
		$now = api::get_now_time();
		foreach ($ids as $id)
		{
			if (in_array($id, $contact_ids))
			{
				$recovered_ids[] = (int) $id;
				$statuses[$id] = array(
					'status'      => 200,
					'id'          => (int) $id,
					'modified_at' => $now
				);
			}
			else
			{
				$statuses[$id] = array(
					'status'      => 404,
					'id'          => (int) $id,
					'modified_at' => 0
				);
			}
		}
		if (! empty($recovered_ids))
		{
			//修改前保存快照
			if ($this->save_snapshot($user_id))
			{
				$added_ids = $this->contact_mapper->move_recycle_to_contact(
					$user_id, $recovered_ids, $this->get_source_name()
				);
				if ($added_ids)
				{
					$this->prepare_task(
						$user_id, $added_ids, array(), array(),
						$recovered_ids
					);
				}
				else
				{
					foreach ($recovered_ids as $id)
					{
						$statuses[$id]['status'] = 500;
					}
				}
			}
			else
			{
				foreach ($recovered_ids as $id)
				{
					$statuses[$id]['status'] = 500;
				}
			}
		}
		return array_values($statuses);
	}

	/**
	 * 从回收站删除联系人
	 * @param int $user_id 用户名
	 * @param array $ids     联系人ID
	 * @return array 成功的联系人ID数组
	 */
	public function delete($user_id, $ids = array())
	{
		$contact_ids = $this->contact_mapper->get_valid_recycled_ids($user_id, $ids);
		if ($ids)
		{
			$deleted_ids = array();
			foreach ($ids as $id)
			{
				if (in_array($id, $contact_ids))
				{
					$deleted_ids[] = (int) $id;
				}
			}
		}
		else
		{
			$deleted_ids = $contact_ids;
		}
		if (! empty($deleted_ids))
		{
			$result = $this->contact_mapper->delete($user_id, $ids ? $deleted_ids : $ids);
			if ($result)
			{
				$this->prepare_task($user_id, array(), array(), array(), $deleted_ids);
			}
			else
			{
				$deleted_ids = array();
			}
		}
		return $deleted_ids;
	}

	/**
	 * 批量新增联系人
	 * @param int $user_id 用户ID
	 * @param array $data    联系人信息数组
	 * @param bool $force   是否强制新增联系人
	 * @return array 新增结果
	 */
	public function add_batch($user_id, $data, $force = FALSE)
	{
		// 超时设置延长
		set_time_limit(300);
		ignore_user_abort(TRUE);
		$success_count = 0;
		$result = $added_ids = $updated_ids = $send_update_ids = array();
		foreach ($data as $key => $contact_arr)
		{
			$contact = Contact_Helper::array_to_contact($contact_arr, $this->get_country_code($user_id));
			$contact->set_user_id($user_id)->set_source($this->get_source_name());
			$status = $this->add($contact, $force);
			$id = $contact->get_id();
			switch ($status)
			{
				case SUCCESS:
					// 新增联系人成功

					$result[$key] = array(
						'status'      => 201,
						'id'          => $id,
						'modified_at' => $contact->get_modified_at()
					);
					$added_ids[] = $id;
					$success_count ++;
					break;
				case MERGE_SUCCESS:
					// 联系人被合并
					$result[$key] = array(
						'status'      => 303,
						'id'          => $id,
						'modified_at' => 0
					);
					$success_count ++;
					$updated_ids[] = $id;
					$this->changed_ids[] = $id;
					break;
				case NO_MODIFY_MERGE_SUCCESS:
					// 联系人被合并到现有的联系人，并且现有的联系人没有修改
					// 合并到同一批添加的联系人
					if (in_array(
						$id,
						$send_update_ids, TRUE
					)
					)
					{
						$result[$key] = array(
							'status'      => 303,
							'id'          => $id,
							'modified_at' => 0
						);
					}
					else
					{
						$result[$key] = array(
							'status'      => 303,
							'id'          => $id,
							'modified_at' => $contact->get_modified_at()
						);
					}
					break;
				default:
					// 未返回以上状态
					$result[$key] = array(
						'status'      => 500,
						'id'          => 0,
						'modified_at' => 0
					);
					break;
			}
		}
		// 清除联系人列表和合并的联系人缓存
		if ($success_count)
		{
			$this->prepare_task($user_id, $added_ids, $updated_ids);
		}
		return $result;
	}

	/**
	 * 新增联系人
	 * @param $contact Contact 联系人对象
	 * @param $force   bool 是否强制新增
	 * @return int FAIL 新增失败 SUCCESS 新增成功 MERGE_SUCCESS 合并成功
	 */
	public function add(Contact $contact, $force = FALSE)
	{
		if ($force)
		{
			//修改前保存快照
			if ($this->save_snapshot($contact->get_user_id()) AND
				$this->contact_mapper->insert($contact)
			)
			{
				return SUCCESS;
			}
			return FAIL;
		}
		else
		{
			$is_append = FALSE;
			$new_contact = $this->_merge_duplicate_contact($contact, $is_append);
			if ($new_contact === FALSE)
			{
				if ($this->save_snapshot($contact->get_user_id()) AND
					$this->contact_mapper->insert($contact)
				)
				{
					return SUCCESS;
				}
				return FAIL;
			}
			else
			{
				// 当完全相同时不更新修改时间
				if ($is_append == FALSE)
				{
					$contact->set_id($new_contact->get_id())->set_modified_at(
						$new_contact->get_modified_at()
					)->set_tels(
							$new_contact->get_tels()
						);
					return NO_MODIFY_MERGE_SUCCESS;
				}
				else
				{
					if ($this->save_snapshot($contact->get_user_id()))
					{
						$status = $this->contact_mapper->update(
							$new_contact,
							'overwrite'
						);
						if ($status == SUCCESS)
						{
							$contact->set_id($new_contact->get_id())->set_modified_at(
								$new_contact->get_modified_at()
							)->set_tels(
									$new_contact->get_tels()
								);
							return MERGE_SUCCESS;
						}
					}
					return FAIL;
				}
			}
		}
	}

	/**
	 * 合并重复的联系人
	 * @param Contact $contact   联系人对象
	 * @param bool $is_append 是否新增数据
	 * @return Contact|bool
	 */
	private function _merge_duplicate_contact(Contact $contact, &$is_append)
	{
		$formatted_name = Contact_Helper::name_to_formatted_name(
			$contact->get_family_name(), $contact->get_given_name(), $contact->get_prefix(),
			$contact->get_middle_name(), $contact->get_suffix()
		);
		/*  1.联系人姓名相同，且有一个手机号码相同的，判断为同一个联系人，
			自动进行合并处理，合并时需要判断好友关系、公司、部门、职位、生日、
	        昵称、头像这几个信息是否有冲突，如果没有冲突则可以合并两个联系人
		*/
		if (! empty($formatted_name))
		{
			$same_name_tel_tag = new Contact();
			$same_name_tel_tag->set_user_id($contact->get_user_id())->set_formatted_name(
				$formatted_name
			);
			$result = $this->contact_mapper->find_by_tags(
				$same_name_tel_tag
			);
			if (! empty($result))
			{
				//冲突检测
				foreach ($result as $id)
				{
					if (! in_array($id, $this->changed_ids))
					{
						$find_contact = $this->get($contact->get_user_id(), $id);
					}
					else
					{
						$find_contact = $this->find_by_id($contact->get_user_id(), $id);
					}
					if ($find_contact !== FALSE)
					{
						//比较、合并失败进行下一项比较，不退出
						if ($this->compare_info(
								$contact->get_tels(), $find_contact->get_tels()
							) or $this->compare_info(
								$contact->get_emails(), $find_contact->get_emails()
							) or $this->compare_info(
								$contact->get_ims(), $find_contact->get_ims()
							) or $this->compare_info(
								$contact->get_urls(), $find_contact->get_urls()
							)
						)
						{
							//比较电话、邮箱、IM、网址看是否有交集
							if ($find_contact->merge(
								$contact, $is_append
							)
							)
							{
								return $find_contact;
							}
						}
						elseif (! $contact->get_tels() AND ! $contact->get_emails() AND
							! $contact->get_ims() AND ! $contact->get_urls()
						)
						{
							//3 所有信息完全相同的联系人，可以合并
							$contact_array = $contact->to_array();
							//去除联系人的ID、创建时间、修改时间、来源、分组、自定义字段，姓名只比较全名后比较是否完全相同
							unset(
							$contact_array['id'], $contact_array['created_at'],
							$contact_array['modified_at'],
//							$contact_array['health'],
							$contact_array['category'],
							$contact_array['source'],
							$contact_array['customs'],
							$contact_array['given_name'],
							$contact_array['family_name'],
							$contact_array['middle_name'],
							$contact_array['prefix'],
							$contact_array['suffix']
							);
							$find_contact_array = array_intersect_key(
								$find_contact->to_array(), $contact_array
							);
							list ($new_md5, $curr_md5) = Photo_Controller::getoriginmd5(
								array(
								     $contact_array['avatar'],
								     $find_contact_array['avatar']
								)
							);
							if (empty($contact_array['avatar']) or $contact_array['avatar'] ==
								$find_contact_array['avatar'] or
								$new_md5 == $curr_md5
							)
							{
								unset($contact_array['avatar'],
								$find_contact_array['avatar']);
								if ($contact_array == $find_contact_array)
								{
									return $find_contact;
								}
							}
						}
					}
				}
			}
		}
		//2 两个联系人中有Email、IM、电话号码相同，其中一个没有姓名，在其他信息没有冲突情况下，可以合并
		$tels = $contact->get_tels();
		$emails = $contact->get_emails();
		$ims = $contact->get_ims();
		if (! empty($tels) or ! empty($emails) or ! empty($ims))
		{
			$ids = array();
			if (! empty($tels))
			{
				$ids = array_merge(
					$ids,
					$this->contact_mapper->get_id_by_info(
						$contact->get_user_id(),
						'tels', $tels
					)
				);
			}
			if (empty($ids) AND ! empty($emails))
			{
				$ids = array_merge(
					$ids,
					$this->contact_mapper->get_id_by_info(
						$contact->get_user_id(),
						'emails', $emails
					)
				);
			}
			if (empty($ids) AND ! empty($ims))
			{
				$ids = array_merge(
					$ids,
					$this->contact_mapper->get_id_by_info(
						$contact->get_user_id(),
						'ims', $ims
					)
				);
			}
			foreach ($ids as $id)
			{
				$find_contact = $this->get($contact->get_user_id(), $id);
				if ($find_contact !== FALSE AND $find_contact->merge($contact, $is_append))
				{
					return $find_contact;
				}
			}
		}
		//空名字，主要信息为空，存在信息完全相同的联系人不处理
		/*
		if (empty($formatted_name))
		{
			$same_name_tel_tag = new Contact();
			$same_name_tel_tag->set_user_id($contact->get_user_id())->set_formatted_name(
				$formatted_name
			);
			$result = $this->contact_mapper->find_by_tags(
				$same_name_tel_tag,
				empty($formatted_name) ? TRUE : FALSE
			);
			if (! empty($result))
			{
				//冲突检测
				foreach ($result as $id)
				{
					$find_contact = $this->get($contact->get_user_id(), $id);
					if ($find_contact !== FALSE)
					{
						//3 所有信息完全相同的联系人，可以合并
						$contact_array = $contact->to_array();
						//去除联系人的ID、创建时间、修改时间后比较是否完全相同
						unset($contact_array['id'],
						$contact_array['created_at'],
						$contact_array['modified_at'], $contact_array['health']);
						$find_contact_array = array_intersect_assoc(
							$find_contact->to_array(), $contact_array
						);
						list ($new_md5, $curr_md5) = Photo_Controller::getoriginmd5(
							array(
							     $contact_array['avatar'],
							     $find_contact_array['avatar']
							)
						);
						if (empty($contact_array['avatar']) or $contact_array['avatar'] ==
							$find_contact_array['avatar'] or $new_md5 == $curr_md5
						)
						{
							unset($contact_array['avatar'], $find_contact_array['avatar']);
							if ($contact_array == $find_contact_array)
							{
								return $find_contact;
							}
						}
					}
				}
			}
		}
		*/
		return FALSE;
	}

	/**
	 * 修改联系人，返回结果和最后一次修改时间
	 * @param int $user_id 用户ID
	 * @param int $id 联系人ID
	 * @param array $data 联系人数组
	 * @param string $mode    修改模式 default、overwrite、special
	 * @return array FAIL 修改失败 SUCCESS 修改成功 CONFLICT 冲突 MERGE_SUCCESS 合并成功
	 */
	public function edit($user_id, $id, $data, $mode = 'default')
	{
		$contact = Contact_Helper::array_to_contact($data, $this->get_country_code($user_id));
		$contact->set_user_id($user_id)->set_id($id)->set_source(
			$this->get_source_name());
		$old_contact = $this->get($user_id, $id);
		$result = FAIL;
		//联系人不存在或在回收站中
		if ($old_contact === FALSE)
		{
			$result = CONFLICT;
		}
		else
		{
			$to_update = FALSE;
			$is_append = FALSE;
			switch ($mode)
			{
				case 'overwrite':
					//覆盖模式，检查联系人是否变更
					$status = $old_contact->compare($contact);
					if ($status)
					{
						$result = NO_MODIFY_MERGE_SUCCESS;
					}
					else
					{
						$to_update = TRUE;
					}
					break;
				case 'special':
					//检查联系人是否变更
					$status = $old_contact->compare_special($contact);
					if ($status)
					{
						$result = NO_MODIFY_MERGE_SUCCESS;
					}
					else
					{
						$to_update = TRUE;
					}
					break;
				default:
					$status = $old_contact->compare($contact);
					if ($status)
					{
						$result = NO_MODIFY_MERGE_SUCCESS;
					}
					else
					{
						$modified_at = $old_contact->get_modified_at()
							? $old_contact->get_modified_at()
							:
							$old_contact->get_created_at();
						if ($modified_at == $contact->get_modified_at())
						{
							$to_update = TRUE;
						}
						else
						{
							$status = $old_contact->merge($contact, $is_append);
							if ($status === FALSE)
							{
								$result = CONFLICT;
							}
							elseif ($is_append == FALSE)
							{
								$result = NO_MODIFY_MERGE_SUCCESS;
							}
							else
							{
								//合并成功设置更新来源
								$old_contact->set_source($contact->get_source());
								$to_update = TRUE;
							}
						}
					}
					break;
			}
			if ($to_update === TRUE)
			{
				//保存快照，同时放入回收站
				if ($this->save_snapshot($user_id) AND $this->contact_mapper->move_update_contact_to_recycle(
						$user_id,
						$id, $this->source
					)
				)
				{
					//切换默认模式到覆盖模式
					$mode = $mode === 'default' ? 'overwrite' : $mode;
					if (! $is_append)
					{
						$result = $this->contact_mapper->update($contact, $mode);
					}
					else
					{
						$result = $this->contact_mapper->update($old_contact, 'overwrite');
					}
				}
			}
			//清除联系人列表缓存
			if ($result == SUCCESS OR $result == MERGE_SUCCESS)
			{
				$this->prepare_task($user_id, array(), (array) $id);
			}
			elseif ($result == NO_MODIFY_MERGE_SUCCESS)
			{
				$contact->set_created_at($old_contact->get_created_at())->set_modified_at(
					$old_contact->get_modified_at()
				);
			}
		}
		return array('result'      => $result,
		             'modified_at' => $contact->get_modified_at()
		);
	}

	/**
	 * 根据用户权限获取好友信息
	 * @param int $user_id        当前用户ID
	 * @param int $friend_user_id 获取用户ID
	 * @return Contact
	 */
	public function get_user_info($user_id, $friend_user_id)
	{
		$user_model = User_Model::instance();
		$result = $user_model->get_user_info($friend_user_id);
		if ($result !== FALSE)
		{
			$friend_country_code = $result['zone_code'];
			$data = $user_model->profile_assembly($result, $user_id);
			$data = array_merge(
				$data,
				Contact_Helper::formatted_name_to_name($data['name'])
			);
			//根据权限获取好友信息
			$user_country_code = $this->get_country_code($user_id);
			$prefix = '';
			if ($user_country_code AND $friend_country_code AND
				$user_country_code != $friend_country_code
			)
			{
				$prefix = '+' . $friend_country_code;
			}
			$array = array();
			foreach ($data as $key => $value)
			{
				if (in_array(
					$key,
					array(
					     'family_name',
					     'given_name',
					     'nickname',
					     'department',
					     'title',
					     'birthday',
					     'organization'
					)
				)
				)
				{
					$array[$key] = $value;
				}
				elseif ($key == 'tels')
				{
					if (! empty($value))
					{
						foreach ($value as $val)
						{
							if ($prefix AND strpos($val['value'], $prefix) !== 0)
							{
								$tel = $prefix . $val['value'];
							}
							else
							{
								$tel = $val['value'];
							}
							if (! empty($val['pref']))
							{
								$array[$key][] = array(
									'type'  => $val['type'],
									'value' => $tel,
									'pref'  => $val['pref']
								);
							}
							else
							{
								$array[$key][] = array(
									'type'  => $val['type'],
									'value' => $tel
								);
							}
						}
					}
				}
				elseif (in_array(
					$key,
					array(
					     'emails',
					     'ims',
					     'addresses',
					     'urls'
					)
				)
				)
				{
					if (! empty($value))
					{
						foreach ($value as $val)
						{
							$array[$key][] = $val;
						}
					}
				}
			}
			//获取好友头像
			$array['avatar'] = sns::getavatar($friend_user_id, 130);
			return Contact_Helper::array_to_contact($array, $user_country_code);
		}
		return FALSE;
	}

	/**
	 * 加入到任务
	 * @param int $user_id      用户ID
	 * @param array $added_ids    添加的联系人ID
	 * @param array $updated_ids  更新的联系人ID
	 * @param array $deleted_ids  删除的联系人ID
	 * @param array $recycled_ids 放入回收站的联系人ID
	 * @return void
	 */
	public function prepare_task(
		$user_id, $added_ids = array(), $updated_ids = array(),
		$deleted_ids = array(), $recycled_ids = array()
	)
	{
		$ids = array_merge($added_ids, $updated_ids, $deleted_ids);
		//防止批量操作写过快，读取不到上次的历史记录
		if (! empty($ids) OR $this->is_history)
		{
			$this->contact_mapper->add_history(
				$user_id, $this->appid,
				$this->source, $this->device_id, $this->phone_model, $this->operation, $this->history,
				$this->is_history,
				$added_ids, $updated_ids, $deleted_ids, $this->count, $this->category_count
			);
			$this->update_cache($user_id, 'history_list_update');
		}
		$this->task_data = array(
			'user_id'      => $user_id,
			'added_ids'    => (array) $added_ids,
			//联系人批量新增时去除被合并的联系人
			'updated_ids'  => array_diff((array) $updated_ids, $added_ids),
			'deleted_ids'  => (array) $deleted_ids,
			'recycled_ids' => (array) $recycled_ids,
		);
		$this->do_task();
	}

	/**
	 * 更新时光机历史
	 * @param int $user_id 用户ID
	 * @param int $dateline 时光机ID
	 * @return int 0 失败 1 成功 -1 历史不存在
	 */
	public function update_history($user_id, $dateline)
	{
		$history = $this->contact_mapper->get_last_history($user_id);
		if ($history['dateline'] == $dateline)
		{
			$setters = array(
				'operation' => $this->operation,
			);
			$result = $this->contact_mapper->update_history($user_id, $history['id'], $setters);
			if ($result)
			{
				$this->update_cache($user_id, 'history_list_update');
			}
			return (int) $result;
		}
		else
		{
			return - 1;
		}
	}

	/**
	 * 执行清空缓存
	 * @return void
	 */
	public function do_task()
	{
		if (! empty($this->task_data))
		{
			$user_id = $this->task_data['user_id'];
			$added_ids = $this->task_data['added_ids'];
			$updated_ids = $this->task_data['updated_ids'];
			$deleted_ids = $this->task_data['deleted_ids'];
			$recycled_ids = $this->task_data['recycled_ids'];

			$ids = array_merge($added_ids, $updated_ids, $deleted_ids);
			if ($updated_ids OR $recycled_ids OR $deleted_ids)
			{
				$this->update_cache($user_id, 'recycled_list_update');
			}

			if (! empty($ids) OR $this->is_snapshot)
			{
				Category_Model::instance()->clear_cache($user_id);
			}

			if (! empty($ids))
			{
				foreach ($ids as $id)
				{
					$this->cache->delete($this->cache_pre . 'find_by_id_' . $user_id . '_' . $id);
				}
				$this->cache->delete($this->cache_pre . 'get_list_' . $user_id);
				$this->cache->delete($this->cache_pre . 'get_count_' . $user_id);
				Friend_Model::instance()->del_user_link_cache($user_id);
			}
		}
	}

	/**
	 * 异步发送消息
	 */
	public function send_message()
	{
		if (! empty($this->task_data))
		{
			$user_id = $this->task_data['user_id'];
			$added_ids = $this->task_data['added_ids'];
			$updated_ids = $this->task_data['updated_ids'];
			$deleted_ids = $this->task_data['deleted_ids'];

			$ids = array_merge($added_ids, $updated_ids, $deleted_ids);
			if (! empty($ids))
			{
//				foreach ($ids as $id)
//				{
//					$this->cache->delete($this->cache_pre . 'find_by_id_' . $user_id . '_' . $id);
//				}
//				$this->cache->delete($this->cache_pre . 'get_list_' . $user_id);
//				$this->cache->delete($this->cache_pre . 'get_count_' . $user_id);
//				Friend_Model::instance()->del_user_link_cache($user_id);

				//发送MQ消息通知联系人更新
				$data = array();
				foreach ($added_ids as $id)
				{
					$data[] = array(
						"type"        => 'add',
						"id"          => $id,
						"modified_at" => api::get_now_time()
					);
				}
				foreach ($updated_ids as $id)
				{
					$data[] = array(
						"type"        => 'update',
						"id"          => $id,
						"modified_at" => api::get_now_time()
					);
				}
				foreach ($deleted_ids as $id)
				{
					$data[] = array(
						"type"        => 'delete',
						"id"          => $id,
						"modified_at" => api::get_now_time()
					);
				}
				if (! empty($data))
				{
					$mq_msg = array(
						"kind" => "contact",
						"data" => $data
					);
					$this->mq_send(json_encode($mq_msg), $user_id . '', 'momo_sys');
				}
			}
		}
	}

	/**
	 * 比较两个信息看是否有交集
	 * @param array $info1 用户更多信息1
	 * @param array $info2 用户更多信息2
	 * @return bool
	 */
	public function compare_info($info1, $info2)
	{
		$val1 = $val2 = array();
		foreach ($info1 as $val)
		{
			$val1[] = ! empty($val['search']) ? $val['search'] : $val['value'];
		}
		foreach ($info2 as $val)
		{
			$val2[] = ! empty($val['search']) ? $val['search'] : $val['value'];
		}
		if (array_intersect($val1, $val2))
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * 复制对方的名片到我的联系人
	 * @param int $user_id     用户ID
	 * @param int $add_user_id 对方用户ID
	 * @return Contact|bool
	 */
	public function add_contact_by_user_id($user_id, $add_user_id)
	{
		$contact = $this->get_user_info($user_id, $add_user_id);
		if ($contact !== FALSE)
		{
			$contact->set_user_id($user_id);
			$status = $this->add($contact, FALSE);
			if ($status !== FAIL)
			{
				$added_ids = $updated_ids = array();
				switch ($status)
				{
					case SUCCESS:
						//新增联系人成功
						$added_ids[] = $contact->get_id();
						break;
					case MERGE_SUCCESS:
						//联系人被合并
						$updated_ids[] = $contact->get_id();
						break;
					case NO_MODIFY_MERGE_SUCCESS:
						//联系人被合并到现有的联系人，并且现有的联系人没有修改
						break;
					default:
						break;
				}
				$this->prepare_task($user_id, $added_ids, $updated_ids);
				return $contact;
			}
		}
		return FALSE;
	}

	/**
	 * 获取当前用户的国家码
	 * @param int $user_id 用户ID
	 * @return int
	 */
	public function get_country_code($user_id)
	{
		if ($this->country_code)
		{
			return $this->country_code;
		}
		else
		{
			$user_info = User_Model::instance()->get_user_info($user_id);
			$country_code = ! empty($user_info['zone_code']) ? (int) $user_info['zone_code'] : 86;
			$this->country_code = $country_code;
			return $this->country_code;
		}
	}

	/**
	 * 检查是否需要保存快照
	 * @param int $user_id   用户ID
	 * @param string $operation 操作说明
	 * @param bool $auto 是否自动保存快照
	 * @param bool $save 是否保存 ($auto == FALSE时生效)
	 * @return bool
	 */
	public function is_save_snapshot($user_id, $operation, $auto = TRUE, $save = FALSE)
	{
		//联系人为空
		$result = FALSE;
		$history = $this->contact_mapper->get_last_history($user_id);
		if ($auto == TRUE)
		{
			if ($operation == 'recover_snapshot')
			{
				$result = TRUE;
			}
			else
			{
				//粒度不分太细
				switch (TRUE)
				{
					//操作历史为空
					case $history === FALSE:
						//上次操作应用不同
					case $history['appid'] != $this->appid:
						//上次操作设备不同
					case $history['device_id'] != $this->device_id:
						//上次操作说明不同
					case $history['operation'] != $operation:
						//上次操作与本次操作时间超过10分钟
					case api::get_now_time() - $history['dateline'] > 600:
						$result = TRUE;
						break;
					//操作为合并操作
					case $operation === 'merge':
						$deleted_ids = unserialize($history['deleted_ids']);
						if (! empty($deleted_ids))
						{
							$result = TRUE;
						}
						break;
					default:
						$result = FALSE;
						break;
				}
			}

			$this->is_snapshot = api::get_now_time() == $history['dateline'] ? FALSE : $result;
			$this->is_history = $result;
		}
		else
		{
			//根据设定参数备份快照
			if ($save == TRUE)
			{
				$this->is_snapshot = TRUE;
				$this->is_history = TRUE;
			}
			else
			{
				$this->is_snapshot = FALSE;
				$this->is_history = FALSE;
			}
		}

		$this->history = $history;
		$this->operation = $operation;

		if ($this->is_snapshot)
		{
			$this->count = $this->contact_mapper->get_count($user_id);
			$this->category_count = $this->contact_mapper->get_category_count($user_id);
		}
		if ($operation == 'add')
		{
			$this->count = isset($this->count) ? $this->count : $this->contact_mapper->get_count($user_id);
			if ($this->count == 0)
			{
				$this->is_snapshot = FALSE;
			}
			else
			{
				$this->category_count = $this->contact_mapper->get_category_count($user_id);
			}
		}
	}

	/**
	 * 保存快照
	 * @param int $user_id 用户ID
	 * @return bool
	 */
	public function save_snapshot($user_id)
	{
		if (Kohana::config('contact.save_snapshot'))
		{
			if ($this->is_snapshot)
			{
				if ($this->contact_mapper->save_snapshot($user_id))
				{
					$this->is_snapshot = FALSE;
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
	 * 获取联系人变更历史
	 * @param int $user_id 用户ID
	 * @param int $offset   起始记录数
	 * @param int $limit     获取记录数
	 * @return array
	 */
	public function get_history($user_id, $offset = 0, $limit = 100)
	{
		$key = $this->cache_pre . 'history_list_' . $user_id . '_' . $offset . '_' . $limit;
		$update_key = $this->cache_pre . 'history_list_update_' . $user_id;
		$list = $this->cache->get($key);
		$update_time = $this->cache->get($update_key);
		if (empty($list) OR (float) $list['update'] < (float) $update_time)
		{
			$history = $this->contact_mapper->get_history($user_id, $offset, $limit);
			$result = array();
			if ($history)
			{
				$brand_model = Brand_Model::instance();
				foreach ($history as $val)
				{
					$add_ids = unserialize($val['added_ids']);
					$update_ids = unserialize($val['updated_ids']);
					$delete_ids = unserialize($val['deleted_ids']);

					//兼容旧数据
					if (is_null($val['count']))
					{
						$res = $this->contact_mapper->get_snapshot_count($user_id, $val['dateline']);
						$val['count'] = $res['count'];
						$val['category_count'] = $res['category_count'];
					}

					$val['device_id'] = $val['device_id'] !== NULL ? $val['device_id'] : '';
					$val['phone_model'] = $val['phone_model'] !== NULL ? $val['phone_model'] : '';
					$device_alias = $val['phone_model'] ? $brand_model->get_by_model($val['phone_model']) : '';

					$result[] = array(
						'user_id'        => (int) $val['uid'],
						'dateline'       => (int) $val['dateline'],
						'app_name'       => api::get_app_name($val['appid']),
						'source'         => api::get_source_name($val['source']),
						'device_id'      => $val['device_id'],
						'phone_model'    => $val['phone_model'],
						'device_alias'   => $device_alias,
						'operation'      => substr($val['operation'], 0, 2) != 'u:' ?
							Kohana::lang('contact.' . $val['operation']) : substr($val['operation'], 2),
						'count'          => (int) $val['count'],
						'category_count' => (int) $val['category_count'],
						'add_count'      => count($add_ids),
						'update_count'   => count($update_ids),
						'delete_count'   => count($delete_ids),
					);
				}
			}
			$list = array(
				'data'   => $result,
				'update' => microtime(TRUE)
			);
			$this->cache->set($key, $list);
		}
		return ! empty($list) ? $list['data'] : array();
	}

	/**
	 * 根据时间获取变更历史
	 * @param int $user_id  用户ID
	 * @param int $dateline 时间
	 * @return array
	 */
	public function get_history_by_dateline($user_id, $dateline)
	{
		return $this->contact_mapper->get_history_by_dateline(
			$user_id,
			$dateline
		);
	}

	/**
	 * 还原快照
	 * @param int $user_id  用户ID
	 * @param int $dateline 时间
	 * @return bool
	 */
	public function recover_snapshot($user_id, $dateline)
	{
		if ($this->save_snapshot($user_id))
		{
			$old_contact_list = $this->get($user_id);
			$old_ids = array_keys($old_contact_list);
			if ($this->contact_mapper->recover_snapshot($user_id, $dateline))
			{
				//缓存未清，须从数据库读取
				$new_contact_list = $this->get_list($user_id);
				$new_ids = array_keys($new_contact_list);
				//相同的ID,更新时间不一致的作更新操作
				$updated_ids = array_intersect(
					$old_ids,
					$new_ids
				);
				if ($updated_ids)
				{
					foreach ($updated_ids as $key => $id)
					{
						if ($old_contact_list[$id]['modified_at'] ==
							$new_contact_list[$id]['modified_at']
						)
						{
							unset($updated_ids[$key]);
						}
					}
				}
				//原来有，现在没有的ID,作删除操作
				$deleted_ids = array_diff($old_ids, $new_ids);
				//原来没有，现在有的ID,作新增操作
				$added_ids = array_diff($new_ids, $old_ids);
				$this->prepare_task(
					$user_id, $added_ids, $updated_ids,
					$deleted_ids
				);
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * 检验快照
	 * @param int $user_id     用户ID
	 * @param int $snapshot_id 快照ID
	 * @return bool
	 */
	public function check_snapshot($user_id, $snapshot_id)
	{
		return $this->contact_mapper->check_snapshot($user_id, $snapshot_id);
	}

	/**
	 * 获取快照
	 * @param int $user_id     用户ID
	 * @param int $snapshot_id 快照ID
	 * @return array
	 */
	public function get_snapshot_list($user_id, $snapshot_id)
	{
		return $this->contact_mapper->get_snapshot_list($user_id, $snapshot_id);
	}

	/**
	 * 获取我的联系人中是否存在该手机号码
	 * @param int $user_id
	 * @param string $friend_user_id
	 * @return bool
	 */
	public function is_contact($user_id, $friend_user_id)
	{
		//获取对方手机国家码和号码
		$user_info = User_Model::instance()->get_user_info($friend_user_id);
		if (! empty($user_info) AND ! empty($user_info['zone_code']) AND ! empty($user_info['mobile']))
		{
			$search = '+' . $user_info['zone_code'] . $user_info['mobile'];
			$list = $this->get($user_id);
			foreach ($list as $val)
			{
				foreach ($val['tels'] as $tel)
				{
					if ($tel['search'] == $search)
					{
						return TRUE;
					}
				}
			}
		}
		return FALSE;
	}

	/**
	 * 修改联系人分组名
	 * @param int $user_id 用户ID
	 * @param array $ids 联系人ID
	 * @return array
	 */
	public function update_contact_modified($user_id, $ids = array())
	{
		if (! empty($ids))
		{
			if ($this->contact_mapper->update_contact_modified($user_id, $ids, $this->get_source_name()))
			{
				$this->prepare_task($user_id, array(), $ids);
				return $ids;
			}
		}
		return array();
	}

	/**
	 * 获取来源名称
	 * @return string
	 */
	public function get_source_name()
	{
		static $source_name;
		if (empty($source_name))
		{
			$source_name = $this->phone_model ? $this->phone_model : api::get_source_name($this->source);
		}
		return $source_name;
	}

	/**
	 * 判断是否关联对方
	 * @param $user_id 用户ID
	 * @param $to_mobile 对方手机号码
	 * @return bool
	 */
	public function is_link($user_id, $to_mobile)
	{
		return $this->contact_mapper->get_id_by_tel($user_id, '+86' . $to_mobile) ? TRUE : FALSE;
	}

	/**
	 * 获取分组联系人
	 * @param int $user_id 用户ID
	 * @param int $category_id 分组ID
	 * @return array
	 */
	public function get_category_contact($user_id, $category_id)
	{
		return $this->contact_mapper->get_category_contact($user_id, $category_id);
	}

	/**
	 * 是否有同步记录
	 * @param int $user_id 用户ID
	 * @param int $app_id 应用ID
	 * @param string $device_id 设备ID
	 * @return bool
	 */
	public function has_history($user_id, $app_id, $device_id = '')
	{
		$key = $this->cache_pre . 'has_history_' . $user_id . '_' . $app_id . '_' . $device_id;
		$result = $this->cache->get($key);
		if (! $result)
		{
			$result = $this->contact_mapper->has_history($user_id, $app_id, $device_id);
			$this->cache->set($key, $result, NULL, 0);
		}
		return (bool) $result;
	}

	public function __destruct()
	{
		$this->send_message();
	}
}
