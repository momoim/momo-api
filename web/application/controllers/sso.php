<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * [MOMO API] (C)1999-2011 ND Inc.
 * SSO控制器文件
 */

class sso_Controller extends Controller {

	public function __construct()
	{
		parent::__construct();
		//模型
        $this->model   = new User_Model;
	}

	public function index()
	{
		$this->send_response(405, NULL, '请求的方法不存在');
	}

	/**
	 * sso token校验
	 * @method POST
	 */
	public function token()
	{
		if ($this->get_method() != 'POST') {
			$this->send_response(405, NULL, '请求的方法不存在');
		}
		$post = $this->get_data();
		$appid = $post ['appid'] ? intval ( $post ['appid'] ) :0;
		$appkey = $post ['appkey'] ? trim ( $post ['appkey'] ) :'';
		$token = $post ['token'] ? trim ( $post ['token'] ) :'';
		if (empty ( $appid )) {
			$this->send_response ( 400, NULL, '40011:appid为空' );
		}
		if (empty ( $appkey )) {
			$this->send_response ( 400, NULL, '40012:appkey为空' );
		}
		//检查app合法性
		if(!$this->model->check_app_valid($appid,$appkey)) {
			$this->send_response ( 400, NULL, $this->model->getResponseMsg() );
		}
		//require_once MODPATH.'filesystem/include/Core.php';
		//$auth_token = Core::authcode($token,'DECODE','sdfjk2348*&21234(*3xx');
		//if($auth_token) {
		$result = $this->model->get_token_info ( $token );
		if ($token && $result) {
			$user_info = sns::getuser($result['ost_usa_id_ref']);
			$this->send_response ( 200, array ('uid' => $user_info ['uid'], 'zone_code' =>$user_info ['zone_code'], 'mobile' =>$user_info ['mobile'], 'status'=>$user_info ['status']));
		}
		//}
		$this->send_response ( 400, NULL, '40022:非法用户' );
	}


}