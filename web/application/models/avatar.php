<?php defined('SYSPATH') or die('No direct script access.');
/**
 * [UAP Portal] (C)1999-2009 ND Inc.
 * 头像照模型文件
 */

class Avatar_Model extends Model {
    /**
     * @var object 相册服务端
     */ 
    public $sid = '';  
    public $albumDB = 'sns_album';
    public $uid = 0;  
    public $TT=NULL;
     
    /**
     * 构造函数，初始化数据库连接
     */
    public function __construct() {
        parent::__construct();
        if ( ! is_object($this->db))
		{
			// Load the album database
			$this->db = Database::instance($this->db);
		}  
        $this->uid = $this->getUid();
    }

	//修改用户头像
	public function update_avatar($small_data, $middle_data, $original_data, $pic_data, $noSmall=false, $client_id=0) {
		$user_id = $this->uid;
		$file_md5 = md5($original_data);
		//判断是否来之j2me
		if($client_id == 6) {
			if(!$original_data) return false;
			//判断图片格式
			if (!is_dir(ALBUM_IMAGE.$user_id)) {
				mkdir(ALBUM_IMAGE.$user_id, 0777);
			}
			$tmpfname = ALBUM_IMAGE.$user_id.'/'.$user_id.'_'.$file_md5.'original.jpg';
			$handle = fopen($tmpfname, "w");
			fseek($handle, 0); //移动指针到文件开始位置
			fwrite($handle, $original_data);
			fclose($handle);
			$image = new Imagick ( $tmpfname );

			//大头像
			$image->cropThumbnailImage(120, 120);
			//缩略图路径文件名
			$middleThumbPath = ALBUM_IMAGE.$user_id.'/'.$user_id.'_'.$file_md5.'middle_120.jpg';		
			//写缩略图
			$image->writeImage($middleThumbPath);
			
			//小头像
			$image->resizeImage(48, 48, 0, 1, true);
			$smallThumbPath = ALBUM_IMAGE.$user_id.'/'.$user_id.'_'.$file_md5.'small_48.jpg';		
			//写缩略图
			$image->writeImage($smallThumbPath);
 
			//缩放
			$small_data = file_get_contents($smallThumbPath);
			$middle_data = file_get_contents($middleThumbPath);
			if(file_exists($tmpfname))  unlink($tmpfname);
			if(file_exists($middleThumbPath))  unlink($middleThumbPath);
			if(file_exists($smallThumbPath))  unlink($smallThumbPath);
		}
		$pic_data_array = array();
		$album_id = $this->db->getOne("album_user_album", "album_id", "user_id=$user_id AND album_default=2");
		if(!$album_id) {
			$data = array(
                'album_name' => '头像照',
                'privacy_lev' => 2,
                'album_desc' => '',
                'allow_comment' => 1,
                'allow_repost' => 1,
                'album_default' => 2,
                'create_dt' => time(),
                'update_dt' => time(),
				'user_id' => $user_id
            );
			$album_id = $this->db->insertData("album_user_album", $data);   
		}
        if(!$this->TT) $this->TT = new TTServer;
        $data = $this->TT->get($file_md5);
        //上传到FS
        include('application/include/FastDFS.php');
        $this->fs = FastDFS::factory('group1');

		
		//插入 数据
		$pic_data_array['album_id'] = $album_id; 
		$pic_data_array['user_id'] = $user_id; 
		$pic_data_array['create_time'] = time(); 
		$pic_data_array['update_time'] = time(); 
		$pic_data_array['upload_ip'] = $_SERVER['REMOTE_ADDR']; 
		$pic_data_array['upload_file_name'] = "avatar.jpg"; 
		$pic_data_array['pic_title'] = "avatar.jpg"; 
		$pic_data_array['file_md5'] = $file_md5; 
		$pic_data_array['file_size'] = strlen($original_data);  
		$pic_data_array['album_default'] = 2;
		//上传size=small的图片
		if($noSmall) {
			//120压缩到48
			if (!is_dir(ALBUM_IMAGE.$user_id)) {
				mkdir(ALBUM_IMAGE.$user_id, 0777);
			}
			$tmpfname = ALBUM_IMAGE.$user_id.'/'.$user_id.'_'.$file_md5.'small.jpg';
			$handle = fopen($tmpfname, "w");
			fseek($handle, 0); //移动指针到文件开始位置
			fwrite($handle, $middle_data);
			fclose($handle);
			$image = new Imagick ( $tmpfname );
			//缩放
			$image->resizeImage(48, 48, 0, 1, true);
			//缩略图路径文件名
			$thumbPath = ALBUM_IMAGE.$user_id.'/'.$user_id.'_'.$file_md5.'small_48.jpg';		
			//写缩略图
			$image->writeImage($thumbPath);
			$small_data = file_get_contents($thumbPath);
			if(file_exists($tmpfname))  unlink($tmpfname);
			if(file_exists($thumbPath))  unlink($thumbPath);
		}
		if($data) {
			//生成新的格式的FS
			$fsfile_name_array = explode('.', $data['pic_fs_path']);
			$thumb_small_path = $fsfile_name_array[0] . '_48.' . $fsfile_name_array[1];
			$thumb_middle_path = $fsfile_name_array[0] . '_120.' . $fsfile_name_array[1];

			//已存在            
            $dataExif = unserialize($data['exif']);
            $pic_data_array['pic_width'] = $dataExif['拍摄分辨率宽'] ? $dataExif['拍摄分辨率宽'] : $data['width'];
            $pic_data_array['pic_height'] = $dataExif['拍摄分辨率高'] ? $dataExif['拍摄分辨率高'] : $data['height']; 
            $pic_data_array['degree'] = intval($dataExif['方向']); 			
			
            $meta = array('remote_filename' => $thumb_small_path);
            $fs_small = $this->fs->upByBuff($small_data, 'jpg', $meta);

            //上传size=middle的图片
            $meta = array('remote_filename' => $thumb_middle_path);
            $fs_middle = $this->fs->upByBuff($middle_data, 'jpg', $meta);
            $pic_id = $this->db->insertData("album_pic",$pic_data_array);
            if($pic_id) {
				$this->db->query("UPDATE album_user_album SET pic_num=pic_num+1 WHERE album_id=$album_id"); 
			} else {
				return null;
			}
		} else {
			//写入用户临时图片文件
           //写入文件
            if (!is_dir(ALBUM_IMAGE.$user_id)) {
                mkdir(ALBUM_IMAGE.$user_id, 0777);
            }
           $tmpfname = ALBUM_IMAGE.$user_id.'/'.$user_id.'_'.$file_md5.'.jpg';
           $handle = fopen($tmpfname, "w");
           fseek($handle, 0); //移动指针到文件开始位置
           fwrite($handle, $original_data);
           fclose($handle);
		   
		    //上传到DFS、获得文件映射名
            //fs图片文件映射
            $img_fs_name = $this->fs->upByFileName($tmpfname);
            if($img_fs_name) {
                $fsfile_type_array = explode('.', $img_fs_name);
                $fsfile_name = $fsfile_type_array[0]; 
                $thumb_small_path = $fsfile_type_array[0] . '_48.' . $fsfile_type_array[1];
                $thumb_middle_path = $fsfile_type_array[0] . '_120.' . $fsfile_type_array[1]; 

                //上传size=small的图片
                $meta = array('remote_filename' => $thumb_small_path);
                $fs_small = $this->fs->upByBuff($small_data, 'jpg', $meta);

                //上传size=middle的图片
                $meta = array('remote_filename' => $thumb_middle_path);
                $fs_middle = $this->fs->upByBuff($middle_data, 'jpg', $meta);
            } 


            //获取图片exif信息
            $exif = Exif::get_exif_info($tmpfname);
            $img_exif = $exif['exif_info'];
            $degree = $exif['degree'] ? intval($exif['degree']) : 0;

            $imgsWH = Image::getImagesWidthHight($tmpfname);
            $images_width  = $imgsWH['width'];
            $images_height = $imgsWH['height']; 

            //添加数据到TTServer 
            $value = array("pic_fs_path" => $img_fs_name, "exif" => serialize($img_exif), "width" => $imgsWH['width'], "height" =>  $imgsWH['height']);
            $this->TT->set($file_md5, $value); 
            $pic_id = $this->db->insertData("album_pic",$pic_data_array);
            if($pic_id) {
				$this->db->query("UPDATE album_user_album SET pic_num=pic_num+1 WHERE album_id=$album_id");
			} else {
				return null;
			} 
		}
		if($pic_id) {			
			$middle_md5 = md5($middle_data);
			$fs_middle_path = $this->fs->upByBuff($middle_data, 'jpg');
			//头像中图截图传到独立的位置
			//添加数据到TTServer
			$value = array("pic_fs_path" => $fs_middle_path, "exif" => "", "width" => 120, "height" => 120);
			$this->TT->set($middle_md5, $value);
			$middle_data = array(
					"album_id" => 0,
					"user_id" => $user_id,
					"create_time" => time(),
					"update_time" => time(),
					"upload_ip" => $_SERVER['REMOTE_ADDR'],
					"upload_file_name" => 'middle',
					"file_md5" => $middle_md5,
					"file_size" => strlen($middle),
					"pic_width" => 120,
					"pic_height" => 120,
					"degree" => 0,
					"file_type" => 2,
					"is_animate" => 0
			);
			$m_pid = $this->db->insertData("wp_pic", $middle_data);
			$m_url = Kohana::config('album.recordThumb').'imgs/'.$m_pid.'.jpg';
			$this->db->query("UPDATE album_pic SET is_avatar=0 WHERE album_id=$album_id");
			$this->db->query("UPDATE album_pic SET is_avatar=1 WHERE pic_id=$pic_id");
			//记录头像修改时间
			$this->db->updateData("membersinfo", array("updatetime" => time()), array("uid" => $user_id));
			
			Cache::instance()->delete("momoim_user/" . $user_id);
			return array("pic_id" => $pic_id, "album_id" => $album_id, "middle_url" => $m_url);
		}

	}


