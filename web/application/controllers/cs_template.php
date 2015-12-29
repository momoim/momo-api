<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * [MOMO API] (C)1999-2012 ND Inc.
 * 来电秀控制器文件
 */

class Cs_Template_Controller extends Controller {

	public function __construct()
	{
		parent::__construct();
		//模型
        $this->model   = new Cs_Template_Model;
	}

	public function index()
	{
		$this->send_response(405, NULL, '请求的方法不存在');
	}
	
	/**
	 * 获取模板秀标签列表
	 * @method GET
	 */
	public function tag()
	{
		if($this->get_method() != 'GET'){
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        
		$this->send_response(200, $this->model->tag(), NULL);
	}
	
	/**
	 * 搜索模板秀
	 * @method GET
	 */
	public function search()
	{
		if($this->get_method() != 'GET'){
            $this->send_response(405, NULL, '请求的方法不存在');
        }

	    $order = $this->input->get("order");
        if($order && $order !== "hot" && $order !== "latest")
        {
        	$this->send_response(400, NULL, "排序规则错误");
        }
	    
        $tag = $this->input->get("tag");
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

		$this->send_response(200, $this->model->search($tag, $order, $pos, $size), NULL);	
	}
	
	/**
	 * 获取模板秀详情
	 * @method GET
	 */
	public function detail()
	{
		if($this->get_method() != 'GET'){
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        
		$id = (int)$this->input->get("id",0);
        if(!$id)
        {
        	$this->send_response(400, NULL, "请指定模板秀id");
        }
        
		$ret = $this->model->detail($id);
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
	 * 推荐模板秀
	 * @method GET
	 */
	public function surprise()
	{
		if($this->get_method() != 'GET'){
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        
        $phone = $this->input->get("phone");
        if(!$this->user_id && !$phone)
        {
        	$this->send_response(400, NULL, '参数错误,无法定位用户身份');
        }
        
        $contact_name = $this->input->get("contact_name");
		
		$ret = $this->model->surprise($this->user_id, $phone,$contact_name);
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