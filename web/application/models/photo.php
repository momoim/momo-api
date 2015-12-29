<?php defined('SYSPATH') or die('No direct script access.');
/**
 */

class Photo_Model extends Model {
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

    //手机端照片上传
    //contentData
    /**
     * 手机端照片上传
     * @param array $contentData 二进制数据
     * @param array $infoData
     * @param string $type 上传类型
     * @param string $flag  上传标识（1日记，2广播，3联系人，4头像，5群组封面）
     */
    public function upload($contentData, $infoData, $type, $flag) {
        $user_id = $this->uid;
        if($type = 'wp') {
            //获取相册ID
            $album_id = $this->db->getOne("wp_album", "album_id", "user_id = $user_id AND album_flag = $flag");
            if(!$album_id) {
                $data = array(
                        "album_name" => '广播相册',
                        "user_id" => $user_id,
                        "create_dt" => time(),
                        "update_dt" => time(),
                        "album_flag" => $flag
                );
                $album_id = $this->db->insertData("wp_album", $data);
            }

            $file_md5 = md5($contentData);

            //上传到FS
            include('application/include/FastDFS.php');
            $this->fs = FastDFS::factory('group1');

            //FS存储地址
            $img_fs_name = $this->fs->upByBuff($contentData, 'jpg');

            //写TTServer
            $value = array("pic_fs_path" => $img_fs_name, "exif" => "", "height" => $infoData['height'], "width" => $infoData['width']);
            $this->TT = new TTServer;
            $this->TT->set($file_md5, $value);

            //插入wp表数据
            $pic_data_array['album_id'] = $album_id;
            $pic_data_array['user_id'] = $user_id;
            $pic_data_array['create_time'] = time();
            $pic_data_array['update_time'] = time();
            $pic_data_array['upload_ip'] = $_SERVER['REMOTE_ADDR'];
            $pic_data_array['upload_file_name'] = 'sj.jpg';
            $pic_data_array['file_md5'] = $file_md5;
            $pic_data_array['file_size'] = $infoData['size'];
            $pic_data_array['file_type'] = $infoData['type'];
            $pic_data_array['pic_width'] = $infoData['width'];
            $pic_data_array['pic_height'] = $infoData['height'];
            $pic_id = $this->db->insertData("wp_pic",$pic_data_array);
            if($pic_id) {
            	$fid=$pic_id+100000;
                if($flag == 2) {
                    //广播返回图片地址不同
                    return array("data" => Kohana::config('album.recordThumb').'imgs/'.$pic_id.'_160.jpg',"fid"=>$fid,"md5"=>$file_md5);
                } else {
                    return array("data" => Kohana::config('album.thumb').'imgs/'.$pic_id.'_160.jpg',"fid"=>$fid,"md5"=>$file_md5);
                }
            }
            else return null;

        }

    }

	//wp的缩略图
    //$show_wh 返回原图的宽度高度
    public function wpThumb($pic_id, $show_wh = false) {
        $thumb = array();
        $thumbUrl = Kohana::config('album.recordThumb');
        $types = array(80,160,320,480,780,1024,1600,48,120);
        foreach($types as $key => $row) {
            $thumb[$key]['url'] = $thumbUrl.'imgs/'.$pic_id.'_'.$row.'.jpg';
            $thumb[$key]['type'] = $row;
            if($show_wh) {
                if(!$width || !$height) {
                    $rs = $this->db->getRow("wp_pic", "pic_width, pic_height", "pic_id=$pic_id");
                    $width = $rs['pic_width'];
                    $height = $rs['pic_height'];
                }
                $thumb[$key]['width'] = $width;
                $thumb[$key]['height'] = $height;
            }
        }
        return $thumb;
    }
	//获取用户的头像信息（独立的头像链接地址）
    public function getLatestAvatar($user_id, $type='120') {
        $picInfo = $this->db->getRow("album_pic", "file_md5, file_size, pic_width, pic_height, degree", "user_id=$user_id AND is_avatar='1'");
        $file_md5 = $picInfo['file_md5'];
        if(!$file_md5) return null;

        $pic_id = $this->db->getOne("wp_pic", "pic_id", "file_md5='$file_md5' AND user_id=$user_id AND appid=1");
        if(!$pic_id) {
            $data = array(
                    "user_id" => $user_id,
                    "album_id" => 0,
                    "create_time" => time(),
                    "update_time" => time(),
                    "upload_ip" => $_SERVER['SERVER_ADDR'],
                    "upload_file_name" => "copy avatar",
                    "file_md5" => $file_md5,
                    "pic_width" => $picInfo['pic_width'],
                    "pic_height" => $picInfo['pic_height'],
                    "degree" => $picInfo['degree'],
                    "appid" => 1
            );
            $pic_id = $this->db->insertData("wp_pic", $data);
            $this->TT = new TTServer;
			$this->TT->set_count($file_md5);
        }
        if($pic_id) return Kohana::config('album.recordThumb'). 'imgs/' . $pic_id."_".$type.".jpg";
        else return null;
    }

