<?php defined('SYSPATH') or die('No direct script access.');
/**
 * [UAP Portal] (C)1999-2009 ND Inc.
 * 相册模型文件
 */

class Thumb_Model extends Model {

    protected $uid = 0;
     
    public function __construct() {
        parent::__construct();
        $this->uid = Session::instance()->get('uid'); 
    }

    /**
     * 获取momo头像中截取的小头像
     * @param int $pid
     * @param int $type
     */
    public function get_small_avatarFS($pid, $type) {
        if($type != 48 && $type != 120) return null;

        //获取图片md5
        $result = $this->db->getRow("album_pic", "file_md5, create_time", "pic_id = $pid");        
        if($result) {
            $file_md5 = $result['file_md5'];
            //获取FS地址
            $this->TT = new TTServer;
            $pic_fs = $this->TT->get($file_md5);
            if($pic_fs['pic_fs_path']) {
                //获取缩略图的FS路径
                $fsfile_name_array = explode('.', $pic_fs['pic_fs_path']);
                $thumb_path = $fsfile_name_array[0] . '_'.$type.'.'. $fsfile_name_array[1];

                //过期时间
                $year = date("Y", $result['create_time']) + 1;
                $month = date("m", $result['create_time']);
                $day = date("d", $result['create_time']);
                $hour = date("H", $result['create_time']);
                $minute = date("i", $result['create_time']);
                $second = date("s", $result['create_time']);
                $s = mktime($hour, $minute, $second, $month, $day, $year);
                $expires = date("D, d M Y H:i:s ", $s) . 'GMT';
                $last_modify = date("D, d M Y H:i:s ", $result['create_time']) . 'GMT';
                $max_age = $s - $result[0]['create_time'];
                $return = array('thumb_path' => $thumb_path,
                    'last_modify' => $last_modify,
                    'expires' => $expires,
                    'max_age' => $max_age,
                    'file_md5' => $file_md5);
                return $return;
                
            }
        } else {
            //没有设置头像,返回默认头像
            return null;
        }

        return null;
    }
    
    /**
     * 缩略图url验证
     * @param int $pid
     * @param int $thumb_type
     */
    public function url_validate($pid, $thumb_type)
    {
        if ($thumb_type != '80' && $thumb_type != '160' && $thumb_type != '320' && $thumb_type != '480' && $thumb_type != '780' && $thumb_type != '1024' && $thumb_type != '1600') {
            return 0;
        }
        $album_id = 0;
        $owner_id = 0;
        $file_md5 = '';
        $result = $this->db->query("SELECT  pic_id, user_id,  album_id,  file_md5, pic_width, pic_height, create_time, file_type, is_animate FROM album_pic WHERE  pic_id = '$pid' AND status >= '0' "); 
        foreach ($result as $value) {
            $owner_id       = $value->user_id;
            $album_id       = $value->album_id;
            $file_md5       = $value->file_md5;
            $pic_width      = $value->pic_width;
            $pic_height     = $value->pic_height;
            $create_time    = $value->create_time;
            $file_type    = $value->file_type;
            $is_animate    = $value->is_animate;
        } 
		if(!$owner_id) return 0;
		$uid = $this->uid;
		if($uid != $owner_id) {
			//判断是否好友
			$isFriend = $this->isFriend($uid, $owner_id);
			if(!$isFriend) return 1;
		}
        if ($owner_id && $album_id) {
            return array("album_id" => $album_id, "privacy_lev" => $privacy_lev, "owner_id" => $owner_id, "file_md5" => $file_md5, "pic_width" => $pic_width, "pic_height" => $pic_height, "create_time" => $create_time, "file_format" => $file_type, "is_animate" => $is_animate);
        } else {
            return 0;
        }
    }

    /**
     * 是否为好友关系
     * @param int $uid
     * @param int $fid
     */
    public function isFriend($uid, $fid) {
        $this->Friend = new Friend_Model;
		return $this->Friend->getCheckIsFriend($uid, $fid); 
    }

    /**
     * 获取图片的缩略图信息
     * @param int $pid
     * @param array $data
     */
    public function getThumbInfo($pid, $type, $data) {
        $this->TT = new TTServer;
        $file_content = $this->TT->get($data['file_md5']);
        $fs_name = $file_content['pic_fs_path'];

        if ($fs_name) {
			if($data['is_animate'] && $type == '80') $type = '160';
            $fsfile_name_array = explode('.', $fs_name);
            $thumb_fs_path = $fsfile_name_array[0] . '_' . $type . '.' . $fsfile_name_array[1];
            $thumb_1600_fs_path = $fsfile_name_array[0] . '_1600.' . $fsfile_name_array[1];

            //过期时间
            $year = date("Y", $data['create_time']) + 1;
            $month = date("m", $data['create_time']);
            $day = date("d", $data['create_time']);
            $hour = date("H", $data['create_time']);
            $minute = date("i", $data['create_time']);
            $second = date("s", $data['create_time']);
            $s = mktime($hour, $minute, $second, $month, $day, $year);
            $expires = date("D, d M Y H:i:s ", $s) . 'GMT';
            $last_modify = date("D, d M Y H:i:s ", $data['create_time']) . 'GMT';
            $max_age = $s - $data['create_time'];
            $return = array('thumb_fs_path' => $thumb_fs_path,
                'file_fs_name' => $fs_name,
                'file_suffix' => $fsfile_name_array[1],
                'thumb_1600_fs_path' => $thumb_1600_fs_path,
                'last_modify' => $last_modify,
                'expires' => $expires,
                'max_age' => $max_age,
                'file_md5' => $data['file_md5']);


            $height = $data['pic_height'];
            $width = $data['pic_width'];
            if(!$width || !$height) {
                //album pic 表中没有宽高记录、查询TT
                $exif = unserialize($file_content['exif']);
                $height = $exif['拍摄分辨率高'];
                $width = $exif['拍摄分辨率宽'];
                if($width && $height) {
                    //更新图片的宽度和高度
                    $update = array('pic_width' => $width, 'pic_height' => $height);
                    $this->db->updateData("album_pic", $update, array('pic_id' => $pid));
                }
            }
			

			if($data['file_type'] == 1 && $data['is_animate'] && $type != '160') {
				//gif动画返回原图
				$return['thumb_fs_path'] = $fs_name;
			} else {
				if ($width >= $type || $height >= $type) {
					//
				} else {
					if($data['is_animate'] && $type == '160') {
					
					} else {
						$return['thumb_fs_path'] = $fs_name;
					}
				}
			}
            return $return;
        } else {
            return 0;
        }

    }
}