	 //修改通讯录头像，手机端上传使用
    public function update_contact_avatar($small_data, $middle_data, $original_data, $pic_data) {
        $user_id = $this->uid;
        //获取通讯录相册
        $album_id = $this->db->getOne("wp_album", "album_id", "user_id = $user_id AND album_flag = '3'");
        if(!$album_id){
            $data = array(
                "album_name" => '通讯录相册',
                "user_id" => $user_id,
                "create_dt" => time(),
                "update_dt" => time(),
                "album_flag" => 3
            );
            $album_id = $this->db->insertData("wp_album", $data); 
        }
        $file_md5 = md5($original_data);
        if(!$this->TT) $this->TT = new TTServer;
        $data = $this->TT->get($file_md5);
        //上传到FS
        include('application/include/FastDFS.php');
        $this->fs = FastDFS::factory('group1');
        if($data) {
            //生成新的格式的FS
            $fsfile_name_array = explode('.', $data['pic_fs_path']);
            $thumb_small_path = $fsfile_name_array[0] . '_48.' . $fsfile_name_array[1];
            $thumb_middle_path = $fsfile_name_array[0] . '_120.' . $fsfile_name_array[1];

            //已存在            
            $dataExif = unserialize($data['exif']);
            $pic_data_array['pic_width'] = $dataExif['拍摄分辨率宽'] ? $dataExif['拍摄分辨率宽'] : $data['width'];
            $pic_data_array['pic_height'] = $dataExif['拍摄分辨率高'] ? $dataExif['拍摄分辨率高'] : $data['height']; 
            $pic_data_array['degree'] = $dataExif['方向']; 

            //上传size=small的图片
            $meta = array('remote_filename' => $thumb_small_path);
            $fs_small = $this->fs->upByBuff($small_data, 'jpg', $meta);

            //上传size=middle的图片
            $meta = array('remote_filename' => $thumb_middle_path);
            $fs_middle = $this->fs->upByBuff($middle_data, 'jpg', $meta);
            
            //插入 数据大wp图片表
            $pic_data_array['album_id'] = $album_id; 
            $pic_data_array['user_id'] = $user_id; 
            $pic_data_array['create_time'] = time(); 
            $pic_data_array['update_time'] = time(); 
            $pic_data_array['upload_ip'] = $_SERVER['REMOTE_ADDR']; 
            $pic_data_array['upload_file_name'] = $pic_data['upload_file_name']; 
            $pic_data_array['file_md5'] = $file_md5; 
            $pic_data_array['file_size'] = strlen($original_data);  
            $pic_id = $this->db->insertData("wp_pic",$pic_data_array);
            if($pic_id) return array("data" => array("0" => Kohana::config('album.recordThumb').'imgs/'.$pic_id.'.jpg', "1" => Kohana::config('album.recordThumb').'imgs/'.$pic_id.'_120.jpg'));
            
            else return null;
        } else {
           //写入用户临时图片文件
           //写入文件
            if (!is_dir(ALBUM_IMAGE.$user_id)) {
                mkdir(ALBUM_IMAGE.$user_id, 0777);
            }
           $tmpfname = ALBUM_IMAGE.$user_id.'/'.$user_id.'_'.$file_md5.'.jpg';
           $handle = fopen($tmpfname, "w");
           fseek($handle, 0); //移动指针到文件开始位置
           fwrite($handle, $original_data);
           fclose($handle);

            //上传到DFS、获得文件映射名
            //fs图片文件映射
            $img_fs_name = $this->fs->upByFileName($tmpfname);
            if($img_fs_name) {
                $fsfile_type_array = explode('.', $img_fs_name);
                $fsfile_name = $fsfile_type_array[0]; 
                $thumb_small_path = $fsfile_type_array[0] . '_48.' . $fsfile_type_array[1];
                $thumb_middle_path = $fsfile_type_array[0] . '_120.' . $fsfile_type_array[1]; 

                //上传size=small的图片
                $meta = array('remote_filename' => $thumb_small_path);
                $fs_small = $this->fs->upByBuff($small_data, 'jpg', $meta);

                //上传size=middle的图片
                $meta = array('remote_filename' => $thumb_middle_path);
                $fs_middle = $this->fs->upByBuff($middle_data, 'jpg', $meta);
            } 


            //获取图片exif信息
            $exif = Exif::get_exif_info($tmpfname);
            $img_exif = $exif['exif_info'];
            $degree = $exif['degree'] ? intval($exif['degree']) : 0;

            $imgsWH = Image::getImagesWidthHight($tmpfname);
            $images_width  = $imgsWH['width'];
            $images_height = $imgsWH['height'];

            //缩略图队列写入内存表                        
            $thumb_array = array();
            $thumb_array['file_path'] = $tmpfname ;
            $thumb_array['fs_path'] = $fsfile_name ;
			
			/*
            $thumbs = $this->create_thumb_by_width_height($images_width, $images_height, true, true);
            foreach ( $thumbs as $key => $value ) {
                if($key == 0) {
                    $thumb_type_array.= $value['type'];
                } else {
                    $thumb_type_array.= ','.$value['type'];
                }
            }

            $thumb_array['thumb_type'] = $thumb_type_array ;
            $thumb_array['images_width'] = $images_width;
            $thumb_array['images_height'] = $images_height;
            $thumb_array['create_time'] = time();                
            $this->add_to_thumb_queue($thumb_array);
            */
            //添加数据到TTServer 
            $value = array("pic_fs_path" => $img_fs_name, "exif" => serialize($img_exif));
            $this->TT->set($file_md5, $value); 

            //写表
            $pic_data_array = array();
            $pic_data_array['pic_width'] = $images_width;
            $pic_data_array['pic_height'] = $images_height; 
            $pic_data_array['degree'] = $degree; 
            $pic_data_array['album_id'] = $album_id; 
            $pic_data_array['user_id'] = $user_id; 
            $pic_data_array['create_time'] = time(); 
            $pic_data_array['update_time'] = time(); 
            $pic_data_array['upload_ip'] = $_SERVER['REMOTE_ADDR']; 
            $pic_data_array['upload_file_name'] = $pic_data['upload_file_name']; 
            $pic_data_array['file_md5'] = $file_md5; 
            $pic_data_array['file_size'] = strlen($original_data);   
            $pic_id = $this->db->insertData("wp_pic",$pic_data_array);
            if($pic_id) return array("data" => array("0" => Kohana::config('album.recordThumb').'imgs/'.$pic_id.'.jpg', "1" => Kohana::config('album.recordThumb').'imgs/'.$pic_id.'_120.jpg')); 
            else return null;        
        } 
    }

