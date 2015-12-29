<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * [MOMO API] (C)1999-2012 ND Inc.
 * 来电秀控制器文件
 */

class Callshow_Controller extends Controller {

	public function __construct()
	{
		parent::__construct();
		//模型
        $this->model   = new Callshow_Model;
	}

	public function index()
	{
		$this->send_response(405, NULL, '请求的方法不存在');
	}

	/**
	 * 设置来电秀
	 * @method POST
	 */
	public function create()
	{
		if ($this->get_method() != 'POST') {
            $this->send_response(405, null, '请求的方法不存在');
        }

        $post = $this->get_data(false);
		
	if (!$post['ring'] || !$post['ring']['name'] || !$post['ring']['url'] ){
            $this->send_response(400, NULL, '铃声信息不全');
        }
		
        $image = NULL;
        if ($post['image'] && $post['image']['url'])
        {
        	$image = $post['image'];
        }
        
        $video = NULL;
		if ($post['video'] && $post['video']['url'] && $post['video']['snapshot']  && $post['video']['snapshot']['url'])
		{
            $video = $post['video'];
        }
        
        if(!$image && !$video)
        {
        	$this->send_response(400, NULL, '请提供图片或视频信息');
        }
        
        $ret = $this->model->create($this->user_id, $this->source, $post['ring'],$image,$video,$post['label'],$post['contact'],$post['refid'],$post['forwarded'],$post['access_ctrl']);
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
	 * 编辑来电秀
	 * @method POST
	 */
	public function modi()
	{
		if ($this->get_method() != 'POST') {
            $this->send_response(405, null, '请求的方法不存在');
        }

        $post = $this->get_data(false);

        if (!isset($post['id']))
        {
        	$this->send_response(400, NULL, '请输入来电秀id');
        }
        
		$ret = $this->model->modi($this->user_id, $this->source,(int)$post['id'], $post['ring'],$post['image'],$post['video'],$post['label'],$post['refid'],$post['access_ctrl'],$post['be_cur_show'],$post['action_in_use']);
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

		$ret = $this->model->del($this->user_id, (int)$post['id']);
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
	 * 拉取当前来电秀
	 * @method GET
	 */
	public function latest()
	{
	    if($this->get_method() != 'GET'){
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        
        $phone = $this->input->get("phone");
        $guid = $this->input->get("guid");
        $since = (int)$this->input->get("since",0);
        if(!$this->user_id && !$phone && !$guid)
        {
        	$this->send_response(400, NULL, '身份信息缺失');
        }
        
        $this->send_response(200, $this->model->latest($this->user_id, $phone,$guid, $since), NULL);
	}
	
	/**
	 * 拉取来电秀历史
	 * 
	 */
	public function history()
	{
	    if($this->get_method() != 'GET'){
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        
        $scope = $this->input->get("scope");
        $guid = $this->input->get("guid");
		if($scope == "relation" && !$this->user_id && !$guid )
        {
        	$this->send_response(400, NULL, '身份信息缺失,无法定位关系网');
        }
        
        $phone = $this->input->get("phone");
        $duid = (int)$this->input->get("uid");
		if($scope == "user" && !$this->user_id && !$phone && !$duid)
        {
        	$this->send_response(400, NULL, '无法定位用户身份');
        }
        
        $start_timestamp = (int)$this->input->get("start_timestamp",time());//若服务器时钟混乱，此处的逻辑可能会失败
        $end_timestamp = (int)$this->input->get("end_timestamp",0);
        $nice = $this->input->get("nice");
        $forwarded = $this->input->get("forwarded");
        $limit = (int)$this->input->get("limit",20);
        if($limit > 50 || $limit <=0)
        {
        	$limit = 20;
        }
        
	    switch($scope)
        {
        	case "global":
        		$this->send_response(200, $this->model->history_all($this->user_id,$nice, $guid, $start_timestamp, $end_timestamp, $limit), NULL);
        		//$this->send_response(200, $this->model->history_global($this->user_id,$nice,$forwarded, $start_timestamp, $end_timestamp, $limit), NULL);
        		break;
        	case "all":
        		$this->send_response(200, $this->model->history_all($this->user_id,$nice, $guid, $start_timestamp, $end_timestamp, $limit), NULL);
        		break;
        	case "relation":
        		$this->send_response(200, $this->model->history_relation($this->user_id,$nice, $guid, $start_timestamp, $end_timestamp, $limit), NULL);
        		break;
        	case "user":
        		$this->send_response(200, $this->model->history_user($this->user_id,$nice, $phone,$duid, $start_timestamp, $end_timestamp, $limit), NULL);
        		break;
        	default:
        		$this->send_response(400, NULL, "非法scope");
        		break;
        }
	}
	
	/**
	 * 拉取热门来电秀
	 */
	public function hot()
	{
		if($this->get_method() != 'GET'){
            $this->send_response(405, NULL, '请求的方法不存在');
        }
		
        $min_score = (int)$this->input->get("min_score",0);
        $max_score = (int)$this->input->get("max_score",PHP_INT_MAX); //此处暂时以PHP_INT_MAX作为score的最大值，要注意保证数据库中的score值不得超过该数，当然，一般是不会超过的
		$limit = (int)$this->input->get("limit",20);
		if($limit > 50)
		{
			$limit = 50;
		}
		else if($limit <= 0)
		{
			$limit = 20;
		}
        
        $this->send_response(200, $this->model->hot($this->user_id,$min_score, $max_score, $limit), NULL);
	}
	
	/**
	 * 发现来电秀
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
        
        $include_latest = (int)$this->input->get("include_latest",0);
		$limit = (int)$this->input->get("limit",1);
		if($limit > 50)
		{
			$limit = 50;
		}
		else if($limit <= 0)
		{
			$limit = 1;
		}
		
        $this->send_response(200, $this->model->surprise($this->user_id, $phone, $include_latest, $limit), NULL);
	}
	
	/**
	 * 获取来电秀详情
	 * @method GET
	 */
	public function detail()
	{
		if($this->get_method() != 'GET'){
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        
        $code = $this->input->get("code");
        if(!$code)
        {
        	$this->send_response(400, NULL, '参数错误,无法定位来电秀');
        }

		$ret = $this->model->detail($code);
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
	 * 送礼物
	 * @method POST
	 */
	public function give_gift()
	{
		if ($this->get_method() != 'POST') {
            $this->send_response(405, null, '请求的方法不存在');
        }

        $post = $this->get_data(false);
		if (!isset($post['id'])){
            $this->send_response(400, NULL, '来电秀id为空');
        }
        
	    if (!isset($post['gift']) ){
            $this->send_response(400, NULL, '礼物信息缺失');
        }
        
	    $ret = $this->model->give_gift($this->user_id, $post['id'],$post['gift']);
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
	 * 新事件计数
	 * @method GET
	 */
	public function new_events_count()
	{
		if($this->get_method() != 'GET'){
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        
		$guid = $this->input->get("guid");
        if(!$this->user_id && !$guid)
        {
        	$this->send_response(400, NULL, '无法定位用户身份');
        }
        
        $this->send_response(200, $this->model->new_events_count($this->user_id, $guid), NULL);
	}
	
	/**
	 * 新事件内容
	 * @method GET
	 */
	public function events()
	{
		if($this->get_method() != 'GET'){
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        
		$guid = $this->input->get("guid");
        if(!$this->user_id && !$guid)
        {
        	$this->send_response(400, NULL, '无法定位用户身份');
        }
        
        $type = $this->input->get("type");
        $new = $this->input->get("new", NULL);
		if(isset($new))
		{
			$new = (int)$new;
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
		
		$readed = (int)$this->input->get("readed",0);

		$this->send_response(200, $this->model->events($this->user_id, $guid, $type, $new, $pos, $size, $readed), NULL);
	}
	
	/**
	 * 分享来电秀
	 * 
	 */
	public function share(){
	    if($this->get_method() != 'POST') {
	        $this->send_response(405, NULL, '请求的方法不存在');
	    }
	    
	    $post = $this->get_data(false);
	    
	    if (empty($post["id"]) ||
	    		(empty($post['sites']) && empty($post['sms']) && empty($post['timeline']))) {
	        $this->send_response(400, NULL, "信息缺失，无法分享来电秀");
	    }
	    
	    $ret = $this->model->share($this->user_id, $post['id'],$post['sites'], $post['sms'], $post['timeline']);
        if($ret['result']==200)
        {
        	$this->send_response($ret['result'], $ret['msg'], NULL);
        }
        else 
        {
        	$this->send_response($ret['result'], NULL, $ret['msg']);
        } 
	}
	
	public function rebuild_mass_show()
	{
		if($this->get_method() != 'POST') {
	        $this->send_response(405, NULL, '请求的方法不存在');
	    }
	    
	    if($this->user_id != 285)
	    {
	    	 $this->send_response(405, NULL, '无权限');
	    }
	    
	    $this->model->rebuild_mass_show();
	    $this->send_response(200, "success", NULL);
	}
}