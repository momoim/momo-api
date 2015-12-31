<?php defined('SYSPATH') or die('No direct access allowed.');

/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * API辅助文件
 */
/**
 * api辅助类
 */
class api {
    
    private static $now = 0;
    
    /**
     * 转换时间戳
     * @param int $timestamp 时间戳
     */
    public static function get_date($timestamp = FALSE)
    {
        if (empty($timestamp)) {
            $timestamp = time();
        }
        return (int)$timestamp;
    }
    
    /**
     * 获取当前时间戳
     */
    public static function get_now_time()
    {
        if (empty(self::$now)) {
            self::$now = time();
        }
        return self::$now;
    }

    /**
     * 发送响应
     * @param int $response_code HTTP状态码
     * @param array $body_data 内容
     * @param string $error_msg 错误消息
     */
    public static function send_response($response_code = 200, $body_data = NULL, $error_msg = '', $exit = true)
    {
        $current_url = url::current();
        $dot_pos = strrpos($current_url, '.');
        $back_slash_pos = strpos($current_url, '/');
        $format = '';
        if ($dot_pos !== FALSE) {
            $format = substr($current_url, strrpos($current_url, '.') + 1);
        }
	    $rewrite_error_msg = $error_msg;
        switch ($response_code) {
            case E_KOHANA :
                $response_code = 503;
//                $error_msg = '服务端资源不可用';
                break;
            case E_PAGE_NOT_FOUND :
                $response_code = 404;
//                $error_msg = '请求资源不存在';
                break;
            case E_DATABASE_ERROR :
                $response_code = 500;
	            $rewrite_error_msg = '数据库错误';
		        break;
            case E_RECOVERABLE_ERROR :
            case E_ERROR :
            case E_USER_ERROR :
            case E_PARSE :
            case E_WARNING :
            case E_USER_WARNING :
            case E_STRICT :
            case E_NOTICE :
                $response_code = 500;
//                $error_msg = '服务器内部错误';
                break;
        }
        $code_message = self::get_status_code_message($response_code);
        if (empty($code_message)) {
            $response_code = 404;
            $code_message = self::get_status_code_message($response_code);
        }
        //@FIXME 暂时解决NULL输出方法，在完善后去除
        //输出内容过滤，确保不存在NULL，如果存在NULL则记录日志
        /*
        if ($body_data !== NULL and is_array($body_data)) {
            $tmp = $body_data;
        	array_walk_recursive($body_data, create_function('&$val, $key', '$val = $val === NULL ? "" : $val;'));
        	//输出包含NULL，记录日志
            if($tmp !== $body_data) {
                ob_start();
                var_dump($tmp);
                $dump = ob_get_clean();
                self::log('error', 'output error!! request '. '/' . url::current(TRUE)."\n"."ouput is ".$dump);                 
            }
            unset($tmp, $dump);
        }
        */
	    $errors = explode(':', $rewrite_error_msg);
	    $error_code = is_numeric($errors[0]) ? $errors[0] : $response_code;
	    //$rewrite_error_msg = (is_numeric($errors[0]) AND !empty($errors[1])) ? $errors[1] : $rewrite_error_msg;
        switch ($format) {
            case 'xml' :
                $content_type = 'text/xml';
                $array_to_xml = new Array_To_Xml();
                $root = '';
                if ($back_slash_pos === FALSE) {
                    $root = substr($current_url, 0, $dot_pos);
                } else {
                    $root = substr($current_url, 0, $back_slash_pos);
                }
                $array_to_xml->set_root($root);
                if ($response_code >= 200 && $response_code < 400) {
                    $body = $body_data !== NULL ? $array_to_xml->to_xml($body_data) : '';
                } else {
                    $array_to_xml->set_root('hash');
                    $error = $array_to_xml->to_xml(array('error_code' => $error_code, 'request' => '/' . url::current(TRUE), 'error' => $rewrite_error_msg));
                }
                break;
            default :
                $content_type = 'application/json';
                if ($response_code >= 200 && $response_code < 400) {
                    $body = is_array($body_data) ? json_encode($body_data) : ($body_data ? $body_data : '');
                } else {
                    $error = json_encode(array('error_code' => $error_code, 'request' => '/' . url::current(TRUE), 'error' => $rewrite_error_msg));
                }
                break;
        }
        
        $response_code_header = 'HTTP/1.1 ' . $response_code . ' ' . $code_message;
        
        header($response_code_header);
        header('Content-Type: ' . $content_type);
        if ($response_code >= 200 && $response_code < 400) {
            echo $body;
        } else {
            echo $error;
        }
        header('Content-Length: ' . ob_get_length());

        //先返回结果，长时间操作继续执行
        if (function_exists ( 'fastcgi_finish_request' )) {
        	fastcgi_finish_request ();
        }
//        self::save_log($response_code, $error_msg);
        if ($exit) {
	        exit();
        }
    }

