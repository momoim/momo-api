<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * [MOMO API] (C)1999-2012 ND Inc.
 * 来电秀控制器文件
 */

class Csbk_Callshow_Controller extends Controller {

	public function __construct()
	{
		parent::__construct();
		//模型
        $this->model   = new Csbk_Callshow_Model;
        $this->bkuser_model = new Csbk_User_Model();
	}

	public function index()
	{
		$this->send_response(405, NULL, '请求的方法不存在');
	}
	
	/**
	 * 删除来电秀
	 * @method POST
	 */
	public function del()
	{
		if ($this->get_method() != 'POST') {
            $this->send_response(405, null, '请求的方法不存在');
        }

        $post = $this->get_data(false);
        
		if (!isset($post['id']) ){
            $this->send_response(400, NULL, '请输入来电秀id');
        }

		if (!isset($post['imsi']) ){
            $this->send_response(400, NULL, '请输入imsi');
        }
        
		//判断用户是否有权限执行该操作
		if(!$this->bkuser_model->check_permission_by_imsi($post['imsi'],"callshow","write"))
		{
			 $this->send_response(403, null, '您没有权限');
		}
        
		$ret = $this->model->del((int)$post['id']);
        if($ret['result']==200)
        {
        	$this->send_response($ret['result'], $ret['msg'], NULL);
        }
        else 
        {
        	$this->send_response($ret['result'], NULL, $ret['msg']);
        }
	}
	
	/**
	 * 加精来电秀
	 * @method POST
	 */
	public function nice()
	{
		if ($this->get_method() != 'POST') {
            $this->send_response(405, null, '请求的方法不存在');
        }

        $post = $this->get_data(false);
        
		if (!isset($post['id']) ){
            $this->send_response(400, NULL, '请输入来电秀id');
        }

		if (!isset($post['imsi']) ){
            $this->send_response(400, NULL, '请输入imsi');
        }
        
		if (!isset($post['nice_coefficient']) ){
            $this->send_response(400, NULL, '请输入nice_coefficient');
        }
        
		//判断用户是否有权限执行该操作
		if(!$this->bkuser_model->check_permission_by_imsi($post['imsi'],"callshow","write"))
		{
			 $this->send_response(403, null, '您没有权限');
		}
        
		$ret = $this->model->nice((int)$post['id'],(int)$post['nice_coefficient']);
        if($ret['result']==200)
        {
        	$this->send_response($ret['result'], $ret['msg'], NULL);
        }
        else 
        {
        	$this->send_response($ret['result'], NULL, $ret['msg']);
        }
	}
	
	public function list_mass()
	{
		if ($this->get_method() != 'GET') {
            $this->send_response(405, null, '请求的方法不存在');
        }
        
        $start_timestamp = (int)$this->input->get("start_timestamp",0);
        $limit = (int)$this->input->get("limit",20);
        if($limit > 50 || $limit <=0)
        {
        	$limit = 20;
        }
        
        $this->send_response(200, Callshow_Model::instance()->list_mass($start_timestamp,$limit),NULL);
	}
}