	//获取头像
    public function getAvatar($user_id, $size ='middle') {
        $user_id = $user_id ? $user_id : $this->uid;
        if($size == 'small') {
            $type = 48;
        } else {
            $type = 120;
        }
        //获取用户头像照相册ID 
        $result = $this->db->getRow("album_user_album","album_id", "album_default = '2' AND user_id=$user_id");
        $album_id = $result['album_id'];
        if(!$album_id) return null;        
        //获取图片md5
        $file_md5 = $this->db->getOne("album_pic","file_md5","album_id=$album_id AND is_avatar = '1' ORDER BY pic_id DESC");
        if($file_md5) { 
            //获取FS地址
            $this->TT = new TTServer;
            $pic_fs = $this->TT->get($file_md5);
            if($pic_fs['pic_fs_path']) {
                //获取缩略图的FS路径
                include('application/include/FastDFS.php');
                $this->fs = FastDFS::factory('group1');
                $fsfile_name_array = explode('.', $pic_fs['pic_fs_path']);
                $thumb_path = $fsfile_name_array[0] . '_'.$type.'.'. $fsfile_name_array[1];
                
                //获取文件数据流
                $thumb = $this->fs->downToBuff($thumb_path);
                $file_size = strlen($thumb);
                if($file_size) return $thumb;
                else return null;
            }
        } else {
            //没有设置头像,返回默认头像
            return null;
        }
    }

