<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * [MOMO API] (C)1999-2012 ND Inc.
 * 来电秀控制器文件
 */

class Csbk_Resource_Controller extends Controller {

	public function __construct()
	{
		parent::__construct();
		//模型
        $this->model   = new Csbk_Resource_Model;
        $this->bkuser_model = new Csbk_User_Model();
	}

	public function index()
	{
		$this->send_response(405, NULL, '请求的方法不存在');
	}

	/**
	 * 批量新增图片
	 * @method POST
	 */
	public function add_image()
	{	
		if ($this->get_method() != 'POST') {
            $this->send_response(405, null, '请求的方法不存在');
        }

		//判断用户是否有权限执行该操作
		if(!$this->bkuser_model->check_permission($this->user_id, api::get_client_ip(),"image","write"))
		{
			 $this->send_response(403, null, '您没有权限');
		}
		
        $post = $this->get_data();
		if (empty($post)){
            $this->send_response(400, NULL, '数据为空');
        }
        
        $this->send_response(200, $this->model->add_image($this->user_id,$post), NULL);
	}
	
	/**
	 * 批量修改图片
	 * @method POST
	 */
	public function modi_image()
	{
		if ($this->get_method() != 'POST') {
            $this->send_response(405, null, '请求的方法不存在');
        }

		//判断用户是否有权限执行该操作
		if(!$this->bkuser_model->check_permission($this->user_id, api::get_client_ip(),"image","write"))
		{
			 $this->send_response(403, null, '您没有权限');
		}
		
        $post = $this->get_data();
		if (empty($post)){
            $this->send_response(400, NULL, '数据为空');
        }
        
        $this->send_response(200, $this->model->modi_image($this->user_id,$post), NULL);			
	}
	
	/**
	 * 批量删除图片
	 * @method POST
	 */
	public function del_image()
	{
		if ($this->get_method() != 'POST') {
            $this->send_response(405, null, '请求的方法不存在');
        }

		//判断用户是否有权限执行该操作
		if(!$this->bkuser_model->check_permission($this->user_id, api::get_client_ip(),"image","write"))
		{
			 $this->send_response(403, null, '您没有权限');
		}
		
        $post = $this->get_data();
		if (empty($post) || empty($post['ids'])){
            $this->send_response(400, NULL, '数据为空');
        }
        
        $this->send_response(200, $this->model->del_image($this->user_id,$post['ids']), NULL);			
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
        
		//判断用户是否有权限执行该操作
		if(!$this->bkuser_model->check_permission($this->user_id, api::get_client_ip(),"image","read"))
		{
			 $this->send_response(403, null, '您没有权限');
		}

	    $order = $this->input->get("order");
        if($order && $order !== "hot" && $order !== "latest")
        {
        	$this->send_response(400, NULL, "排序规则错误");
        }
	    
        $key = $this->input->get("key");
        $tag = $this->input->get("tag");
        $notag = $this->input->get("notag");
        $gif = $this->input->get("gif",NULL);
        $nice = $this->input->get("nice",NULL);
        $approve_stat = $this->input->get("approve_stat",NULL);
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
		
		$this->send_response(200, $this->model->search_image($this->user_id, $key,$tag,$notag, $gif,$nice,$order, $pos, $size, $approve_stat), NULL);
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
		
        //判断用户是否有权限执行该操作
		if(!$this->bkuser_model->check_permission($this->user_id, api::get_client_ip(),"image","read"))
		{
			 $this->send_response(403, null, '您没有权限');
		}
		          
		$this->send_response(200, $this->model->tag_image(), NULL);
	}
	
