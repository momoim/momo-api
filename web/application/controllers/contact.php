<?php
defined('SYSPATH') or die('No direct access allowed.');
/*
 * [MOMO API] (C)1999-2012 ND Inc.
 * 联系人控制器文件
 */
/**
 * 联系人控制器
 */
class Contact_Controller extends Controller implements Contact_Interface {

	/**
	 * 是否发布模式
	 */
	const ALLOW_PRODUCTION = TRUE;

	/**
	 * 联系人模型
	 * @var Contact_Model
	 */
	protected $model;

	/**
	 * 多个联系人ID
	 * @var array
	 */
	protected $ids = array();

	/**
	 * POST输入数据
	 * @var array
	 */
	protected $data = array();

	public function __construct()
	{
		parent::__construct();
		$this->model = Contact_Model::instance();
		$this->model->source = $this->source;
		$this->model->device_id = $this->device_id;
		$this->model->phone_model = $this->phone_model;
		$this->model->appid = $this->appid;
		$this->_check_request();
	}

	/**
	 * 检查请求方法或参数是否正确
	 * @return void
	 */
	private function _check_request()
	{
		// 全局处理请求方法是否正确
		$get_method = array(
			'index',
			'count',
			'show',
			'recycled',
			'get_history',
			'recover_to_do',
			'search',
			'recycled_search',
			'sort',
			'snapshot'
		);
		$post_method = array(
			'create_at',
			'create_batch',
			'destroy_batch',
			'recover_batch',
			'recycle_batch',
			'save',
			'show_batch',
			'update',
			'recover_history',
			'get_link',
			'recycle_clean',
			'snapshot_show_batch',
			'manual_backup',
			'manual_create_batch',
			'manual_destroy_batch',
			'manual_update',
		);
		$type = strtoupper($this->get_method());
		$array = strtolower($type) . '_method';
		$uri = URI::$method;
		if (! in_array($type, array('GET', 'POST'), TRUE) OR ! in_array($uri, $$array, TRUE))
		{
			$this->send_response(405, NULL,
				Kohana::lang('contact.method_not_exist'));
		}
		$this->data = $this->get_data();
		if ($type === 'POST')
		{
			//正在迁移数据，暂停写操作
			/*
			if (in_array($uri,
				array(
				     'create_at',
				     'create_batch',
				     'destroy_batch',
				     'recover_batch',
				     'recycle_batch',
				     'save',
				     'update',
				     'recover_history',
				     'recycle_clean'
				), TRUE) AND
				Cache::instance('contact')->get(CACHE_PRE . 'contact_move') == 'doing'
			)
			{
				$this->send_response(503, NULL, Kohana::lang('contact.service_unavailable'));
			}
			*/

			if (empty($this->data))
			{
				$this->send_response(400, NULL,
					Kohana::lang('contact.contact_info_incorrect'));
			}

			// 相同操作合并，验证联系人ID合法性
			if (in_array($uri,
				array(
				     'destroy_batch',
				     'recover_batch',
				     'recycle_batch',
				     'show_batch',
				     'snapshot_show_batch',
				     'manual_destroy_batch'
				), TRUE)
			)
			{
				$ids = isset($this->data['ids']) ? $this->data['ids'] : '';
				if (empty($ids))
				{
					$this->send_response(400, NULL,
						Kohana::lang('contact.contact_ids_empty'));
				}
				$ids = explode(',', $ids);
				$this->ids = $ids;
				if (count($ids) > 100)
				{
					$this->send_response(400, NULL,
						Kohana::lang('contact.contact_ids_exceed_limit'));
				}
			}

			if (in_array($uri,
				array('manual_create_batch',
				      'manual_destroy_batch',
				      'manual_update'
				), TRUE)
			)
			{
				$auto = FALSE;
				$save = FALSE;
				$uri = substr($uri, 7);
			}
			else
			{
				$auto = TRUE;
				$save = TRUE;
			}

			$operation = isset($this->data['operation']) ? $this->data['operation'] : '';
			$this->_check_save_snapshot($uri, $operation, $auto, $save);
		}
	}

	/**
	 * 检查是否保存快照
	 * @param string $uri 请求URI
	 * @param string $operation 操作说明
	 * @param bool $auto 是否自动保存快照
	 * @param bool $save 是否保存 ($auto == FALSE时生效)
	 * @return void
	 */
	private function _check_save_snapshot($uri, $operation, $auto = TRUE, $save = TRUE)
	{
		// 检查操作类型
		if (in_array($uri,
			array(
			     'create_batch',
			     'destroy_batch',
			     'recover_batch',
			     'save',
			     'update',
			     'recover_history',
			     'manual_backup'
			), TRUE)
		)
		{
			if ($auto == TRUE)
			{
				// 非网站的默认为同步
				if ($this->get_source() != 0 AND ! in_array($uri, array('recover_history', 'manual_backup')))
				{
					$operation = 'sync';
				}
				else
				{
					switch ($uri)
					{
						case 'create_batch':
							$operation = empty($operation) ? 'add' : $operation;
							break;
						case 'destroy_batch':
							$operation = empty($operation) ? 'delete' : $operation;
							break;
						case 'recover_batch':
							$operation = empty($operation) ? 'recover' : $operation;
							break;
						case 'save':
							$operation = empty($operation) ? 'save' : $operation;
							break;
						case 'update':
							$operation = empty($operation) ? 'update' : $operation;
							break;
						case 'recover_history':
							$operation = 'recover_snapshot';
							break;
					}
				}

				//针对手动备份处理
				if ($uri == 'manual_backup')
				{
					$operation = in_array($operation, array_keys(Kohana::lang('contact')), TRUE) ? $operation
						: ($operation ? 'u:' . $operation : $uri);
					$auto = FALSE;
					$save = TRUE;
				}
				else
				{
					$auto = TRUE;
					$save = TRUE;
				}
			}

			$this->model->is_save_snapshot($this->user_id, $operation, $auto, $save);
		}
	}

