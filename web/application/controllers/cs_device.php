<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * [MOMO API] (C)1999-2012 ND Inc.
 * 来电秀控制器文件
 */

class Cs_Device_Controller extends Controller {

	public function __construct()
	{
		parent::__construct();
		//模型
        $this->model   = new Cs_Device_Model;
	}

	public function index()
	{
		$this->send_response(405, NULL, '请求的方法不存在');
	}

	/**
	 * 绑定设备
	 * @method POST
	 */
	public function bind()
	{
		if ($this->get_method() != 'POST') {
            $this->send_response(405, null, '请求的方法不存在');
        }

        $post = $this->get_data(false);
        if(!isset($this->source) && !isset($post['client_id']))
        {
        	$this->send_response(400, null, 'client_id 不存在');
        }
        $client_id = $this->source?$this->source:$post['client_id']?(int)$post['client_id']:0;
        $phone_model = $this->phone_model?$this->phone_model:$post['phone_model']?$post['phone_model']:"";
        $os = $this->os?$this->os:$post['os']?$post['os']:"";
		
		$ret = $this->model->bind($this->user_id, $client_id, $phone_model, $os, $post['mac']?$post['mac']:"", $post['imei']?$post['imei']:"", $post['imsi']?$post['imsi']:"",$post['guid']?$post['guid']:"",$post['mobile']?$post['mobile']:"",$post['sign']?$post['sign']:"");
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