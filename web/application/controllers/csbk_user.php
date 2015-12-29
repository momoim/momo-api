<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * [MOMO API] (C)1999-2012 ND Inc.
 * 来电秀控制器文件
 */

class Csbk_User_Controller extends Controller {

	public function __construct()
	{
		parent::__construct();
		//模型
        $this->model   = new Csbk_User_Model;
	}

	public function index()
	{
		$this->send_response(405, NULL, '请求的方法不存在');
	}

	/**
	 * 查询管理员权限
	 * @method: GET
	 */
	public function permission()
	{
		if($this->get_method() != 'GET'){
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        
		$ret = $this->model->permission_info($this->user_id, api::get_client_ip());
		if($ret['result']==200)
        {
        	$this->send_response($ret['result'], $ret['msg'], NULL);
        }
        else 
        {
        	$this->send_response($ret['result'], NULL, $ret['msg']);
        }
	}
}