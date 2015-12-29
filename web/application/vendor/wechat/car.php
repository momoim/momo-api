<?php

class car {
	
	function _fopen($url, $limit = 0, $post = '',$type='GET', $cookie = '', $bysocket = FALSE, $ip = '', $timeout = 120, $block = TRUE,$referer='')
	{
	 	$return = '';
        $hd = "";
        $matches = parse_url($url);
        !isset($matches['host']) && $matches['host'] = '';
        !isset($matches['path']) && $matches['path'] = '';
        !isset($matches['query']) && $matches['query'] = '';
        !isset($matches['port']) && $matches['port'] = '';
        $host = $matches['host'];
        $path = $matches['path'] ? $matches['path'].($matches['query'] ? '?'.$matches['query'] : '') : '/';
        $port = !empty($matches['port']) ? $matches['port'] : 80;
        if($type != 'GET') {
            $content_length = strlen((string)$post);

            $out = "$type $path HTTP/1.0\r\n";
            $out .= "Host:fzjj.easdo.com\r\n";
            $out .= "User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:18.0) Gecko/20100101 Firefox/18.0\r\n";
            $out .= "Accept: application/json, text/javascript, */*; q=0.01\r\n";
            $out .= "Accept-Language: zh-cn,zh;q=0.8,en-us;q=0.5,en;q=0.3\r\n";
            $out .= "Content-Type: application/x-www-form-urlencoded; charset=UTF-8\r\n";
            $out .= "X-Requested-With: XMLHttpRequest\r\n";
            $out .= "Content-Length: $content_length\r\n";
            $out .= "Cookie: $cookie;\r\n";
            $out .= "Connection: keep-alive\r\n";
            $out .= "Pragma: no-cache\r\n";
            $out .= "Cache-Control: no-cache\r\n\r\n";
            $out .= $post;
        } else {
            $out = "GET $path HTTP/1.0\r\n";
            $out .= "Host:fzjj.easdo.com\r\n";
            $out .= "User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:18.0) Gecko/20100101 Firefox/18.0\r\n";
            $out .= "Accept: image/png,image/*;q=0.8,*/*;q=0.5\r\n";
            $out .= "Accept-Language: zh-cn,zh;q=0.8,en-us;q=0.5,en;q=0.3\r\n";
            $out .= "Cookie:$cookie;\r\n";
            $out .= "Connection: keep-alive\r\n\r\n";
        }
        //var_dump($out);
        $fp = @fsockopen(($ip ? $ip : $host), $port, $errno, $errstr, $timeout);
        if(!$fp) {
            return '';//note $errstr : $errno \r\n
        } else {
            stream_set_blocking($fp, $block);
            stream_set_timeout($fp, $timeout);
            @fwrite($fp, $out);
            $status = stream_get_meta_data($fp);
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
}

?>

