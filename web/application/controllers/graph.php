<?php
defined('SYSPATH') or die('No direct access allowed.');

/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * 图数据库控制器文件
 */
/**
 * 图数据库控制器
 */
class Graph_Controller extends Controller {
	/**
	 * 是否发布模式
	 */
	const ALLOW_PRODUCTION = TRUE;
	/**
	 * 图数据库模型
	 * @var Graph_Model
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
		$this->model = Graph_Model::instance();

	}

	public function get_contact()
	{
		$result = $this->model->get_contact_by_uid($this->user_id,array(1,2));
		$this->send_response(200, $result);
	}

	public function get_all_contact()
	{
		$id = $this->user_id;
		if (empty($id)) {
			$id = isset($_GET['guid']) ? $_GET['guid'] : '';
			if (empty($id))
			{
				$this->send_response(400, NULL,
					Kohana::lang('graph.guid_empty'));
			}
			$result = $this->model->get_contact_by_device($id);
		} else {
			$result = $this->model->get_contact_by_uid($id);
		}
		$this->send_response(200, $result);
	}

	public function check_device()
	{
		$guid = isset($_GET['guid']) ? $_GET['guid'] : '';
		$result = FALSE;
		if ($guid)
		{
			$result = $this->model->check_device($guid);
		}
		$this->send_response(200, $result);
	}

	public function block()
	{
		$this->_set_block(1);
	}

	public function unblock()
	{
		$this->_set_block(0);
	}

	private function _set_block($status)
	{
		$data = $this->get_data();
		$mobile = isset($data['mobile']) ? $data['mobile'] : '';
		if (!$mobile)
		{
			$this->send_response(400, NULL, Kohana::lang('graph.mobile_not_allow'));
		}
		$id = $this->user_id;
		if (empty($id)) {
			$id = isset($data['guid']) ? $data['guid'] : '';
			$id_type = 'guid';
			if (empty($id))
			{
				$this->send_response(400, NULL,
					Kohana::lang('graph.guid_empty'));
			}
		} else {
			$id_type = 'uid';
		}
		if($res = international::check_mobile($mobile)) {
			$result = $this->model->set_block($id, implode('', $res), $status, $id_type);
			if($result === -1) {
				$this->send_response(400, NULL, Kohana::lang('graph.mobile_not_exist'));
			} else {
				$this->send_response(200, array('result' => (bool)$result));
			}
		} else {
			$this->send_response(400, NULL, Kohana::lang('graph.mobile_not_allow'));
		}
	}

	public function block_list()
	{
		$id = $this->user_id;
		if (empty($id))
		{
			$id = isset($_GET['guid']) ? $_GET['guid'] : '';
			$id_type = 'guid';
			if (empty($id))
			{
				$this->send_response(400, NULL,
					Kohana::lang('graph.guid_empty'));
			}
		} else {
			$id_type = 'uid';
		}
		$result = $this->model->block_list($id, $id_type);
		$this->send_response(200, $result);
	}

	public function upload()
	{
		header("Access-Control-Allow-Origin: *");
		header("Access-Control-Allow-Credentials: true");
		header("Access-Control-Allow-Methods:POST, GET, PUT, DELETE, OPTIONS");
		header("Access-Control-Allow-Headers:X-FILENAME");

		if (strtolower($this->get_method()) == "options")
		{
			exit;
		}

		if (! $tmp = $this->_get_tmp_file())
		{
			$this->send_response(400, NULL, Kohana::lang('graph.upload_file_empty'));
		}
		//文件超过64M
		if ($tmp['length'] > Kohana::config('graph.file_max_size'))
		{
			$this->send_response(400, NULL, Kohana::lang('graph.upload_file_max'));
		}
		//未登陆处理
		if ($this->un_oauth_check)
		{
			$guid = isset($_GET['guid']) ? $_GET['guid'] : '';
			$client_id = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;

			if (empty($guid))
			{
				$this->send_response(400, NULL,
					Kohana::lang('graph.guid_empty'));
			}

//			if (! $time = $this->model->add_contact_data($tmp['file'], $guid))
//			{
//				$this->send_response(400, NULL, Kohana::lang('graph.upload_file_error'));
//			}
			if ($md5 = $this->model->upload($tmp['file']))
			{
				$this->model->add_device_history($md5, $guid, $client_id);
				$this->send_response(200, array('guid' => $guid, 'time' => time()), '', FALSE);
			}
			else
			{
				$this->send_response(400, NULL, Kohana::lang('graph.upload_file_fail'));
			}
		}
		else
		{
			//已登陆处理
			$reason = isset($_GET['reason']) ? $_GET['reason'] : '';
			if (empty($reason))
			{
				$this->send_response(400, NULL,
					Kohana::lang('graph.reason_empty'));
			}
			if ($md5 = $this->model->upload($tmp['file']))
			{
				$url = url::base(FALSE) . 'graph/download/' . $md5;
				if ($this->model->update($this->user_id, $url, $reason, $this->appid, $this->device_id, $this->source))
				{
					$this->send_response(200, NULL, '', FALSE);
				}
				else
				{
					$this->send_response(500, NULL,
						Kohana::lang('graph.operation_fail'));
				}
			}
			else
			{
				$this->send_response(400, NULL, Kohana::lang('graph.upload_file_fail'));
			}
		}
	}

	public function download($md5 = '')
	{
//		$guid = isset($_GET['guid']) ? $_GET['guid'] : '';
//		if ($guid)
//		{
//			$this->model->get_file_by_device($guid);
//		}
//		elseif ($md5)
//		{
		$this->model->get_file_by_md5($md5);
//		}
	}

	/**
	 * 生成临时文件名
	 * @param string $filename
	 * @return string
	 */
	public static function temp_name($filename = '')
	{
		$tmp_dir = Kohana::config('graph.dir_tmp');
		if (! is_dir($tmp_dir))
		{
			mkdir($tmp_dir, 0777);
		}
		if ($filename)
		{
			return $tmp_dir . $filename;
		}
		return tempnam($tmp_dir, 'buf_');
	}

