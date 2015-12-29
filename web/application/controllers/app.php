<?php
defined('SYSPATH') OR die('No direct script access.');
/*
 * [MOMO API] (C)1999-2012 ND Inc.
 * 应用模型文件
 */
/**
 * 应用模型
 */
class App_Controller extends Controller {

	protected $model;

	public function __construct()
	{
		parent::__construct();
		$this->_check_permission();
		$this->model = App_Model::instance();
	}

	/**
	 * 检查请求方法或参数是否正确
	 * @return void
	 */
	private function _check_permission()
	{
		// 全局处理请求方法是否正确
		$get_method = array(
			'index', 'subscription', 'show', 'subscriber'
		);
		$post_method = array(
			'create', 'subscribe', 'unsubscribe', 'setting', 'destroy','apply_91'
		);
		$type = strtoupper($this->get_method());
		$array = strtolower($type) . '_method';
		$uri = URI::$method;
		if (! in_array($type,
			array(
			     'GET', 'POST'
			), TRUE) OR ! in_array($uri, $$array, TRUE)
		)
		{
			$this->send_response(405, NULL,
				Kohana::lang('app.method_not_exist'));
		}
	}

	/**
	 * 获取所有应用列表
	 */
	public function index()
	{
		$result = $this->model->find_all();
		$robot_ids = $this->model->get_subscribed_ids($this->user_id);
		foreach ($result as $key => $val)
		{
			$result[$key]['is_subscribed'] = in_array($val['robot_id'], $robot_ids);
		}
		$this->send_response(200, $result);
	}

	/**
	 * 用户订阅机器人
	 */
	public function subscribe()
	{
		$data = $this->get_data();
		if (empty($data['robot_id']))
		{
			$this->send_response(400, NULL, Kohana::lang('app.robot_id_empty'));
		}
		if (! $this->model->find_by_id($data['robot_id']))
		{
			$this->send_response(400, NULL, Kohana::lang('app.robot_no_exist'));
		}
		$data['dateline'] = time();
		$result = $this->model->subscribe($this->user_id, $data);
		if ($result)
		{
			$this->send_response(200);
		}
		else
		{
			$this->send_response(400, NULL, Kohana::lang('app.operation_fail'));
		}
	}

	public function setting()
	{
		$setting = $this->get_data();
		if (empty($setting['robot_id']))
		{
			$this->send_response(400, NULL, Kohana::lang('app.robot_id_empty'));
		}
		if (! $this->model->find_by_id($setting['robot_id']))
		{
			$this->send_response(400, NULL, Kohana::lang('app.robot_no_exist'));
		}

		if ($setting['robot_id'] != Kohana::config('app.weather_robot_id'))
		{
			$this->send_response(400, NULL, Kohana::lang('app.only_setting_weather_robot'));
		}
		$setting['push'] = isset($setting['push']) ? $setting['push'] : array();
		foreach ($setting['push'] as &$val)
		{
			$val['push_time'] = isset($val['push_time']) ? $val['push_time'] : '';
			$hour = (int) substr($val['push_time'], 0, 2);
			$minute = (int) substr($val['push_time'], 2, 2);
			if (! is_numeric($val['push_time']) OR strlen($val['push_time']) != 4 OR $hour > 23 OR $hour < 0
				OR $minute > 60 OR $minute < 0
			)
			{
				$this->send_response(400, NULL, Kohana::lang('app.push_time_not_allow'));
			}
			if (empty($val['location']))
			{
				$this->send_response(400, NULL, Kohana::lang('app.location_not_allow'));
			}
			$val['location'] = implode(',', array_unique(explode(',', $val['location'])));
			$val['push_interval'] = isset($val['push_interval']) ? $val['push_interval'] : '';
		}

		$ids = $this->model->get_subscribed_ids($this->user_id);
		if (! in_array($setting['robot_id'], $ids))
		{
			$this->send_response(400, NULL, Kohana::lang('app.has_not_subscribed'));
		}
		$result = $this->model->setting($this->user_id, $setting);
		if ($result)
		{
			$this->send_response(200);
		}
		else
		{
			$this->send_response(400, NULL, Kohana::lang('app.operation_fail'));
		}
	}

	/**
	 * 用户取消订阅机器人
	 */
	public function unsubscribe()
	{
		$data = $this->get_data();
		if (empty($data['robot_id']))
		{
			$this->send_response(400, NULL, Kohana::lang('app.robot_id_empty'));
		}
		if (! $this->model->find_by_id($data['robot_id']))
		{
			$this->send_response(400, NULL, Kohana::lang('app.robot_no_exist'));
		}
		$result = $this->model->unsubscribe($this->user_id, $data['robot_id']);
		if ($result)
		{
			$this->send_response(200);
		}
		else
		{
			$this->send_response(400, NULL, Kohana::lang('app.operation_fail'));
		}
	}

	/**
	 * 获取用户订阅机器人列表
	 */
	public function subscription()
	{
		$result = $this->model->subscription($this->user_id);
		$this->send_response(200, $result);
	}

	/**
	 * 获取机器人信息
	 * @param $robot_id
	 */
	public function show($robot_id = 0)
	{
		if (empty($robot_id))
		{
			$this->send_response(400, NULL, Kohana::lang('app.robot_id_empty'));
		}
		$result = $this->model->find_detail_by_id($this->user_id, $robot_id);
		if (! $result)
		{
			$this->send_response(404, NULL, Kohana::lang('app.robot_no_exist'));
		}
		$this->send_response(200, $result);
	}