	/**
	 * 获取联系人列表
	 * @method GET
	 * @return void
	 */
	public function index()
	{
		// 概要信息字段,0：默认 1：全部 其他：默认+字段名
		$info = $this->input->get('info', '0');
		$category_id = (int) $this->input->get('category_id', 0);
		$ids = array();
		if (Kohana::config('contact.from_category_table') AND $category_id)
		{
			$ids = $this->model->get_category_contact($this->user_id, $category_id);
		}

		//分页支持
		$page = (int) $this->input->get('page', 0);
		$page_size = (int) $this->input->get('page_size', 0);
		$page = ($page_size AND $page == 0) ? 1 : $page;

		// 可获取的字段
		$allow_fields = Contact::get_list_fields();
		$more_fields = array('tels', 'emails');
		$allow_fields = array_merge($allow_fields, $more_fields);
		$default_fields = array('id', 'modified_at');

		if ($info == '0')
		{
			$fields = $default_fields;
		}
		elseif ($info == '1')
		{
			$fields = $allow_fields;
		}
		else
		{
			$fields = explode(',', $info);
			if (array_diff($fields, $allow_fields))
			{
				$this->send_response(400, NULL, Kohana::lang('contact.info_limit'));
			}
			$fields = array_merge($default_fields, $fields);
		}
		$list = $this->model->get($this->user_id);
		$result = array();
		$flipped_fields = array_flip($fields);
		foreach ($list as $value)
		{
			if ($value AND (! $category_id OR ($category_id AND in_array($value['id'], $ids))))
			{
				if ($val = array_intersect_key($value, $flipped_fields))
				{
					$result[] = $val;
				}
			}
		}

		if (! empty($page) AND ! empty($page_size))
		{
			$this->send_response(200, array_slice($result, ($page - 1) * $page_size, $page_size));
		}
		else
		{
			$this->send_response(200, $result);
		}
	}

	/**
	 * 搜索联系人
	 */
	public function search()
	{
		$query = trim(urldecode($this->input->get('q')));
		$sort = strtolower(trim(urldecode($this->input->get('sort'))));

		//分页支持
		$page = (int) $this->input->get('page', 0);
		$page_size = (int) $this->input->get('page_size', 0);
		$page = ($page_size AND $page == 0) ? 1 : $page;

		if (empty($query) AND empty($sort))
		{
			$this->send_response(400, NULL, Kohana::lang('contact.query_limit'));
		}

		if (! empty($page) AND $page < 1)
		{
			$this->send_response(400, NULL, Kohana::lang('contact.page_limit'));
		}
		if (! empty($page_size) AND $page_size < 1)
		{
			$this->send_response(400, NULL, Kohana::lang('contact.page_size_limit'));
		}

		// 可获取的字段
		$allow_fields = Contact::get_list_fields();
		$more_fields = array('tels', 'emails');
		$allow_fields = array_merge($allow_fields, $more_fields);

		$fields = $this->input->get('fields');
		$fields = $fields ? explode(',', $fields) : array('id', 'modified_at');
		if (array_diff($fields, $allow_fields))
		{
			$this->send_response(400, NULL, Kohana::lang('contact.info_limit'));
		}
		$flipped_fields = array_flip($fields);

		$list = $this->model->get($this->user_id);
		$data = $result = array();

		//准备搜索数据
		foreach ($list as $value)
		{
			if (empty($sort) OR substr($value['sort'], 0, 1) == $sort)
			{
				$str = '';
				//电话号码和归属地
				if ($value['tels'])
				{
					foreach ($value['tels'] as $tmp)
					{
						$str .= '|' . $tmp['value'] . '|' . $tmp['city'];
					}
				}
				//邮箱
				if ($value['emails'])
				{
					foreach ($value['emails'] as $tmp)
					{
						$str .= '|' . $tmp['value'];
					}
				}
				//姓名、拼音首字母、拼音
				$data[$value['id']] = $value['formatted_name'] . '|' . $value['sort'] . '|' . $value['phonetic'] . $str;
			}
		}

		//搜索
		foreach ($data as $key => $val)
		{
			if (empty($query) OR stristr($val, $query))
			{
				$result[] = array_intersect_key($list[$key], $flipped_fields);
			}
		}


		if (! empty($page) AND ! empty($page_size))
		{
			$response = array(
				'meta' => array(
					'total_count' => count($result),
					'page'        => $page,
					'page_size'   => $page_size
				),
				'data' => array_splice($result, ($page - 1) * $page_size, $page_size)
			);
		}
		else
		{
			$response = array(
				'meta' => array(
					'total_count' => count($result),
				),
				'data' => $result
			);
		}
		$this->send_response(200, $response);
	}

