<?php
defined('SYSPATH') or die('No direct access allowed.');

/*
 * [MOMO API] (C)1999-2013 ND Inc.
 * 图数据库控制器文件
 */
/**
 * 图数据库控制器
 */
class Mining_Controller extends Controller {
	/**
	 * 是否发布模式
	 */
	const ALLOW_PRODUCTION = TRUE;
	/**
	 * 图数据库模型
	 * @var Mining_Model
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
		$this->model = Mining_Model::instance();

	}

	public function query()
	{
		$this->delete_error();
		exit;

		$imid = isset($_GET['imid']) ? $_GET['imid'] : '';
		$imei = isset($_GET['imei']) ? $_GET['imei'] : '';
		$return_type = isset($_GET['type']) ? $_GET['type'] : 'imid';
		$result = array();
		if (! empty($imid))
		{
			if (in_array($return_type, array('imid', 'imei')))
			{
				$result = $this->model->get_contact_by_imid($imid, $return_type);
			}
		}
		elseif (! empty($imei))
		{
			if (in_array($return_type, array('imid', 'imei')))
			{
				$result = $this->model->get_contact_by_uid($this->model->get_uid_by_imei($imei), $return_type);
			}
		}
		$this->send_response(200, $result);
	}

	public function relationship()
	{
		$id = isset($_GET['id']) ? $_GET['id'] : '';
		$id2 = isset($_GET['id2']) ? $_GET['id2'] : '';
		$type = isset($_GET['type']) ? $_GET['type'] : 'imid';
		$count = 0;
		if (! empty($id) AND ! empty($id2))
		{
			if ($type == 'imid')
			{
				$count = $this->model->get_relationship_by_imid($id, $id2);
			}
			elseif ($type == 'imei')
			{
				$count = $this->model->get_relationship_by_imei($id, $id2);
			}
		}
		$this->send_response(200, array('count' => $count));
	}

	public function cypher()
	{
		$data = $this->get_data();
		$query = isset($data['query']) ? $data['query'] : '';
		if ($query)
		{
			$result = $this->model->cypher($query);

			if ($result)
			{
				$this->send_response(200, $result);
			}
			else
			{
				$this->send_response(400, NULL, Kohana::lang('mining.result_empty'));
			}
		}
		else
		{
			$this->send_response(400, NULL,
				Kohana::lang('mining.invalid_request'));
		}
	}

	public function delete_error()
	{
		//$uid = 10;
		//$db = Database::instance();
		$cache = Cache::instance();
		$uids = array(
			378869842,
			482388904,
			378869842,
			413901072,
			499755140,
			499968669,
			361031450,
			473855656,
			401289529,
			480791138
		);
		foreach($uids as $uin) {
			$key = CACHE_PRE . 'user_'.'_91uin_'. $uin;
			echo 'key = '. $key. "\n";
			var_dump($cache->delete($key));
		}
		exit;


		$query = $db->query("SELECT ost_id, ost_token_type, ost_token, osr_consumer_key
		FROM oauth_server_token
		LEFT JOIN oauth_server_registry ON oauth_server_token.ost_osr_id_ref = oauth_server_registry.osr_id
		WHERE ost_usa_id_ref = {$uid}");
		if ($query->count())
		{
			$result = $query->result(FALSE);
			foreach ($result as $val)
			{
				$key = 'momov3_oauth_'.md5($val['ost_token_type'].'_'.$val['osr_consumer_key'].'_'. $val['ost_token']);
				var_dump($cache->delete($key));
			}
			var_dump($db->query("DELETE FROM oauth_server_token where ost_usa_id_ref = {$uid}"));
		}
	}

} // End Mining_Controller
