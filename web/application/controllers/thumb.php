<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * [UAP Portal] (C)1999-2009 ND Inc.
 * 相册控制器
 */

class Thumb_Controller extends Controller {   
 
    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $this->thumb();
    }
    
    public function thumb() {
    	$this->Thumb = new Thumb_Model;
    	
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

        //获取头像中截图的小头像，特殊处理
        if($file_type == 48 || $file_type == 120) {
            $avatar_fs = $this->Thumb->get_small_avatarFS($pid, $file_type);
 
            if($avatar_fs) {
                //获取文件数据流
                $this->_connectFastDFS();
                $thumb = $this->fs->downToBuff($avatar_fs['thumb_path']);
                $file_size = strlen($thumb);
                
                header("Content-type: image/jpeg");
                header('Content-Length: ' . $file_size);
                header("Last-Modified: " . $avatar_fs['last_modify']);
                header("Expires: " . $avatar_fs['expires']);
                header("Etag: " . $avatar_fs['file_md5'] . "v3:" . $file_type);
                header("Pragma: cache");
                header("Cache-Control: private");
                echo $thumb;
                exit;

            } else {
				$this->_show_nopicture();
            }
        }
		
        $data = $this->Thumb->url_validate($pid, $file_type);
        if(!$data) {
			$this->_show_nopicture();
        } else if($data == 1) {
			$this->_show_justfriend();
		} else {
            $privacy_lev = $data['privacy_lev'];
            $owner_id = $data['owner_id'];
			$file_format = $data['file_type'];
			$is_animate = $data['is_animate'];            
        }
        
        $this->_show_by_browser_cache($data['file_md5']);
                
        //访问FS 输出
        $thumb_array = $this->Thumb->getThumbInfo($pid, $file_type, $data);
        if ($thumb_array) {
			$this->_showThumb($thumb_array,$data['owner_id'],$data['file_md5'],$file_type,$file_format);
        } else {
			$this->_show_nopicture();
        } 
    }
    
    protected function _show_by_browser_cache($file_md5){
    	//浏览器缓存输出
        $file_md5_out = $file_md5 ? $file_md5 . "mm:" . $file_type : "";
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $file_md5_out && $_SERVER['HTTP_IF_NONE_MATCH'] == $file_md5_out) {
            header("Pragma: cache");
            header("Cache-Control: private");
            header("Etag: " . $file_md5_out, true, 304);
            exit;
        }
    }
    
    protected function _connectFastDFS(){
        if(!$this->fs) {
        	include('application/include/FastDFS.php');
        	$this->fs = new FastDFS('group1');
        }
    }
    
    protected function _showThumb($thumb_array, $owner_id, $file_md5, $file_type, $file_format){
		$thumb_fs_name = $thumb_array['thumb_fs_path'];
		$file_fs_name = $thumb_array['file_fs_name'];

		//获取当前请求的缩略图
		$this->_connectFastDFS();
		$thumb = $this->fs->downToBuff($thumb_fs_name);
		$file_size = strlen($thumb);
		if ($file_size == 0) {
			//当前请求的缩略图FS上未生成
			//下载原图来生成当前请求的缩略图
			//判断本地是否存在原图
			$original_file_path = ALBUM_IMAGE . $owner_id . '/' . $owner_id . '_' . $file_md5 . '.' . $thumb_array['file_suffix'];
			if (file_exists($original_file_path)) {
				$file_path=$original_file_path;
			} else {
				//原图不存在、下载FS上的原图
				if (!is_dir(ALBUM_IMAGE . $owner_id)) {
					mkdir(ALBUM_IMAGE . $owner_id, 0777);
				}
				$local_filename = ALBUM_IMAGE . $owner_id . '/tmp_' . $file_md5 . '.' . $thumb_array['file_suffix'];
				$this->fs->downToFile($file_fs_name, $local_filename);

				$file_path=$local_filename;
			}
			
			//生成新的缩略图
			$images = Image::createThumb_by_type($file_path, $file_type);
			//上传到FS
			$meta = array('remote_filename' => $thumb_fs_name);
			$this->fs->upByFileName($images, $meta);
			//获取新的缩略图
			if(file_exists($images)){
				$thumb = file_get_contents($images);
				@unlink($images);
			} else {
				$thumb = $this->fs->downToBuff($thumb_fs_name);
			} 
			$file_size = strlen($thumb);
			@unlink($local_filename);
		}

		if ($file_size) {
			$mimetype=image_type_to_mime_type($file_format);
			if($mimetype) $mimetype=$mimetype;
			else $mimetype='image/jpeg';
                
			header("Content-type: ".$mimetype);
			header('Content-Length: ' . $file_size);
			header("Last-Modified: " . $thumb_array['last_modify']);
			header("Expires: " . $thumb_array['expires']);
			header("Etag: " . $thumb_array['file_md5'] . "mm:" . $file_type);
			header("Pragma: cache");
			header("Cache-Control: private");
			echo $thumb;
			exit;
		} else {
			$this->_show_nopicture();
		}
    }
    
    private function _show_noaccess(){
    	$this->_show_error('noaccess');
    }
    
    protected function _show_nopicture(){
    	$this->_show_error('nopicture');
    }
    
    protected function _show_justfriend(){
    	$this->_show_error('justfriend');
    }
    
    protected function _show_error($msg){
    	if(! in_array($msg,array('noaccess','justfriend'))) $msg='nopicture';
    	
    	$data = file_get_contents(DOCROOT . 'style/images/'.$msg.'.jpg');
        $file_size = strlen($data);
        header("Content-type: image/jpg");
        header('Content-Length: ' . $file_size);
        echo $data;
        exit;
    }
}
