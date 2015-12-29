<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * SNS helper class.
 *
 */
class sns_Core {

    //传出当前用户 uid
    public static function getuid() {
        $uid = Session::instance()->get('uid');
        return $uid ? $uid : NULL;
    }

    //传入 uid 传出 头你的是地址
    public static function getavatar($uid=NULL, $size='small') {
        if ($uid == NULL) $uid = sns::getuid();
//        return  Kohana::config('album.avatar')."/photo/avatar/$uid?appid=2&size=$size&p=1";
        $size=($size=='small' || $size==48)? 48: 130;
        $avatar = Photo_Controller::getavatar($uid,$size);
	    return empty($avatar) ? '' : $avatar;
    }

    //传入 uid 传出 真实姓名
    public static function getrealname($uid=NULL) {
        if ($uid == NULL) $uid = sns::getuid();
        $data = sns::getuser($uid);
        return $data['username'];
    }
    //传入 uid 传出 昵称
    public static function getnickname($uid=NULL) {
        if ($uid == NULL) $uid = sns::getuid();
        $data = sns::getuser($uid);
        return $data['nickname'];
    }

    //传入 uid 传出 真实姓名
    public static function getstatus($uid=NULL) {
        if ($uid == NULL) $uid = sns::getuid();
        $data = sns::getuser($uid);
        return $data['status'];
    }


    //传入 uid 传出 用户心情
    public static function getsign($uid=NULL) {
        if ($uid == NULL) $uid = sns::getuid();
        $data = sns::getuser($uid);
        return $data['sign'];
    }

    //传入 uid 传出 他 或 她
    public static function getwho($uid=NULL) {
        if ($uid == NULL) {
            $uid = sns::getuid();
            return Kohana::lang('global.me');
        }

        //当前用户的情况下
        // if ($uid == sns::getuid() && !preg_match('|user/[\d]+|', url::current())) return Kohana::lang('global.me');
        if ($uid == sns::getuid()) return Kohana::lang('global.me');

        $data = sns::getuser($uid);
        return $data['sex'] == '2' ? Kohana::lang('global.her') : Kohana::lang('global.his');
    }

    //传入 uid 传出 个人信息数组
    public static function getuser($uid) {
        $res = User_Model::instance()->get_user_info($uid);
        return $res;
    }

    public static function getalluser() {
        $data = User_Model::instance()->getAllUser();
        return $data;
    }

    /**
     *
     * 获取可用短信条数
     */
    public static function getsmscount($uid) {
    	$data = User_Model::instance()->get_sms_count($uid);
    	return $data;
    }

    //传入gid 传出群头像地址
    public static function getgavatar($gid, $size='small') {
        return Kohana::config('uap.server')."gavatar.php?gid=$gid&size=$size";
    }

    //时间的友好格式
    public static function gettime($timestamp, $format='Y-m-d') {
        $nowtime = time();
        $t = $nowtime-$timestamp;
        //今日0点
        $t0 = strtotime(date('Y-m-d'));
        //昨日0点
        $t1 = $t0-86400;
        //前日0点
        $t2 = $t1-86400;
        if ($t<60) return $t.'秒前';
        elseif ($t<3600) return round($t/60).'分钟前';
        elseif ($t<86400 && $t0<$timestamp) return date('H:i', $timestamp);
        elseif ($t<172800 && $t1<$timestamp) return date('昨天H:i', $timestamp);
        elseif ($t<259200 && $t2<$timestamp) return date('前天H:i', $timestamp);
        elseif ($t<864000) return round($t/86400).'天前';
        else return date($format, $timestamp);
    }

    //为没有表而要评论赞的创造一个唯一性objid
    public static function getFeedUniqid($field) {
    	$db = Database::instance();
    	$result = $db->insert('feedkey', array('feedtype'=>$field));
	return $result->insert_id();
    }

	/**
     * 获取电话加密串
     * @param array $tels 电话号码
     * @return array
     */
    public static function get_encrypt_tels($tels)
    {
        $result = array();
        $tels = (array)$tels;
        foreach ($tels as $tel) {
            $result[] = md5('nsofi&@6767sfsdd76#$8745dd%&addsdsa'.$tel);
        }
        return $result;
    }

    /**
     * 判断是否是中文
     * @param string $str
     * @return boolean
     */
    public static function isCh($str) {
        if(preg_match("/^[\x{4e00}-\x{9fa5}]+$/u", $str)) {
            return true;
        }
        return false;
    }

    public static function isNameEnChn($name, $len)
    {
    	$en = $ch = 0;
	for ($i = 0; $i < $len; $i++) {
		$word = mb_substr($name,$i,1);
		if (strlen($word) == 1) {
			$en = 1;
		} else {
			$ch = 1;
		}
	}
	if ($en && $ch) {
		return true;
	} else {
		return false;
	}
    }
}