	/**
	 * 获取拼音首字母联系人个数
	 */
	public function sort()
	{
		$list = $this->model->get($this->user_id);
		$result = array(
			'#' => 0,
			'A' => 0,
			'B' => 0,
			'C' => 0,
			'D' => 0,
			'E' => 0,
			'F' => 0,
			'G' => 0,
			'H' => 0,
			'I' => 0,
			'J' => 0,
			'K' => 0,
			'L' => 0,
			'M' => 0,
			'N' => 0,
			'O' => 0,
			'P' => 0,
			'Q' => 0,
			'R' => 0,
			'S' => 0,
			'T' => 0,
			'U' => 0,
			'V' => 0,
			'W' => 0,
			'X' => 0,
			'Y' => 0,
			'Z' => 0,
		);

		foreach ($list as $value)
		{
			$result[strtoupper(substr($value['sort'], 0, 1))] ++;
		}
		$this->send_response(200, $result);
	}

	/**
	 * 获取回收站联系人列表
	 * @method GET
	 * @return void
	 */
	public function recycled()
	{
		$page = (int) $this->input->get('page', 1);
		$page_size = (int) $this->input->get('page_size', 100);
		$page_limit = Kohana::config('contact.recycled_page_limit');

		if ($page < 1)
		{
			$this->send_response(400, NULL, Kohana::lang('contact.page_limit'));
		}
		if ($page_size < 1 OR $page_size > $page_limit OR $page_size % 10 != 0)
		{
			$this->send_response(400, NULL, Kohana::lang('contact.page_size_limit'));
		}

		// 可获取的字段
		$allow_fields = Contact::get_list_fields(FALSE, TRUE);
		$more_fields = array('tels');
		$allow_fields = array_merge($allow_fields, $more_fields);

		$fields = $this->input->get('fields');
		$fields = $fields ? explode(',', $fields) : $allow_fields;
		if (array_diff($fields, $allow_fields))
		{
			$this->send_response(400, NULL, Kohana::lang('contact.info_limit'));
		}
		$flipped_fields = array_flip($fields);
		$result = array();
		$start = ($page - 1) * $page_size;
		$total = $this->model->get_recycled_count($this->user_id, 'recycled');
		if ($start < $total)
		{
			$cache_start = (int) floor($start / $page_limit) * $page_limit;
			$array_offset = (int) ($start % $page_limit);

			$list = $this->model->get_recycled_list($this->user_id, $cache_start, $page_limit);
			$data = array_splice(array_values($list), $array_offset, $page_size);

			$count = count($data);
			if ($count < $page_size AND $start + $count < $total)
			{
				$list = $this->model->get_recycled_list($this->user_id, $cache_start + $page_limit, $page_limit);
				$data = array_merge($data, array_splice(array_values($list), 0, $page_size - $count));
			}
			foreach ($data as $value)
			{
				$result[] = array_intersect_key($value, $flipped_fields);
			}
		}
		$this->send_response(200, $result);
	}

	/**
	 * 搜索回收站联系人
	 */
	public function recycled_search()
	{
		$query = trim(urldecode($this->input->get('q')));

		if (empty($query))
		{
			$this->send_response(400, NULL, Kohana::lang('contact.query_limit'));
		}

		//分页支持
		$page = (int) $this->input->get('page', 0);
		$page_size = (int) $this->input->get('page_size', 0);
		$page = ($page_size AND $page == 0) ? 1 : $page;

		if (! empty($page) AND $page < 1)
		{
			$this->send_response(400, NULL, Kohana::lang('contact.page_limit'));
		}
		if (! empty($page_size) AND $page_size < 1)
		{
			$this->send_response(400, NULL, Kohana::lang('contact.page_size_limit'));
		}

		// 可获取的字段
		$allow_fields = Contact::get_list_fields();
		$more_fields = array('tels');
		$allow_fields = array_merge($allow_fields, $more_fields);

		$fields = $this->input->get('fields');
		$fields = $fields ? explode(',', $fields) : array('id', 'modified_at');
		if (array_diff($fields, $allow_fields))
		{
			$this->send_response(400, NULL, Kohana::lang('contact.info_limit'));
		}
		$flipped_fields = array_flip($fields);
		//只对第一页进行搜索
		$list = $this->model->get_recycled_list($this->user_id, 0, Kohana::config('contact.recycled_page_limit'));
		$data = $result = array();

		//准备搜索数据
		foreach ($list as $value)
		{
			$str = '';
			//电话号码和归属地
			if ($value['tels'])
			{
				foreach ($value['tels'] as $tmp)
				{
					$str .= '|' . $tmp['value'] . '|' . $tmp['city'];
				}
			}

			//姓名、拼音首字母、拼音
			$data[$value['id']] = $value['formatted_name'] . '|' . $value['sort'] . '|' . $value['phonetic'] . $str;
		}

		//搜索
		foreach ($data as $key => $val)
		{
			if (stristr($val, $query))
			{
				$result[] = array_intersect_key($list[$key], $flipped_fields);
			}
		}

		if (! empty($page) AND ! empty($page_size))
		{
			$response = array(
				'meta' => array(
					'total_count' => count($result),
					'page'        => $page,
					'page_size'   => $page_size
				),
				'data' => array_splice($result, ($page - 1) * $page_size, $page_size)
			);
		}
		else
		{
			$response = array(
				'meta' => array(
					'total_count' => count($result),
				),
				'data' => $result
			);
		}

		$this->send_response(200, $response);
	}

