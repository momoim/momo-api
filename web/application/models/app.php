<?php
defined('SYSPATH') or die('No direct script access.');
/*
 * [MOMO API] (C)1999-2012 ND Inc.
 * 应用模型文件
 */
/**
 * 应用模型
 */
class App_Model extends Model implements User_Interface {

	/**
	 * 实例
	 * @var App_Model
	 */
	protected static $instance;

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
	 * 单例模式
	 * @return App_Model
	 */
	public static function &instance()
	{
		if (! isset(self::$instance))
		{
			// Create a new instance
			self::$instance = new App_Model();
		}
		return self::$instance;
	}

	/**
	 * 构造函数
	 */
	public function __construct()
	{
		parent::__construct();
		$this->cache = Cache::instance();
		$this->cache_pre = CACHE_PRE . 'app_';
	}

	/**
	 * 获取所有机器人列表
	 * @return array
	 */
	public function find_all()
	{
//		$result = $this->cache->get($this->cache_pre.'find_all');
//		if (! $result)
//		{
		$query = $this->db->get('app_robot');
		$result = array();
		if ($query->count())
		{
			$res = $query->result_array(FALSE);
			foreach ($res as $key => $val)
			{
				unset($val['appid'], $val['desc'], $val['created']);
				$result[$key] = array(
					'robot_id' => (int) $val['robot_id'],
					'avatar' => sns::getavatar($val['robot_id']),
				) + $val;
			}
		}
//			$this->cache->set($this->cache_pre.'find_all', $result);
//		}
		return $result;
	}

	/**
	 * 根据过滤条件获取机器人推送信息
	 * @param array $where 过滤条件
	 * @return array
	 */
	public function get_push_info($where)
	{
		$query = $this->db->getwhere('app_push', $where);
		$result = array();
		if ($query->count())
		{
			$result = $query->result_array(FALSE);
		}
		return $result;
	}

	/**
	 * 用户订阅机器人列表
	 * @param int $user_id 用户ID
	 * @param array $data 机器人信息
	 * @return bool
	 */
	public function subscribe($user_id, $data)
	{
		$result = FALSE;
		$query = $this->db->select('robot_id, active')
			->getwhere('app_subscription',
			array('uid' => $user_id,
			      'robot_id' => $data['robot_id'],
			));
		if ($query->count())
		{
			$res = $query->result_array(FALSE);
			if ($res[0]['active'] == 0)
			{
				$this->db->update('app_subscription', array('dateline' => $data['dateline'], 'active' => 1),
					array('uid' => $user_id,
					      'robot_id' => $data['robot_id']
					)
				);
			}
			$result = TRUE;
		}
		else
		{
			$query = $this->db->insert('app_subscription',
				array(
				     'uid' => $user_id,
				     'robot_id' => $data['robot_id'],
				     'dateline' => $data['dateline'],
				     'active' => 1,

				));
			if ($query)
			{
//				$this->cache->delete($this->cache_pre.'subscription_'.$user_id);
				$mq_msg = array(
					'kind' => 'robot_help',
					'data' => array(
						'uids' => array($user_id)
					)
				);
				$this->mq_send(json_encode($mq_msg), (string) $data['robot_id'], 'momo_sys');
				$result = TRUE;
			}
		}
		return $result;
	}

	/**
	 * 用户订阅机器人列表
	 * @param int $user_id 用户ID
	 * @param array $data 机器人信息
	 * @return bool
	 */
	public function setting($user_id, $data)
	{
		$res = $this->get_push_info(
			array('uid' => $user_id,
			      'robot_id' => $data['robot_id'],
			));
		if ($res)
		{
			$old_push_info = $push_info = array();
			foreach ($res as $val)
			{
				$key = md5($val['push_time'] . $val['location'] . $val['push_interval']);
				$old_push_info[$key] = $val;
			}
			foreach ($data['push'] as $val)
			{
				$key = md5($val['push_time'] . $val['location'] . $val['push_interval']);
				$push_info[$key] = $val;
			}
			//删除旧的推送
			$to_deletes = array_diff_key($old_push_info, $push_info);
			foreach ($to_deletes as $val)
			{
				$query = $this->db->delete('app_push',
					array('uid' => $user_id,
					      'robot_id' => $data['robot_id'],
					      'push_time' => $val['push_time'],
					      'push_interval' => $val['push_interval'],
					      'location' => $val['location'],
					));
				if (! $query)
				{
					return FALSE;
				}
			}
			//增加新的推送
			$to_inserts = array_diff_key($push_info, $old_push_info);
			foreach ($to_inserts as $val)
			{
				$query = $this->db->insert('app_push',
					array(
					     'uid' => $user_id,
					     'robot_id' => $data['robot_id'],
					     'push_time' => $val['push_time'],
					     'push_interval' => $val['push_interval'],
					     'location' => $val['location'],
					)
				);
				if (! $query)
				{
					return FALSE;
				}
			}
		}
		else
		{
			$tmp = array();
			foreach ($data['push'] as $val)
			{
				$key = md5($val['push_time'] . $val['location'] . $val['push_interval']);
				if (! in_array($key, $tmp))
				{
					$query = $this->db->insert('app_push',
						array(
						     'uid' => $user_id,
						     'robot_id' => $data['robot_id'],
						     'push_time' => $val['push_time'],
						     'push_interval' => $val['push_interval'],
						     'location' => $val['location'],
						)
					);
					if (! $query)
					{
						return FALSE;
					}
					$tmp[] = $key;
				}
			}
		}
		return TRUE;
	}

