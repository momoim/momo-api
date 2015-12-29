<?php
defined('SYSPATH') or die('No direct access allowed.');

/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * 控制器基类库文件
 */

/**
 * 控制器基类
 */
class Controller extends Controller_Core
{
    /**
     * 请求方法
     * @var string
     */
    protected $method = 'GET';
    /**
     * 请求来源
     * @var int
     */
    protected $source = 0;
    /**
     * 根节点名
     * @var string
     */
    protected $root = '';
    /**
     * 二级结点
     * @var string
     */
    protected $second = null;

    /**
     * 用户uid
     * @var int
     */
    protected $user_id = 0;
    protected $uid = 0;
    /**
     * 用户加入的群组
     * @var array
     */
    protected $group_ids = array();
    protected $appid = 0;
    protected $user_status = 0;
    protected $device_id = null;
    protected $phone_model = '';
    protected $qname = null;
    protected $oauth_server;
    protected $useragent = 'MOMO API OAuth v0.1.0-beta1';
    protected $timeout = 30;
    protected $connecttimeout = 30;
    protected $ssl_verifypeer = FALSE;
    protected $un_oauth_check = FALSE;
    protected $http_code;
    protected $http_info;

    protected $no_auth = array('welcome', 'user/login', 'auth/verify_code', 'auth/token');

    /**
     * Contains the last API call.
     *
     * @ignore
     */
    public $url;

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
        $session = Session::instance();
        $session->set('request_time', microtime(TRUE));
        $this->method = $_SERVER['REQUEST_METHOD'];
        $current_url = url::current();
        $this->current_url = $current_url;
        $url_arr = explode('/', $current_url);
        $dot_pos = strrpos($current_url, '.');
        $back_slash_pos = strpos($current_url, '/');

        if ($back_slash_pos === FALSE) {
            //防止没有设置返回类型的输入
            $this->root = substr($current_url, 0, $dot_pos ? $dot_pos : strlen($current_url));
        } else {
            $this->root = substr($current_url, 0, $back_slash_pos);
            if (isset($url_arr[1]) && !preg_match("/\d+/is", $url_arr[1])) {
                $this->second = preg_replace('/.(xml|json)$/', '', $url_arr[1]);
            }
        }

        $url = $this->second = preg_replace('/.(xml|json)$/', '', $this->current_url);

        if (in_array($url, $this->no_auth)) {
            return TRUE;
        }

        $headers = self::get_all_headers();
        $access_token = $this->get_access_token($headers);

        if (empty($access_token)) {
            $this->send_response(401, NULL, Kohana::lang('authorization.missing_auth'));
        }

        $token = User_Model::instance()->get_access_token($access_token);
        if (empty($token)) {
            $this->send_response(401, NULL, Kohana::lang('authorization.missing_auth'));
        }
        if (isset($token['expires']) && time() > $token['expires']) {
            $this->send_response(401, NULL, Kohana::lang('authorization.auth_expired'));
        }

        $this->user_id = $this->uid = $token['id'];
        return TRUE;

    }

    public function to_postdata($parameters, $multi = false)
    {
        if ($multi)
            return OAuthUtil::build_http_query_multi($parameters);
        else
            return OAuthUtil::build_http_query($parameters);
    }

    /**
     * 取得访问来源类型
     */
    final protected function get_source()
    {
        return $this->source;
    }

    /**
     * 获取请求方法
     * @return string
     */
    public function get_method()
    {
        return $this->method;
    }

    /**
     * 获取请求数据
     * @return array
     */
    public function get_data($standardize = true)
    {
        $data = array();
        $input_data = file_get_contents('php://input');
        $data = json_decode($input_data, TRUE);
        if ((function_exists('json_last_error') and
            json_last_error() != JSON_ERROR_NONE)
        ) {
            $this->send_response('400', NULL, '400901:输入数据有误');
        }
        //过滤输入字符
        if (is_array($data)) {
            foreach ($data as $key => $val) {
                // Sanitize $data
                $data[$this->input->clean_input_keys($key)] = $this->input->clean_input_data($val, $standardize);
            }
            if ($standardize) {
                array_walk_recursive($data, create_function('&$val, $key', '$val = trim($val);'));
            }
        }
        return $data;
    }

    /**
     * 发送响应
     * @param int $response_code HTTP状态码
     * @param array $body_data 内容
     * @param string $error_msg 错误消息
     * @param bool $exit 是否退出
     * @return void
     */
    public function send_response($response_code = 200, $body_data = NULL,
                                  $error_msg = '', $exit = TRUE)
    {
        api::send_response($response_code, $body_data, $error_msg, $exit);
    }

    /**
     * 请求资源不存在
     * @param $method
     * @param $arguments
     */
    public function __call($method, $arguments)
    {
        $this->send_response(404, NULL, '请求的资源不存在');
    }

    /**
     * 获取当前登录用户 ID
     * @return int|NULL
     */
    public function getUid()
    {
        //$this->init_oauth(true);
        if ($this->user_id) {
            return intval($this->user_id);
        } else {
            $session = Session::instance();
            $uid = $session->get('uid');
            return $uid ? $uid : NULL;
        }
    }

    /**
     * 获取客户端访问ip
     */
    public function get_ip()
    {
        $cip = getenv('HTTP_CLIENT_IP');
        $xip = getenv('HTTP_X_FORWARDED_FOR');
        $rip = getenv('REMOTE_ADDR');
        $srip = $_SERVER['REMOTE_ADDR'];
        if ($cip && strcasecmp($cip, 'unknown')) {
            $onlineip = $cip;
        } elseif ($xip && strcasecmp($xip, 'unknown')) {
            $onlineip = $xip;
        } elseif ($rip && strcasecmp($rip, 'unknown')) {
            $onlineip = $rip;
        } elseif ($srip && strcasecmp($srip, 'unknown')) {
            $onlineip = $srip;
        }
        preg_match("/[\d\.]{7,15}/", $onlineip, $match);
        $onlineip = $match[0] ? $match[0] : 'unknown';
        return $onlineip;
    }

    /**
     * helper to try to sort out headers for people who aren't running apache,
     * or people who are running PHP as FastCGI.
     *
     * @return array of request headers as associative array.
     */
    public static function get_all_headers()
    {
        $retarr = array();
        $headers = array();

        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            ksort($headers);
            return $headers;
        } else {
            $headers = array_merge($_ENV, $_SERVER);

            foreach ($headers as $key => $val) {
                //we need this header
                if (strpos(strtolower($key), 'content-type') !== FALSE)
                    continue;
                if (strtoupper(substr($key, 0, 5)) != "HTTP_")
                    unset($headers[$key]);
            }
        }

        //Normalize this array to Cased-Like-This structure.
        foreach ($headers AS $key => $value) {
            $key = preg_replace('/^HTTP_/i', '', $key);
            $key = str_replace(
                " ",
                "-",
                ucwords(strtolower(str_replace(array("-", "_"), " ", $key)))
            );
            $retarr[$key] = $value;
        }
        ksort($retarr);

        return $retarr;
    }

    /**
     * Parse the oauth parameters from the request headers
     * Looks for something like:
     *
     * Authorization: Bearer G8uaTDyexXwyBDLn04iaeO0O4HXoJA
     *
     * @param array $headers
     * @return string
     */
    private function get_access_token($headers)
    {
        if (isset($headers['Authorization'])) {
            $auth = trim($headers['Authorization']);
            if (strncasecmp($auth, 'Bearer', 6) == 0) {
                return substr($auth, 7);
            }
        }
        return '';
    }

}
