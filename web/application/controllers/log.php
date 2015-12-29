<?php
defined('SYSPATH') OR die('No direct script access.');
/*
 * [MOMO API] (C)1999-2012 ND Inc.
 * 日志模型文件
 */
/**
 * 日志模型
 */
class Log_Controller extends Controller {

	protected $model;

	public function __construct()
	{
		parent::__construct();
		$this->model = Log_Model::instance();
	}

	/**
	 * 
	 * 记录崩溃日志
	 */
	public function add() {
		$data = $this->get_data();
		$content = $data['content'];
		if($content) {
			$this->model->addCrashLog($data);
			$this->send_response (200);
		}
		$this->send_response ( 400, NULL,'内容为空');
	}
	
	public function lists() {
		$data = $this->get_data();
		$appid = $data['appid']?$data['appid'].'':0;
		$result = $this->model->listsCrashLog($appid);
		$this->send_response (200,$result);
	}
}