	/**
	 * 用户取消订阅机器人列表
	 * @param int $user_id 用户ID
	 * @param int $robot_id 机器人ID
	 * @return bool
	 */
	public function unsubscribe($user_id, $robot_id)
	{
		$result = FALSE;
		$query = $this->db->select('robot_id')
			->getwhere('app_subscription',
			array('uid' => $user_id,
			      'robot_id' => $robot_id
			));
		if ($query->count())
		{
			$query = $this->db->update('app_subscription',
				array('active' => 0),
				array(
				     'uid' => $user_id,
				     'robot_id' => $robot_id,
				));
			$query_push = $this->db->delete('app_push',
				array('uid' => $user_id,
				      'robot_id' => $robot_id,
				));
			if ($query AND $query_push)
			{
//				$this->cache->delete($this->cache_pre.'subscription_'.$user_id);
				$result = TRUE;
			}
		}
		else
		{
			$result = TRUE;
		}
		return $result;
	}

	/**
	 * 获取订阅机器人ID
	 * @param int $user_id 用户ID
	 * @return array
	 */
	public function get_subscribed_ids($user_id)
	{
		$query = $this->db->select('robot_id')->getwhere('app_subscription', array('uid' => $user_id, 'active' => 1));
		$result = array();
		if ($query->count())
		{
			$res = $query->result_array(FALSE);
			foreach ($res as $val)
			{
				$result[] = $val['robot_id'];
			}
		}
		return $result;
	}

	/**
	 * 获取用户订阅列表
	 * @param int $user_id 用户ID
	 * @return array
	 */
	public function subscription($user_id)
	{
//		$result = $this->cache->get($this->cache_pre.'subscription_'.$user_id);
//		if (! $result)
//		{
		$query = $this->db->getwhere('app_subscription', array('uid' => $user_id));
		$result = array();
		if ($query->count())
		{
			$res = $query->result_array(FALSE);
			foreach ($res as $val)
			{
				if ($val['active'] == 1)
				{
					$robot_info = $this->find_by_id($val['robot_id']);
					unset($robot_info['appid'], $robot_info['desc'], $robot_info['created'], $val['uid'], $val['active']);
					$result[] = array(
						'robot_id' => (int) $val['robot_id'],
						'dateline' => (int) $val['dateline'],
					) + $robot_info + $val;
				}
			}
		}
		elseif (Kohana::config('app.auto_subscribe'))
		{
			$robot_list = $this->find_all();
			foreach ($robot_list as $key => $robot)
			{
				$data = array(
					'robot_id' => (int) $robot['robot_id'],
					'dateline' => time()
				);
				$this->subscribe($user_id, $data);
				$result[$key] = $robot + $data;
			}
		}
//			$this->cache->set($this->cache_pre.'subscription_'.$user_id, $result);
//		}

		return $result;
	}


	/**
	 * 获取机器人信息
	 * @param int $robot_id 机器人ID
	 * @return array
	 */
	public function find_by_id($robot_id)
	{
		$query = $this->db->getwhere('app_robot', array('robot_id' => $robot_id));
		$result = array();
		if ($query->count())
		{
			$res = $query->result_array(FALSE);
			$result = $res[0];
			$result['avatar'] = sns::getavatar($robot_id);
		}
		return $result;
	}

