<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * [MOMO API] (C)1999-2012 ND Inc.
 * 来电秀控制器文件
 */

class Cs_Feedback_Controller extends Controller {

	public function __construct()
	{
		parent::__construct();
		//模型
        $this->model   = new Cs_Feedback_Model;
	}

	public function index()
	{
		$this->send_response(405, NULL, '请求的方法不存在');
	}

	public function create()
	{
		if ($this->get_method() != 'POST') {
            $this->send_response(405, null, '请求的方法不存在');
        }
        
        $post = $this->get_data();
		if (!isset($post['content'])){
            $this->send_response(400, NULL, 'content为空');
        }
        
        if(!$this->user_id)
        {
	        $token = $this->input->get("token");
			if($token)
	        {
		        	$user_model = User_Model::instance();
	    			$uinfo = $user_model->get_token_info($token);
	    			if($uinfo && $uinfo['ost_usa_id_ref'])
		    		{
		    			$this->user_id = (int)$uinfo['ost_usa_id_ref'];
		    			$this->source = (int)$uinfo['ost_client_id'];
		    			$this->phone_os = $uinfo['ost_phone_os'];
		    			$this->phone_model = $uinfo['ost_phone_model'];
		    		}  	
	        }
        }
        $guid = $this->input->get("guid");
		
        if(!$this->user_id && !$guid)
        {
        	$this->send_response(400, NULL, '参数错误');
        }
        
		$client_id = $post['client_id']?$post['client_id']:($this->source?$this->source:0);
		$phone_os = $post['phone_os']?$post['phone_os']:($this->phone_os?$this->phone_os:"");
		$phone_model = $post['phone_model']?$post['phone_model']:($this->phone_model?$this->phone_model:"");

        
		$ret = $this->model->create($this->user_id, $client_id,$phone_os,$phone_model, $guid,$post['content'],$post['version']);
        if($ret['result']==200)
        {
        	$this->send_response($ret['result'], $ret['msg'], NULL);
        }
        else 
        {
        	$this->send_response($ret['result'], NULL, $ret['msg']);
        }
	}
	
	public function getlist()
	{
		if ($this->get_method() != 'GET') {
            $this->send_response(405, null, '请求的方法不存在');
        }
        
        $token = $this->input->get("token");
        $guid = $this->input->get("guid");     
		if(!$this->user_id && $token)
        {
    		    $user_model = User_Model::instance();
	    		$uinfo = $user_model->get_token_info($token);
	    		if($uinfo && $uinfo['ost_usa_id_ref'])
	    		{
	    			$this->user_id = $uinfo['ost_usa_id_ref'];
	    		}
        }
		
        if(!$this->user_id && !$guid)
        {
        	$this->send_response(400, NULL, '参数错误');
        }
		
		$ret = $this->model->getlist($this->user_id, $guid);
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