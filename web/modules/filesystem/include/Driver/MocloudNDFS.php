<?php
namespace Driver;

use Core;
use Uploader;
use MongoLogger;

class MocloudNDFS {
    
    static $session=NULL;

    private $fs_group;

    public $fs_filename;

    public $uploader;

    private $size;

    private $ip_addr;

    private $created_at;

    //文件的硬盘地址
    public $in_path;
    //数据字节
    public $length;
    //数据偏移量
    public $offset;
    
    public function ndfs_session(){
        $ndfs_conf=Core::config('ndfs_servers');
        if(! self::$session){
            $r_conn = ndfs_create_session($ndfs_conf['host'], $ndfs_conf['port']);
            
            if($r_conn['status'] !== 0){
                $this->ndfs_error($r_conn);
            }else{
                $r_login = ndfs_login($r_conn['session'], $ndfs_conf['user'], $ndfs_conf['pwd']);
                
                if($r_login['status'] !== 0){
                    $this->ndfs_error($r_login);
                }
                
                self::$session=$r_conn["session"];
            }
        }
        
        return self::$session;
    }
    
    public static function ndfs_quit(){
        if(function_exists('ndfs_close_session')){
            if(self::$session){
                ndfs_close_session(self::$session);
                self::$session = NULL;
            }
        }
    }
    
    public function ndfs_error($r,$fd=0){
        $this->ndfs_close($fd);
        $bt=debug_backtrace();
        if (is_array($bt[0])){
            $bt[0]['server_addr'] = Core::server_addr();
        }
        MongoLogger::instance()->log('ndcs_error', $bt[0]);
        self::ndfs_quit();
        Core::fault(509);
    }
    
    public function ndfs_open($mode){
        $session = $this->ndfs_session();
        
        if($this->fs_filename){
            $id = intval($this->fs_filename);
            if($id <= 0){
                MongoLogger::instance()->log('ndcs_error', $this->fs_filename);
                return FALSE;
            }
            $r=ndfs_open_file_by_id($session, $id, $mode, 7, NDFS_FILE_OPEN, 0);
            if($r['status'] !== 0){
                $this->ndfs_error($r);
            }
            $fd = $r['fd'];
        }else{
            $r=ndfs_open_file_by_id($session, 0, $mode, 7, NDFS_FILE_CREATE, 0);
            if($r['status'] !== 0){
                $this->ndfs_error($r);
            }
            $fd = $r['fd'];
            
            $r=ndfs_query_file_info($session, $fd);
            if($r['status'] !== 0){
                $this->ndfs_error($r,$fd);
            }
            
            $this->fs_filename=$r['id'];
        }
        return $fd;
    }
    
    public function ndfs_close($fd){
        if($fd){
            ndfs_close_file($this->ndfs_session(), $fd);
        }
    }
    
    

    public function __construct($fs_group = '', $fs_filename = '') {
        $this->fs_group = $fs_group;
        $this->fs_filename = $fs_filename;
    }
    
    public function get_md5() {
        $uploader = $this->_get_uploader();
        return $uploader ? $uploader->getMD5() : '';
    }

    public function get_mime() {
        $uploader = $this->_get_uploader();
        return $uploader ? $uploader->getMIME() : '';
    }

    public function get_info() {
        $uploader = $this->_get_uploader();
        return $uploader ? $uploader->getInfo() : array();
    }

    public function get_meta() {
        $uploader = $this->_get_uploader();
        return $uploader ? $uploader->getMeta() : array();
    }

    public function get_size() {
        if(!$this->size) 
            $this->_update_info();
        return $this->size;
    }

    public function get_ip_addr() {
        if(!$this->ip_addr) 
            $this->_update_info();
        return $this->ip_addr;
    }

    public function get_created_at() {
        if(!$this->created_at) 
            $this->_update_info();
        return $this->created_at;
    }
    /*
     * 更新元信息的过程
     */
    private function _update_info() {
        if($this->fs_filename){
            $r=ndfs_query_info_by_id($this->ndfs_session(), $this->fs_filename);
            if($r['status'] === 0){
                $this->size = $r['size'];
                $this->created_at = $r['creation_time'];
            }
        }
    }
    