	/**
	 * 获取机器人详情
	 * @param int $user_id 用户ID
	 * @param int $robot_id 机器人ID
	 * @return array
	 */
	public function find_detail_by_id($user_id, $robot_id)
	{
		$robot_info = $this->find_by_id($robot_id);
		$app_info = $this->find_app_by_id($robot_info['appid']);
		unset($robot_info['appid']);

		$result = array(
			'robot_id' => (int) $robot_id,
			'created' => (int) $robot_info['created'],
			'app' => array(
				'id' => (int) $app_info['id'],
				'app_name' => $app_info['app_name'],
				'app_title' => $app_info['app_title'],
				'description' => $app_info['description'],
				'created' => (int) $app_info['created'],
			)
		) + $robot_info;

		$ids = $this->get_subscribed_ids($user_id);
		if (in_array($robot_id, $ids))
		{
			$result['is_subscribed'] = TRUE;

			$push_info = $this->get_push_info(
				array('uid' => $user_id,
				      'robot_id' => $robot_id,
				));
			foreach ($push_info as &$val)
			{
				unset($val['uid'], $val['robot_id']);
			}
			$result['push'] = $push_info;
		}
		else
		{
			$result['is_subscribed'] = FALSE;
			$result['push'] = array();
		}
		return $result;
	}

	/**
	 * 获取应用详情
	 * @param int $app_id 应用ID
	 * @return array
	 */
	public function find_app_by_id($app_id)
	{
		$query = $this->db->select('osr_id AS id, osr_name AS app_name,osr_application_title AS app_title,
		osr_application_descr AS description,
		osr_timestamp as created')->getwhere('oauth_server_registry',
			array('osr_id' => $app_id));
		$result = array();
		if ($query->count())
		{
			$res = $query->result_array(FALSE);
			$res[0]['created'] = strtotime($res[0]['created']);
			$result = $res[0];
		}
		return $result;
	}

	/**
	 * 创建机器人
	 * @param array $robot 机器人信息
	 * @return bool
	 */
	public function create($robot)
	{
		if ($this->is_robot_exist($robot['robot_id']))
		{
			return TRUE;
		}
		else
		{
			$result = User_Model::instance()->update_to_robot($robot['robot_id'], $robot['app_id'], $robot['name'],
				$robot['desc']);
			$query = $this->db->insert('app_robot', $robot + array('created' => time()));
			if ($result AND $query)
			{
				//$this->cache->delete($this->cache_pre . 'find_all');
				return TRUE;
			}
			return FALSE;
		}
	}

	public function destroy($robot_id)
	{
		if (! $this->is_robot_exist($robot_id))
		{
			return TRUE;
		}
		else
		{
			$query1 = $this->db->delete('app_robot', array('robot_id' => $robot_id));
			$query2 = $this->db->delete('app_subscription', array('robot_id' => $robot_id));
			$query3 = $this->db->delete('app_push', array('robot_id' => $robot_id));
			if ($query1 AND $query2 AND $query3)
			{
				//$this->cache->delete($this->cache_pre . 'find_all');
				return TRUE;
			}
			return FALSE;
		}
	}

	/**
	 * 检查应用是否存在
	 * @param $app_id 应用ID
	 * @return bool
	 */
	public function is_app_exist($app_id)
	{
		$query = $this->db->select('osr_id')->getwhere('oauth_server_registry', array('osr_id' => $app_id));
		if ($query->count())
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * 检查机器人是否存在
	 * @param $robot_id 机器人ID
	 * @return bool
	 */
	public function is_user_exist($robot_id)
	{
		$user_info = User_Model::instance()->get_user_info($robot_id);
		if ($user_info)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * 检查机器人是否存在
	 * @param $robot_id 机器人ID
	 * @return bool
	 */
	public function is_robot_exist($robot_id)
	{
		$query = $this->db->select('robot_id')->getwhere('app_robot', array('robot_id' => $robot_id));
		if ($query->count())
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * 获取订阅者列表
	 * @param int $robot_id 机器人ID
	 * @param int $push_time 推送时间
	 * @return array
	 */
	public function get_subscribers($robot_id, $push_time)
	{
		$result = array();
		$sql = sprintf("SELECT uid,location FROM app_push WHERE robot_id = %d AND push_time = %s",
			$robot_id, $push_time);
		$query = $this->db->query($sql);
		if ($query->count())
		{
			$result = $query->result_array(FALSE);
		}
		return $result;
	}
}
