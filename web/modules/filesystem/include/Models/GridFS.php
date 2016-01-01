<?php
namespace Models;
use Core;
use Driver\NDCS;
//use FastDFS;

class GridFS {

    private $parent;

    private $fileid;
    //private $_chunksize=524288;
    private $_chunksize = 0;

    private static $fdfs;

    public function __construct($parent) {
        $this->parent = $parent;
//         if(!self::$fdfs) {
//             self::$fdfs = new FastDFS();
//         }
    }

    public static function fdfs_error(){
        $err = array(
                'no' => fastdfs_get_last_error_no(),
                'info' => fastdfs_get_last_error_info(),
                'args' => func_get_args(),
                'server_addr' => Core::server_addr(),
        );
        MongoLogger::instance()->log('fdfs_error', $err);
        Core::header('x-apiperf-fdfserr: ' . json_encode($err));
        Core::fault(510);
    }
    
    public static function fdfs_quit(){
        if(function_exists('fastdfs_tracker_close_all_connections')){
            fastdfs_tracker_close_all_connections();
        }
    }

    public function filesize($filename) {
        $size = filesize($filename);
        if(!$size) {
            if($fh = fopen($filename, 'rb')) {
                $stat = fstat($fh);
                $size = $stat['size'];
                fclose($fh);
            }
        }
        return $size;
    }

    public function storeFile($source, $fields) {
        $r = NULL;
        if(is_readable($source)) {
//对象方式
//             self::$fdfs->tracker_get_connection();
//             $r = self::$fdfs->storage_upload_by_filename($source);
//             self::$fdfs->tracker_close_all_connections();
            //$r = fastdfs_storage_upload_by_filename($source);
        	$r = NDCS::upload_by_filename($source);
        	
            if(!$r){
                self::fdfs_error();
            }
            
            usleep(300000);
        }
        if($r) {
            !$fields['md5'] && $fields['md5'] = md5_file($source);
            !$fields['length'] && $fields['length'] = $this->filesize($source);
            $fields['fs_group_name'] = $r['group_name'];
            $fields['fs_filename'] = $r['filename'];
            $mongocol = $this->parent->_getCollection();
            $r2 = $mongocol->insert($fields);
            if($r2) 
                return $fields['_id'];
        }
        return NULL;
    }

    public function __get($name) {
        if($name == 'chunks') 
            return $this;
    }

    public function find($field) {
        return $this;
    }

    public function sort() {
        /*
         $this->chunk=0;
         $this->chunks_num=ceil($this->parent->length/$this->_chunksize);

         $chunk=array('data'=>$this);
         $cursor=array();

         for($i=0;$i<$this->chunks_num;$i++){
         $cursor[]=$chunk;
         }
         */
//         $fs_group_name = $this->parent->fs_group_name;
//         $fs_filename = $this->parent->fs_filename;
//         $file_offset = 0;
//对象方式
//         $this->bin = self::$fdfs->storage_download_file_to_buff($fs_group_name, $fs_filename, $file_offset, $this->_chunksize);
        //$this->bin = fastdfs_storage_download_file_to_buff($this->parent->fs_group_name, $this->parent->fs_filename);
    	$this->bin = NDCS::download_file_to_buff($this->parent->fs_group_name, $this->parent->fs_filename);
    	
    	if(strlen($this->bin) > 0) {
            $chunk = array('data' => $this);
            $cursor[] = $chunk;
            return $cursor;
        }else{
            self::fdfs_error();
        }
        
    }

}