	/**
	 * 获取系统联系人数
	 * @method GET
	 * @return void
	 */
	public function count()
	{
		$count = $this->model->get($this->user_id, NULL, 'get_count');
		$this->send_response(200, $count);
	}

	/**
	 * 获取单个联系人信息
	 * @method GET
	 * @param int $contact_id 联系人ID
	 * @return void
	 */
	public function show($contact_id = NULL)
	{
		if (! is_numeric($contact_id) or empty($contact_id))
		{
			$this->send_response(400, NULL,
				Kohana::lang('contact.contact_id_empty'));
		}
		else
		{
			$user_id = $this->user_id;
			$contact = $this->model->get($user_id, (int) $contact_id);
			if ($contact === FALSE)
			{
				$this->send_response(404, NULL,
					Kohana::lang('contact.resource_not_exist'));
			}
			else
			{
				$result = $contact->to_array();
				$result['source'] = api::get_source_name($result['source']);
				if ($this->appid <= 11)
				{
					foreach ($result as $key => $val)
					{
						if (in_array($key, array_keys(Contact::$allow_cols, TRUE)))
						{
							foreach ($val as $k => $v)
							{
								$result[$key][$k]['type'] = strtolower($v['type']);
							}
						}
					}
					//网站更新
					if ($this->source != 0)
					{
						//兼容旧的关联用户ID
						$result['user_id'] = 0;
						//兼容关联用户ID=0，默认以下信息为空
						$result['health'] = 1;
						$result['name'] = '';
						$result['user_status'] = 0;
						$result['user_link'] = 0;
						$result['gender'] = 0;
						$result['residence'] = '';
						$result['lunar_bday'] = '';
						$result['is_lunar'] = FALSE;
						$result['animal_sign'] = '';
						$result['zodiac'] = '';
					}
				}

				if (Kohana::config('contact.filter'))
				{
					$result = array_filter($result);
				}
				$this->send_response(200, $result);
			}
		}
	}

	/**
	 * 获取多个联系人信息
	 * @method POST
	 * @return void
	 */
	public function show_batch()
	{
		// $ids 联系人ID(用,隔开)
		$ids = $this->ids;
		$result = array();
		$contact_ids = array_keys($this->model->get($this->user_id));
		foreach ($ids as $id)
		{
			if (in_array($id, $contact_ids))
			{
				$contact = $this->model->get($this->user_id, $id);
				if ($contact !== FALSE)
				{
					$res = $contact->to_array();
					$res['source'] = api::get_source_name($res['source']);

					if ($this->appid <= 11)
					{
						foreach ($res as $key => $val)
						{
							if (in_array($key, array_keys(Contact::$allow_cols, TRUE)))
							{
								foreach ($val as $k => $v)
								{
									$res[$key][$k]['type'] = strtolower($v['type']);
								}
							}
						}
						//网站更新
						if ($this->source != 0)
						{
							//兼容旧的关联用户ID
							$res['user_id'] = 0;
							//兼容关联用户ID=0，默认以下信息为空
							$res['health'] = 1;
							$res['name'] = '';
							$res['user_status'] = 0;
							$res['user_link'] = 0;
							$res['gender'] = 0;
							$res['residence'] = '';
							$res['lunar_bday'] = '';
							$res['is_lunar'] = FALSE;
							$res['animal_sign'] = '';
							$res['zodiac'] = '';
						}
					}
					//过滤空输出
					if (Kohana::config('contact.filter'))
					{
						$res = array_filter($res);
					}
					$result[] = $res;
				}
				unset($contact);
			}
		}
		$this->send_response(200, $result);
	}

	/**
	 * 创建联系人
	 * @method POST
	 * @return void
	 */
	public function create_batch()
	{
		// $data 多个联系人数据
		$data = $this->data;

		// 是否强制新增
		$force = isset($data['force']) ? (bool) $data['force'] : FALSE;
		// 检查每组数据是否符合要求
		if (is_array($data['data']))
		{
			foreach ($data['data'] as $key => $value)
			{
				if (! is_numeric($key))
				{
					$this->send_response(400, NULL,
						Kohana::lang('contact.contact_info_incorrect'));
				}
				if (! is_array($value) OR ! array_intersect(array_keys($value), Contact::$allowed_fields))
				{
					$this->send_response(400, NULL,
						Kohana::lang('contact.contact_info_incorrect'));
				}
			}
		}
		else
		{
			$this->send_response(400, NULL,
				Kohana::lang('contact.contact_info_incorrect'));
		}
		if (count($data['data']) > 100)
		{
			$this->send_response(400, NULL,
				Kohana::lang('contact.contact_exceed_limit'));
		}
		if (! empty($data['data']))
		{
			$result = $this->model->add_batch($this->user_id, $data['data'],
				$force);
			//非MOMO客户端强制转对象
			if ($this->appid > 11)
			{
				$result = json_encode($result, JSON_FORCE_OBJECT);
			}
			$this->send_response(200, $result, '', FALSE);
		}
		else
		{
			$this->send_response(400, NULL,
				Kohana::lang('contact.contact_info_incorrect'));
		}
	}

