<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * [MOMO API] (C)1999-2012 ND Inc.
 * 来电秀控制器文件
 */

class Cs_Resource_Controller extends Controller {

	public function __construct()
	{
		parent::__construct();
		//模型
        $this->model   = new Cs_Resource_Model;
	}

	public function index()
	{
		$this->send_response(405, NULL, '请求的方法不存在');
	}
	
	/**
	 * 搜索图片
	 * @method:GET
	 */
	public function search_image()
	{
		if($this->get_method() != 'GET'){
            $this->send_response(405, NULL, '请求的方法不存在');
        }

	    $order = $this->input->get("order");
        if($order && $order !== "hot" && $order !== "latest")
        {
        	$this->send_response(400, NULL, "排序规则错误");
        }
	    
        $key = $this->input->get("key");
        $tag = $this->input->get("tag");
        $nice = $this->input->get("nice",NULL);
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
		
		$this->send_response(200, $this->model->search_image($key,$tag, $nice,$order, $pos, $size), NULL);
	}
	
	/**
	 * 搜索铃音
	 * @method GET
	 */
	public function search_ring()
	{
		if($this->get_method() != 'GET'){
            $this->send_response(405, NULL, '请求的方法不存在');
        }

	    $order = $this->input->get("order");
        if($order && $order !== "hot" && $order !== "latest")
        {
        	$this->send_response(400, NULL, "排序规则错误");
        }
	    
        $key = $this->input->get("key");
        $tag = $this->input->get("tag");
        $topic_id = $this->input->get("topic_id");
        $singer = $this->input->get("singer");
        $nice = $this->input->get("nice",NULL);
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

		$this->send_response(200, $this->model->search_ring($key,$tag,$topic_id, $singer, $nice,$order, $pos, $size), NULL);		
	}

	/**
	 * 搜索来电秀
	 * @method GET
	 */
	public function search_show()
	{
		if($this->get_method() != 'GET'){
            $this->send_response(405, NULL, '请求的方法不存在');
        }

	    $order = $this->input->get("order");
        if($order && $order !== "hot" && $order !== "latest")
        {
        	$this->send_response(400, NULL, "排序规则错误");
        }
	    
        $key = $this->input->get("key");
        $tag = $this->input->get("tag");
        $nice = $this->input->get("nice",NULL);
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

		$this->send_response(200, $this->model->search_show($key,$tag, $nice,$order, $pos, $size), NULL);	
	}
	
	/**
	 * 获取图片标签列表
	 * @method GET
	 */
	public function tag_image()
	{
		if($this->get_method() != 'GET'){
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        
		$this->send_response(200, $this->model->tag_image(), NULL);
	}

	/**
	 * 获取铃声标签列表
	 * @method GET
	 */
	public function tag_ring()
	{
		if($this->get_method() != 'GET'){
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        
		$this->send_response(200, $this->model->tag_ring(), NULL);
	}

	/**
	 * 获取来电秀标签列表
	 * @method GET
	 */
	public function tag_show()
	{
		if($this->get_method() != 'GET'){
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        
		$this->send_response(200, $this->model->tag_show(), NULL);
	}
	
	/**
	 * 获取铃声专题列表
	 * @method GET
	 */
	public function topic_ring()
	{
		if($this->get_method() != 'GET'){
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        
		$this->send_response(200, $this->model->topic_ring($this->user_id), NULL);
	}
}