	/**
	 * 临时文件处理
	 * @return array|bool
	 */
	private function _get_tmp_file()
	{
		$contentType = '';
		if (isset($_SERVER['HTTP_CONTENT_TYPE']))
		{
			$contentType = $_SERVER['HTTP_CONTENT_TYPE'];
		}
		if (isset($_SERVER['CONTENT_TYPE']))
		{
			$contentType = $_SERVER['CONTENT_TYPE'];
		}
		$file_field = '';
		$file_name = '';
		if ($_FILES)
		{
			$file_field = key($_FILES);
			$file_name = $_FILES['$file_field']['name'];
		}

		if (isset($_GET['filename']))
		{
			$file_name = $_GET['filename'];
		}

		// php://input 不支持 enctype="multipart/form-data"
		if (strpos($contentType, 'multipart') !== false && $file_field)
		{
			if (isset($_FILES[$file_field]['tmp_name']) && is_uploaded_file($_FILES[$file_field]['tmp_name']) &&
				(int) $_FILES[$file_field]['error'] === UPLOAD_ERR_OK
			)
			{
				// 已上传的临时文件
				$tmp_file = $_FILES[$file_field]['tmp_name'];
				$tmp_size = $_FILES[$file_field]['size'];
			}
			else
			{
				return FALSE;
			}
		}
		else
		{
			$tmp_file = self::temp_name();
			$tmp_size = 0;
			$tmp_handler = fopen($tmp_file, 'wb');
			if ($tmp_handler)
			{
				$in = fopen("php://input", "rb");
				if ($in)
				{
					while ($buff = fread($in, 8096))
					{
						$tmp_size += strlen($buff);
						fwrite($tmp_handler, $buff);
					}
					fclose($in);
				}
				else
				{
					return FALSE;
				}
				fclose($tmp_handler);
			}
			else
			{
				return FALSE;
			}
		}

		if (is_readable($tmp_file))
		{
			return array('file' => $tmp_file, 'length' => $tmp_size, 'file_name' => $file_name);
		}
		return FALSE;
	}

	public function update()
	{
		if ($this->get_method() != 'POST')
		{
			$this->send_response(405, NULL,
				Kohana::lang('graph.method_not_exist'));
		}

		$data = $this->get_data();
		if (empty($data))
		{
			$this->send_response(400, NULL,
				Kohana::lang('graph.invalid_request'));
		}
		$guid = isset($data['guid']) ? $data['guid'] : '';
		if($guid) {
			$reason = 'guid';
			$url = '';
		} else {
			$reason = isset($data['reason']) ? $data['reason'] : '';
			$url = isset($data['url']) ? $data['url'] : '';
		}

		if (empty($reason))
		{
			$this->send_response(400, NULL,
				Kohana::lang('graph.reason_empty'));
		}

		if (empty($url) AND empty($guid))
		{
			$this->send_response(400, NULL,
				Kohana::lang('graph.url_empty'));
		}

		if ($this->model->update($this->user_id, $url, $reason, $this->appid, $this->device_id, $this->source, $guid))
		{
			$this->send_response(200, NULL, '', FALSE);
		}
		else
		{
			$this->send_response(500, NULL,
				Kohana::lang('graph.operation_fail'));
		}
	}


} // End Graph_Controller