	/**
	 * 更新联系人
	 * @method POST
	 * @param int $contact_id 联系人ID
	 * @return void
	 */
	public function update($contact_id = 0)
	{
		$data = $this->data;
		//修改模式，包括 default、overwrite、special模式，默认default
		$mode = isset($data['mode']) ? (in_array($data['mode'],
			array(
			     'default',
			     'overwrite',
			     'special'
			)) ? $data['mode'] : 'default') : 'default';
		unset($data['mode'], $data['user_id']);
		$contact_id = (int) $contact_id;
		if (empty($contact_id))
		{
			$this->send_response(400, NULL,
				Kohana::lang('contact.contact_id_empty'));
		}
		$status = $this->model->edit($this->user_id, $contact_id, $data, $mode);
		//异常留给框架处理
		$code = 500;
		$result = array();
		switch ($status['result'])
		{
			case SUCCESS:
				$code = 200;
				$result = array(
					'id'          => $contact_id,
					'modified_at' => $status['modified_at']
				);
				break;
			case NO_MODIFY_MERGE_SUCCESS:
				//默认200
				$code = 200;
				$result = array(
					'id'          => $contact_id,
					'modified_at' => $status['modified_at']
				);
				break;
			case MERGE_SUCCESS:
				//默认200
				$code = 200;
				$result = array(
					'id'          => $contact_id,
					'modified_at' => 0
				);
				break;
			case CONFLICT:
				$this->send_response(409, NULL,
					Kohana::lang('contact.contact_info_conflict'));
				break;
			case NO_EXIST:
				$this->send_response(400, NULL,
					Kohana::lang('contact.contact_not_exist'));
				break;
			default:
				$this->send_response(500, NULL,
					Kohana::lang('contact.contact_update_fail'));
				break;
		}
		$this->send_response($code, $result, '', FALSE);
	}

	/**
	 * 批量删除联系人
	 * @method POST
	 * @return void
	 */
	public function destroy_batch()
	{
		//多个联系人ID(用,隔开)
		$ids = $this->ids;
		$result = $this->model->move_contact_to_recycle($this->user_id, $ids);
		$this->send_response(200, $result, '', FALSE);
	}

	/**
	 * 批量恢复联系人
	 * @method POST
	 * @return void
	 */
	public function recover_batch()
	{
		$ids = $this->ids;
		$result = $this->model->move_recycle_to_contact($this->user_id, $ids);
		$this->send_response(200, $result, '', FALSE);
	}

	/**
	 * 批量删除回收站联系人
	 * @method POST
	 * @return void
	 */
	public function recycle_batch()
	{
		$ids = $this->ids;
		$result = $this->model->delete($this->user_id, $ids);
		$this->send_response(200, $result, '', FALSE);
	}

	/**
	 * 清空回收站联系人
	 * @method POST
	 * @return void
	 */
	public function recycle_clean()
	{
		$result = $this->model->delete($this->user_id);
		$this->send_response(200, $result, '', FALSE);
	}

	/**
	 * 保存名片到通讯录
	 * @method POST
	 * @return void
	 */
	public function save()
	{
		$data = $this->data;
		$user_id = (int) $data['id'];
		if (empty($user_id))
		{
			$this->send_response(400, NULL,
				Kohana::lang('contact.user_id_empty'));
		}
		//判断他是否给我授权
		$user_model = User_Model::instance();
		if ($user_model->get_user_info($user_id) === FALSE)
		{
			$this->send_response(404, NULL,
				Kohana::lang('contact.resource_not_exist'));
		}
		$give_permission = $user_model->get_card_sharelog($user_id,
			$this->user_id);
		//判断我是否在他的联系人中
		if ($give_permission === FALSE)
		{
			$have_permission = Friend_Model::instance()->get_link($user_id,
				$this->user_id);
		}
		else
		{
			$have_permission = FALSE;
		}
		if ($give_permission === FALSE and $have_permission === FALSE)
		{
			$this->send_response(403, NULL,
				Kohana::lang('contact.no_permission'));
		}
		if (! $this->model->is_contact($this->user_id, $user_id))
		{
			$contact = $this->model->add_contact_by_user_id($this->user_id,
				$user_id);
			if ($contact === FALSE)
			{
				$this->send_response(500, NULL,
					Kohana::lang('contact.operation_fail'));
			}
			//如果是对方给我授权并且我没有给对方授权，需要回赠名片
			if ($give_permission and
				$user_model->get_card_sharelog($this->user_id, $user_id) === FALSE
			)
			{
				//增加授权
				$user_model->update_card_sharelog($user_id, $this->user_id);
				Im_Model::instance()->send_card($this->user_id, $user_id, $this->get_source());
			}
			//如果自己没有对方名片，保存对方名片
			if (! $this->model->is_contact($user_id, $this->user_id))
			{
				$sender_contact = $this->model->add_contact_by_user_id($user_id,
					$this->user_id);
				if ($sender_contact === FALSE)
				{
					$this->send_response(500, NULL,
						Kohana::lang('contact.operation_fail'));
				}
			}
			$this->send_response(200, $contact->to_simple_array(), '', FALSE);
		}
		else
		{
			$this->send_response(400, NULL,
				Kohana::lang('contact.contact_has_saved'));
		}
	}

