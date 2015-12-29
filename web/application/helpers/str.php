<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * SNS helper class.
 *
 */
class str_Core {

    //转译html标签
    public static function unhtmlspecialchars( $string )
    {
      $string = str_replace ( '&amp;', '&', $string );
      $string = str_replace ( '&#039;', '\'', $string );
      $string = str_replace ( '&quot;', '"', $string );
      $string = str_replace ( '&lt;', '<', $string );
      $string = str_replace ( '&gt;', '>', $string );
      $string = str_replace ( '&uuml;', '', $string );
      $string = str_replace ( '&Uuml;', '', $string );
      $string = str_replace ( '&auml;', '', $string );
      $string = str_replace ( '&Auml;', '', $string );
      $string = str_replace ( '&ouml;', '', $string );
      $string = str_replace ( '&Ouml;', '', $string );
      return $string;
    }
    
	/**
	 * 截取中文字符
	 */
	public static function cnSubstr($string, $start, $length, $charset="utf-8", $suffix=false) 
	{ 
		if(strlen($string)>$length){
			if(function_exists("mb_substr")){
				$slice = mb_substr($string, $start, $length, $charset);
			}elseif(function_exists('iconv_substr')){
				$slice = iconv_substr($string,$start,$length,$charset);
			}else{
				$re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
				$re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
				$re['gbk']	  = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
				$re['big5']	  = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
				preg_match_all($re[$charset], $string, $match);
				$slice = join("",array_slice($match[0], $start, $length));
			}
			if($suffix){
				return $slice.$suffix;
			}
			return $slice;
		} else {
			return $string;
		}
	}
	

	public static function strLen($str) {
		$i = 0;
		$str = preg_replace('#^(https?)://[-A-Z0-9+&@\#/%?=~_|!:,.;]*[-A-Z0-9+&@\#/%=~_|]#i', '', trim($str));
		$str = preg_replace_callback(
                                    '#\b(https?)://[-A-Z0-9+&@\#/%?=~_|!:,.;]*[-A-Z0-9+&@\#/%=~_|]#i',
                            create_function(
                                    '$matches',
                                    'if(strlen($matches[0])>24) { return "一二三四五六七八九十一二";} else { return str_repeat("a", strlen($matches[0])); }'
                            ),
                            $str
                    );

		preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/", $str, $match);
		foreach ($match[0] as $val) {
			$i = ord($val) > 127 ? $i+2 : $i+1;
		}
		return ceil($i/2);
	}
	

    /**
    * 生产随机码
    */
    public static function rand_number($len=6) {
    	$chars='123456789';
        mt_srand((double)microtime()*1000000*getmypid());
        $password='';
        while(strlen($password)<$len)
        	$password.=substr($chars,(mt_rand()%strlen($chars)),1);
        return $password;
	}
}
