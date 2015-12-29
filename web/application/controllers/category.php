<?php
defined('SYSPATH') or die('No direct access allowed.');

/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * 联系人分组控制器文件
 */
/**
 * 联系人分组控制器
 */
class Category_Controller extends Controller {
	/**
	 * 是否发布模式
	 */
	const ALLOW_PRODUCTION = TRUE;
	/**
	 * 联系人分组模型
	 * @var Category_Model
	 */
	protected $model;

	/**
	 * 构造函数
	 */
	public function __construct()
	{
		//必须继承父类控制器
		parent::__construct();
		//实例化模型
		$this->model = Category_Model::instance();
		$this->model->set_from($this->source, $this->appid, $this->device_id, $this->phone_model);
		$this->_check_request();
	}

	/**
	 * 检查请求方法或参数是否正确
	 * @return void
	 */
	private function _check_request()
	{
		if(!Kohana::config('contact.from_category_table')) {
			$this->send_response(503, NULL,	Kohana::lang('contact.service_unavailable'));
		}
		$get_method = array(
			'index',
		);
		$post_method = array(
			'create',
			'add_batch',
			'destroy',
			'remove_batch',
			'order',
			'update',
			'set_batch'
		);
		$type = strtoupper($this->get_method());
		$array = strtolower($type) . '_method';
		$uri = URI::$method;
		if (! in_array($type, array('GET', 'POST'), TRUE) OR ! in_array($uri, $$array, TRUE))
		{
			$this->send_response(405, NULL,
				Kohana::lang('contact.method_not_exist'));
		}

		if ($type === 'POST')
		{
			$this->_check_save_snapshot($uri);
		}
	}

	/**
	 * 检查是否保存快照
	 * @param string $uri 请求URI
	 * @param string $operation 操作说明
	 * @return void
	 */
	private function _check_save_snapshot($uri, $operation = '')
	{
		// 检查操作类型
		if (in_array($uri,
			array(
			     'add_batch',
			     'destroy',
			     'remove_batch',
			     'update',
			     'set_batch'
			), TRUE)
		)
		{
			// 非网站的默认为同步
			if ($this->get_source() != 0 AND $uri != 'recover_history')
			{
				$operation = 'sync';
			}
			else
			{
				switch ($uri)
				{
					case 'add_batch':
						$operation = empty($operation) ? 'add_category' : $operation;
						break;
					case 'remove_batch':
						$operation = empty($operation) ? 'remove_category' : $operation;
						break;
					case 'update':
						$operation = empty($operation) ? 'update_category' : $operation;
						break;
					case 'set_batch':
						$operation = empty($operation) ? 'set_category' : $operation;
						break;
					case 'destroy':
						$operation = empty($operation) ? 'remove_category' : $operation;
						break;

				}
			}
			Contact_Model::instance()->is_save_snapshot($this->user_id, $operation);
		}
	}

	/**
	 * 获取联系人分组列表
	 */
	public function index()
	{
		if ($this->get_method() != 'GET')
		{
			$this->send_response(405, NULL,
				Kohana::lang('contact.method_not_exist'));
		}
		else
		{
			$result = $this->model->get($this->user_id);
			$this->send_response(200, $result);
		}
	}

	/**
	 * 创建联系人分组
	 */
	public function create()
	{
		if ($this->get_method() != 'POST')
		{
			$this->send_response(405, NULL,
				Kohana::lang('contact.method_not_exist'));
		}
		$data = $this->get_data();
		$name = isset($data['name']) ? $data['name'] : '';
		if (empty($name))
		{
			$this->send_response(400, NULL,
				Kohana::lang('contact.group_name_empty'));
		}
		elseif (mb_strlen($name, 'utf8') > 32)
		{
			$this->send_response(400, NULL,
				Kohana::lang('contact.group_name_too_long'));
		}
		else
		{
			if ($this->model->check_name($this->user_id, $name))
			{
				$this->send_response(400, NULL,
					Kohana::lang('contact.group_name_exist'));
			}
			else
			{
				$gid = $this->model->add($this->user_id, $name);
				$result = array('id' => (int) $gid, 'name' => $name);
				$this->send_response(200, $result);
			}
		}
	}

	/**
	 * 删除联系人分组
	 * @param int $id 分组ID
	 */
	public function destroy($id = NULL)
	{
		$id = (int) $id;
		if ($this->get_method() != 'POST')
		{
			$this->send_response(405, NULL,
				Kohana::lang('contact.method_not_exist'));
		}
		elseif (empty($id))
		{
			$this->send_response(400, NULL,
				Kohana::lang('contact.group_id_empty'));
		}
		elseif ($this->model->get_category_name($this->user_id, $id))
		{
			$status = $this->model->delete($this->user_id, $id);
			if ($status == FALSE)
			{
				$this->send_response(404, NULL,
					Kohana::lang('contact.group_not_exist'));
			}
			else
			{
				$this->send_response(200, NULL, '', FALSE);
			}
		}
		else
		{
			$this->send_response(404, NULL,
				Kohana::lang('contact.group_not_exist'));
		}
	}

