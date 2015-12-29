<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * 绑定微博
 * 
 * @package Bind_Controller
 * @author Yufeng <ivwsai@gmail.com>
 * @copyright (c) 2010-2011 MOMO Team
 */
class Bind_Controller extends Controller 
{
    // Allow all controllers to run in production by default
    const ALLOW_PRODUCTION = TRUE;
    
    private $site;
    
    private $momo_weibo_uid = 2255055220;

    public function __construct()
    {
        parent::__construct();
        
        $this->model = Bind_Model::instance();
        
	$this->site = array(
			'weibo.com', 
			't.qq.com', 
			'renren.com', 
			't.sohu.com', 
			'kaixin001.com');
    }
    
    /**
     * @method GET
     */
    public function index()
    {
        $this->send_response(405, NULL, '请求的方法不存在');
    }
    
    //oauth回调用，是假的
    public function confirm()
    {
        //
    }
    
	/**
     * 绑定
     * @method POST
     */
    public function web_create()
    {
        if($this->get_method() != 'POST' && $this->get_method() != 'PUT') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        
        $post = $this->get_data();
        
        if (!$post || empty($post["oauth_token"]) || empty($post["oauth_token_secret"]) || empty($post["homepage"])) {
            $this->send_response(400, NULL, "输入有误");
        }
        
        if (!in_array($post["site"], $this->site)) {
            $this->send_response(400, NULL, "输入有误");
        }
        
        $field = array(
        	'homepage'=>$post["homepage"], 
        	'user_id'=>$this->user_id, 
        	'oauth_token'=>$post["oauth_token"], 
        	'oauth_token_secret'=>$post["oauth_token_secret"], 
        	'name'=>isset($post["name"]) ? $post["name"] : "", 
        	"usa_id"=> isset($post["user_id"]) ? (int)$post["user_id"] : 0
        );
        
	    if( $this->model->ckBinding($this->user_id, $post["site"]) || $this->model->ckToken($access_token["oauth_token"], $access_token["oauth_token_secret"], $post["site"]) ){
	        $this->send_response(409, null , "你的微博帐号已经跟一个MOMO帐号绑定了");
        } else {
		    $status = $this->model->saveToken($field, $post["site"]);
		
    		if ($status) {
    		    $this->send_response(200);
    		} else {
    		    $this->send_response(500);
    		}
	    }
    }
    
