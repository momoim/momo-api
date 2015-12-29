<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * [UAP Portal] (C)1999-2009 ND Inc.
 * 相册控制器
 */

class Groupthumb_Controller extends Thumb_Controller {   
 
    public function index() {
        $this->groupthumb();
    }

    public function groupthumb() {
        $user_id = $this->getUid();
        $show_404 = false;
        $path = isset(Router::$arguments[0])?Router::$arguments[0] : '';
        if ($path == 'index') {
            $show_404 = true;
        } else {
            $path_array = explode('.', $path);
            if (count($path_array) != 2 || $path_array[1] != 'jpg') {
                $show_404 = true;
            }

            $param_array = explode('_', $path_array[0]);
            $pid = isset($param_array[0]) ? floatval($param_array[0]) : 0;
            $file_type = isset($param_array[1]) ? intval($param_array[1]) : 0;

            if (!$pid || !$file_type) {
                $show_404 = true;
            }
        }

        if($show_404) {
			$this->_show_nopicture();
        }

        $this->GroupAlbum = new Group_Model;
        $data = $this->GroupAlbum->url_validate($pid, $file_type);
        if ($data) {
			$file_format = $data['file_type'];
            $album_id = $data['album_id'];
			$is_animate = $data['is_animate'];
            //判断用户访问权限
            $group_album_privacy = $this->GroupAlbum->check_group_album_privacy($album_id);
            //if ($group_album_privacy['privacy_lev'] == 2 ) {
                if($user_id) {
                    //判断是否是群成员
                    $grade = $this->GroupAlbum->getmembergrade($group_album_privacy['group_id'], $user_id);
                    if ($grade < 1) {
						$this->_show_noaccess();
                    }
                    
                } else {
					$this->_show_noaccess();
                }
            //}
        } else {
			$this->_show_nopicture();
        }
        
        $this->_show_by_browser_cache($data['file_md5']);
        
        //访问FS 输出
        $thumb_array = $this->GroupAlbum->getThumbfsname($pid, $file_type, $is_animate);
        if ($thumb_array) {
            $this->_showThumb($thumb_array,$data['user_id'],$data['file_md5'],$file_type,$file_format);
        } else {
			$this->_show_nopicture();
        }
    }
}