	/**
	 * 更新联系人分组名
	 * @param int $id 分组ID
	 */
	public function update($id = NULL)
	{
		if ($this->get_method() != 'POST')
		{
			$this->send_response(405, NULL,
				Kohana::lang('contact.method_not_exist'));
		}
		$data = $this->get_data();
		$name = isset($data['name']) ? $data['name'] : '';
		$id = (int) $id;
		if (empty($id))
		{
			$this->send_response(400, NULL,
				Kohana::lang('contact.group_id_empty'));
		}
		elseif (empty($name))
		{
			$this->send_response(400, NULL,
				Kohana::lang('contact.group_name_empty'));
		}
		elseif (mb_strlen($name, 'utf8') > 32)
		{
			$this->send_response(400, NULL,
				Kohana::lang('contact.group_name_too_long'));
		}
		elseif ($this->model->check_name($this->user_id, $name, $id))
		{
			$this->send_response(400, NULL,
				Kohana::lang('contact.group_name_exist'));
		}
		else
		{
			$status = $this->model->edit($this->user_id, $name, $id);
			if ($status == FALSE)
			{
				$this->send_response(404, NULL,
					Kohana::lang('contact.group_not_exist'));
			}
			else
			{
				$this->send_response(200, array('id' => (int) $id, 'name' => $name), '', FALSE);
			}
		}
	}

	/**
	 * 修改联系人分组顺序
	 */
	public function order()
	{
		if ($this->get_method() != 'POST')
		{
			$this->send_response(405, NULL,
				Kohana::lang('contact.method_not_exist'));
		}
		$data = $this->get_data();
		$ids = !empty($data['ids']) ? $data['ids'] : array();
		if (empty($ids))
		{
			$this->send_response(400, NULL,
				Kohana::lang('contact.group_ids_empty'));
		}
		else
		{
			$ids = explode(',', $ids);
			$list = $this->model->get($this->user_id);
			$old_ids = array();
			foreach ($list as $value)
			{
				$old_ids[] = $value['id'];
			}

			if (array_intersect($old_ids, $ids) != $old_ids)
			{
				$this->send_response(400, NULL,
					Kohana::lang('contact.group_ids_not_complete'));
			}
			else
			{
				if($this->model->change_order($this->user_id, $ids)) {
					$this->send_response(200);
				} else {
					$this->send_response(500, NULL,
						Kohana::lang('contact.operation_fail'));
				}
			}
		}
	}

	/**
	 * 联系人批量加入分组
	 * @param int $id 分组ID
	 */
	public function add_batch($id = 0)
	{
		$this->_contact_to_group($id);
	}

	/**
	 * 联系人批量移出分组
	 * @param int $id 分组ID
	 */
	public function remove_batch($id = 0)
	{
		$this->_contact_to_group($id, 'remove');
	}

	/**
	 * 设置分组
	 */
	public function set_batch()
	{
		if ($this->get_method() != 'POST')
		{
			$this->send_response(405, NULL,
				Kohana::lang('contact.method_not_exist'));
		}
		$data = $this->get_data();

		$ids = !empty($data['ids']) ? explode(',', $data['ids']) : array();
		$category_ids = !empty($data['category_ids']) ? explode(',', $data['category_ids']) : array();

		if (empty($ids))
		{
			$this->send_response(400, NULL,
				Kohana::lang('contact.contact_ids_empty'));
		}
		if ($category_ids)
		{
			$categories = $this->model->get_categories($this->user_id);

			if (! empty($category_ids))
			{
				if (array_diff($category_ids, array_keys($categories)))
				{
					$this->send_response(400, NULL,
						Kohana::lang('contact.group_not_exist'));
				}
			}
		}

		$result = array();
		$update_ids = $this->model->set_contact_category($this->user_id, $category_ids, $ids);

		if ($update_ids)
		{
			$now = api::get_now_time();
			foreach ($update_ids as $id)
			{
				$result[] = array('id'          => (int) $id,
				                  'modified_at' => $now
				);
			}
		}
		$this->send_response(200, $result, '', FALSE);
	}

	/**
	 * 更新联系人所在分组
	 * @param int $id
	 * @param string $type
	 */
	private function _contact_to_group($id, $type = 'add')
	{
		if ($this->get_method() != 'POST')
		{
			$this->send_response(405, NULL,
				Kohana::lang('contact.method_not_exist'));
		}
		$id = (int) $id;
		if($type == 'add' AND empty($id)) {
			$this->send_response(400, NULL,
				Kohana::lang('contact.group_id_empty'));
		}

		if (! empty($id) AND ! $this->model->get_category_name($this->user_id, $id))
		{
			$this->send_response(400, NULL,
				Kohana::lang('contact.group_not_exist'));
		}

		$data = $this->get_data();
		$ids = !empty($data['ids']) ? explode(',', $data['ids']) : array();
		if (empty($ids))
		{
			$this->send_response(400, NULL,
				Kohana::lang('contact.contact_ids_empty'));
		}
		$result = array();
		$update_ids = $this->model->move_contact_category($this->user_id, $id, $ids, $type);

		if ($update_ids)
		{
			$now = api::get_now_time();
			foreach ($update_ids as $id)
			{
				$result[] = array('id'          => (int) $id,
				                  'modified_at' => $now
				);
			}
		}
		$this->send_response(200, $result, '', FALSE);
	}
} // End Contact_Category Controller