	/**
	 * 获取联系人变更历史
	 * @method GET
	 * @return void
	 */
	public function get_history()
	{
		$page = (int) $this->input->get('page', 1);
		$page_size = (int) $this->input->get('page_size', 100);
		$page_limit = 100;
		if ($page < 1)
		{
			$this->send_response(400, NULL, Kohana::lang('contact.page_limit'));
		}
		if ($page_size < 1 OR $page_size > $page_limit OR $page_size % 10 != 0)
		{
			$this->send_response(400, NULL, Kohana::lang('contact.page_size_limit'));
		}

		$start = ($page - 1) * $page_size;
		$cache_start = (int) floor($start / $page_limit) * $page_limit;
		$array_offset = (int) ($start % $page_limit);
		$list = $this->model->get_history($this->user_id, $cache_start, $page_limit);
		$total = count($list);
//		$data = array_splice($list, $array_offset, $page_size);
		$count = 0;
		$data = array();
		for ($i = $array_offset; $i < $total; $i ++)
		{
			// 过滤错误和空的联系人快照
			if (($list[$i]['dateline'] < strtotime('2013-05-01 00:00:00')
					OR $list[$i]['dateline'] > strtotime('2013-05-22 15:08:00'))
				AND $list[$i]['count'] > 0
			)
			{
				$data[$count ++] = $list[$i];
				if ($count == $page_size)
				{
					break;
				}
			}
		}

		if ($count < $page_size && count($list) == $page_limit)
		{
			$list = $this->model->get_history($this->user_id, $cache_start + $page_limit, $page_limit);
//			$data = array_merge($data, array_splice(array_values($list), 0, $page_size - $count));
			$total = count($list);
			for ($i = 0; $i < $total; $i ++)
			{
				// 过滤错误和空的联系人快照
				if (($list[$i]['dateline'] < strtotime('2013-05-01 00:00:00')
						OR $list[$i]['dateline'] > strtotime('2013-05-22 15:08:00'))
					AND $list[$i]['count'] > 0
				)
				{
					$data[$count ++] = $list[$i];
					if ($count == $page_size)
					{
						break;
					}
				}
			}
		}
		$this->send_response(200, $data);
	}

	/**
	 * 还原快照
	 * @method POST
	 * @return void
	 */
	public function recover_history()
	{
		$data = $this->data;
		$dateline = isset($data['dateline']) ? $data['dateline'] : 0;
		if (empty($dateline))
		{
			$this->send_response(400, NULL,
				Kohana::lang('contact.dateline_empty'));
		}
		if (! Kohana::config('contact.save_snapshot'))
		{
			$this->send_response(400, NULL,
				Kohana::lang('contact.operation_not_enable'));
		}
		$history = $this->model->get_history_by_dateline($this->user_id,
			$dateline);
		if (empty($history))
		{
			$this->send_response(400, NULL,
				Kohana::lang('contact.history_not_exist'));
		}
		if (empty($history['count']))
		{
			$this->send_response(400, NULL,
				Kohana::lang('contact.snapshot_data_empty'));
		}
		$result = $this->model->recover_snapshot($this->user_id, $dateline);
		if ($result === FALSE)
		{
			$this->send_response(400, NULL,
				Kohana::lang('contact.recover_history_fail'));
		}
		$this->send_response(200, NULL, '', FALSE);
	}

	/**
	 * 兼容旧接口
	 */
	public function create_at()
	{
		$data = $this->data;
		//检查每组数据是否符合要求
		if (count($data) > 100)
		{
			$this->send_response(400, NULL, '400220:手机号码超出最大限制');
		}
		foreach ($data as $key => $val)
		{

			$count = count($val['mobile']);
			if ($count == 0)
			{
				$data[$key]['mobile'] = '';
			}
			elseif ($count == 1)
			{
				$data[$key]['mobile'] = $val['mobile'][0];
			}
			else
			{
				$data[$key]['mobile'] = $val['mobile'][0];
				foreach ($val['mobile'] as $mobile)
				{
					$valid = international::check_mobile($mobile, '86');
					if ($valid)
					{
						$user_info = User_Model::instance()
						             ->get_user_by_mobile($valid['mobile'], $valid['country_code']);
						if (! empty($user_info) && $user_info['uid'])
						{
							$data[$key]['mobile'] = $mobile;
							break;
						}
					}
				}
			}
		}
		$result = User_Model::instance()->create_at($data, $this->user_id, $this->source);
		$return = array();
		foreach ($result as $val)
		{
			if (isset($val['error']))
			{
				$return[] = array(
					'user_id' => 0,
					'error'   => $val['error']
				);
			}
			else
			{
				$return[] = array(
					'user_id'     => (int) $val['user_id'],
					'pref_mobile' => $val['mobile']
				);
			}
		}
		$this->send_response(200, $return);

	}

