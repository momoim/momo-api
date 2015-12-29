<?php defined('SYSPATH') or die('No direct access allowed.');
/**
* [MOMO API] (C)1999-2011 ND Inc.
* 二手交易控制器文件
*/

class Deal_Controller extends Controller {

	/**
	 * 二手交易模型
	 * @var Contact_Model
	 */
	protected $model;
	
    public function __construct()
    {
        parent::__construct();
        $this->model = Deal_Model::instance();
    }

    public function index()
    {
        $this->send_response(405, NULL, '请求的方法不存在');
    }
    
    /**
     * 
     * 创建二手交易
     */
    public function create() {
        if ($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        } 
    	$data = $this->get_data();
    	$title = $data['title']?trim($data['title']):'';
    	$description = $data['description']?trim($data['description']):'';
    	$price = $data['price']?trim($data['price']):'';
    	$sync = $data['sync']?$data['sync']:array();
    	$private = $data['private']?(int)$data['private']:0;
    	$image = $data['image']?$data['image']:array();
    	$location = $data['location']?$data['location']:array();
    	$str_len = str::strLen($description);
    	if(empty($title))
    		$this->send_response(400, NULL,'401301:标题为空');
    	if(empty($description))
    		$this->send_response(400, NULL,'401302:描述为空');
    	if(!empty($image) && !is_array($image))
            $this->send_response(400, NULL,'401303:图片参数不正确');
    	if(!empty($location) && (!$location['longitude'] || !$location['latitude']))
            $this->send_response(400, NULL,'401304:地理位置参数不正确');
    	if ($str_len>500)
            $this->send_response(400, NULL,'401305:字数超出500字');
        $synced = $this->_sync_weibo($sync,$this->user_id,$site,$title,$description,$image,$location);
    	if($this->model->create($title,$description,$price,$private,$image,$location,$this->user_id,$synced)) {
    		$this->send_response(200,array('sync'=>$synced));
    	}
    		
    	$this->send_response(400, NULL,'401306:创建二手交易失败');
    }

    /**
     * 
     * @return array
     */
    public function item($id=NULL) {
        if ($this->get_method() != 'GET') {
            $this->send_response(405, NULL, '请求的方法不存在');
        } 
        if(empty($id)) {
        	$this->send_response(400, NULL, '401307:交易ID为空');
        }
		$deal_id = (int)$id;
		$item = $this->model->item($deal_id,$this->user_id);
		$this->send_response(200,$item);
    }
    
    /**
     * 
     * @return array
     */
    public function private_lists() {
        if ($this->get_method() != 'GET') {
            $this->send_response(405, NULL, '请求的方法不存在');
        } 
		$pagesize = (int)($this->input->get('pagesize', 20));
		$uptime = (int)($this->input->get('uptime', 0));
		$downtime = (int)($this->input->get('downtime', 0));
		$trustworthiness = (int)($this->input->get('trustworthiness', ''));
		$weight = (int)($this->input->get('weight', 0));
		
    	$lists = $this->model->private_lists($uptime,$downtime,$trustworthiness,$pagesize,$this->user_id,$weight);
    	$this->send_response(200,$lists);
    }
    
    /**
     * 
     * @return array
     */
    public function public_lists() {
        if ($this->get_method() != 'GET') {
            $this->send_response(405, NULL, '请求的方法不存在');
        } 
		$pagesize = (int)($this->input->get('pagesize', 20));
		$uptime = (int)($this->input->get('uptime', 0));
		$downtime = (int)($this->input->get('downtime', 0));
		$longitude = (float)($this->input->get('longitude', 0));
		$latitude = (float)($this->input->get('latitude', 0));
		$scope = (float)($this->input->get('scope', 0));
		$uid = (int)($this->input->get('uid', 0));
		$private = (int)($this->input->get('private', 0));
		$weight = $this->user_id?(int)($this->input->get('weight', 0)):0;
		$trustworthiness = (int)($this->input->get('trustworthiness', ''));
    	$lists = $this->model->public_lists($uptime,$downtime,$longitude,$latitude,$scope,$private,$trustworthiness,$pagesize,$uid,$weight,$this->user_id);
    	$this->send_response(200,$lists);
    }
    
