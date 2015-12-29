<?php defined('SYSPATH') or die('No direct script access.');
/**
 * [UAP Portal] (C)1999-2009 ND Inc.
 * 相册模型文件
 */

class Imgs_Model extends Model {

    protected $uid = 0;
     
    public function __construct() {
        parent::__construct();
        $this->uid = Session::instance()->get('uid'); 
    }
    /**
     * 验证图片url的合法性
     * @param int $pic_id
     * @param int $file_type
     */
    public function url_validate($pic_id, $thumb_type) {
        if ($thumb_type != '48' && $thumb_type != '80' && $thumb_type != '120' && $thumb_type != '160' && $thumb_type != '320' && $thumb_type != '480' && $thumb_type != '780' && $thumb_type != '1024'
                && $thumb_type != '1600') {
            return 0;
        }
        $data = array();
        $result = $this->db->getRow("wp_pic","pic_id, user_id, create_time, file_md5, pic_width, pic_height, file_type, is_animate","pic_id=$pic_id"); 
        if($result) {
            $data['pic_id'] = $result['pic_id'];
            $data['file_md5'] = $result['file_md5'];
            $data['pic_width'] = $result['pic_width'];
            $data['pic_height'] = $result['pic_height'];
            $data['user_id'] = $result['user_id'];
            $data['file_type'] = $result['file_type'];
            $data['is_animate'] = $result['is_animate'];
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
            
            $data['expires'] = $expires;
            $data['last_modify'] = $last_modify;
        }

        return $data;  

    }
    /**
     * 获取图片的FS信息
     * @param int $pic_id
     */
    public function get_FS_path($pic_id, $type, $data) {
        $file_md5 = $data['file_md5'];
        $pic_width = $data['pic_width'];
        $pic_height = $data['pic_height'];
		$is_animate = $data['is_animate'];

        $this->TT = new TTServer;
        $file_content = $this->TT->get($file_md5);
        if($file_content['pic_fs_path']) {
            if(!$pic_width || !$pic_height) {
                //无宽高数据，重现获取宽高
                $result = $this->update_heihgt_Width($file_content['pic_fs_path'], $pic_id);
                if($result) {
                    $pic_width = $result['pic_width'];
                    $pic_height = $result['pic_height'];
                    $this->db->updateData("wp_pic", array("pic_width" => $pic_width, "pic_height" => $pic_height), array("pic_id" => $pic_id));
                }
            }
            $fsfile = explode('.', $file_content['pic_fs_path']);
            $fsfile_name = $fsfile[0];
            $fsfile_type = $fsfile[1];
           
			if($type == "48" || $type == "120") {
				$thumb_fs_path = $fsfile_name . '_' . $type . '.' . $fsfile_type;
			} else {
				if($is_animate) {
					//gif动画返回原图
					if($type == '160') $thumb_fs_path = $fsfile_name . '_' . $type . '.' . $fsfile_type;
					else $thumb_fs_path = $fsfile_name . '.' . $fsfile_type;
				} else {
					if ($pic_width >= $type || $pic_height >= $type) {
						$thumb_fs_path = $fsfile_name . '_' . $type . '.' . $fsfile_type;
					} else {
						$thumb_fs_path = $fsfile_name . '.' . $fsfile_type;
					}
				}
			}

			return array("thumb_fs_path" => $thumb_fs_path, "pic_fs_path" => $file_content['pic_fs_path'], 'type' => $type);
        } else {
            return null;
        }
    }

    //重新获取图片的宽高
    public function update_heihgt_Width($FSpath, $pic_id){
        if (!is_dir(ALBUM_IMAGE . "tmp")) {
            mkdir(ALBUM_IMAGE . "tmp", 0777);
        }

        include_once('application/include/FastDFS.php');
        $this->fs = new FastDFS('group1');
        $local_filename = ALBUM_IMAGE."tmp/".$pic_id.time().'.jpg';
        $tmp = $this->fs->downToFile($FSpath, $local_filename);
        if(file_exists($local_filename)) {
            $img = Func_Core::getimagesize($local_filename);
            $width = $img[0];
            $height = $img[1];
            unlink($local_filename);
            if($width && $height) return array("pic_width" => $width, "pic_height" => $height);
        } else {
            return null;
        }
    }

    //获取wp原图信息
    public function getOriginalPic($pic_id) {
        
        $data = array();
        $result = $this->db->getRow("wp_pic","pic_id, user_id, create_time, file_md5, pic_width, pic_height, file_type, is_animate","pic_id=$pic_id"); 
        if($result) {
            $data['pic_id'] = $result['pic_id'];
            $data['file_md5'] = $result['file_md5'];
            $data['pic_width'] = $result['pic_width'];
            $data['pic_height'] = $result['pic_height'];
            $data['user_id'] = $result['user_id'];
            $data['file_type'] = $result['file_type'];
            $data['is_animate'] = $result['is_animate'];
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
            
            $data['expires'] = $expires;
            $data['last_modify'] = $last_modify;

            $this->TT = new TTServer;
            $file_content = $this->TT->get($result['file_md5']);
            $data['pic_fs_path'] = $file_content['pic_fs_path'];

        }

        return $data;  
    }
}