    /**
     * 获取状态码对应内容
     * @param int $response_code HTTP状态码
     * @return string 状态码对应内容
     */
    public static function get_status_code_message($response_code)
    {
        $codes = array(
                100 => 'Continue', 
                101 => 'Switching Protocols', 
                200 => 'OK', 
                201 => 'Created', 
                202 => 'Accepted', 
                203 => 'Non-Authoritative Information', 
                204 => 'No Content', 
                205 => 'Reset Content', 
                206 => 'Partial Content', 
                207 => 'Multi-Status', 
                300 => 'Multiple Choices', 
                301 => 'Moved Permanently', 
                302 => 'Found', 
                303 => 'See Other', 
                304 => 'Not Modified', 
                305 => 'Use Proxy', 
                306 => '(Unused)', 
                307 => 'Temporary Redirect', 
                400 => 'Bad Request', 
                401 => 'Unauthorized', 
                402 => 'Payment Required', 
                403 => 'Forbidden', 
                404 => 'Not Found', 
                405 => 'Method Not Allowed', 
                406 => 'Not Acceptable', 
                407 => 'Proxy Authentication Required', 
                408 => 'Request Timeout', 
                409 => 'Conflict', 
                410 => 'Gone', 
                411 => 'Length Required', 
                412 => 'Precondition Failed', 
                413 => 'Request Entity Too Large', 
                414 => 'Request-URI Too Long', 
                415 => 'Unsupported Media Type', 
                416 => 'Requested Range Not Satisfiable', 
                417 => 'Expectation Failed',
                500 => 'Internal Server Error', 
                501 => 'Not Implemented', 
                502 => 'Bad Gateway', 
                503 => 'Service Unavailable', 
                504 => 'Gateway Timeout', 
                505 => 'HTTP Version Not Supported');
        return (isset($codes[$response_code])) ? $codes[$response_code] : '';
    }

