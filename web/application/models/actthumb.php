<?php
/**
 * [移动SNS网站] (C) 1999-2009 ND Inc.
 * 活动模块模型类
 **/
defined('SYSPATH') or die('No direct script access.');

class Actthumb_Model extends Model {
    protected $uid = 0;
    public function __construct($sid=null) {
        // 加载数据库类。以下可以使用 $this->db 操作数据库（如果不要求可以省略）
        parent::__construct();
        $this->uid = Session::instance()->get('uid');
    }


    //活动照片缩略图验证
    public function url_validate($pid, $thumb_type) {
        if ($thumb_type != '80' && $thumb_type != '160' && $thumb_type != '320' && $thumb_type != '480' && $thumb_type != '780' && $thumb_type != '1024' && $thumb_type != '1600') {
            return 0;
        }
        $album_id = 0;
        $data = array();
        $result = $this->db->getRow("album_activity_pic","user_id, activity_id, album_id, file_md5, file_type, is_animate, pic_width, pic_height" ,"pic_id =$pid");
        if($result) {
            $data['activity_id'] = $result['activity_id'];
            $data['album_id'] = $result['album_id'];
            $data['file_md5'] = $result['file_md5'];
            $data['file_type'] = $result['file_type'];
            $data['is_animate'] = $result['is_animate'];
        }
        return $data;
    }

//活动照片缩略图输出信息
    public function getThumbfsname($pid, $type, $is_animate = false)
    {
        $data = array();
        $result = $this->db->getRow("album_activity_pic", "create_time,file_md5, pic_width, pic_height", "pic_id = '$pid'");
        if($result) {
            $data['create_time'] = $result['create_time'];
            $data['file_md5'] = $result['file_md5'];
            $data['pic_width'] = $result['pic_width'];
            $data['pic_height'] = $result['pic_height'];
        }
        $file_md5 = $data['file_md5'];
        $this->TT = new TTServer;
        $file_content = $this->TT->get($file_md5);

        if($file_content['pic_fs_path']) {
            $data['pic_fs_path'] =$file_content['pic_fs_path'];
        } else {
            return 0;
        }
        $fs_name = $data['pic_fs_path'];
        $pic_width = $data['pic_width'];
        $pic_height = $data['pic_height'];

        if ($fs_name) {
            $fsfile_name_array = explode('.', $fs_name);
            $fsfile_name = $fsfile_name_array[0];
            $fsfile_type = $fsfile_name_array[1];

			if($is_animate && $type == '80') $type = '160';

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
            $result = $this->check_thumb_type($pid, $type, $is_animate);
            if(!$result) $return['thumb_fs_path'] = $fs_name;
            return $return;
        } else {
            return 0;
        }
    }
    
/**
     * 判断是否要生成新的缩略图
     * @param int
     */
    public function check_thumb_type($pic_id, $thumb_type, $is_animate = false)
    {
        if ($thumb_type != '80' && $thumb_type != '160' && $thumb_type != '320' && $thumb_type != '480' && $thumb_type != '780' && $thumb_type != '1024' && $thumb_type != '1600') {
            return false;
        }
		if($is_animate && $thumb_type != '160') return false;

        $width = 0;
        $height = 0;
        $file_md5 = '';
        $result = $this->db->getRow( "album_activity_pic","file_md5, pic_width, pic_height"," pic_id = '$pic_id'");
        if ($result ) {
            $width = $result['pic_width'];
            $height = $result['pic_height'];
            $file_md5 = $result['file_md5'];
        }

        if(!$height || !$width) {
            //album pic 表中没有宽高记录、查询TT
            $this->TT = new TTServer;
            $result = $this->TT->get($file_md5);
            $data = unserialize($result['exif']);
            $height = $data['拍摄分辨率高'];
            $width = $data['拍摄分辨率宽'];
            if($width && $height) {
                //更新图片的宽度和高度
                $update = array('pic_width' => $width, 'pic_height' => $height);
                $this->db->updateData("album_activity_pic", $update, array('pic_id' => $pid));
            }
        }

        if ($width >= $thumb_type || $height >= $thumb_type) {
            return true;
        } else {
            return false;
        }
    }

/**
     * 获取图片md5，user_id
     * @param int $pic_id 图片ID
     */
    public function get_pic_info($pic_id)
    {
        $info = array();
        $result = $this->db->getRow( "album_activity_pic","file_md5, user_id, pic_width, pic_height"," pic_id = '$pic_id'");
        if ($result) {
            $info['file_md5'] = $result['file_md5'];
            $info['user_id'] = $result['user_id'];
            $info['pic_width'] = $result['pic_width'];
            $info['pic_height'] = $result['pic_height'];
        }

        return $info;
    }




}