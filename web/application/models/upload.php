<?php defined('SYSPATH') or die('No direct script access.');
/**
 */

class Upload_Model extends Model { 
    public $sid = '';  
    public $uid = 0;  
     
    /**
     * 构造函数，初始化数据库连接
     */
    public function __construct() {
        parent::__construct();
        //$this->sid = Session::instance()->get('sid');
        //$this->uid = Session::instance()->get('uid'); 
        $this->uid=$this->getUid();
    }

	  /**
     * 创建临时文件
     */
	public function CreateTemp($data) {
		$uid = $this->uid;
		//创建临时文件
		$file_md5 = $data['file_md5'];
        if (!is_dir(ALBUM_TEMP.$uid)) {
            mkdir(ALBUM_TEMP.$uid, 0777);
        }
        $tmpfname = $uid.'_'.$file_md5.'.jpg.tmp';

        //以读写的方式打开文件，如果文件不存在创建之
        fopen(ALBUM_TEMP.$uid.'/'.$tmpfname, 'w');
        if( file_exists(ALBUM_TEMP.$uid.'/'.$tmpfname) ) {
            $data['file_path'] = $uid.'/'.$tmpfname;
			$data['user_id'] = $uid;
			$data['create_dt'] = time();
			//写临时数据库表
            $upload_id = $this->db->insertData('album_upload_temp', $data);
            if($upload_id) {
                return $upload_id;
            } else {
				//写数据库表失败
                return 0;
            }
        } else {
			//创建临时文件失败
            return 0;
        } 
	}
	
	//判断临时文件id合法
	public function check_tempid($uploadID) {
		//判断tempID
		$uid = $this->uid;
		return $this->db->getRow("album_upload_temp", "file_path,file_size,file_md5", "temp_id=$uploadID AND user_id=$uid");
	}

	//上传文件数据
	public function uploadFile($uploadID, $file_len, $offset, $file_data, $tmpfname) {
	
        $handle = fopen(ALBUM_TEMP.$tmpfname, "r+");
        fseek($handle, $offset); //移动指针到offset_start位置
        fwrite($handle, $file_data);
        fclose($handle);
        //更新偏移量
        $offset = $offset + $file_len;
		return $this->db->updateData("album_upload_temp", array("file_offset" => $offset), array("temp_id" => $uploadID));
	}

	 /**
     * 更新偏移量
     * @param int $temp_id 临时文件ID
     * @param int $offset 偏移量
     * @return int 1 成功
     */
    public function set_offset($temp_id, $offset) {
        $result = $this->db->update('album_pic_temp', array('tmp_offset' => $offset), array('id' => $temp_id));
        $result = $result->count();
        return $result;
    }

	/**
     * 判断上传偏移量是否合法
     * @param int $offset_start 文件开始上传的偏移量
     * @param int $temp_id 临时文件ID
     */
    public function check_offset($temp_id, $offset_start) {
        $offset = $this->db->getOne("album_upload_temp","file_offset","temp_id = $temp_id");
        if( intval($offset) >= intval($offset_start) ) {
            return 1;
        } else {
            return 0;
        }
    }

