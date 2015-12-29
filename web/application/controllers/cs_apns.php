<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * [MOMO API] (C)1999-2012 ND Inc.
 * 来电秀控制器文件
 */

class Cs_Apns_Controller extends Controller {

	public function __construct()
	{
		parent::__construct();
		//模型
        $this->model   = new Cs_Apns_Model;
	}

	public function index()
	{
		$this->send_response(405, NULL, '请求的方法不存在');
	}

	/**
	 * 订阅通知
	 * @method POST
	 */
	public function subscribe()
	{
		if ($this->get_method() != 'POST') {
            $this->send_response(405, null, '请求的方法不存在');
        }

        $post = $this->get_data();
	    if (!$post['device_token']){
            $this->send_response(400, NULL, 'device_token错误');
        }
		
		if (!$this->user_id && !isset($post['guid'])){
            $this->send_response(400, NULL, 'guid错误');
        }
        
		$ret = $this->model->subscribe($this->user_id, $post['guid'],$post['device_token']);
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
	 * 取消订阅
	 * @method POST
	 */
	public function unsubscribe()
	{
		if ($this->get_method() != 'POST') {
            $this->send_response(405, null, '请求的方法不存在');
        }

        $post = $this->get_data();
		
		if (!$this->user_id && !isset($post['guid'])){
            $this->send_response(400, NULL, 'guid错误');
        }
        
		$this->model->unsubscribe($this->user_id, $post['guid'],$post['device_token']);
        $this->send_response(200, NULL, NULL);		
	}
	
}