<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * [MOMO API] (C)1999-2011 ND Inc.
 * 短信短地址控制器文件
 */
class Url_Controller extends Controller {


    public function __construct()
    {
        parent::__construct();
        $this->model = Url_Model::instance();
    }
    
    /**
     * 获取短地址
     * @method GET
     */
    public function short()
    {
        if($this->get_method() == 'GET') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        $post = $this->get_data();
        if(isset($post['password']) && $post['password']=='best!author99') {
        	$type = $post['type'];
        	$appid = $post['appid'];
        	$sender_uid = $post['sender_uid'];
        	$sender_name = $post['sender_name'];
        	$receiver_uid = $post['receiver_uid'];
        	$receiver_name = $post['receiver_name'];
        	$msgid = $post['msgid'];
        	$content_type = $post['content_type'];
        	$receiver_zone_code = $post['receiver_zone_code']?$post['receiver_zone_code']:'86';
        	$receiver_mobile = $post['receiver_mobile'];
        	$status_id = $post['status_id']?$post['status_id']:'';
        	if($type && $sender_uid && $receiver_uid && $receiver_mobile && $receiver_zone_code) {
        		if($type=='status' && $status_id='') {
        			$this->send_response ( 400,  NULL, '400:动态id为空' );
        		}
        		
				if($url_code = $this->model->create($type,$sender_uid,$sender_name,$receiver_uid,$receiver_name,$receiver_mobile,$receiver_zone_code,$status_id,$msgid,$content_type,$appid)) {
					$this->send_response ( 200, array('url' => MO_SMS_JUMP.$url_code));
				}
				$this->send_response ( 400,  NULL, '400:短地址生成失败' );
        	}
        	$this->send_response ( 400, NULL, '400:参数不完整' );
        }
        $this->send_response ( 403, NULL, '400:无权限' );
    }
    
    /**
     * 
     * 生成随机字符串
     * @param int $len
     */
	private function _rand_str($len=5) {
        $chars='23456789abcdefghijklmnopqrstuvwxyz';
        mt_srand((double)microtime()*1000000*getmypid());
        $rand='';
        while(strlen($rand)<$len)
            $rand.=substr($chars,(mt_rand()%strlen($chars)),1);
        return $rand;
    }
}