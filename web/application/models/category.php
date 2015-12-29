<?php
defined('SYSPATH') or die('No direct script access.');
/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * 联系人分组模型文件
 */
/**
 * 联系人分组模型器
 */
class Category_Model extends Model {
	/**
	 * 缓存
	 * @var Cache
	 */
	protected $cache;
	/**
	 * 缓存前缀
	 * @var string
	 */
	protected $cache_pre;

	/**
	 * 联系人数据映射
	 * @var Contact_Mapper
	 */
	protected $contact_mapper;

	/**
	 * 联系人模型
	 * @var Contact_Model
	 */
	protected $contact_model;

	/**
	 * 实例
	 * @var Category_Model
	 */
	protected static $instance;

	/**
	 * 客户端来源
	 * @var string
	 */
	public $source = '';

	/**
	 * 应用ID
	 * @var int
	 */
	public $appid = 0;

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
	 * 单例模式
	 * @return Category_Model
	 */
	public static function &instance()
	{
		if (! isset(self::$instance))
		{
			// Create a new instance
			self::$instance = new Category_Model();
		}
		return self::$instance;
	}

	/**
	 * 构造函数,
	 * 为了避免循环实例化，请尽量调用单例模式
	 */
	public function __construct()
	{
		$this->contact_mapper = Contact_Mapper::instance();
		$this->contact_model = Contact_Model::instance();


		$this->cache_pre = CACHE_PRE . 'category_';
		$this->cache = Cache::instance();
	}

	public function set_from($source, $appid, $device_id, $phone_model)
	{
		$this->source = $source;
		$this->appid = $appid;
		$this->device_id = $device_id;
		$this->phone_model = $phone_model;

		$this->contact_model->source = $this->source;
		$this->contact_model->appid = $this->appid;
		$this->contact_model->device_id = $this->device_id;
		$this->contact_model->phone_model = $this->phone_model;
	}

	/**
	 * 获取分组名
	 * @param int $user_id 用户ID
	 * @param int $id 分组ID
	 * @return string
	 */
	public function get_category_name($user_id, $id)
	{
		return $this->contact_mapper->get_category_name($user_id, $id);
	}

	/**
	 * 获取分组联系人数
	 * @param int $user_id 用户ID
	 * @param int $id 分组ID
	 * @return string
	 */
	public function get_category_contact_count($user_id, $id)
	{
		return $this->contact_mapper->get_category_contact_count($user_id, $id);
	}

	/**
	 * 获取联系人分组列表
	 * @param int $user_id 用户ID
	 * @return array
	 */
	public function get_list($user_id)
	{
		$list = array();
		$all_category_list = $this->contact_mapper->get_category_list($user_id);
		if ($all_category_list)
		{
			foreach ($all_category_list as $id => $category_name)
			{
				$list[] = array('id'            => (int) $id,
				                'name'          => $category_name,
				                'contact_count' => $this->get_category_contact_count($user_id, $id)
				);
			}
		}
		return $list;
	}

	/**
	 * 获取联系人分组
	 * @param int $user_id 用户ID
	 * @return array
	 */
	public function get_categories($user_id)
	{
		return $this->contact_mapper->get_category_list($user_id);
	}

