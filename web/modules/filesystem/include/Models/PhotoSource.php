<?php
/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * 资源存储模型文件
 */
namespace Models;
use Core;
use ActiveMongo;
use MongoLogger;
use Driver\NDCS;

class PhotoSource extends ActiveMongo {
    //fs.files字段
    public $filename;
    //图片地址
    public $mime;

    public $ref;

    public $meta;

    public $width;

    public $height;

    private $_fdfs;

    function getCollectionName() {
        return 'fs.files';
    }

    function setup() {
        $this->addIndex(array('filename' => 1), array('unique' => 1));
    }
    /**
	 * 
	 * 设置文件名 文件名协议 mime : length : md5 : direction : size [: 6$animate][: 7$field]
	 * @param string $fmime 原图的mime
	 * @param int $flength 原图的大小
	 * @param string $fmd5 原图的md5
	 * @param int $direction 新图的方向，顺时针0,1,2,3
	 * @param int $type 新图的规范尺寸
	 * @param bool $animate 是否要动态的图
	 */
    public function setFilename($fmime, $flength, $fmd5, $direction = 0, $type = 0, $animate = false) {
        $schema = array();
        
        $fmime = strtolower($fmime);
        $flength = intval($flength);
        $fmd5 = strtolower($fmd5);
        if(!in_array($direction, array(1, 2, 3))) 
            $direction = 0;
        if(!in_array($type, Core::config('photo_standard_type'))) 
            $type = 0;
        
        $schema[] = $fmime;
        $schema[] = $flength;
        $schema[] = $fmd5;
        $schema[] = $direction;
        $schema[] = $type;
        //动态的缩略图
        if($animate && $type){
            $schema[] = '6$1';
        }
        
        if($fmime && $flength && $fmd5) {
            //$filename_raw = $fmime . ':' . $flength . ':' . $fmd5 . ':' . $direction . ':' . $type;
            $filename_raw = implode(':', $schema);
            $this->filename = Core::base64url_encode($filename_raw);
        }
    }

    public function extractFilename($filename) {
        $filename_raw = Core::base64url_decode($filename);
        $filename_raw && $file_meta = explode(':', $filename_raw);
        if(count($file_meta) == 5) 
            return $file_meta;
        else 
            return NULL;
    }

    public function getFilename() {
        return $this->filename;
    }
    /**
     * 
     * 此gridfs对象是否存在
     */
    public function ifExist() {
        if($filename = $this->getFilename()) {
            $this->findOne(NULL, NULL, array('filename' => $filename));
            if($this->getID()) {
                return TRUE;
            }
        }
        return FALSE;
    }
    /**
     * 
     * 将本地文件上传到gridfs（已经确认不重复）
     * @param string $source 文件本地路径
     * @param array $updata 上传成功之后要更新文件的元信息（必须有fmime,flength,fmd5原图信息）
     */
    public function upBuffer($source, $updata) {
        $this->reset();
        $this->setFilename($updata['fmime'], $updata['flength'], $updata['fmd5'], $updata['direction'], $updata['type'], $updata['animate']);
        if(!$filename = $this->getFilename()) {
            return FALSE;
        }
        $grid = $this->getGridFS();
        $row['filename'] = $filename;
        $row['mime'] = strtolower($updata['mime']);
        $row['ref'] = 1;
        $row['meta'] = json_encode($updata['meta']);
        $row['width'] = intval($updata['width']);
        $row['height'] = intval($updata['height']);
        header('x-apiperf-fn:'.$filename);
        if($cur_fsid = $grid->storeFile($source, $row)) {
            $this->findOne($cur_fsid);
        }
        if($this->getID()) {
            return TRUE;
        }
        return FALSE;
    }
    /**
     * 
     * 从gridfs下载到本地
     */
    public function downBuffer() {
        $tmp_file = '';
        if($id = $this->getID()) {
            $grid = $this->getGridFS();
            $cursor = $grid->chunks->find(array('files_id' => $id))->sort(array('n' => 1));
            $tmp_file = Core::tempname($filename);
            $f = fopen($tmp_file, 'w');
            if($f) {
                foreach($cursor as $chunk) {
                    fwrite($f, $chunk['data']->bin);
                }
                fclose($f);
            }
        }
        if($tmp_file && is_readable($tmp_file)) 
            return $tmp_file;
        else 
            return NULL;
    }
    /**
     * 
     * 更新本地资源到gridfs
     * @param string $source 本地路径
     
    public function updateBuffer($source){
    	if($id=$this->getID() && is_readable($source)){
    		$grid=$this->getGridFS();
    		return $grid->storeFile($source, array('_id' => $id));
    	}
    	return FALSE;
    }
    */
    /**
     * 
     * 从gridfs直接输出到客户端
     */
    public function output() {
        /*
         * Mongo方式
        $grid=$this->getGridFS();
		
		$cursor = $grid->chunks->find(array('files_id' => $id))->sort(array('n' => 1));
        foreach($cursor as $chunk) {
            echo $chunk['data']->bin;
        }
        */
        
        //$bin = fastdfs_storage_download_file_to_buff($this->fs_group_name, $this->fs_filename);
    	$bin = NDCS::download_file_to_buff($this->fs_group_name, $this->fs_filename);
        
        if(strlen($bin) > 0) {
            Core::header('Content-Type: ' . $this->mime);
            Core::header('Content-Length: ' . strlen($bin));
            
            echo $bin;
            
            Core::quit();
        } else {
            GridFS::fdfs_error($this->fs_group_name, $this->fs_filename);
        }
    }

    public function getGridFS() {
        return new GridFS($this);
        //return $this->_getConnection()->getGridFS();
    }
    /**
     * 
     * md5返溯原图文件的md5
     * @param array $md5_arr MD5数组
     */
    public function getOriginByMD5($md5_arr) {
        $result = array();
        if($md5_arr) {
            $files = $this->_getCollection();
            $cursor = $files->find(array('md5' => array('$in' => $md5_arr)), array('filename', 'md5'));
            while($cursor->hasNext()) {
                $row = $cursor->getNext();
                $filemeta = $this->extractFilename($row['filename']);
                $result[$row['md5']] = $filemeta;
            }
        }
        return $result;
    }
}
