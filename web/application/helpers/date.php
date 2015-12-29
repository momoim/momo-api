<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Date helper class.
 *
 */
class date_Core {

	private static $_DIFF_FORMAT = array(
	  'HOUR'        => '%s小时',
	  'HOUR_MINUTE'  => '%s小时%s分',
	  'MINUTE'   => '%s分钟',
	  'MINUTE_SECOND' => '%s分%s秒',
	  'SECOND'  => '%s秒',
	);
	/**
	  * 友好格式化时间
	  *
	  * @param int 时间
	  * @param array $formats
	  * @return string
	  */
	public static function diff($seconds, $formats = null)
	{
	  if ($formats == null) {
	   $formats = self::$_DIFF_FORMAT;
	  }
	  /* 计算出时间差 */
	  $minutes = floor($seconds / 60);
	  $hours   = floor($minutes / 60);
	
	   $diffFormat = ($hours > 0) ? 'HOUR' : 'MINUTE';
	   if ($diffFormat == 'HOUR') {
	        $diffFormat .= ($minutes > 0 && ($minutes - $hours * 60) > 0) ? '_MINUTE' : '';
	   } else {
	        $diffFormat = (($seconds - $minutes * 60) > 0 && $minutes > 0)
	        ? $diffFormat.'_SECOND' : 'SECOND';
	   }
	
	  $dateDiff = null;
	  switch ($diffFormat) {
	   case 'HOUR':
	        $dateDiff = sprintf($formats[$diffFormat], $hours);
	        break;
	   case 'HOUR_MINUTE':
	        $dateDiff = sprintf($formats[$diffFormat], $hours, $minutes - $hours * 60);
	        break;
	   case 'MINUTE':
	        $dateDiff = sprintf($formats[$diffFormat], $minutes);
	        break;
	   case 'MINUTE_SECOND':
	        $dateDiff = sprintf($formats[$diffFormat], $minutes, $seconds - $minutes * 60);
	        break;
	   case 'SECOND':
	        $dateDiff = sprintf($formats[$diffFormat], $seconds);
	        break;
	  }
	  return $dateDiff;
	}
}