    /**
     * 绑定
     * @method POST
     */
    public function create()
    {
        if ($this->get_method() != 'POST' && $this->get_method() != 'PUT') {
		// 只支持新增绑定和编辑数据
		$this->send_response(405, NULL, '请求的方法不存在');
        }
        
        $post = $this->get_data();
        
        if (!$post || empty($post["username"]) || empty($post["password"]) || empty($post["site"])) {
		// 保证数据的完整性
            $this->send_response(400, NULL, "输入有误");
        }
        
        if (!in_array($post["site"], $this->site)) {
            $this->send_response(400, NULL, "输入有误");
        }
        
        //关注官方微博
        $follow = isset($post["follow"]) ? (bool)$post["follow"] : false;
        
        $access_token = "";
        switch ($post["site"]) {
            case 'weibo.com':
                $access_token = $this->weibo($post["username"], $post["password"], $follow);
            break;
            
            case 't.qq.com':
                $access_token = $this->t_qq($post["username"], $post["password"], $follow);
            break;
            
            case 'renren.com':
                $access_token = $this->renren($post["username"], $post["password"], $follow);
            break;
            
            case 't.sohu.com':
                $access_token = $this->t_sohu($post["username"], $post["password"], $follow);
            break;
            
            case 'kaixin001.com':
                $access_token = $this->kaixin001($post["username"], $post["password"], $follow);
            break;
        }
        //Array ( [oauth_token] => 32b50df69550200338539040feb6caf9 [oauth_token_secret] => 399fbf795b661ecb78aa9abaf254828e [user_id] => 1931197670 ) 
        
        $field = array(
        	'homepage'=>$access_token["homepage"], 
        	'user_id'=>$this->user_id, 
        	'oauth_token'=>$access_token["oauth_token"], 
        	'oauth_token_secret'=>$access_token["oauth_token_secret"], 
        	'name'=>$access_token["name"], 
        	"usa_id"=> $access_token["user_id"] ? (int)$access_token["user_id"] : 0
        );
        
	    if($this->model->ckBinding($this->user_id, $post["site"]) || $this->model->ckToken($access_token["oauth_token"], $access_token["oauth_token_secret"], $post["site"])){
	        $this->send_response(409, null , "你的微博帐号已经跟一个MOMO帐号绑定了");
        } else {
		    $status = $this->model->saveToken($field, $post["site"]);
		
    		if ($status) {
    		    
    		    //只有第一次绑定才发
    		    if ($status == 1 && 'weibo.com' == $post["site"]) {
    		        //$text = "我刚刚在 @移动MOMO 绑定微博啦！不仅可以同步消息到新浪微博，还能通过短信将照片、语音、地图等信息免费发送给我的朋友，支持200多个国家的信息互通呢！更棒的是朋友们无需注册安装就能和我交流，推荐你也试一下！http://momo.im/user/" . $this->user_id;
    		        $text = "我刚刚在 @移动MOMO 绑定微博啦！不仅可以同步消息到新浪微博，还能通过短信将照片、语音、地图等信息免费发送给我的朋友，支持200多个国家的信息互通呢！更棒的是朋友们无需注册安装就能和我交流，推荐你也试一下！http://momo.im/about";
        			$message = array(
        				'kind' => 'momoweibo',
        				'data' => array(
        					'source_id'=> '', 
        					'owner_uid'=> $this->user_id, 
        					'name'=> $access_token["name"], 
        					'text' => $text, 
        					'images' => array("http://momo.im/style/images/v3/medal.jpg"), 
        					'site' => $post["site"], 
        					'oauth_token' => $access_token['oauth_token'], 
        					'oauth_token_secret' => $access_token['oauth_token_secret']
        				)
        			);
        	    
        			$this->model->mq_send(json_encode($message), "queue_momoweibo","amq.direct");
    		    }
    		    
    		    $this->send_response(200);
    		} else {
    		    $this->send_response(500);
    		}	
	    }
    }
    
    public function oauth2_cb(){
        //回调
    }
    