	//结束上传
	public function uploadFileEnd($temp_id, $flag){
		$uid = $this->uid;
		$data = $this->db->getRow("album_upload_temp", "file_title, file_md5, file_size, file_offset, file_path, file_type", "temp_id=$temp_id AND user_id=$uid");
		if(!$data) {
			return array("code" => 403, "msg" => "uploadID不存在或无权限");	
		}
		
		//if($data['file_size'] != $data['file_offset']) {
		//	return array("code" => 403, "msg" => "文件大小不匹配.");	
		//}
		//$tempfsize = 0;
		//if( file_exists(ALBUM_TEMP.$data['file_path']) ) {
		//	$tempfsize = filesize(ALBUM_TEMP.$data['file_path']);
		//}
		//if(!$tempfsize || $tempfsize != $data['file_size']) {
		//	return array("code" => 403, "msg" => "文件大小不匹配!");	
		//}
		 //移动临时文件到文件目录
		if (!is_dir(ALBUM_IMAGE.$uid)) {
			mkdir(ALBUM_IMAGE.$uid, 0777);
		}

		//临时文件移动到user文件夹下
        $tempfname = substr($data['file_path'], 0, -4);
		rename(ALBUM_TEMP.$data['file_path'], ALBUM_IMAGE.$tempfname);
		//移动文件成功
		if( file_exists(ALBUM_IMAGE.$tempfname) ) {
				$file_url = ALBUM_IMAGE.$tempfname;
				//获取图片的EXIF信息
				$exif = Exif::get_exif_info($file_url);
				$img_exif = $exif['exif_info'];
				$degree = $exif['degree'];
				//获取图片的高度、宽度
				$img = getimagesize($file_url);
				$width = $img[0];
				$height = $img[1];
				$file_format = $img[2];
				if(!$img ||  !$width || !$height || $file_format > 6) return false;
				if($file_format == 2 && $degree) { 
					//旋转图片
					$rotateDeg = $degree*90;
					Image::rotate_by_degree($file_url, $rotateDeg);
					if($degree == 1 || $degree == 3) {
						$t = $width;
						$width = $height;
						$height = $t;
					}
					$img_exif['degree'] = 0; 
					$degree = 0;
				}
				$flag = $flag ? $flag : 2; //广播标识
				$album_id = $this->db->getOne("wp_album", "album_id", "user_id = $uid AND album_flag=$flag");
				if(!$album_id) {
					//创建广播相册
					$album_data = array(
						'album_name' => '广播相册',
						'user_id' => $uid,
						'create_dt' => time(),
						'update_dt' => time(),
						'album_flag' => $flag
					);
					$album_id = $this->db->insertData("wp_album", $album_data);
				}
				$pic_info = array(
					'album_id' => $album_id,
					'user_id' => $uid,
					'create_time' => time(),
					'update_time' => time(),
					'upload_ip' => $_SERVER['REMOTE_ADDR'],
					//'upload_file_name' => $data['file_title'],
					'file_md5' => $data['file_md5'],
					'file_size' => $data['file_size'],
					'file_type' => $data['file_type'],
					'pic_width' => $width,
					'pic_height' => $height,
					'degree' => intval($degree),
					'appid' => 1
				);
				//上传到FS				 
				include('application/include/FastDFS.php');
				$this->fs = FastDFS::factory('group1');
				
				//FS存储地址
                $img_fs_name = $this->fs->upByFileName($file_url);

				//写TTServer
				$value = array("pic_fs_path" => $img_fs_name, "exif" => serialize($img_exif), "width" => $width, "height" => $height);
				$this->TT = new TTServer;
				$this->TT->set($data['file_md5'], $value); 
				$pic_id = $this->db->insertData("wp_pic", $pic_info);
				$this->db->query("UPDATE wp_album SET pic_num = pic_num+1 WHERE album_id=$album_id");
				//删除临时表数据
				$this->db->DeleteData("album_upload_temp", array("temp_id" => $temp_id));

				//生成160
				$images = Image::createThumb_by_type($file_url, 160);
				$thumb_fs_name = substr($img_fs_name, 0, -4);
				$meta = array('remote_filename' => $thumb_fs_name.'_160.jpg');
				$this->fs->upByFileName($images, $meta);
				if(file_exists($images)) {
					unlink($images);
				}
				return array("pid" => $pic_id, "url" => Kohana::config('album.recordThumb').'imgs/'.$pic_id.'_160.jpg', "uploaded" => true);

		} else {
			//移动文件失败
			return null;
		}
	}

	//检查md5值是否存在于TTServer
	public function check_md5($file_md5, $pic_info){
		if(!$file_md5) return null;
		$uid=$this->uid;
		$this->TT = new TTServer;
		$data = $this->TT->get($file_md5);
		if($data) {
			$this->TT->set_count($file_md5);
			$exif = unserialize($data['exif']);
			$width = $data['width'] ? $data['width'] : $pic_info['width'];
			$height = $data['height'] ? $data['height'] : $pic_info['height'];
			//$flag = 2 广播标识
			$flag = $pic_info['flag'] ? $pic_info['flag'] : 2;
			$album_id = $this->db->getOne("wp_album", "album_id", "user_id = $uid AND album_flag=$flag");
			if(!$album_id) {
				//创建广播相册
				$album_data = array(
					'album_name' => '广播相册',
					'user_id' => $uid,
					'create_dt' => time(),
					'update_dt' => time(),
					'album_flag' => $flag
				);
				$album_id = $this->db->insertData("wp_album", $album_data);
			}
			$pic_info = array(
				'album_id' => $album_id,
				'user_id' => $uid,
				'create_time' => time(),
				'update_time' => time(),
				'upload_ip' => $_SERVER['REMOTE_ADDR'],
				'upload_file_name' => $pic_info['title'],
				'file_md5' => $file_md5,
				'file_size' => $pic_info['size'],
				'file_type' => $pic_info['type'],
				'pic_width' => $width ? intval($width) : intval($exif['width']),
				'pic_height' => $height ? intval($height) : intval($exif['height']),
				'degree' => intval($exif['degree']),
				'appid' => $pic_info['from']
			);
			$pic_id = $this->db->insertData("wp_pic", $pic_info);
			$this->db->query("UPDATE wp_album SET pic_num = pic_num+1 WHERE album_id=$album_id");
			return array("pid" => $pic_id, "url" => Kohana::config('album.recordThumb').'imgs/'.$pic_id.'_160.jpg', "uploaded" => true);
		} else {
			return null;
		}
	}

}
