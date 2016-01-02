<?php
class Core {
    
    public static $start_time = 0;
    
    private static $user_id = 0;

    private static $client_id = 0;
    
    private static $appid = 0;

    private static $mongo = NULL;

    private static $config = array();
    //获取配置字典
    public static function config($key) {
        $scope = explode('.', $key);
        if(count($scope) < 2) {
            $scope = array('filesystem', $key);
            $path = FS_ROOT;
        } else {
            $path = substr(FS_ROOT, 0, - 19) . 'application' . DS;
        }
        if(!isset(self::$config[$scope[0]])) {
            require $path . 'config' . DS . $scope[0] . '.php';
            self::$config[$scope[0]] = $config;
        }
        return self::$config[$scope[0]][$scope[1]];
    }
    //截取混合字符
    public static function cutstr($sourcestr, $cutlength, $append = FALSE) {
        $fontwidth = 0;
        //字宽
        $returnstr = '';
        $i = 0;
        $str_length = strlen($sourcestr);
        while(($fontwidth < $cutlength) and ($i <= $str_length)) {
            $temp_str = substr($sourcestr, $i, 1);
            $ascnum = ord($temp_str);
            //11110xxx 10xxxxxx 10xxxxxx 10xxxxxx 4字节
            if($ascnum >= 224) {
                //1110xxxx 10xxxxxx 10xxxxxx 3字节
                $returnstr = $returnstr . substr($sourcestr, $i, 3);
                $i = $i + 3;
                $fontwidth = $fontwidth + 2;
            } elseif($ascnum >= 192) {
                //110xxxxx 10xxxxxx 双字节
                $returnstr = $returnstr . substr($sourcestr, $i, 2);
                $i = $i + 2;
                $fontwidth = $fontwidth + 2;
                //			}elseif($ascnum>=65 && $ascnum<=90){
                //				$returnstr=$returnstr.substr($sourcestr,$i,1); //如果是大写字母
                //				$i=$i+1;
                //				$fontwidth=$fontwidth+1;
            } else {
                //0xxxxxxx 单字节
                $returnstr = $returnstr . substr($sourcestr, $i, 1);
                $i = $i + 1;
                $fontwidth = $fontwidth + 1;
            }
        }
        if($append && ($str_length > $cutlength)) {
            $returnstr = $returnstr . "...";
        }
        return $returnstr;
    }

