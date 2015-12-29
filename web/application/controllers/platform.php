<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * 
 * 开放平台控制器
 * @author andy
 *
 */

class Platform_Controller extends Controller {

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
        //模型
        $this->model   = new User_Model;
    }
    
    /**
     * 
     * 设置密码
     */
    public function set_password() {
    	$post = $this->get_data ();
		$appid = $post ['appid'] ? intval ( $post ['appid'] ) :0;
		$appkey = $post ['appkey'] ? trim ( $post ['appkey'] ) :'';
		$client_id = $post ['client_id']?intval($post ['client_id']):0;
		$password = $post ['password'] ? trim ( $post ['password'] ) : '';
    	if (empty ( $appid )) {
			$this->send_response ( 400, NULL, '40011:appid为空' );
		}
    	if (empty ( $appkey )) {
			$this->send_response ( 400, NULL, '40012:appkey为空' );
		}
    	if (!$this->model->check_app_valid($appid,$appkey)) {
    		$this->send_response ( 400, NULL, $this->model->getResponseMsg());
    	}
    	if (empty ( $password )) {
			$this->send_response ( 400, NULL, '40003:密码为空' );
		}
		if (strlen ( $password ) < 6 || strlen ( $password ) > 20) {
			$this->send_response ( 400, NULL, '400124:密码长度须6-20位' );
		}
		if($this->model->update_user_passwd($this->user_id,$password,USER)) {
			$this->send_response ( 200 );
		}
		$this->send_response ( 400, NULL, '40007:密码设置失败' );
    }
    
    /**
     * 
     * 检查用户名是否注册91通行证
     */
    public function check_register() {
    	$post = $this->get_data ();
    	$account = $post['account'];
    	$result = api_91::check_register($account);
    	$this->send_response ( 200, $result);
    }

    /**
     * 
     * 91通行证登录
     */
    public function login_91() {
    	$post = $this->get_data ();
		$appid = $post ['appid'] ? intval ( $post ['appid'] ) :0;
		$appkey = $post ['appkey'] ? trim ( $post ['appkey'] ) :'';
		$client_id = $post ['client_id']?intval($post ['client_id']):0;
		$account = $post ['account'] ? trim ( $post ['account'] ) : '';
		$password = $post ['password'] ? trim ( $post ['password'] ) : '';
		$md5 = $post ['md5'] ? intval ( $post ['md5'] ) : 0;
    	if (empty ( $appid )) {
			$this->send_response ( 400, NULL, '40011:appid为空' );
		}
    	if (empty ( $appkey )) {
			$this->send_response ( 400, NULL, '40012:appkey为空' );
		}
    	if (empty ( $account )) {
			$this->send_response ( 400, NULL, '40013:account为空' );
		}
    	if (empty ( $password )) {
			$this->send_response ( 400, NULL, '40014:password为空' );
		}
    	//检查app合法性
		if(!$this->model->check_app_valid($appid,$appkey)) {
			$this->send_response ( 400, NULL, $this->model->getResponseMsg() );
		}
    	$result = api_91::login($account,$password,$md5);
   		if($result['error'] == 0) {
		    if(!empty($result['user_id']) && $result['user_id'] > 0) {
			    $user = $this->model->check_91_uin($result['user_id']);
			    if($user && $user['uid']>0) {
				    $momo_uid = $user['uid'];
			    } else {
				    $momo_uid = $this->model->add_91_uin($appid,$result['user_id'],$client_id);
			    }
			    $this->send_response ( 200, array('momo_uid'=>$momo_uid,'91_name'=>$result['user_name']));
		    } else {
			    $this->send_response ( 400, NULL,'登录失败');
		    }
		}
		$this->send_response ( 400, NULL,$result['msg']);
    }


    public function test_login_91() {
    	$post = $this->get_data ();
		$appid = $post ['appid'] ? intval ( $post ['appid'] ) :0;
		$appkey = $post ['appkey'] ? trim ( $post ['appkey'] ) :'';
		$client_id = $post ['client_id']?intval($post ['client_id']):0;
		$account = $post ['account'] ? trim ( $post ['account'] ) : '';
		$password = $post ['password'] ? trim ( $post ['password'] ) : '';
		$md5 = $post ['md5'] ? intval ( $post ['md5'] ) : 0;
    	if (empty ( $appid )) {
			$this->send_response ( 400, NULL, '40011:appid为空' );
		}
    	if (empty ( $appkey )) {
			$this->send_response ( 400, NULL, '40012:appkey为空' );
		}
    	if (empty ( $account )) {
			$this->send_response ( 400, NULL, '40013:account为空' );
		}
    	if (empty ( $password )) {
			$this->send_response ( 400, NULL, '40014:password为空' );
		}
    	//检查app合法性
		if(!$this->model->check_app_valid($appid,$appkey)) {
			$this->send_response ( 400, NULL, $this->model->getResponseMsg() );
		}
    	$result = api_91::login_test($account,$password,$md5);
   		if($result['error'] == 0) {
   			$user = $this->model->check_91_uin($result['user_id']);
			if($user && $user['uid']>0) {
				$momo_uid = $user['uid'];
			} else {
				$momo_uid = $this->model->add_91_uin($appid,$result['user_id'],$client_id);	
			}
    		$this->send_response ( 200, array('momo_uid'=>$momo_uid,'91_name'=>$result['user_name']));
		}
		$this->send_response ( 400, NULL,$result['msg']);
    }
    
    public function test_cache() {
    	$post = $this->get_data ();
		$key = $post ['key'] ? trim ( $post ['key'] ) :'';
		if($key) {
			$rs = Cache::instance()->get($key);
			$this->send_response ( 200,$rs);
		}
    }
    
    /**
     * 
     * 91通行证登录
     */
    public function login_by_uin() {
    	$post = $this->get_data ();
		$uin = $post ['uin'] ? trim ( $post ['uin'] ) : '';
		$password = $post ['password'] ? trim ( $post ['password'] ) : '';
    	$result = api_91::login_by_uin($uin,$password);
    	$this->send_response ( 200, $result);
    }
    
    /**
     * 
     * 91通行证注册
     */
    public function register() {
    	$post = $this->get_data ();
		$account = $post ['account'] ? trim ( $post ['account'] ) : '';
		$password = $post ['password'] ? trim ( $post ['password'] ) : '';
    	$result = api_91::register($account,$password);
    	$this->send_response ( 200, $result);
    }
    
    /**
     * 
     * 91通行证密保手机绑定
     */
    public function bind_mobile() {
    	$post = $this->get_data ();
    	$account = $post['account']?trim($post['account']):'';
    	$phone = $post['phone']?trim($post['phone']):'';
    	if (empty ( $phone )) {
			$this->send_response ( 400, NULL, '40021:phone为空' );
		}
    	$result = api_91::bind_mobile($account,$phone);
    	$this->send_response ( 200, $result);
    }
    

    /**
     * 
     * 91通行证密保手机改绑
     */
    public function change_bind_mobile() {
    	$post = $this->get_data ();
    	$account = $post['account']?trim($post['account']):'';
    	$phone = $post['phone']?trim($post['phone']):'';
    	$phone_old = $post['phone_old']?trim($post['phone_old']):'';
    	if (empty ( $phone )) {
			$this->send_response ( 400, NULL, '40021:phone为空' );
		}
    	$result = api_91::change_bind_mobile($account,$phone,$phone_old);
    	$this->send_response ( 200, $result);
    }
    
    /**
     * 
     * 查询通用平台91 uin是否绑定
     */
    public function check_uin_bind() {
    	$post = $this->get_data ();
    	$uin = $post['uin']?trim($post['uin']):0;
    	if (empty ( $post )) {
			$this->send_response ( 400, NULL, '40020:uin为空' );
		}
    	$result = api_91::check_uin_bind($uin);
    	$this->send_response ( 200, $result);
    }
    

    /**
     * 
     * 查询通用平台91 uin是否绑定
     */
    public function check_user_login_by_cookie() {
    	$post = $this->get_data ();
    	$appid = $post['appid']?(int)$post['appid']:0;
    	$client_id = $post['client_id']?(int)$post['client_id']:0;
    	$cookie['UserId'] = $post['UserId']?trim($post['UserId']):'';
    	$cookie['CookieOrdernumberMaster'] = $post['CookieOrdernumberMaster']?trim($post['CookieOrdernumberMaster']):'';
    	$cookie['CookieOrdernumberSlave'] = $post['CookieOrdernumberSlave']?trim($post['CookieOrdernumberSlave']):'';
    	$cookie['CookieSiteflag'] = $post['CookieSiteflag']?trim($post['CookieSiteflag']):'';
    	$cookie['TimeStamp'] = $post['TimeStamp']?trim($post['TimeStamp']):'';
    	$cookie['CookieCheckcode'] = $post['CookieCheckcode']?trim($post['CookieCheckcode']):'';
    	if (empty($appid)) {
    		$this->send_response ( 400, NULL, '40021:appid参数缺失' );
    	}
    	if (empty ( $cookie['UserId'] ) || empty ( $cookie['CookieOrdernumberMaster'] ) || empty ( $cookie['CookieOrdernumberSlave'] ) || empty ( $cookie['TimeStamp'] ) || empty ( $cookie['CookieCheckcode'])) {
			$this->send_response ( 400, NULL, '40020:cookie参数缺失' );
		}
    	$result = api_91::check_user_login_by_cookie($cookie);
    	if(isset($result['user_id']) && !empty($result['user_id'])) {
    		$user = $this->model->check_91_uin($result['user_id']);
			if($user && $user['uid']>0) {
				$momo_uid = $user['uid'];
			} else {
				$momo_uid = $this->model->add_91_uin($appid,$result['user_id'],$client_id);	
			}
	    	$this->send_response ( 200, array('user_id'=>$momo_uid,'user_name'=>$result['user_name']));
    	}
		$this->send_response ( 400, NULL,$result['msg']);
    }
    
    
    /**
     * 
     * 查询通用平台手机是否被绑定
     */
    public function check_phone_bind() {
    	$post = $this->get_data ();
    	$phone = $post['phone']?trim($post['phone']):'';
    	if (empty ( $phone )) {
			$this->send_response ( 400, NULL, '40021:phone为空' );
		}
    	$result = api_91::check_phone_bind($phone);
    	$this->send_response ( 200, $result);
    }
    /**
     * 
     * 通用平台91 uin绑定手机号码
     */
    public function bind_phone() {
    	$post = $this->get_data ();
    	$uin = $post['uin']?trim($post['uin']):0;
    	$phone = $post['phone']?trim($post['phone']):'';
    	if (empty ( $uin )) {
			$this->send_response ( 400, NULL, '40020:uin为空' );
		}
    	if (empty ( $phone )) {
			$this->send_response ( 400, NULL, '40021:phone为空' );
		}
    	$result = api_91::bind_phone($uin,$phone);
    	$this->send_response ( 200, $result);
    }
    
    /**
     * 
     * 通用平台91 uin解绑手机号码
     */
    public function unbind_phone() {
    	$post = $this->get_data ();
    	$uin = $post['uin']?trim($post['uin']):0;
    	$phone = $post['phone']?trim($post['phone']):'';
    	if (empty ( $uin )) {
			$this->send_response ( 400, NULL, '40020:uin为空' );
		}
    	if (empty ( $phone )) {
			$this->send_response ( 400, NULL, '40021:phone为空' );
		}
    	$result = api_91::unbind_phone($uin,$phone);
    	$this->send_response ( 200, $result);
    }
    
    public function test_mq() {
    	$result =  $this->model->test_mq();
    	$this->send_response ( 200, $result);
    }

	/**
	 * 返回91时光机网站地址
	 */
	public function url()
	{
		$this->send_response ( 200, array('url' => ''));
	}
	
/**
     * 
     * 91帐号sid校验
     */
    public function check_91_sid_test() {
		if ($this->get_method () != 'POST') {
			$this->send_response ( 405, NULL, '请求的方法不存在' );
		}
		$post = $this->get_data();
		$consumer_key = isset($post['consumer_key']) ? $post['consumer_key'] : '';
        $app = $this->model->get_91_app_test($consumer_key);
        $this->send_response ( 200, $app);
    }
}

