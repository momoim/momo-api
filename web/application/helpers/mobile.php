<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * mobile helper class.
 *
 */
class mobile_Core {

    //格式化手机号
    public static function zone_code_format($mobile) {
        switch($mobile) {
        	default:
        		return trim($mobile);
        		break;
        	case 86:
        	case 086:
        	case 0086:
        		return 86;
        		break;
        }
    }
}
