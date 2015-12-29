<?php
namespace Models;
use Core;
use Mongo;
use Driver\NDCS;

class FileSource {

    private static $instance;

    private $conn;

    public $filename;

    public $mime;

    public $ref;

    public $meta;

    public static function instance() {
        if(!self::$instance) {
            self::$instance = new FileSource();
        }
        return self::$instance;
    }

    public function __construct() {
        $gridfs_conf = Core::config('gridfs_servers');
        $mongo = new Mongo($gridfs_conf['host'], $gridfs_conf['opt']);
        $this->conn = $mongo->selectDB($gridfs_conf['db']['file']);
        if(!is_NULL($gridfs_conf['user']) && !is_NULL($gridfs_conf['pwd'])) {
            $this->conn->authenticate($gridfs_conf['user'], $gridfs_conf['pwd']);
        }
    }

    public function _getConnection() {
        return $this->conn;
    }

    public function _getCollection() {
        return $this->_getConnection()->selectCollection('fs.files');
    }

    public function getGridFS() {
        return new GridFS($this);
        //return $this->conn->getGridFS();
    }

    public function reset() {
        $this->_id = NULL;
        $this->filename = NULL;
    }

    public function save() {
        if($this->getID()) {
            return $this->_getCollection()->update(array('_id' => $this->getID()), array('$set' => array('ref' => $this->ref)));
        }
        return FALSE;
    }

    public function getID() {
        return $this->_id;
    }

    public function setFilename($fmime, $flength, $fmd5) {
        $fmime = strtolower($fmime);
        $flength = intval($flength);
        $fmd5 = strtolower($fmd5);
        if($fmime && $flength && $fmd5) {
            $filename_raw = $fmime . ':' . $flength . ':' . $fmd5;
            $this->filename = Core::base64url_encode($filename_raw);
        }
    }

    public function setFields($doc) {
        foreach($doc as $k => $v) {
            $this-> {$k} = $v;
        }
    }

    public function ifExist() {
        if($this->filename) {
            if($doc = $this->_getCollection()->findOne(array('filename' => $this->filename))) {
                $this->setFields($doc);
                return TRUE;
            }
        }
        return FALSE;
    }

    public function upBuffer($source, $updata) {
        $this->reset();
        $this->setFilename($updata['mime'], $updata['size'], $updata['md5']);
        if(!$this->filename) {
            return FALSE;
        }
        $doc = array('filename' => $this->filename, 'mime' => $updata['mime'], 'ref' => 1, 'length' => (int) $updata['size'], 'md5' => $updata['md5'], 'meta' => json_encode($updata['meta']));
        if($cur_fsid = $this->getGridFS()->storeFile($source, $doc)) {
            $this->setFields($doc);
            $this->_id = $cur_fsid;
            return TRUE;
        }
        return FALSE;
    }

    public function output($offset=0,$length=0) {
//         $fdfs = new \FastDFS();

            //创建一个临时文件
            $tmp_file = Core::tempname();
            //从fastdfs下载到本地
//             $fdfs->storage_download_file_to_file($this->fs_group_name, $this->fs_filename, $tmp_file);

            //$fdfs_result = fastdfs_storage_download_file_to_file($this->fs_group_name, $this->fs_filename, $tmp_file, $offset, $length);
            $fdfs_result = NDCS::download_file_to_file($this->fs_group_name, $this->fs_filename, $tmp_file, $offset, $length);
            
            if(!$fdfs_result){
                GridFS::fdfs_error($this->fs_group_name, $this->fs_filename);
            }
            
            $fh = fopen($tmp_file, "rb");
            if($fh) {
                //流输出
                while(!feof($fh)) {
                    echo fread($fh, 4096);
                    @ob_flush();
                    @flush();
                }
                fclose($fh);
                //删除临时文件
                @unlink($tmp_file);
            } else {
                Core::fault(554);
            }

//         $fdfs->tracker_close_all_connections();
        Core::quit();
    }
}