	//修改头像发送动态
    public function sendfacefeed($pid, $album_id = 0, $img = "", $appid = 0) {
        $return = false;
        if ($pid) {
            $feed_flag = 1;
            if($feed_flag == 1) {
                //发送好友动态
                $data = $this->db->getRow("album_pic", "pic_width, pic_height", "pic_id=$pid");
                if($data['pic_height'] >= 780 || $data['pic_width'] >= 780) {
                    if($height >= $width) {
                        $height_780 = 780;
                        $width_780 = intval($data['pic_width'] / ($data['pic_height'] /780));
                    } else {
                        $width_780 = 780;
                        $height_780 = intval($data['pic_height'] / ($data['pic_width'] /780));
                    }
                } else {
                    $height_780 = $data['pic_height'];
                    $width_780 = $data['pic_width'];
                }

                $this->feed = new Feed_Model ();
                if(!$img) $img = Kohana::config('album.recordThumb') . 'thumb/' . $pid . '_120.jpg';
                $realname = sns::getrealname($this->uid);
                $feedkey  = sns::getFeedUniqid('user_face_update');
                $feed = array(
                        'title' => array('uid' => $this->uid, 'name'=> sns::getrealname($this->uid), 'time' => time (), 'appid' => $appid ),
                        'body' => array ('uid' => $this->uid, 'image' => $img, 'name' => $realname, 'pid' => $pid, 'img_780' => Kohana::config('album.recordThumb') . 'thumb/' . $pid . '_780.jpg',  'aid' => $album_id, 'height_780' => $height_780, 'width_780' => $width_780)
                );

                $result = $this->feed->addFeed($this->uid, 'user_face_update', Kohana::config('uap.app.user'), $feed ['title'], $feed ['body'], $feedkey, '', '' , array(), $appid);

                $return = $result ['code'] == 200 ? true : false;
            }
        }

        return $return;
    }

}
