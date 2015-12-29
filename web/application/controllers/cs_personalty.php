<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * [MOMO API] (C)1999-2012 ND Inc.
 * 来电秀控制器文件
 */

class Cs_Personalty_Controller extends Controller {

	public function __construct()
	{
		parent::__construct();
		//模型
        $this->model   = new Cs_Personalty_Model;
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
		
		$this->send_response(200, $this->model->search_image($this->user_id, $pos, $size), NULL);
	}
	
	/**
	 * 搜索视频
	 * @method:GET
	 */
	public function search_video()
	{
		if($this->get_method() != 'GET'){
            $this->send_response(405, NULL, '请求的方法不存在');
        }

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
		
		$this->send_response(200, $this->model->search_video($this->user_id, $pos, $size), NULL);
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

		$this->send_response(200, $this->model->search_ring($this->user_id, $pos, $size), NULL);		
	}
	
	/**
	 * 修改铃音
	 * @method POST
	 */
	public function modi_ring()
	{
		if($this->get_method() != 'POST'){
            $this->send_response(405, NULL, '请求的方法不存在');
        }

		$post = $this->get_data();
		if (empty($post) || empty($post['rid'])){
            $this->send_response(400, NULL, '数据为空');
        }
        
        if(!$post['name'] && !$post['mime'] && !$post['duration'] && !$post['url'])
        {
        	$this->send_response(400, NULL, "无修改项");
        }

		$ret = $this->model->modi_ring($this->user_id, $post['rid'], $post['name'],$post['mime'],$post['duration'], $post['url']);
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
	 * 全部铃音
	 * @method GET
	 */
	public function all_ring()
	{
		if($this->get_method() != 'GET'){
            $this->send_response(405, NULL, '请求的方法不存在');
        }

		$timestamp = (int)$this->input->get("timestamp",time());
		$size = (int)$this->input->get("size",20);
		if($size > 50)
		{
			$size = 50;
		}
		else if($size <= 0)
		{
			$size = 20;
		}

		$this->send_response(200, $this->model->all_ring($timestamp, $size), NULL);		
	}
	
	/**
	 * 从历史来电秀中重新生成用户资源库,非公开接口
	 * @method GET
	 */
	public function rebuild()
	{
		if($this->get_method() != 'GET'){
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        
        $pos = (int)$this->input->get("pos",0);
		$size = (int)$this->input->get("size",1000);
		
        $this->send_response(200, $this->model->rebuild($pos, $size), NULL);			
	}
}