    /*
     * 直接上传内存数据
     */
    public function append_buff() {
        /*
        $data=file_get_contents($this->in_path);
        //实际大小与参数不符
        if(strlen($data) != $this->length) 
            return FALSE;
        */
        $inh=fopen($this->in_path, 'rb');
        if(! $inh){
            return FALSE;
        }
        $fh=$this->ndfs_open(NDFS_ACCESS_WRITE);
        if($fh){
            $offset = $this->offset;
            while(! feof($inh)){
                $data=fread($inh, 262144);
                $r=ndfs_write_file($this->ndfs_session(), $fh, $offset, -1, $data);
                if($r['status'] !== 0){
                    fclose($inh);
                    $this->ndfs_error($r,$fh);
                }
                $offset += strlen($data);
            }
            fclose($inh);
            
            if($offset-$this->offset > 0){
                $r=ndfs_query_file_info($this->ndfs_session(), $fh);
                if($r['status'] !== 0){
                    $this->ndfs_error($r,$fh);
                }else{
                    $this->size = $r['size'];
                    $this->created_at = $r['creation_time'];
                    $this->offset = $offset;
                    $fs_file = array('group_name' => $this->fs_group, 'filename' => $this->fs_filename);
                }
            }
            
            $this->ndfs_close($fh);
        }
        
        if($fs_file){
            return $fs_file;
        }else{
            return FALSE;
        }
    }
    
    /*
     * 需要下载完整的数据到本地计算
     */
    private function _get_uploader() {
        if(!$this->uploader) {
            //下载到本地临时文件，在创建成功之后需要删除它
            $tmp_filename = Core::tempname($this->fs_filename);
            
            $fh=$this->ndfs_open(NDFS_ACCESS_READ);
            if($fh){
                $r=ndfs_query_file_info($this->ndfs_session(), $fh);
                if($r['status'] !== 0){//错误终断
                    $this->ndfs_error($r,$fh);
                }
                $size=$r['size'];
                $offset = 0;
                $content='';
                while(TRUE){
                    $r=ndfs_read_file($this->ndfs_session(), $fh, $offset, $size);
                    if($r['status'] !== 0){//错误终断
                        $this->ndfs_error($r,$fh);
                    }
                    
                    $length=strlen($r['read_content']);
                    
                    if($length>0){
                        $content .= $r['read_content'];
                        $offset += $length;
                    }else{
                        break;
                    }
                }
                if($offset>0 && file_put_contents($tmp_filename, $content)){
                    $uploader = new Uploader();
                    $uploader->process($tmp_filename);
                    $this->uploader = $uploader;
                }
                
                $this->ndfs_close($fh);
            }
        }
        
        return $this->uploader;
    }

    public function get_uploader() {
        return $this->_get_uploader();
    }

    public function output($offset=0, $actualize=TRUE) {
        $data_buff = '';
        $fh=$this->ndfs_open(NDFS_ACCESS_READ);
        if($fh){
            $r=ndfs_query_file_info($this->ndfs_session(), $fh);
            if($r['status'] !== 0){//错误终断
                $this->ndfs_error($r,$fh);
            }
            $size=$r['size'];
            if($actualize){
                if($offset > 0){
                    Core::header("Content-Length:".($size-$offset));
                    Core::header("Content-Range:bytes {$offset}-".($size-1)."/{$size}");
                }else{
                    Core::header("Content-Length:$size");
                }
            }
            //$offset=0;
            while(TRUE){
                $r=ndfs_read_file($this->ndfs_session(), $fh, $offset, $size);
                if($r['status'] !== 0){//错误终断
                    $this->ndfs_error($r,$fh);
                }
                
                $length=strlen($r['read_content']);
                
                if($length>0){
                    $offset += $length;
                    if($actualize){
                        echo $r['read_content'];
                        @ob_flush();
                        @flush();
                    }else{
                        $data_buff .= $r['read_content'];
                    }
                }else{
                    break;
                }
            }
            
            $this->ndfs_close($fh);
        }
        
        return $data_buff;
    }
    /*
     * 删除文件存储
     */
    public function destroy() {
        if($this->fs_filename){
            ndfs_delete_by_id($this->ndfs_session(), $this->fs_filename);
        }
    }
    
    public function clear() {
        if($this->fs_filename) {
            //清除临时文件
            $tmp_filename = Core::tempname($this->fs_filename);
            @unlink($tmp_filename);
        }
    }
}