    /**
     * //@todo qq绑定
     * @return unknown_type
     */
    public function oauth2_create() {
   	 	if($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        $data = $this->get_data();
    	$access_token = $data['access_token']?trim($data['access_token']):'';
    	$expires_in = $data['expires_in']?(int)$data['expires_in']:0;
    	$site = $data['site']?trim($data['site']):'';
    	$follow = $data['follow']?1:0;
    	if(empty($access_token))
    		$this->send_response(400, NULL,'401601:access_token为空');
    	if(empty($expires_in))
    		$this->send_response(400, NULL,'401602:expires_in为空');
    	if(empty($site))
    		$this->send_response(400, NULL,'401603:site为空');
    	
    	$appid=$this->appid;
    	//@todo 91来电秀分离
    	if($appid != 29){
            $appid = 0;
        }
    	
    	$method_name = "{$site}_userinfo";
    	if(!method_exists($this->model, $method_name)){
    	    $this->send_response(400, NULL,'401604:不支持的站点绑定');
    	}
    	//约定
    	$userinfo = $this->model->{$method_name}($access_token,$follow,$appid);
    	
    	if(!$userinfo){
    	    $this->send_response(400, NULL,'401605:access_token无效');
    	}
    	$wb_uid = $userinfo['uid'];
    	$wb_name = $userinfo['name'];
    	
    	$check = $this->model->oauth2_check($this->user_id,$site,$appid);
    	
    	if($check) {
    		$result = $this->model->oauth2_update($this->user_id,$access_token,$expires_in,$site,$appid,$wb_uid,$wb_name);
    	} else {
    		$result = $this->model->oauth2_create($this->user_id,$access_token,$expires_in,$site,$appid,$wb_uid,$wb_name);
    	}
    	
    	$this->send_response(200,$userinfo);
    }
    public function oauth2_destroy() {
        if($this->get_method() != 'POST' && $this->get_method() != 'PUT') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        
        $post = $this->get_data();
        
        if (empty($post["site"]) || !in_array($post["site"], $this->site)) {
            $this->send_response(400, NULL, "输入有误");
        }
        
        $status = $this->model->oauth2_destroy($this->user_id, $post["site"], $this->appid);
        
        if ($status) {
            $this->send_response(200);
        } else {
            $this->send_response(400);
        }
    }
    
    /**
     * 取消绑定
     * @method POST
     */
    public function destroy()
    {
        if($this->get_method() != 'POST' && $this->get_method() != 'PUT') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        
        $post = $this->get_data();
        
        if (empty($post["site"]) || !in_array($post["site"], $this->site)) {
            $this->send_response(400, NULL, "输入有误");
        }
        
        $status = $this->model->destroyToken($this->user_id, $post["site"]);
        
        if ($status) {
            $this->send_response(200);
        } else {
            $this->send_response(500);
        }
    }
    
    /**
     * 绑定新浪微博.
     * @param string $username
     * @param string $password
     * @param boolean $follow
     */
    private function weibo($username, $password, $follow)
    {
        set_time_limit(120);
        require_once Kohana::find_file('vendor', 'oauth/OAuthRequester');
        
        $okey = Kohana::config('uap.oauth');

        define("MOMO_CONSUMER_KEY", $okey['weibo.com']['WB_AKEY']);
        define("MOMO_CONSUMER_SECRET", $okey['weibo.com']['WB_SKEY']);
        define("MOMO_OAUTH_HOST", "http://api.t.sina.com.cn");
        define("MOMO_REQUEST_TOKEN_URL", MOMO_OAUTH_HOST . "/oauth/request_token");
        define("MOMO_AUTHORIZE_URL", MOMO_OAUTH_HOST . "/oauth/authorize");
        define("MOMO_ACCESS_TOKEN_URL", MOMO_OAUTH_HOST . "/oauth/access_token");

        define('OAUTH_TMP_DIR', function_exists('sys_get_temp_dir') ? sys_get_temp_dir() : realpath($_ENV["TMP"]));
        
        $store = OAuthStore::instance('MySQL');
        $usr_id = $this->user_id;


        try {
            try{
                $store->getServer(MOMO_CONSUMER_KEY, $usr_id);
            } catch ( OAuthException2 $e ) {
                //初始化
                $server = array(
                	'consumer_key' => MOMO_CONSUMER_KEY, 
                	'consumer_secret' => MOMO_CONSUMER_SECRET, 
                	'server_uri' => MOMO_OAUTH_HOST, 
                	'signature_methods'=>array('HMAC-SHA1', 'PLAINTEXT'), 
                	'request_token_uri' => MOMO_REQUEST_TOKEN_URL, 
                	'authorize_uri' => MOMO_AUTHORIZE_URL, 
                	'access_token_uri' => MOMO_ACCESS_TOKEN_URL
                );
                //$store->deleteServer(MOMO_CONSUMER_KEY, $usr_id);

		// 更新应用服务器信息
                $consumer_key = $store->updateServer($server, $usr_id);
            }
            
	    // 获取请求的token
            $tokenResultParams = OAuthRequester::requestRequestToken( MOMO_CONSUMER_KEY, $usr_id );
            
            usleep(5);
	    $bindUrl = url::site("bind/confirm");

            //STEP 1:  If we do not have an OAuth token yet, go get one
            if(!empty($_SERVER["HTTP_CLIENT_IP"])) {
                $cip = $_SERVER["HTTP_CLIENT_IP"];
            } else if(!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                $cip = $_SERVER["HTTP_X_FORWARDED_FOR"];
            } else if(!empty($_SERVER["REMOTE_ADDR"])) {
                $cip = $_SERVER["REMOTE_ADDR"];
            } else {
                //找不到默認為momo服務器ip
                $cip = "58.22.103.199";
            }
        
$regCallbackUrl =urlencode('http://api.t.sina.com.cn/oauth/authorize?oauth_token=' . $tokenResultParams['token'] . '&oauth_callback=' . urlencode(url::site( "bind/confirm" )) . '&from=&with_cookie=' );

$ch = curl_init( );
curl_setopt( $ch, CURLOPT_REFERER, 'http://api.weibo.com/oauth/authorize');
//curl_setopt( $ch, CURLOPT_HTTPHEADER, array ('Content-Type: application/x-www-form-urlencoded', "CLIENT-IP: $cip", "X-FORWARDED-FOR: $cip") );
$header = array();
$header['Host'] = 'api.weibo.com';
$header['User-Agent'] = 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; .NET4.0C; .NET4.0E)';
curl_setopt( $ch, CURLOPT_HEADER, true );
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIEJAR);
curl_setopt( $ch, CURLOPT_URL, "http://api.weibo.com/oauth/authorize?oauth_token=" . $tokenResultParams['token'] . "&oauth_callback=/wb.php" );
curl_setopt( $ch, CURLOPT_POST, true );

$Params = array (
                "action" => "submit", 
                "forcelogin" => "", 
                "from" => "", 
                "oauth_callback" => "none", 
                "oauth_token" => $tokenResultParams['token'], 
				  
                "passwd" => $password, 
                "regCallback" => "http://weibo.com/", 
		"ssoDoor" => "",
                "userId" => $username,
		"vdCheckflag" => 1,
		"verifyToken"=>"null",
		"vsnval"=>""
            );
            
            $query = '';
            foreach ( $Params as $key => $value ) {
                $query .= $key . '=' . $value . '&';
            }
             curl_setopt( $ch, CURLOPT_POSTFIELDS, $query );
            $txt = curl_exec( $ch );
            curl_close( $ch );
            
            preg_match( '@Location:(.*)@i', $txt, $matches );
            if (! isset( $matches[1] )) {
                $this->send_response(407, null, "用户名或密码错误");
                return null;
            }
            
            $oauth_verifier = "";
            $tmp = explode( "&", trim( $matches[1] ) );
            
            foreach ( $tmp as $val ) {
                if (stripos( $val, 'oauth_verifier=' ) !== FALSE) {
                    $oauth_verifier = ltrim( $val, 'oauth_verifier=' );
                }
            }
            
            if (! $oauth_verifier) {
                $this->send_response(500, null, "oauth_verifier未取到");
                return null;
            }
            
            usleep(5);

            //STEP 2:  Get an access token               
            try {
                $access_token = OAuthRequester::requestAccessToken( MOMO_CONSUMER_KEY, $tokenResultParams['token'], $usr_id, 'POST', array ("oauth_verifier" => $oauth_verifier) );
                
                try {
                    //取得个人信息
                     usleep(5);
                    $request = new OAuthRequester("http://api.t.sina.com.cn/users/show/{$access_token['user_id']}.json", 'GET');
                    $result = $request->doRequest($this->user_id);
                    
                    if ($result['code'] != 200) {
                        //$this->send_response(500, null, $result['body']);
                    }
                    
                    $result = json_decode($result['body'], true);
                    
                    if ($result) {
						$domain = $result["domain"] ? $result["domain"] : $result["id"];
						$access_token = array_merge($access_token, array("name"=>$result["name"], "homepage"=>"http://weibo.com/" . $domain));
                    }
                }catch ( OAuthException2 $e ) {}
                
                try {
                    if ($follow) {
                        //关注官网
                         usleep(5);
                        $request = new OAuthRequester("http://api.t.sina.com.cn/friendships/create/2255055220.json", 'POST');
                        $result = $request->doRequest($this->user_id);
                    }
                }catch ( OAuthException2 $e ) {}
 
                return $access_token;
                
            } catch ( OAuthException2 $e ) {
                $this->send_response(500, null, $e->getMessage());
                return;
            }
        } catch ( OAuthException2 $e ) {
            $this->send_response(500, null, $e->getMessage());
        }
    }
    
    /**
     * 绑定腾讯微博.
     * @param string $username
     * @param string $password
     * @param boolean $follow
     */
    private function t_qq($username, $password, $follow)
    {
        $this->send_response(501, NULL, "暂不支持");
    }
    
    /**
     * 绑定人人网.
     * @param string $username
     * @param string $password
     * @param boolean $follow
     */
    private function renren($username, $password, $follow)
    {
        $this->send_response(501, NULL, "暂不支持");
    }
    
    /**
     * 绑定搜狐微博.
     * @param string $username
     * @param string $password
     * @param boolean $follow
     */
    private function t_sohu($username, $password, $follow)
    {
        $this->send_response(501, NULL, "暂不支持");
    }
    
    /**
     * 绑定开心网.
     * @param string $username
     * @param string $password
     * @param boolean $follow
     */
    private function kaixin001($username, $password, $follow)
    {
        set_time_limit(120);
        
        require_once Kohana::find_file('vendor', 'oauth/OAuthRequester');
        
        $okey = Kohana::config('uap.oauth');
        define("MOMO_CONSUMER_KEY", $okey['kaixin001.com']['WB_AKEY']);
        define("MOMO_CONSUMER_SECRET", $okey['kaixin001.com']['WB_SKEY']);
        
        define("MOMO_OAUTH_HOST", "http://api.kaixin001.com");
        define("MOMO_REQUEST_TOKEN_URL", MOMO_OAUTH_HOST . "/oauth/request_token");
        define("MOMO_AUTHORIZE_URL", MOMO_OAUTH_HOST . "/oauth/authorize");
        define("MOMO_ACCESS_TOKEN_URL", MOMO_OAUTH_HOST . "/oauth/access_token");

        define('OAUTH_TMP_DIR', function_exists('sys_get_temp_dir') ? sys_get_temp_dir() : realpath($_ENV["TMP"]));
        
        $store = OAuthStore::instance('MySQL');
        
        $usr_id = $this->user_id;
        try {
            try{
                
                $store->getServer(MOMO_CONSUMER_KEY, $usr_id);
                
            } catch ( OAuthException2 $e ) {
                //初始化
                $server = array(
                	'consumer_key' => MOMO_CONSUMER_KEY, 
                	'consumer_secret' => MOMO_CONSUMER_SECRET, 
                	'server_uri' => MOMO_OAUTH_HOST, 
                	'signature_methods'=>array('HMAC-SHA1', 'PLAINTEXT'), 
                	'request_token_uri' => MOMO_REQUEST_TOKEN_URL, 
                	'authorize_uri' => MOMO_AUTHORIZE_URL, 
                	'access_token_uri' => MOMO_ACCESS_TOKEN_URL
                );
                //$store->deleteServer(MOMO_CONSUMER_KEY, $usr_id);
                
                $consumer_key = $store->updateServer($server, $usr_id);
            }
            
            $tokenResultParams = OAuthRequester::requestRequestToken( MOMO_CONSUMER_KEY, $usr_id, array("scope" => "basic create_records"), "GET" );
            
            usleep(5);
            //STEP 1:  If we do not have an OAuth token yet, go get one
            $Params = array (
                "email" => $username, 
                "password" => $password, 
                "callback" => urlencode( 'http://api.kaixin001.com/oauth/authorize?oauth_token=' . $tokenResultParams['token'] . '&oauth_callback=' . urlencode(url::site( "bind/confirm" )) . '&from=&oauth_client=1' ), 
                "appkey" => MOMO_CONSUMER_KEY, 
                "fromclient" => "", 
                "return" => "", 
                "login" => "登陆" 
            );
            
            $query = '';
            foreach ( $Params as $key => $value ) {
                $query .= $key . '=' . $value . '&';
            }
            
            $ch = curl_init();
            curl_setopt( $ch, CURLOPT_POST, true );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $query );
            
            if(!empty($_SERVER["HTTP_CLIENT_IP"])) {
                $cip = $_SERVER["HTTP_CLIENT_IP"];
            } else if(!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                $cip = $_SERVER["HTTP_X_FORWARDED_FOR"];
            } else if(!empty($_SERVER["REMOTE_ADDR"])) {
                $cip = $_SERVER["REMOTE_ADDR"];
            } else {
                //找不到默認為momo服務器ip
                $cip = "58.22.103.199";
            }
        
            curl_setopt( $ch, CURLOPT_HTTPHEADER, array ('Content-Type: application/x-www-form-urlencoded', "CLIENT-IP: $cip", "X-FORWARDED-FOR: $cip") );
            curl_setopt( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; U; Linux i686; zh-CN; rv:1.9.2.23) Gecko/20110921 Ubuntu/10.10 (maverick) Firefox/3' );
            curl_setopt( $ch, CURLOPT_URL, "http://wap.kaixin001.com/auth/login.php?isoauth=1" );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_HEADER, true );
            curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
            //curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1); //是否抓取３０２跳转后的
            
            $txt = curl_exec( $ch );
            curl_close( $ch );
			
			preg_match( '@Location:(.*)@i', $txt, $matches );
            if (! isset( $matches[1] ) || stripos($matches[1], '/oauth/authorize') === FALSE) {
                $this->send_response(407, null, "用户名或密码错误");
                return null;
            }
            
            $Params = array (
                "loginnewsfeed" => 1, 
                "oauth_token" => $tokenResultParams['token'], 
                "oauth_callback" => "", 
                "appid" => $okey['kaixin001.com']['APP_ID'], 
                "oauth_client" => 1, 
                "accept" => "允许" 
            );
            
            $query = '';
            foreach ( $Params as $key => $value ) {
                $query .= $key . '=' . $value . '&';
            }
            $ch = curl_init();
            curl_setopt( $ch, CURLOPT_POST, true );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $query );
            
            curl_setopt( $ch, CURLOPT_HTTPHEADER, array ('Content-Type: application/x-www-form-urlencoded', "CLIENT-IP: $cip", "X-FORWARDED-FOR: $cip") );
            curl_setopt( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; U; Linux i686; zh-CN; rv:1.9.2.23) Gecko/20110921 Ubuntu/10.10 (maverick) Firefox/3' );
            curl_setopt( $ch, CURLOPT_URL, trim($matches[1]) );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_HEADER, true );
            curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
            
            $txt = curl_exec( $ch );
            curl_close( $ch );
            
            preg_match( '@你获取到的授权码是：<b>(\w+)</b>@i', $txt, $matches );
            if (! isset( $matches[1] )) {
                $this->send_response(500, null, "oauth_verifier未取到");
                return null;
            }
            
            
            $oauth_verifier = $matches[1];
            
            usleep(5);
            //STEP 2:  Get an access token               
            try {
                $access_token = OAuthRequester::requestAccessToken( MOMO_CONSUMER_KEY, $tokenResultParams['token'], $usr_id, 'POST', array ("oauth_verifier" => $oauth_verifier) );
                
                try {
                    //取得个人信息
                    usleep(5);
                    $request = new OAuthRequester("http://api.kaixin001.com/users/me.json", 'GET');
                    $result = $request->doRequest($this->user_id);
                    
                    if ($result['code'] != 200) {
                        //$this->send_response(500, null, $result['body']);
                    }
                    
                    $result = json_decode($result['body'], true);
                    
                    if ($result) {
						$access_token = array_merge($access_token, array("name"=>$result["name"], "user_id"=>$result["uid"], "homepage"=>"http://www.kaixin001.com/home/{$result["uid"]}.html" . $domain));
                    }
                }catch ( OAuthException2 $e ) {}
                
                try {
                    if ($follow) {
                        //关注官网
                        //usleep(5);
                        //$request = new OAuthRequester("", 'POST');
                        //$result = $request->doRequest($this->user_id);
                    }
                }catch ( OAuthException2 $e ) {}
 
                return $access_token;
                
            } catch ( OAuthException2 $e ) {
                $this->send_response(500, null, $e->getMessage());
                //echo "OAuthException:  " . $e->getMessage();
                //var_dump( $e );
                return;
            }
        } catch ( OAuthException2 $e ) {
            $this->send_response(500, null, $e->getMessage());
            //echo "OAuthException:  " . $e->getMessage();
            //var_dump($e);
        }
    }
}