	/**
	 * 删除图片标签
	 * @method POST
	 */
	public function del_tag_image()
	{
		if ($this->get_method() != 'POST') {
            $this->send_response(405, null, '请求的方法不存在');
        }

		//判断用户是否有权限执行该操作
		if(!$this->bkuser_model->check_permission($this->user_id, api::get_client_ip(),"image","write"))
		{
			 $this->send_response(403, null, '您没有权限');
		}
		
        $post = $this->get_data();
		if (empty($post)){
            $this->send_response(400, NULL, '数据为空');
        }
        
        if(!$post['tag'])
        {
        	$this->send_response(400, NULL, "tag为空");
        }
        
        $ret = $this->model->del_tag_image($post['tag'],$post['force']);
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
	 * 批量新增铃音
	 * @method POST
	 */
	public function add_ring()
	{
		if ($this->get_method() != 'POST') {
            $this->send_response(405, null, '请求的方法不存在');
        }

		//判断用户是否有权限执行该操作
		if(!$this->bkuser_model->check_permission($this->user_id, api::get_client_ip(),"ring","write"))
		{
			 $this->send_response(403, null, '您没有权限');
		}
		
        $post = $this->get_data();
		if (empty($post)){
            $this->send_response(400, NULL, '数据为空');
        }
        
        $this->send_response(200, $this->model->add_ring($this->user_id,$post), NULL);			
	}
	
	/**
	 * 批量修改铃音
	 * @method POST
	 */
	public function modi_ring()
	{
		if ($this->get_method() != 'POST') {
            $this->send_response(405, null, '请求的方法不存在');
        }

		//判断用户是否有权限执行该操作
		if(!$this->bkuser_model->check_permission($this->user_id, api::get_client_ip(),"ring","write"))
		{
			 $this->send_response(403, null, '您没有权限');
		}
		
        $post = $this->get_data();
		if (empty($post)){
            $this->send_response(400, NULL, '数据为空');
        }
        
        $this->send_response(200, $this->model->modi_ring($this->user_id,$post), NULL);					
	}
	
	/**
	 * 格式化铃声名字
	 * @method POST
	 */
	public function format_ring_name()
	{
		if ($this->get_method() != 'POST') {
            $this->send_response(405, null, '请求的方法不存在');
        }

		//判断用户是否有权限执行该操作
		if(!$this->bkuser_model->check_permission($this->user_id, api::get_client_ip(),"ring","write"))
		{
			 $this->send_response(403, null, '您没有权限');
		}

		$this->model->format_ring_name();
		$this->send_response(200,"success", NULL);	
	}
	
	/**
	 * 批量删除铃音
	 * @method POST
	 */
	public function del_ring()
	{
		if ($this->get_method() != 'POST') {
            $this->send_response(405, null, '请求的方法不存在');
        }

		//判断用户是否有权限执行该操作
		if(!$this->bkuser_model->check_permission($this->user_id, api::get_client_ip(),"ring","write"))
		{
			 $this->send_response(403, null, '您没有权限');
		}
		
        $post = $this->get_data();
		if (empty($post) || !$post['ids']){
            $this->send_response(400, NULL, '数据为空');
        }
        
        $this->send_response(200, $this->model->del_ring($this->user_id,$post['ids'],(int)$post['force']), NULL);					
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
        
		//判断用户是否有权限执行该操作
		if(!$this->bkuser_model->check_permission($this->user_id, api::get_client_ip(),"ring","read"))
		{
			 $this->send_response(403, null, '您没有权限');
		}

	    $order = $this->input->get("order");
        if($order && $order !== "hot" && $order !== "latest")
        {
        	$this->send_response(400, NULL, "排序规则错误");
        }
	    
        $key = $this->input->get("key");
        $tag = $this->input->get("tag");
        $notag = $this->input->get("notag");
        $topic_id = $this->input->get("topic_id");
        $singer = $this->input->get("singer");
        $nice = $this->input->get("nice",NULL);
        $approve_stat = $this->input->get("approve_stat",NULL);
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

		$this->send_response(200, $this->model->search_ring($this->user_id, $key,$tag,$notag,$topic_id, $singer,$nice,$order, $pos, $size, $approve_stat), NULL);	
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
		
        //判断用户是否有权限执行该操作
		if(!$this->bkuser_model->check_permission($this->user_id, api::get_client_ip(),"ring","read"))
		{
			 $this->send_response(403, null, '您没有权限');
		}
		        
		$this->send_response(200, $this->model->tag_ring(), NULL);
	}

	/**
	 * 修改铃声标签
	 * @method POST
	 */
	public function modi_tag_ring()
	{
		if ($this->get_method() != 'POST') {
            $this->send_response(405, null, '请求的方法不存在');
        }

		//判断用户是否有权限执行该操作
		if(!$this->bkuser_model->check_permission($this->user_id, api::get_client_ip(),"ring","write"))
		{
			 $this->send_response(403, null, '您没有权限');
		}
		
        $post = $this->get_data();
		if (empty($post)){
            $this->send_response(400, NULL, '数据为空');
        }
        
        if(!$post['tag'])
        {
        	$this->send_response(400, NULL, "tag为空");
        }
        
		if(!$post['new_tag'] && !isset($post['cover']))
        {
        	$this->send_response(400, NULL, "无可修改的参数");
        }
        
        $ret = $this->model->modi_tag_ring($post['tag'],$post['new_tag'],$post['cover'], $post['merge']);
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
	 * 删除铃声标签
	 * @method POST
	 */
	public function del_tag_ring()
	{
		if ($this->get_method() != 'POST') {
            $this->send_response(405, null, '请求的方法不存在');
        }

		//判断用户是否有权限执行该操作
		if(!$this->bkuser_model->check_permission($this->user_id, api::get_client_ip(),"ring","write"))
		{
			 $this->send_response(403, null, '您没有权限');
		}
		
        $post = $this->get_data();
		if (empty($post)){
            $this->send_response(400, NULL, '数据为空');
        }
        
        if(!$post['tag'])
        {
        	$this->send_response(400, NULL, "tag为空");
        }
        
        $ret = $this->model->del_tag_ring($post['tag'],$post['force']);
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
	 * 新增铃声专题
	 * @method POST
	 */
	public function add_ring_topic()
	{
		if ($this->get_method() != 'POST') {
            $this->send_response(405, null, '请求的方法不存在');
        }

		//判断用户是否有权限执行该操作
		if(!$this->bkuser_model->check_permission($this->user_id, api::get_client_ip(),"ring","write"))
		{
			 $this->send_response(403, null, '您没有权限');
		}
		
        $post = $this->get_data();
		if (empty($post)){
            $this->send_response(400, NULL, '数据为空');
        }
        
        if(empty($post['name']) || empty($post['cover']))
        {
        	$this->send_response(400, NULL, '参数错误');
        }

        $this->send_response(200, $this->model->add_ring_topic($post['name'],$post['cover'],$post['desc']?$post['desc']:"",$post['sequence']?(int)$post['sequence']:65535), NULL);
	}
	
	/**
	 * 修改铃声专题
	 * @method POST
	 */
	public function modi_ring_topic()
	{
		if ($this->get_method() != 'POST') {
            $this->send_response(405, null, '请求的方法不存在');
        }

		//判断用户是否有权限执行该操作
		if(!$this->bkuser_model->check_permission($this->user_id, api::get_client_ip(),"ring","write"))
		{
			 $this->send_response(403, null, '您没有权限');
		}
		
        $post = $this->get_data();
		if (empty($post) || empty($post['id'])){
            $this->send_response(400, NULL, '参数错误');
        }

		$ret = $this->model->modi_ring_topic((int)$post['id'],$post['name'],$post['cover'],$post['desc'],(int)$post['sequence']);
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
	 * 删除铃声专题
	 * @method POST
	 */
	public function del_ring_topic()
	{
		if ($this->get_method() != 'POST') {
            $this->send_response(405, null, '请求的方法不存在');
        }

		//判断用户是否有权限执行该操作
		if(!$this->bkuser_model->check_permission($this->user_id, api::get_client_ip(),"ring","write"))
		{
			 $this->send_response(403, null, '您没有权限');
		}
		
        $post = $this->get_data();
		if (empty($post) || empty($post['id'])){
            $this->send_response(400, NULL, '参数错误');
        }

		$ret = $this->model->del_ring_topic((int)$post['id'],$post['force']);
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
	 * 后台获取铃声专题
	 * @method GET
	 */
	public function list_ring_topic()
	{
		if ($this->get_method() != 'GET') {
            $this->send_response(405, null, '请求的方法不存在');
        }

		//判断用户是否有权限执行该操作
		if(!$this->bkuser_model->check_permission($this->user_id, api::get_client_ip(),"ring","read"))
		{
			 $this->send_response(403, null, '您没有权限');
		}

		$this->send_response(200,$this->model->list_ring_topic(), NULL);
	}
	
	/**
	 * 批量新增专题铃声
	 * @method POST
	 */
	public function add_topic_rings()
	{
		if ($this->get_method() != 'POST') {
            $this->send_response(405, null, '请求的方法不存在');
        }

		//判断用户是否有权限执行该操作
		if(!$this->bkuser_model->check_permission($this->user_id, api::get_client_ip(),"ring","write"))
		{
			 $this->send_response(403, null, '您没有权限');
		}
		
        $post = $this->get_data();
		if (empty($post) || empty($post['id']) || empty($post['ring_ids'])){
            $this->send_response(400, NULL, '参数错误');
        }

		$ret = $this->model->add_topic_rings((int)$post['id'],$post['ring_ids']);
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
	 * 批量删除专题铃声
	 * @method POST
	 */
	public function del_topic_rings()
	{
		if ($this->get_method() != 'POST') {
            $this->send_response(405, null, '请求的方法不存在');
        }

		//判断用户是否有权限执行该操作
		if(!$this->bkuser_model->check_permission($this->user_id, api::get_client_ip(),"ring","write"))
		{
			 $this->send_response(403, null, '您没有权限');
		}
		
		$post = $this->get_data();
		if (empty($post) || empty($post['id']) || empty($post['ring_ids'])){
            $this->send_response(400, NULL, '参数错误');
        }

		$ret = $this->model->del_topic_rings((int)$post['id'],$post['ring_ids']);
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
	 * 新增来电秀
	 * @method POST
	 */
	public function add_show()
	{
		if ($this->get_method() != 'POST') {
            $this->send_response(405, null, '请求的方法不存在');
        }

		//判断用户是否有权限执行该操作
		if(!$this->bkuser_model->check_permission($this->user_id, api::get_client_ip(),"show","write"))
		{
			 $this->send_response(403, null, '您没有权限');
		}
		
		$post = $this->get_data();
		if (empty($post)){
            $this->send_response(400, NULL, '数据为空');
        }
        
        if(!$post['name'] || !$post['image_id'] || !$post['ring_id'])
        {
        	$this->send_response(400, NULL, "参数错误");
        }
        
		$ret = $this->model->add_show($this->user_id,$post['name'],$post['tag'],$post['nice'],$post['image_id'],$post['ring_id'],$post['label'],$post['remark'],$post['approve']);
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
	 * 批量修改来电秀
	 * @method POST
	 */
	public function modi_show()
	{
		if ($this->get_method() != 'POST') {
            $this->send_response(405, null, '请求的方法不存在');
        }

		//判断用户是否有权限执行该操作
		if(!$this->bkuser_model->check_permission($this->user_id, api::get_client_ip(),"show","write"))
		{
			 $this->send_response(403, null, '您没有权限');
		}
		
        $post = $this->get_data();
		if (empty($post)){
            $this->send_response(400, NULL, '数据为空');
        }
        
        $this->send_response(200, $this->model->modi_show($this->user_id,$post), NULL);			
	}
	
	/**
	 * 批量删除来电秀
	 * @method POST
	 */
	public function del_show()
	{
		if ($this->get_method() != 'POST') {
            $this->send_response(405, null, '请求的方法不存在');
        }

		//判断用户是否有权限执行该操作
		if(!$this->bkuser_model->check_permission($this->user_id, api::get_client_ip(),"show","write"))
		{
			 $this->send_response(403, null, '您没有权限');
		}
		
        $post = $this->get_data();
		if (empty($post)){
            $this->send_response(400, NULL, '数据为空');
        }
        
        $this->send_response(200, $this->model->del_show($this->user_id,$post['ids']), NULL);			
	}
	
	/**
	 * 后台搜索来电秀
	 * @method GET 
	 */
	public function search_show()
	{
		if($this->get_method() != 'GET'){
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        
		//判断用户是否有权限执行该操作
		if(!$this->bkuser_model->check_permission($this->user_id, api::get_client_ip(),"show","read"))
		{
			 $this->send_response(403, null, '您没有权限');
		}

	    $order = $this->input->get("order");
        if($order && $order !== "hot" && $order !== "latest")
        {
        	$this->send_response(400, NULL, "排序规则错误");
        }
	    
        $key = $this->input->get("key");
        $tag = $this->input->get("tag");
        $notag = $this->input->get("notag");
        $nice = $this->input->get("nice",NULL);
        $approve_stat = $this->input->get("approve_stat",NULL);
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

		$this->send_response(200, $this->model->search_show($this->user_id, $key,$tag,$notag,$nice,$order, $pos, $size, $approve_stat), NULL);			
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
		
        //判断用户是否有权限执行该操作
		if(!$this->bkuser_model->check_permission($this->user_id, api::get_client_ip(),"show","read"))
		{
			 $this->send_response(403, null, '您没有权限');
		}
		          
		$this->send_response(200, $this->model->tag_show(), NULL);
	}
	
	/**
	 * 删除来电秀标签
	 * @method POST
	 */
	public function del_tag_show()
	{
		if ($this->get_method() != 'POST') {
            $this->send_response(405, null, '请求的方法不存在');
        }

		//判断用户是否有权限执行该操作
		if(!$this->bkuser_model->check_permission($this->user_id, api::get_client_ip(),"show","write"))
		{
			 $this->send_response(403, null, '您没有权限');
		}
		
        $post = $this->get_data();
		if (empty($post)){
            $this->send_response(400, NULL, '数据为空');
        }
        
        if(!$post['tag'])
        {
        	$this->send_response(400, NULL, "tag为空");
        }
        
        $ret = $this->model->del_tag_show($post['tag'],$post['force']);
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