    public static function convertToUTF8($str) {
        $enc = mb_detect_encoding($str, array('UTF-8', 'NONE'));
        if($enc != 'UTF-8') {
            return utf8_encode($str);
        } else {
            return $str;
        }
    }
    //http头信息
    public static function header($string, $replace = true, $http_response_code = 0) {
        $string = str_replace(array("\r", "\n"), array('', ''), $string);
        if(empty($http_response_code) || PHP_VERSION < '4.3') {
            @header($string, $replace);
        } else {
            @header($string, $replace, $http_response_code);
        }
        if(preg_match('/^\s*location:/is', $string)) {
            self::quit();
        }
    }
    //过滤反斜杠
    public static function addslashes_deep($string, $force = 0) {
        !defined('MAGIC_QUOTES_GPC') && define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc());
        if(!MAGIC_QUOTES_GPC || $force) {
            if(is_array($string)) {
                foreach($string as $key => $val) {
                    $string[$key] = gaddslashes($val, $force);
                }
            } else {
                $string = addslashes($string);
            }
        }
        return $string;
    }
    //过滤html特殊字符
    public static function htmlspecialchars_deep($string) {
        if(is_array($string)) {
            foreach($string as $key => $val) {
                $string[$key] = ghtmlspecialchars($val);
            }
        } else {
            //$string = preg_replace('/&amp;((#(\d{3,5}|x[a-fA-F0-9]{4})|[a-zA-Z][a-z0-9]{2,5});)/', '&\\1',
            $string = preg_replace('/&amp;((#(\d{3,5}|x[a-fA-F0-9]{4}));)/', '&\\1', str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $string));
        }
        return $string;
    }
    //替换字符串
    public static function sprintf($var, $args) {
        if(!$args || !is_array($args)) {
            return $var;
        }
        $pattern = array();
        for($i = 1;$i <= count($args);$i++) {
            $pattern[] = '{' . $i . '}';
        }
        return str_replace($pattern, $args, $var);
    }
    //输出调试信息
    public static function debug($file, $log) {
        $yearmonth = date('Ym', time());
        $logdir = self::config('dir_log');
        $logfile = $logdir . $yearmonth . '_' . $file . '.php';
        $newfile = FALSE;
        if(@filesize($logfile) > 20971520) {
            $newfile = TRUE;
            $dir = opendir($logdir);
            $length = strlen($file);
            $maxid = $id = 0;
            while($entry = readdir($dir)) {
                if(strpos($entry, $yearmonth . '_' . $file) !== FALSE) {
                    $id = intval(substr($entry, $length + 8, - 4));
                    $id > $maxid && $maxid = $id;
                }
            }
            closedir($dir);
            $logfilebak = $logdir . $yearmonth . '_' . $file . '_' . ($maxid + 1) . '.php';
            @rename($logfile, $logfilebak);
        }
        if($fp = @fopen($logfile, 'a')) {
            @flock($fp, 2);
            if($newfile) {
                fwrite($fp, "<?PHP exit;?>\n");
            }
            $log = is_array($log) ? $log : array($log);
            foreach($log as $tmp) {
                fwrite($fp, date('Y-m-d H:i:s') . "\t" . str_replace(array('<?', '?>'), '', $tmp) . "\n");
            }
            fclose($fp);
        }
    }
    //获取mongo数据库连接
    public static function getMongo($host, $opt) {
        //echo json_encode(debug_backtrace());
        if(!self::$mongo[$host]) {
            self::$mongo[$host] = new MongoClient($host, $opt);
        }
        return self::$mongo[$host];
    }

    public static function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64url_decode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
    /**
     *  
     * @param $string 预加密或解密的字符串
     * @param $operation 加密：ENCODE 解密：DECODE(默认)
     */
    public static function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
        $ckey_length = 0;
        //字符串每次可变
        $key = md5($key ? $key : 'NULL');
        $keya = md5(substr($key, 7, 13));
        $keyb = md5(substr($key, 18, 11));
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), - $ckey_length)) : '';
        $cryptkey = $keya . md5($keya . $keyc);
        $key_length = strlen($cryptkey);
        $string = $operation == 'DECODE' ? self::base64url_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
        $string_length = strlen($string);
        $result = '';
        $box = range(0, 255);
        $rndkey = array();
        for($i = 0;$i <= 255;$i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }
        for($j = $i = 0;$i < 256;$i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        for($a = $j = $i = 0;$i < $string_length;$i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i])^($box[($box[$a] + $box[$j]) % 256]));
        }
        if($operation == 'DECODE') {
            if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            return $keyc . self::base64url_encode($result);
        }
    }
    //获得加密字符串
    public static function getSourceID($raw_sid) {
        $args = func_get_args();
        $p = implode("\t", $args);
        $source_key = self::config('source_key');
        //如果是图片key与pid和ver有关
        $key = self::authcode($p, 'ENCODE', $source_key);
        return $raw_sid . '_' . $key;
    }
    //验证加密字符串
    public static function validSourceID($sid) {
        list($raw_sid, $key) = explode('_', $sid, 2);
        if($raw_sid == '') 
            return FALSE;
        $source_key = self::config('source_key');
        $p = self::authcode($key, 'DECODE', $source_key);
        $raw_arr = explode("\t", $p);
        if($raw_arr[0] == $raw_sid) 
            return $raw_arr;
        else 
            return FALSE;
    }
    //生成临时文件名
    private static $tempname_list = array();
    public static function tempname($filename = '') {
        $tmpdir = self::config('dir_tmp');
        if(!is_dir($tmpdir)) 
            mkdir($tmpdir, 0777);
        
        if($filename) {
            $tempname = $tmpdir . $filename;
        }else{
            $tempname = tempnam($tmpdir, 'buf_');
        }
        array_push(self::$tempname_list, $tempname);
        return $tempname;
    }
    
    public static function tempname_clear(){
        foreach (self::$tempname_list as $tempname){
            @unlink($tempname);
        }
    }

    public static function getUserID() {
        return self::$user_id;
    }

    public static function setUserID($user_id) {
        self::$user_id = $user_id;
    }

    public static function getClientID() {
        return self::$client_id;
    }

    public static function setClientID($client_id) {
        self::$client_id = $client_id;
    }
    
    public static function getAPPID(){
        return self::$appid;
    }
    
    public static function setAPPID($appid){
        self::$appid = $appid;
    }
    //初始gridfs连接参数
    public static function initGridFS($dbname) {
        ActiveMongo::disconnect();
        $gridfs_conf = self::config('gridfs_servers');
        ActiveMongo::connect($gridfs_conf['db'][$dbname], $gridfs_conf['host'], $gridfs_conf['user'], $gridfs_conf['pwd'], $gridfs_conf['opt']);
    }

    public static function get_object_public_vars($obj) {
        return get_object_vars($obj);
    }

    public static function cmdRun($cmd, &$code) {
        $descriptorspec = array(0 => array("pipe", "r"), 1 => array("pipe", "w"), 2 => array("pipe", "w"));
        $pipes = array();
        $process = proc_open($cmd, $descriptorspec, $pipes);
        $output = "";
        if(!is_resource($process)) 
            return false;
        #close child's input imidiately
        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $todo = array($pipes[1], $pipes[2]);
        while(true) {
            $read = array();
            if(!feof($pipes[1])) 
                $read[] = $pipes[1];
            if(!feof($pipes[2])) 
                $read[] = $pipes[2];
            if(!$read) 
                break;
            $write = $ex = array();
            $ready = stream_select($read, $write, $ex, 2);
            if($ready === false) {
                break;
                #should never happen - something died
            }
            foreach($read as $r) {
                $s = fread($r, 1024);
                $output .= $s;
            }
        }
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($process);
        return $output;
    }

    private static $trace = array();

    public static function addTrace($mixed) {
        array_push(self::$trace, $mixed);
    }

    public static function outTrace() {
        if(self::$trace) {
            if(isset($_SERVER['HTTP_MOMO_DEBUG_LEVEL']) && $_SERVER['HTTP_MOMO_DEBUG_LEVEL'] == 'print') {
                //只打印
                print_r(self::$trace);
            } else {
                //记录日志
                //MongoLogger::instance()->log('trace', self::$trace);
            }
        }
    }

    public static function fault($code = 500, $msg = '') {
        self::header('HTTP/1.1 ' . $code . ' ' . $msg);
        header_remove('Etag');
        header_remove('Last-Modified');
        header_remove('Cache-Control');
        header_remove('Pragma');
        self::quit();
    }

    public static function set_exception_handler() {
        set_error_handler(array('Core', 'exception_handler'));
        set_exception_handler(array('Core', 'exception_handler'));
    }
    
    public static function quit(){
        self::tempname_clear();
        Models\GridFS::fdfs_quit();
        
        if(self::$start_time) self::debug('trace', self::client_ip()."\t".self::$start_time."\t".microtime(TRUE));
        exit;
    }
    /**
     *
     * @param   integer|object  exception object or error code
     * @param   string          error message
     * @param   string          filename
     * @param   integer         line number
     * @return  void
     */
    public static function exception_handler($exception, $message = NULL, $file = NULL, $line = NULL) {
        //过滤掉警告和通知
        if($exception==E_NOTICE||$exception==E_WARNING){
            return;
        }
        $error = array('error' => array($exception, $message, $file, $line), 'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
        if(isset($_SERVER['HTTP_MOMO_DEBUG_LEVEL']) && $_SERVER['HTTP_MOMO_DEBUG_LEVEL'] == 'print') {
            //只打印
            print_r($error);
        } else {
            //记录日志
            //MongoLogger::instance()->log('exception_handler', $error);
        }
    }
    
    public static function parse_request_ranges(){
        $r = array();
        
        if (isset($_SERVER['HTTP_RANGE'])) {
            if (!preg_match('/^bytes=\d*-\d*(,\d*-\d*)*$/i', $_SERVER['HTTP_RANGE'])) {
                self::header('HTTP/1.1 416 Requested Range Not Satisfiable');
                self::quit();
            }
            $ranges = explode(',', substr($_SERVER['HTTP_RANGE'], 6));
            foreach ($ranges as $range) {
                $parts = explode('-', $range);
                $start = $parts[0]; //若为空则视为0
                $end = $parts[1]; //若为空或大于(filelength - 1)则视为(filelength - 1)
                $r[] = array($start,$end);
            }
        }
        
        return $r;
    }
    
    public static function header_disposition($filename){
        $pathinfo = pathinfo($filename);
        $filename = $pathinfo['basename'];
    
        header_remove('Content-Disposition');
    
        $ua = strtolower($_SERVER["HTTP_USER_AGENT"]);
        $encoded_filename = str_replace("+", "%20", urlencode($filename));
        if(preg_match("/msie/", $ua)) {
            self::header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
        } elseif(preg_match("/firefox/", $ua)) {
            self::header('Content-Disposition: attachment; filename*=UTF-8\'\'' . rawurlencode($filename));
        } else {
            self::header('Content-Disposition: attachment; filename="' . $filename . '"');
        }
    }
    
    public static function header_range($size, & $start, & $end, & $length){
        $start = 0;
        $end = $size - 1;
        $length = $size;
        
        self::header("Accept-Ranges: 0-$size");
        
        $ranges_arr = self::parse_request_ranges();
        if($ranges_arr){
            $ranges = $ranges_arr[0];
            if($ranges[0]==''){
                if($ranges[1]!=''){
                    $length = (int)$ranges[1];
                    $start = $size - $length;
                }else{
                    self::header('HTTP/1.1 416 Requested Range Not Satisfiable');
                }
            }else{
                $start = (int)$ranges[0];
                if($ranges[1]!=''){
                    $end = (int)$ranges[1];
                }
                $length = $end - $start + 1;
            }
            self::header('HTTP/1.1 206 PARTIAL CONTENT');
        }
        
        self::header("Content-Range:bytes {$start}-{$end}/{$size}");
        
        header_remove('Content-Length');
        
        self::header("Content-Length:$length");
    }
    
    public static function readfile($localfile, $offset, $length){
        $end = $offset + $length - 1;
        $buffer = 8096;
        $file = fopen($localfile, 'rb');
        if($file){
            fseek($file, $offset);
            while (!feof($file) && ($p = ftell($file)) <= $end){
                if ($p + $buffer > $end) {
                    $buffer = $end - $p + 1;
                }
                set_time_limit(0);
                echo fread($file, $buffer);
                flush();
            }
            fclose($file);
        }
    }
    
    public static function server_addr(){
        $ifconfig_out=self::cmdRun('/sbin/ifconfig', $cmd_error);
        if($ifconfig_out){
            preg_match('/(\d+\.\d+\.\d+\.\d+)/', $ifconfig_out, $ifconfig);
            return $ifconfig[1];
        }
        
        return '';
    }
    
    public static function attach_exif($jpegfile, $option){
        require_once FS_ROOT . 'include/PEL/PelJpeg.php';
        
        $jpeg = new PelJpeg($jpegfile);
        
        $exif = new PelExif();
        $jpeg->setExif($exif);
        
        $tiff = new PelTiff();
        $exif->setTiff($tiff);
        
        $ifd0 = new PelIfd(PelIfd::IFD0);
        $tiff->setIfd($ifd0);
        
        if(isset($option['orientation']) && $option['orientation'] > 0){
            $ifd0->addEntry(new PelEntryShort(PelTag::ORIENTATION, intval($option['orientation'])));
        }
//         $exif_ifd = new PelIfd(PelIfd::EXIF);
//         $exif_ifd->addEntry(new PelEntryUserComment('Hello World!'));
//         $ifd0->addSubIfd($exif_ifd);
        file_put_contents($jpegfile, $jpeg->getBytes());
    }
    
    public static function client_ip(){
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip=$_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip=$_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
}
