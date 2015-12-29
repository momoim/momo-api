<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * [MOMO API] (C)1999-2012 ND Inc.
 * 来电秀控制器文件
 */

class Csbk_Feedback_Controller extends Controller {

	public function __construct()
	{
		parent::__construct();
		//模型
        $this->model   = new Csbk_Feedback_Model;
        $this->bkuser_model = new Csbk_User_Model();
	}

	public function index()
	{
		$this->send_response(405, NULL, '请求的方法不存在');
	}

	public function search()
	{
		if($this->get_method() != 'GET'){
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        
		//判断用户是否有权限执行该操作
		if(!$this->bkuser_model->check_permission($this->user_id, api::get_client_ip(),"feedback","read"))
		{
			 $this->send_response(403, null, '您没有权限');
		}
		
		$state = $this->input->get("state");
		$pos = (int)$this->input->get("pos",0);
		$size = (int)$this->input->get("size",20);
		if($size > 50)
		{
			$size = 50;
		}
		else if($size <= 0)
		{
			$size = 20;
		}
		
		$this->send_response(200, $this->model->search($state,$pos,$size), NULL);	
	}
	
	public function send_sms()
	{
		if($this->get_method() != 'POST'){
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        
		//判断用户是否有权限执行该操作
		if(!$this->bkuser_model->check_permission($this->user_id, api::get_client_ip(),"feedback","write"))
		{
			 $this->send_response(403, null, '您没有权限');
		}

		$post = $this->get_data();
		if (empty($post) || empty($post['id']) || empty($post['sms'])){
            $this->send_response(400, NULL, '数据为空');
        }
        $ret = $this->model->send_sms($this->user_id,$post['id'], $post['sms'], $post['mobile']);
        $this->send_response($ret['result'],$ret['msg'] , NULL);
	}
	
	public function resend_sms()
	{
		if($this->get_method() != 'GET'){
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        
		//判断用户是否有权限执行该操作
		if(!$this->bkuser_model->check_permission($this->user_id, api::get_client_ip(),"feedback","write"))
		{
			 $this->send_response(403, null, '您没有权限');
		}

		$start = (int)$this->input->get("start",0);
		$end = (int)$this->input->get("end",0);
		
		$ret = $this->model->resend_sms($start, $end);
        $this->send_response($ret['result'],$ret['msg'] , NULL);
	}
}