	/**
     * 保存访问日志
     * @param int $response_code 响应状态码
     * @param stirng $error 错误消息
     */
	public static function save_log ($response_code, $error_msg)
	{
		try
		{
			$session = Session::instance();
			$request_time = $session->get('request_time', 0);
			$exec_time = microtime(TRUE) - $request_time;
			$user_id = (int) $session->get('uid', 0);
			$url = url::current();
			$source = $session->get('source') !== NULL ? (int) $session->get(
			'source') : -1;
			$appid = (int) $session->get('appid', 0);
			$token_id = (int) $session->get('token_id', 0);
			$sets = array(
				'uid' => $user_id, 'source' => $source,'appid' => $appid, 'url' => $url, 
				'request_method' => $_SERVER['REQUEST_METHOD'], 
				'request_time' => $request_time, 'exec_time' => $exec_time, 
				'response_code' => $response_code, 'token_id' => $token_id, 
				'msg' => '"' . $error_msg . '"'
			);
			$output = '';
			foreach ($sets as $key => $val)
			{
				$output .= "$key = $val, ";
			}
			require_once APPPATH . 'vendor/log4php/Logger.php';
			Logger::configure(APPPATH . 'config/log4php.properties');
			$logger = Logger::getLogger('api_access');
			$logger->debug(trim($output));
			if ($response_code >= 500)
			{
				$msg = 'request~' . self::get_my_ip() . '`url~';
				if ($_SERVER['REQUEST_METHOD'] == 'GET')
				{
					list ($url, $input) = explode('?', url::current(TRUE));
					$msg .= $url . '`input~' . $input;
				}
				else
				{
					$msg .= url::current() . '`input~' .
					 print_r(file_get_contents('php://input'), TRUE);
				}
				$error = "uid~$user_id`source~$source`" . $msg .
				 "`code~{$response_code}`error~" . $error_msg;

				//增加UA、authorization和客户端IP
				$error .= '`user_agent~' .(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
				$error .= '`authorization~' . (isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '').
					'`client_ip~' .self::get_client_ip();
				if ($response_code == 500)
				{
					$trace = '';
					foreach (debug_backtrace() as $k => $v)
					{
						if ($v['function'] == "include" ||
						 $v['function'] == "include_once" ||
						 $v['function'] == "require_once" ||
						 $v['function'] == "require")
						{
							$trace .= "#" . $k . " " . $v['function'] . "(" .
							 $v['args'][0] . ") called at [" . $v['file'] . ":" .
							 $v['line'] . "]\n";
						}
						else
						{
							$trace .= "#" . $k . " " . $v['function'] .
							 "() called at [" . $v['file'] . ":" . $v['line'] .
							 "]\n";
						}
					}
					$error .= '`trace~' . $trace;
				}
				$logger = Logger::getLogger('api_error');
				$logger->error($error);
			}
		}
		catch (Exception $e)
		{}
	}
    
    /**
     * 获取当前服务器IP
     * @param string $dest
     * @param int $port
     * @return string
     */
    public static function get_my_ip($dest='64.0.0.0', $port=80)
    {
    	$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    	socket_connect($socket, $dest, $port);
    	socket_getsockname($socket, $addr, $port);
    	socket_close($socket);
    	return $addr;
    }
    
    /**
     * 获取来源名称
     * @param int $id 来源ID
     * @return string 
     */
	public static function get_source_name($id)
	{
		static $name = array();
		if (empty($name[$id]))
		{
			if (is_numeric($id))
			{
				if ($id == 0)
				{
					$name[$id] = '网站';
				}
				elseif ($id <= 12)
				{
					$db = Database::instance();
					$query = $db->select('osr_application_title')->from('oauth_server_registry')->where('osr_id', $id)
						->get();
					$result = $query->result_array(FALSE);
					$name[$id] = isset($result[0]['osr_application_title']) ? $result[0]['osr_application_title'] : '';
				}
				else
				{
					$name[$id] = $id;
				}
			}
			else
			{
				$name[$id] = $id;
			}
		}
		return $name[$id];
	}

	/**
	 * 获取应用名称
	 * @param int $id 应用ID
	 * @return string
	 */
	public static function get_app_name($id)
	{
		static $name = array();
		if (empty($name[$id]))
		{
			if ($id <= 11) {
				$name[$id] = 'MOMO';
			} else {
				$db = Database::instance();
				$query = $db->select('osr_application_title')->from('oauth_server_registry')->where('osr_id', $id)->get();
				$result = $query->result_array(FALSE);
				$name[$id] = isset($result[0]['osr_application_title']) ? $result[0]['osr_application_title'] : '';
			}
		}
		return $name[$id];
	}

    /**
     * 获取来源app key
     * @param int $id 来源ID
     * @return string 
     */
    public static function get_source_app_key($id)
    {
        $db = Database::instance();
        $query = $db->select('osr_consumer_key')->from('oauth_server_registry')->where('osr_id', $id)->get();
        $result = $query->result_array(FALSE);
        $app_key = isset($result[0]['osr_consumer_key']) ? $result[0]['osr_consumer_key'] : '';
        return $app_key;
    }
    
	/*
	 * 截取固定长度限UTF-8
	 */
    public static function cutFixLen($str, $len, $suffix=true)
    {   
        $tstr ='';
    	preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/", $str, $match);
    	foreach ($match[0] as $val) {
    		$i = ord($val) > 127 ? $i+2 : $i+1;
    		$tstr .= $val;
    		if ($i>$len) break;
    	}
        if ($tstr != $str && $suffix==true) {   
            $tstr .= '...';   
        }   
        return $tstr; 
    }
    
	/**
     * 写日志
     * @static
     * @param string $type 日志类型
     * @param string $str 日志内容
     */
    public static function log ($type, $str = '', $filename = '', $clear = FALSE)
    {
        $filename = empty($filename) ? str_replace('.', '_', 
        str_replace(array('http://', '/'), '', url::base())) : $filename;
        // Filename of the log
        $filename = Kohana::config('config.log_directory') . '/' .
         $filename . '_' . date('Y-m-d') . '.log' . EXT;
        if (! is_file($filename) or $clear) {
            file_put_contents($filename, 
            '<?php defined(\'SYSPATH\') or die(\'No direct script access.\'); ?>' .
             PHP_EOL . PHP_EOL);
            // Prevent external writes
            chmod($filename, 0644);
        }
        file_put_contents($filename, 
        date('Y-m-d H:i:s P') . ' --- [' . $type . ']: ' . $str . "\n", 
        FILE_APPEND);
    }
    
    /**
     * 字数统计
     * @param string $str
     * @return int
     */
    public static function word_count($str)
    {
        return ceil(strlen(iconv("utf-8", "gbk", $str))/2);
    }
    
	/**
	 * 还原短地址
	 * @param string $url
	 */
	public function getOriginalUrl($shorturl)
	{
		$apiUrl		= YOURLS_SITE.'/yourls-api.php';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $apiUrl);
		curl_setopt($ch, CURLOPT_HEADER, 0);            // No header in the result
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return, do not echo result
		curl_setopt($ch, CURLOPT_POST, 1);              // This is a POST request
		curl_setopt($ch, CURLOPT_POSTFIELDS, array(     // Data to POST
				'shorturl'      => $shorturl,
				'keyword'  => '',
				'format'   => '',
				'action'   => 'expand',
				'username' => 'xilin',
				'password' => 'best!author99'
		));			
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
	

	/**
	 * 
	 * 构造消息体id
	 */
	public static function uuid() {
	    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
	        // 32 bits for "time_low"
	        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
	        // 16 bits for "time_mid"
	        mt_rand( 0, 0xffff ),
	        // 16 bits for "time_hi_and_version",
	        // four most significant bits holds version number 4
	        mt_rand( 0, 0x0fff ) | 0x4000,
	        // 16 bits, 8 bits for "clk_seq_hi_res",
	        // 8 bits for "clk_seq_low",
	        // two most significant bits holds zero and one for variant DCE1.1
	        mt_rand( 0, 0x3fff ) | 0x8000,
	        // 48 bits for "node"
	        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
	    );
	}

	/**
	 * fetch user ip address.
	 *
	 * @param
	 * @return  string
	 */
	public static function get_client_ip()
	{
		if (getenv('HTTP_CLIENT_IP'))
			$ip = getenv('HTTP_CLIENT_IP');
		else if (getenv('HTTP_X_FORWARDED_FOR'))
			$ip = getenv('HTTP_X_FORWARDED_FOR');
		else if (getenv('REMOTE_ADDR'))
			$ip = getenv('REMOTE_ADDR');
		else
			$ip = $_SERVER['REMOTE_ADDR'];
		return $ip;
	}  
	
} // End api
