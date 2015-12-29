<?php
/**
 +-------------------------------------------------------------------------------------------
 * @project ComProject
 * @package 91PM
 * @author Mc@Spring <Heresy.Mc@gmail.com>
 * @todo TODO
 * @update Modified on 2008-8-25 by Mc@Spring at ����10:24:17
 * @link http://groups.google.com/group/mspring
 * @copyright Copyright (C) 2007-2008 Mc@Spring. All rights reserved.
 *
 * 					Licensed under The Apache License
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License
 +-------------------------------------------------------------------------------------------
 */
 // (true === SO_SYS_ACCESS) || exit ('System access denied!');

/*
 +----------------------------------------------------------
 * 密码加/解密算法示例
 +----------------------------------------------------------
 * 私密设置/获取
 * MD5Crypt::setKey('key');
 * MD5Crypt::getKey(); // 返回当前的私密值
 +----------------------------------------------------------
 * 字符串加/解密
 * $string = 'Mc@Spring';
 * $encrypt = MD5Crypt::encrypt($string); // 返回加密后的值
 * $decrypt = MD5Crypt::decrypt($encrypt); // 返回解密后的值
 */
class MD5Crypt {
	/*
	 +----------------------------------------------------------
	 * 密钥
	 +----------------------------------------------------------
	 * @var string $key
	 +----------------------------------------------------------
	 * @access public
	 */
	protected static $key = 'ab343ty';

	private function __construct(){}

	/**
	 +----------------------------------------------------------
	 * 设置密钥函数
	 +----------------------------------------------------------
	 * @param string $key
	 * @access public
	 +----------------------------------------------------------
	 * @return void
	 */
	public static function setKey($key){
		self::$key = $key;
	}

	/**
	 +----------------------------------------------------------
	 * 获取密钥函数
	 +----------------------------------------------------------
	 * @param void
	 * @access public
	 +----------------------------------------------------------
	 * @return string
	 */
	public static function getKey(){
		return self::$key;
	}

	/**
	 +----------------------------------------------------------
	 * 加密函数
	 +----------------------------------------------------------
	 * @param string $string
	 * @param string $key
	 * @access public
	 +----------------------------------------------------------
	 * @return string
	 */
	public static function encrypt($string, $key = null) {
		$_key = md5(128);
		$_count = 0;
		$_check = strlen($_key);
		$_length = strlen($string);
		$return = '';
		for ($i = 0; $i < $_length; $i++) {
			($_count != $_check) || $_count = 0;
			$return .= substr($_key, $_count, 1) . (substr($string, $i, 1) ^ substr($_key, $_count++, 1));
		}
		return base64_encode(self::_ed($return, (isset($key) ? $key : self::$key)));
	}

	/**
	 +----------------------------------------------------------
	 * 解密函数
	 +----------------------------------------------------------
	 * @param string $string
	 * @param string $key
	 * @access public
	 +----------------------------------------------------------
	 * @return string
	 */
	public static function decrypt($string, $key = null) {
		$string = self::_ed(base64_decode($string), (isset($key) ? $key : self::$key));
		$_length = strlen($string);
		$return = '';
		for ($i = 0; $i < $_length; $i++) {
			$_tmp = substr($string,$i++,1);
			$return .= (substr($string,$i,1) ^ $_tmp);
		}
		return $return;
	}
	
	/**
	 +----------------------------------------------------------
	 * 密钥函数
	 +----------------------------------------------------------
	 * @param string $string
	 * @param string $key
	 * @access private
	 +----------------------------------------------------------
	 * @return string
	 */
	private static function _ed($string, $key) {
		$key = md5($key);
		$_count = 0;
		$_check = strlen($key);
		$_length = strlen($string);
		$return = '';
		for ($i = 0; $i < $_length; $i++) {
			($_count != $_check) || $_count = 0;
			$return .= substr($string, $i, 1) ^ substr($key, $_count++, 1);
		}
		return $return;
	}
}