	/**
	 * 获取联系人历史详情
	 * @param int $dateline 时间
	 */
	public function recover_to_do($dateline = 0)
	{
		$history = $this->_check_dateline($dateline);
		$to_add = $to_update = $to_delete = $to_list = array();
		$list = $this->model->get($this->user_id);
		$snapshot_list = $this->model->get_snapshot_list($this->user_id, $history['dateline']);
		if (! Kohana::config('contact.from_category_table'))
		{
			$categories = $to_categories = array();
		}
		$ids = array_keys($list);
		foreach ($snapshot_list as $contact)
		{
			if (! in_array($contact['id'], $ids))
			{
				$to_add[] = array('id' => (int) $contact['id'], 'formatted_name' => $contact['formatted_name']);
			}
			elseif ($contact['modified_at'] != $list[$contact['id']]['modified_at'])
			{
				$to_update[] = array('id' => (int) $contact['id'], 'formatted_name' => $contact['formatted_name']);
			}
			$to_list[$contact['id']] = $contact;

			if (! Kohana::config('contact.from_category_table'))
			{
				if (! empty($contact['category']) AND ! in_array($contact['category'], $to_categories))
				{
					$to_categories[] = $contact['category'];
				}
			}

		}
		$to_ids = array_keys($to_list);

		foreach ($list as $id => $contact)
		{
			if (! in_array($id, $to_ids))
			{
				$to_delete[] = array('id' => (int) $id, 'formatted_name' => $contact['formatted_name']);
			}

			if (! Kohana::config('contact.from_category_table'))
			{
				if (! empty($contact['category']) AND ! in_array($contact['category'], $categories))
				{
					$categories[] = $contact['category'];
				}
			}

		}
		if (! Kohana::config('contact.from_category_table'))
		{
			$tmp = array();
			foreach ($categories as $c)
			{
				$tmp = array_merge($tmp, explode(',', $c));
			}
			$category_count = count(array_unique($tmp));

			$tmp = array();
			foreach ($to_categories as $c)
			{
				$tmp = array_merge($tmp, explode(',', $c));
			}
			$to_category_count = count(array_unique($tmp));
		}
		else
		{
			$category_count = $this->model->contact_mapper->get_category_count($this->user_id);
			$to_category_count = $this->model->contact_mapper->get_snapshot_category_count($this->user_id, $dateline);
		}

		$result = array(
			'user_id'           => (int) $history['uid'],
			'dateline'          => (int) $history['dateline'],
			'source'            => api::get_source_name($history['source']),
			'operation'         => Kohana::lang('contact.' . $history['operation']),
			'count'             => count($list),
			'to_count'          => count($snapshot_list),
			'category_count'    => $category_count,
			'to_category_count' => $to_category_count,
			'to_add'            => $to_add,
			'to_update'         => $to_update,
			'to_delete'         => $to_delete
		);

		$this->send_response(200, $result);
	}

	/**
	 * 检查联系人快照是否存在
	 * @param int $dateline 时间
	 * @return array
	 */
	private function _check_dateline($dateline)
	{
		if (empty($dateline))
		{
			$this->send_response(400, NULL,
				Kohana::lang('contact.dateline_empty'));
		}
		if (! Kohana::config('contact.save_snapshot'))
		{
			$this->send_response(400, NULL,
				Kohana::lang('contact.operation_not_enable'));
		}
		$history = $this->model->get_history_by_dateline($this->user_id, $dateline);
		if (empty($history))
		{
			$this->send_response(400, NULL, Kohana::lang('contact.history_not_exist'));
		}
		return $history;
	}

	/**
	 * 获取联系人快照数据列表
	 * @param int $dateline 时间
	 */
	public function snapshot($dateline = 0)
	{
		$history = $this->_check_dateline($dateline);

		// 可获取的字段
		$allow_fields = array('id', 'formatted_name', 'category', 'avatar', 'modified_at');
		$default_fields = array('id', 'modified_at');
		$fields = $this->input->get('fields');
		$fields = $fields ? array_merge($default_fields, explode(',', $fields)) : $default_fields;
		if (array_diff($fields, $allow_fields))
		{
			$this->send_response(400, NULL, Kohana::lang('contact.info_limit'));
		}
		$flipped_fields = array_flip($fields);
		//分页支持
		$page = (int) $this->input->get('page', 0);
		$page_size = (int) $this->input->get('page_size', 0);
		$page = ($page_size AND $page == 0) ? 1 : $page;

		$snapshot_list = $this->model->get_snapshot_list($this->user_id, $history['dateline']);

		$result = array();

		foreach ($snapshot_list as $value)
		{
			if ($value)
			{
				if ($val = array_intersect_key($value, $flipped_fields))
				{
					$result[] = $val;
				}
			}
		}

		if (! empty($page) AND ! empty($page_size))
		{
			$this->send_response(200, array_splice($result, ($page - 1) * $page_size, $page_size));
		}
		else
		{
			$this->send_response(200, $result);
		}
	}

