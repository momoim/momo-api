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
class Input extends Input_Core {

	/**
	 * 重载过滤非法字符
	 *
	 * @param   string  string to clean
	 * @return  string
	 */
	public function clean_input_keys($str)
	{
		$chars = PCRE_UNICODE_PROPERTIES ? '\pL' : 'a-zA-Z';

		if ( ! preg_match('#^['.$chars.'0-9:_.-]++$#uD', $str)) {
		    //api::send_response('400', NULL, '400902:输入数据包含非法字符');
		}

		return $str;
	}

} // End Input