	/**
	 * 获取订阅者
	 */
	public function subscriber()
	{
		$robot_id = (int) $this->input->get('robot_id', 0);
		$push_time = $this->input->get('push_time', '0000');
		if (empty($robot_id))
		{
			$this->send_response(400, NULL, Kohana::lang('app.robot_id_empty'));
		}

		$hour = (int) substr($push_time, 0, 2);
		$minute = (int) substr($push_time, 2, 2);
		if (! is_numeric($push_time) OR strlen($push_time) != 4 OR $hour > 23 OR $hour < 0
			OR $minute > 60 OR $minute < 0
		)
		{
			$this->send_response(400, NULL, Kohana::lang('app.push_time_not_allow'));
		}

		if (! $this->model->is_robot_exist($robot_id))
		{
			$this->send_response(404, NULL, Kohana::lang('app.robot_no_exist'));
		}

		$res = $this->model->get_subscribers($robot_id, $push_time);
		foreach($res as &$val) {
			$val['uid'] = (int)$val['uid'];
		}
		$result = array(
			'robot_id' => (int) $robot_id,
			'push_time' => $push_time,
			'total' => count($res),
			'data' => $res
		);
		$this->send_response(200, $result);
	}

	/*
	 * 非开放接口
	 */

	/**
	 * 创建机器人
	 * @todo 需要权限验证
	 */
	public function create()
	{
		if ($this->user_id > 2000)
		{
			$this->send_response(403, NULL, Kohana::lang('app.no_permission'));
		}
		$robot = $this->get_data();
		if (empty($robot['appid']))
		{
			$this->send_response(400, NULL, Kohana::lang('app.app_id_empty'));
		}
		if (! $this->model->is_app_exist($robot['appid']))
		{
			$this->send_response(400, NULL, Kohana::lang('app.app_no_exist'));
		}
		if (empty($robot['robot_id']))
		{
			$this->send_response(400, NULL, Kohana::lang('app.robot_id_empty'));
		}
		if (! $this->model->is_user_exist($robot['robot_id']))
		{
			$this->send_response(400, NULL, Kohana::lang('app.robot_no_exist'));
		}
		if (empty($robot['name']))
		{
			$this->send_response(400, NULL, Kohana::lang('app.name_empty'));
		}
		if (empty($robot['desc']))
		{
			$this->send_response(400, NULL, Kohana::lang('app.desc_empty'));
		}

		if (empty($robot['command_type']))
		{
			$this->send_response(400, NULL, Kohana::lang('app.command_type_empty'));
		}

		if (! in_array($robot['command_type'], array('text', 'audio')))
		{
			$this->send_response(400, NULL, Kohana::lang('app.command_type_not_allow'));
		}

		$robot['command'] = isset($robot['command']) ? $robot['command'] : '';
		$robot['auto_query_command'] = isset($robot['auto_query_command']) ? $robot['auto_query_command'] : '';

		$result = $this->model->create($robot);
		if ($result)
		{
			$this->send_response(200);
		}
		else
		{
			$this->send_response(500, NULL, Kohana::lang('app.create_fail'));
		}
	}

	/**
	 * 删除机器人
	 * @todo 需要权限验证
	 */
	public function destroy()
	{
		if ($this->user_id > 2000)
		{
			$this->send_response(403, NULL, Kohana::lang('app.no_permission'));
		}
		$robot = $this->get_data();
		if (empty($robot['robot_id']))
		{
			$this->send_response(400, NULL, Kohana::lang('app.robot_id_empty'));
		}
		$result = $this->model->destroy($robot['robot_id']);
		if ($result)
		{
			$this->send_response(200);
		}
		else
		{
			$this->send_response(500, NULL, Kohana::lang('app.create_fail'));
		}
	}
	/**
	 * 
	 * 申请应用
	 */
	public function apply_91() {
		$ip = $this->get_ip();
		if(IN_PRODUCTION === TRUE && !in_array($ip,Kohana::config('91.authorized_ip'))){
			$this->send_response(403, NULL, 'ip未授权');
		}
		$data = $this->get_data();
		$appid = $data['appid']?(int)$data['appid']:0;
		$name = $data['name']?trim($data['name']):'';
		$title = $data['title']?trim($data['title']):'';
		$description = $data['description']?trim($data['description']):'';
		if(empty($appid))
			$this->send_response(400, NULL, Kohana::lang('app.app_id_empty'));
		if(empty($name))
			$this->send_response(400, NULL, Kohana::lang('app.dev_name_empty'));
		if(empty($title))
			$this->send_response(400, NULL, Kohana::lang('app.title_empty'));
		if(empty($description))
			$this->send_response(400, NULL, Kohana::lang('app.desc_empty'));
		$result = Oauth_Model::instance()->get_91($appid);
		if(!$result)
			$result = Oauth_Model::instance()->create($appid,'91',$name,$title,$description);
		if($result)
			$this->send_response(200,array('appid'=>$result['appid'],'oauth_consumer_key'=>$result['consumer_key'],'oauth_consumer_secret'=>$result['consumer_secret']));
		else 	
			$this->send_response(500, NULL, Kohana::lang('app.create_fail'));
	}
}
