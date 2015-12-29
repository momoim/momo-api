<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * 消息统计
 * 
 * @package Bind_Controller
 * @author Yufeng <ivwsai@gmail.com>
 * @copyright (c) 2010-2011 MOMO Team
 */
class Msg_Count_Controller extends Controller 
{

    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * @method GET
     */
    public function index()
    {
        if ($this->get_method() != 'GET') {
            $this->send_response(405, NULL, '请求的方法不存在');
        } 
		$sid = $this->input->get('sid', '');
		$code = $this->input->get('code', 'im');
		$appid = $this->input->get('appid', 0);
		if(!$sid){
            $this->send_response ( 400, NULL, '40070：sid为空');
        }
		$count = 0;
		$result = api_91::oap_passport_check($sid);
		if($result['error'] == 0) {
			$user = User_Model::instance()->check_91_uin($result['uap_uid']);
			if($user) {
				$imModel=new Im_Model();
				$imModel->uid=$user['uid'];
				$count = $imModel->getCount(0, 1,$appid);
			}
			$this->send_response(200,array('im'=>(int)$count));	
		}
		$this->send_response(400,NULL,$result['msg']);
    }
}
