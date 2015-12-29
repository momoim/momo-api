<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * [UAP Portal] (C)1999-2009 ND Inc.
 * 相册控制器
 */

class Imgs_Controller extends Thumb_Controller {

    public function index() {
        $this->thumb();
    }
    
    public function thumb() {
    	$this->Imgs = new Imgs_Model; 
    	
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
            $data = $this->Imgs->getOriginalPic($pid);
            if($pid && !$file_type) {
                //获取原图
                if(!$data) {
					$this->_show_nopicture();
                }
                $file_md5 = isset($data['file_md5']) ? $data['file_md5'] . "wp" :"";

                if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $file_md5 && $_SERVER['HTTP_IF_NONE_MATCH'] == $file_md5) {
                    //浏览器缓存输出
                    header("Pragma: cache");
                    header("Cache-Control: private");
                    header("Etag: " . $file_md5, true, 304);
                    exit;
                } 
                include('application/include/FastDFS.php');
                $this->fs = new FastDFS('group1');
                //从FS上获取图片地址 
                $thumb = $this->fs->downToBuff($data['pic_fs_path']);
                $file_size = strlen($thumb); 
                if ($file_size) {
                	$mimetype=image_type_to_mime_type($data['file_type']);
					if($mimetype) $mimetype=$mimetype;
					else $mimetype='image/jpeg';
		                
					header("Content-type: ".$mimetype);
                    header('Content-Length: ' . $file_size);
                    header("Last-Modified: " . $data['last_modify']);
                    header("Expires: " . $data['expires']);
                    header("Etag: " . $data['file_md5'] . "wp");
                    header("Pragma: cache");
                    header("Cache-Control: private");
                    echo $thumb;
                    exit;

                } else {
					$this->_show_nopicture();
                }

            }
            if (!$pid || !$file_type) {
                $show_404 = true;
            }
        }

        if($show_404) {
			$this->_show_nopicture();
        }

        //验证URL
        $pic_data = $this->Imgs->url_validate($pid, $file_type);
        if(!$pic_data) {
			$this->_show_nopicture();
        }
		
		$file_format = $pic_data['file_type'];
		$is_animate = $pic_data['is_animate'];
		
		$this->_show_by_browser_cache($data['file_md5']);
       
        //获取图片的FS地址
		$thumb_data = $this->Imgs->get_FS_path($pid, $file_type, $pic_data);
        if($thumb_data) {
        	$thumb_array=$pic_data;
        	$thumb_array['thumb_fs_path']=$thumb_data['thumb_fs_path'];
			$thumb_array['file_fs_name']=$data['pic_fs_path'];
			$thumb_array['file_suffix']='jpg';
		
        	$this->_showThumb($thumb_array, $pic_data['user_id'], $pic_data['file_md5'], $file_type, $file_format);
        } else {
			$this->_show_nopicture();
        }
    }
}