	/**
	 * 改变分组顺序
	 * @param int $user_id 用户ID
	 * @param array $ids 新分组ID顺序
	 * @return bool
	 */
	public function change_order($user_id, $ids)
	{
		if ($this->contact_mapper->update_category_order_by($user_id, $ids))
		{
			$this->clear_cache($user_id);
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * 通过分组ID删除分组
	 * @param int $user_id 用户ID
	 * @param int $id 分组ID
	 * @return bool
	 */
	public function delete($user_id, $id)
	{
		$ids = $this->contact_mapper->get_category_contact($user_id, $id);
		if ($ids AND
			$this->contact_model->save_snapshot($user_id) AND $this->contact_mapper->delete_category($user_id, $id)
		)
		{
			//调用删除联系人分组接口,分组缓存在联系人处理后更新
			$this->contact_model->update_contact_modified($user_id, $ids);
			$this->clear_cache($user_id, array('type' => 'delete', 'id' => $id, 'name' => ''));
			return TRUE;
		}
		//分组没有联系人，直接删除分组
		elseif($this->contact_mapper->delete_category($user_id, $id))
		{
			$this->clear_cache($user_id, array('type' => 'delete', 'id' => $id, 'name' => ''));
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * 增加联系人分组,返回分组ID
	 * @param int $user_id 用户ID
	 * @param string $name 分组名
	 * @return int
	 */
	public function add($user_id, $name)
	{
		$id = $this->contact_mapper->add_category($user_id, $name);
		if ($id)
		{
			$this->clear_cache($user_id, array('type' => 'add', 'id' => $id, 'name' => $name));
		}
		return $id;
	}

	/**
	 * 编辑分组名
	 * @param int $user_id 用户ID
	 * @param string $name 分组名
	 * @param int $id 分组ID
	 * @return bool
	 */
	public function edit($user_id, $name, $id)
	{
		if ($old_name = $this->contact_mapper->get_category_name($user_id, $id))
		{
			if ($old_name != $name)
			{
				$ids = $this->contact_mapper->get_category_contact($user_id, $id);
				//分组中有联系人，保存快照，更新分组名和联系人
				if ($ids AND $this->contact_model->save_snapshot($user_id) AND
					$this->contact_mapper->update_category($user_id, $id, $name)
				)
				{
					$this->contact_model->update_contact_modified($user_id, $ids);
					$this->clear_cache($user_id, array('type' => 'update', 'id' => $id, 'name' => $name));
					return TRUE;
				}
				//分组中没有联系人，直接更新分组名
				elseif ($this->contact_mapper->update_category($user_id, $id, $name))
				{
					$this->clear_cache($user_id, array('type' => 'update', 'id' => $id, 'name' => $name));
					return TRUE;
				}
			}
			else
			{
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * 从缓存获取分组列表或分组联系人ID
	 * @param int $user_id 用户ID
	 * @return array
	 */
	public function get($user_id)
	{
		$result = $this->cache->get($this->cache_pre . $user_id);
		if ($result === NULL)
		{
			$result = call_user_func(array($this, 'get_list'), $user_id);
			$this->cache->set($this->cache_pre . $user_id, $result);
		}
		return $result;
	}

	/**
	 * 清除缓存
	 * @param int $user_id 用户ID
	 * @param array $data 分组修改消息
	 */
	public function clear_cache($user_id, $data = array())
	{
		$this->cache->delete($this->cache_pre . $user_id);
//		if (! empty($data))
//		{
//			$mq_msg = array("kind" => "contact_group", "data" => $data);
//			$this->mq_send(json_encode($mq_msg), $user_id . '', 'momo_sys');
//		}
	}

	/**
	 * 判断分组名是否存在
	 * @param int $user_id 用户ID
	 * @param string $name 分组名
	 * @param int $id 分组ID
	 * @return bool
	 */
	public function check_name($user_id, $name, $id = 0)
	{
		//检查是否系统分组
		$system_groups = Kohana::config('contact.sys_groups');
		$groups = array();
		foreach ($system_groups as $system_group)
		{
			$groups[] = $system_group['name'];
		}
		if (in_array($name, $groups))
		{
			return TRUE;
		}
		//检查是否已存在的分组
		return $this->contact_mapper->check_category_name($user_id, $name, $id);
	}

	/**
	 * 批量分组
	 * @param int $user_id 用户ID
	 * @param int $id 分组ID
	 * @param array $ids 联系人ID
	 * @param string $type 分组方式
	 * @return array
	 */
	public function move_contact_category($user_id, $id, $ids, $type)
	{
		if ($id)
		{
			$old_ids = $this->contact_mapper->get_category_contact($user_id, $id);
			if ($type == 'add')
			{
				$update_ids = array_unique(array_diff($ids, $old_ids));
				if ($update_ids AND $this->contact_model->save_snapshot($user_id) AND
					$this->contact_mapper->add_contact_category($user_id, $id, $update_ids)
						AND $this->contact_model->update_contact_modified($user_id, $update_ids)
				)
				{
					return $update_ids;
				}
			}
			else
			{
				$update_ids = array_unique(array_intersect($ids, $old_ids));
				if ($update_ids AND $this->contact_model->save_snapshot($user_id)
					AND $this->contact_mapper->delete_contact_category($user_id, $update_ids, $id)
						AND $this->contact_model->update_contact_modified($user_id, $update_ids)
				)
				{
					return $update_ids;
				}
			}
		}
		else
		{
			$update_ids = array_unique($this->contact_mapper->get_valid_category_contact_ids($user_id, $ids));
			if ($update_ids AND $this->contact_model->save_snapshot($user_id) AND
				$this->contact_mapper->delete_contact_category($user_id, $update_ids) AND
					$this->contact_model->update_contact_modified($user_id, $update_ids)
			)
			{

				return $update_ids;
			}
		}
		return array();
	}

	/**
	 * 批量分组
	 * @param int $user_id 用户ID
	 * @param array $category_ids 分组名
	 * @param array $ids 联系人ID
	 * @return array
	 */
	public function set_contact_category($user_id, $category_ids, $ids)
	{
		$category_list = $this->contact_mapper->get_contact_category_list($user_id);

		$add_ids = $delete_ids = array();
		foreach ($ids as $id)
		{
			$category_list[$id] = isset($category_list[$id]) ? $category_list[$id] : array();
			if ($add = array_diff($category_ids, $category_list[$id]))
			{
				$add_ids[$id] = $add;
			}
			if ($delete = array_diff($category_list[$id], $category_ids))
			{
				$delete_ids[$id] = $delete;
			}
		}
		$ids = array_unique(array_merge(array_keys($add_ids), array_keys($delete_ids)));
		if ($ids AND $this->contact_model->save_snapshot($user_id))
		{
			$this->contact_mapper->set_contact_category($user_id, $add_ids, $delete_ids);
			if ($this->contact_model->update_contact_modified($user_id, $ids))
			{
				return $ids;
			}
		}
		return array();
	}
}