    //删除wp图片
    public function delWpPic($pic_id) {
        //获取图片的相关信息
        $uid = $this->uid;
        $picInfo = $this->db->getRow("wp_pic","pic_id, album_id, file_md5", "pic_id=$pic_id AND user_id=$uid");
        if(!$picInfo) return null;
        $data = $this->db->deleteData("wp_pic", array("pic_id" => $pic_id));
        if($data) {
            //TTServer 计数-1
            $this->TT = new TTServer;
            $this->TT->del($picInfo['file_md5']);
            $album_id = $picInfo['album_id'];
            //wp album图片数量-1
            $this->db->query("UPDATE wp_album SET pic_num = pic_num -1 WHERE album_id =$album_id");
            return $data;
        } else {
            return null;
        }
    }

    /**
     * 删除图片
     * @param int $photo_id
     * @return array
     */
    public function delete($photo_id) {
        $rs = $this->db->getRow("album_pic", "album_id, file_md5", "pic_id=$photo_id");
        $album_id = $rs['album_id'];
        $file_md5 = $rs['file_md5'];
        //删除照片表
        $result = $this->db->deleteData("album_pic", array("pic_id" => $photo_id, "user_id" => $this->uid));
        if(!$result) {
            return array('code' => 500, 'data' => array('msg' => ''));
        }

        //删除照片描述表
        //$this->db->deleteData("album_pic_desc", array("pic_id" => $photo_id));

        //修改照片排序
        //$this->updatePicSort($album_id, $photo_id, $flag='del');

        //相册表数量修改（-1）
        $this->db->query("UPDATE album_user_album SET pic_num = pic_num-1 WHERE album_id = '$album_id'");

        //判断是否是相册封面
        $rs = $this->db->getRow("album_user_album", "cover_pic_id, pic_num", "album_id=$album_id");
        $cover_pic_id = $rs['cover_pic_id'];
        if(!$rs['pic_num']) {
            //相册的最后一张照片，删除照片相册的动态
            if(!$this->Feed) $this->Feed = new Feed_Model;
            //动态模板'typeid'=>21,'typename'=>'album'
            $this->Feed->delFeed(21, $album_id);

            $data = array("cover_pic_id" => 0, "cover_pic_url" => "");
            $this->db->updateData("album_user_album", $data, array("album_id" => $album_id));
        } else if($cover_pic_id == $photo_id) {
            //重新设置相册封面
            $cover_pic_id = $this->db->getOne("album_pic", "pic_id", "album_id=$album_id ORDER BY pic_id ASC LIMIT 0,1");
            if($cover_pic_id) {
                $data = array("cover_pic_id" => $cover_pic_id, "cover_pic_url" => "thumb/".$cover_pic_id."_160.jpg");
            } else {
                $data = array("cover_pic_id" => 0, "cover_pic_url" => "");
            }
            $this->db->updateData("album_user_album", $data, array("album_id" => $album_id));
        }

        if(!$this->TT) $this->TT = new TTServer;
        $this->TT->del($file_md5);

        if(!$this->Feed) $this->Feed = new Feed_Model;
        //动态模板'typeid'=>13,'typename'=>'photo_comment'
        $this->Feed->delFeed(13, $photo_id);


        return array('code' => 200);

    }
}
