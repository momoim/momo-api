<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * [MOMO API] (C)1999-2012 ND Inc.
 * 来电秀控制器文件
 */

class Csbk_Template_Controller extends Controller {

	public function __construct()
	{
		parent::__construct();
		//模型
        $this->model   = new Cs_Template_Model;
        $this->bkuser_model = new Csbk_User_Model();
	}

	public function index()
	{
		$this->send_response(405, NULL, '请求的方法不存在');
	}

	/**
	 * 批量新增模板秀
	 * @method POST
	 */
	public function add()
	{	
		if ($this->get_method() != 'POST') {
            $this->send_response(405, null, '请求的方法不存在');
        }

		//判断用户是否有权限执行该操作
		if(!$this->bkuser_model->check_permission($this->user_id, api::get_client_ip(),"template","write"))
		{
			 $this->send_response(403, null, '您没有权限');
		}
		
        $post = $this->get_data();
		if (empty($post)){
            $this->send_response(400, NULL, '数据为空');
        }
        
        $this->send_response(200, $this->model->add($this->user_id,$post), NULL);
	}
	
	/**
	 * 批量修改模板秀
	 * @method POST
	 */
	public function modi()
	{
		if ($this->get_method() != 'POST') {
            $this->send_response(405, null, '请求的方法不存在');
        }

		//判断用户是否有权限执行该操作
		if(!$this->bkuser_model->check_permission($this->user_id, api::get_client_ip(),"template","write"))
		{
			 $this->send_response(403, null, '您没有权限');
		}
		
        $post = $this->get_data();
		if (empty($post)){
            $this->send_response(400, NULL, '数据为空');
        }
        
        $this->send_response(200, $this->model->modi($this->user_id,$post), NULL);			
	}
	
	/**
	 * 批量删除模板秀
	 * @method POST
	 */
	public function del()
	{
		if ($this->get_method() != 'POST') {
            $this->send_response(405, null, '请求的方法不存在');
        }

		//判断用户是否有权限执行该操作
		if(!$this->bkuser_model->check_permission($this->user_id, api::get_client_ip(),"template","write"))
		{
			 $this->send_response(403, null, '您没有权限');
		}
		
        $post = $this->get_data();
		if (empty($post)){
            $this->send_response(400, NULL, '数据为空');
        }
        
        $this->send_response(200, $this->model->del($this->user_id,$post), NULL);			
	}
}