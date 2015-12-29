<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Model base class.
 *
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007-2009 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Model extends Model_Core {

    protected $uap;
    public $session = '';
    public $uid = '';
    protected $oauth_server;
    protected $authorized = false;

    /**
     * Loads the database instance, if the database is not already loaded.
     *
     * @return  void
     */
    public function __construct() {
        parent::__construct();
        $this->session = Session::instance();
        $this->uid = $this->session->get('uid');
    }


    //时间戳转换成16位的字符串
    public function setDate($date) {
        return date('YmdHis', $date);
    }

    //16位的字符串转换成时间戳
    public function getTime($date) {
        $year=((int)substr($date,0,4));//取得年份
        $month=((int)substr($date,4,2));//取得月份
        $day=((int)substr($date,6,2));//取得几号
        return mktime(0,0,0,$month,$day,$year);
    }


    /**
     * UAP SDK 获取用户名头像
     * @parma $uid 用户ID
     * @success string url
     */
    public function getAvatar($uid) {
        return $this->uapserver_host.'avatar.php?uid='.$uid.'&size=middle';
    }


    /**
     * 获取当前登录用户 ID
     * @return <type>
     */
    public function getUid() {
        $session = Session::instance();
        $uid = $session->get('uid');
        return $uid ? $uid : NULL;
    }


    /**
     * 消息推送
     * @param <type> $url
     * @param <type> $limit
     * @param <type> $post
     * @param <type> $type
     * @param <type> $cookie
     * @param <type> $bysocket
     * @param <type> $ip
     * @param <type> $timeout
     * @param <type> $block
     * @return <type>
     */
    public function _uc_fopen($url, $limit = 0, $post = '', $type='GET', $cookie = '', $bysocket = FALSE, $ip = '', $timeout = 15, $block = TRUE) {
        $return = '';
        $matches = parse_url($url);
        !isset($matches['host']) && $matches['host'] = '';
        !isset($matches['path']) && $matches['path'] = '';
        !isset($matches['query']) && $matches['query'] = '';
        !isset($matches['port']) && $matches['port'] = '';
        $host = $matches['host'];
        $path = $matches['path'] ? $matches['path'].($matches['query'] ? '?'.$matches['query'] : '') : '/';
        $port = !empty($matches['port']) ? $matches['port'] : 80;
        if($type != 'GET') {
            $out = "$type $path HTTP/1.0\r\n";
            $out .= "Accept: application/json\r\n";
            $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $out .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
            $out .= "Host: $host\r\n";
            $out .= 'Content-Length: '.strlen((string)$post)."\r\n";
            $out .= "Connection: Close\r\n";
            $out .= "Cache-Control: no-cache\r\n";
            $out .= "Cookie: $cookie\r\n\r\n";
            $out .= $post;
        } else {
            $out = "GET $path HTTP/1.0\r\n";
            $out .= "Accept: application/json\r\n";
            $out .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
            $out .= "Host: $host\r\n";
            $out .= "Connection: Close\r\n";
            $out .= "Cookie: $cookie\r\n\r\n";
        }

        $fp = @fsockopen(($ip ? $ip : $host), $port, $errno, $errstr, $timeout);
        if(!$fp) {
            return '';//note $errstr : $errno \r\n
        } else {
            stream_set_blocking($fp, $block);
            stream_set_timeout($fp, $timeout);
            @fwrite($fp, $out);
            $status = stream_get_meta_data($fp);
            $hd = '';
            if(!$status['timed_out']) {
                while (!feof($fp)) {
                    if(($header = @fgets($fp)) && ($header == "\r\n" ||  $header == "\n")) {
                        break;
                    }
                    $hd .= $header;
                }
                $stop = false;
                while(!feof($fp) && !$stop) {
                    $data = fread($fp, ($limit == 0 || $limit > 8192 ? 8192 : $limit));
                    $return .= $data;
                    if($limit) {
                        $limit -= strlen($data);
                        $stop = $limit <= 0;
                    }
                }
            }
            @fclose($fp);

            return array('header'=>$hd, 'data'=>$return);
        }
    }
    

    public  function mq_send($message, $routekey, $exchange='momo_nd'){
		mq::send($message, $routekey, $exchange, 0, (int)($this->uid));
	}

    public function mq_uid() {
        return md5(($this->getUid()).'wefwj3453fewf');
        //return $this->getUid();
    }
	
	public function atLink($content, $at){
		if($at && $content){
			foreach($at as $k => $v){
				$link = '<a target="_blank" data-uid="'.$v['id'].'" href="'.url::base().'user/'.$v['id'].'">@'.$v['name'].'</a>';
				$str = '[@'.$k.']';
				$content = str_replace($str, $link, $content);
			}			
		}
		return $content;
	}
	
	public function client_from($id){
            $id = is_numeric($id)?$id:0;
            return api::get_source_name($id);
            
	}


        /**
         * 请求soap调用
         */
        public function _soap_do($interface,$uri,$op,$args, $args2,$part=80) {
            $content = '';
            $ret = '';

            $fp = fsockopen($interface, $part, $errno, $errstr);
            if (!$fp){
                    return false;
            }else
            {
                    $content .= '<?xml version="1.0" encoding="utf-8"?>';
                    $content .= '<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">';
                    $content .= '<soap:Header>';
                    $content .='<UserNameToken xmlns="http://tempuri.org/">';
                    if(is_array($args2))
                    {
                            foreach ( $args2 as $key2=>$value2)
                                    $content .= '<' . $key2 . '>' . $value2 . '</' . $key2 . '>';
                    }
                    $content .= '</UserNameToken>';
                    $content .= '</soap:Header>';
                    $content .= '<soap:Body>';
                    $content .= '<' . $op . ' xmlns="http://tempuri.org/">';
                    if ( is_array($args))
                    {
                            foreach ( $args as $key=>$value)
                                    $content .= '<' . $key . '>' . $value . '</' . $key . '>';
                    }
                    $content .= '</' . $op . '>';
                    $content .= '</soap:Body>';
                    $content .= '</soap:Envelope>';

                    //die;
                    $out = "POST /".$uri." HTTP/1.1\r\n";
                    $out .= "Host: " .$interface . "\r\n";
                    $out .= "Content-Type: text/xml; charset=utf-8\r\n";
                    $out .= "Content-Length: " . strlen($content) . "\r\n";
                    $out .= "Connection: Close \r\n";
                    $out .= "SOAPAction: \"http://tempuri.org/" . $op . "\"\r\n\r\n";

                    fwrite($fp, $out . $content);
                    while (!feof($fp))
                    {
                            $ret .= fgets($fp, 128);
                    }
                    fclose($fp);

                    if ( preg_match('/<soap:Body>(.+)<\/soap:Body>/', $ret, $mc) )
                            preg_match_all('/<([^>\/]+)>([^>\/]+)<\/([^>\/]+)>/', $mc[1], $tmp);

                    if ( count($tmp) != 4 )
                            return false;
                    else
                            return array_combine($tmp[1], $tmp[2]);
            }
        }

        
} // End Model