	/**
	 * 获取联系人快照数据列表
	 */
	public function snapshot_show_batch()
	{
		$dateline = $this->data['dateline'];
		$history = $this->_check_dateline($dateline);
		$ids = $this->ids;
		$result = array();
		$snapshot_list = $this->model->get_snapshot_list($this->user_id, $history['dateline']);
		$contact_ids = array();
		foreach ($snapshot_list as $snapshot)
		{
			$contact_ids[] = $snapshot['id'];
		}
		foreach ($ids as $id)
		{
			if (in_array($id, $contact_ids))
			{
				$contact = $this->model->find_by_dateline_id($this->user_id, $id, $dateline);
				if ($contact !== FALSE)
				{
					$res = $contact->to_array();
					$res['source'] = api::get_source_name($res['source']);

					if ($this->appid <= 11)
					{
						foreach ($res as $key => $val)
						{
							if (in_array($key, array_keys(Contact::$allow_cols, TRUE)))
							{
								foreach ($val as $k => $v)
								{
									$res[$key][$k]['type'] = strtolower($v['type']);
								}
							}
						}
						//网站更新
						if ($this->source != 0)
						{
							//兼容旧的关联用户ID
							$res['user_id'] = 0;
							//兼容关联用户ID=0，默认以下信息为空
							$res['health'] = 1;
							$res['name'] = '';
							$res['user_status'] = 0;
							$res['user_link'] = 0;
							$res['gender'] = 0;
							$res['residence'] = '';
							$res['lunar_bday'] = '';
							$res['is_lunar'] = FALSE;
							$res['animal_sign'] = '';
							$res['zodiac'] = '';
						}
					}
					//过滤空输出
					if (Kohana::config('contact.filter'))
					{
						$res = array_filter($res);
					}
					$result[] = $res;
				}
				unset($contact);
			}
		}
		$this->send_response(200, $result);
	}

	//获取两个用户连接关系
	public function get_link()
	{
		$data = $this->data;
		$sender_id = isset($data['sender_id']) ? (int) $data['sender_id'] : 0;
		$receiver_id = isset($data['receiver_id']) ? (int) $data['receiver_id'] : 0;
		$sender_mobile = isset($data['sender_mobile']) ? $data['sender_mobile'] : '';
		$receiver_mobile = isset($data['receiver_mobile']) ? $data['receiver_mobile'] : '';

		if (! in_array($this->user_id, array($sender_id, $receiver_id)))
		{
			$this->send_response(403, NULL, Kohana::lang('contact.no_permission'));
		}

		if (empty($sender_id) OR empty($receiver_id) OR empty($sender_mobile) OR empty($receiver_mobile)
			OR ! international::check_is_valid('86', $sender_mobile) OR
			! international::check_is_valid('86', $receiver_mobile)
		)
		{
			$this->send_response(400, NULL, Kohana::lang('contact.param_limit'));
		}
		$link = $this->model->is_link($sender_id, $receiver_mobile);
		$to_link = $this->model->is_link($receiver_id, $sender_mobile);
		$this->send_response(200, array('sender_to_receiver' => $link, 'receiver_to_sender' => $to_link));
	}

	/**
	 * 手动备份联系人时光机
	 */
	public function manual_backup()
	{
		$result = $this->model->save_snapshot($this->user_id);
		if ($result)
		{
			$this->model->prepare_task($this->user_id);
			$this->send_response(200,
				array(
				     'dateline' => api::get_now_time(),
				)
			);
		}
		else
		{
			$this->send_response(400, NULL, Kohana::lang('contact.operation_fail'));
		}
	}

	/*
	public function update_history()
	{
		$data = $this->data;
		$dateline = isset($data['dateline']) ? (int) $data['dateline'] : 0;
		if ($dateline)
		{
			$result = $this->model->update_history($this->user_id, $dateline);
			if ($result == - 1)
			{
				$this->send_response(400, NULL, Kohana::lang('contact.history_not_exist'));
			}
			elseif ($result == 1)
			{
				$this->send_response(200,
					array(
					     'dateline' => api::get_now_time(),
					)
				);
			}
			else
			{
				$this->send_response(400, NULL, Kohana::lang('contact.operation_fail'));
			}
		}
		else
		{
			$this->send_response(400, NULL, Kohana::lang('contact.dateline_empty'));
		}
	}
	*/

	/**
	 * 请求资源不存在
	 */
	public function __call($method, $arguments)
	{
		if (substr($method, 0, 7) == 'manual_')
		{
			$method = substr($method, 7);
			if (method_exists($this, $method))
			{
				call_user_func_array(array($this, $method), $arguments);
			}
			else
			{
				parent::__call($method, $arguments);
			}
		}
		else
		{
			parent::__call($method, $arguments);
		}

	}

} // End Contact Controller