    /**
     * 
     * @return none
     */
    public function update($id=NULL) {
        if ($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        } 
    	$data = $this->get_data();
        if(empty($id)) {
        	$this->send_response(400, NULL, '401307:交易ID为空');
        }
		$id = (int)$id;
		$title = isset($data['title'])?trim($data['title']):'';
		$price = $data['price']?(float)$data['price']:0;
    	$description = isset($data['description'])?trim($data['description']):'';
		$status = $data['status']?(int)$data['status']:0;
		$image = $data['image']?$data['image']:array();
    	if(isset($data['title']) && empty($title))
    		$this->send_response(400, NULL,'401301:标题为空');
    	if(isset($data['description']) && empty($description))
    		$this->send_response(400, NULL,'401302:描述为空');
    	if(!empty($image) && !is_array($image))
            $this->send_response(400, NULL,'401308:图片参数不正确');
        $deal = $this->model->get($id);
        if(!$deal)
        	$this->send_response(400, NULL,'401309:交易ID非法');
        if($deal['uid']!=$this->user_id)
        	$this->send_response(400, NULL,'401312:无权限修改');
    	$letters = array('title'=>$title,'description'=>$description,'status'=>$status,'image'=>$image,'price'=>$price);
		if($this->model->update($id,$this->user_id,$deal['deal_id'],$letters)) 
			$this->send_response(200);
		$this->send_response(400, NULL,'401310:交易更新失败');	
    }
    
    /**
     * 
     * @return none
     */
    public function done($id=NULL) {
        if ($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        } 
    	$data = $this->get_data();
        if(empty($id)) {
        	$this->send_response(400, NULL, '401307:交易ID为空');
        }
		$id = (int)$id;
		$remark = $data['remark']?trim($data['remark']):'';
		$status = $data['status']==1?1:2;
        $deal = $this->model->get($id);
        if(!$deal)
        	$this->send_response(400, NULL,'401309:交易ID非法');
        $letters = array('status'=>$status,'remark'=>$remark);
		if($this->model->update($id,$this->user_id,'',$letters)) 
			$this->send_response(200);
		$this->send_response(400, NULL,'401310:交易更新失败');	
    }
    
    /**
     * 
     * @return none
     */
    public function personal($id=NULL) {
        if ($this->get_method() != 'GET') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
    	$data = $this->get_data();
        if(empty($id)) {
        	$this->send_response(400, NULL, '401307:交易ID为空');
        }
		$id = (int)$id;
		$user_info = sns::getuser($id);
		if(!$user_info)
			$this->send_response(400, NULL,'401311:用户不存在');
		$stat = $this->model->stat($id); 
		$deal_lists = $this->model->personal_lists($id,$this->user_id);
		$this->send_response(200,array('id'=>(int)$id,'name'=>$user_info['realname'],'avatar'=>sns::getAvatar($id),'deal_stat'=>array('total'=>(int)$stat['total'],'success'=>(int)$stat['success']),'deal_lists'=>$deal_lists,'relationship'=>0,'social_account'=>array('sina_weibo'=>'','qq_weibo'=>'')));
    }
    
    /**
     * 
     * @param $uid
     * @param $site
     * @param $content
     * @param $image
     * @return unknown_type
     */
    private function _sync_weibo($sync,$uid,$site,$title,$description,$image,$location) {
    	$res = array();
    	if(is_array($sync) && count($sync) > 0 && $title && $description && $image) {
    		if($sync['weibo']) {
	    		require_once Kohana::find_file('vendor', 'weibo/saetv2.ex.class');
		        $oauth = Kohana::config('uap.oauth');
		        $token = Bind_Model::instance()->oauth2_check($uid,'weibo');
		        $updated_time = $token['updated']?$token['updated']:$token['created'];
		        if(($updated_time+$token['expires_in']) > time() ) {
			        $c = new SaeTClientV2( $oauth['weibo.com']['WB_AKEY'] ,$oauth['weibo.com']['WB_SKEY'] , $token['access_token']);
			        if($c) {
			        	$img = $this->model->_warp_image($image);
			        	$img_url = $img[0]['url']?$img[0]['url']:'';
			        	$content = '#'.$title.'#'.$description;
			        	$content = str::strLen($content)>120?str::cnSubstr($content,0,120).'..':$content;
			        	//$img_url = 'http://momoimg.com/photo/3846870_LrurOnCRM365Gc_cI0ferPZaqFP2hLDtdsB2R1WtHFsrGiLDQ647LfN09AM_780.jpg';
			        	$latitude = $location['latitude']?$location['latitude']:NULL;
			        	$longitude = $location['longitude']?$location['longitude']:NULL;
			        	if($img_url)
			        		$result = $c->upload($content,$img_url,$latitude,$longitude);
			        	else
			        		$result = $c->update($content,$latitude,$longitude);
			        	if($result['id'])
			        		$res = array('weibo'=>1);
			        	else
			        		$res = array('weibo'=>0,'error'=>$result['error'],'type'=>'error');
			        }
		        } else {
		        	$res = array('weibo'=>0,'error'=>'access_token expired!','type'=>'expire');
		        }
    		}
    	}
    	return $res;
    }
}