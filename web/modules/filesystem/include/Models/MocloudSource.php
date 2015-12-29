<?php
namespace Models;
use Core;
use Uploader;
use MongoLogger;
/**
 * 
 * 此类是对底层储存操作的封装
 * 包括获取文件数据，追加文件内容，获取存储的元数据等。
 *
 */
class MocloudSource {
    const DRIVER_FDFS = 1;
    const DRIVER_NDCS = 2;

    private $driver;

    private $fs_group;

    private $fs_filename;

    private $uploader;

    private $size;

    private $ip_addr;

    private $created_at;
    
    //文件的硬盘地址
    public $in_path;
    //数据字节
    public $length;
    //数据偏移量
    public $offset;

    private function _get_ndcs_filename() {
        return Core::config('ndcs_mocloud') . $this->fs_group . '/' . $this->fs_filename;
    }

    public function __construct($fs_group = '', $fs_filename = '', $driver = self::DRIVER_FDFS) {
        $this->fs_group = $fs_group;
        $this->fs_filename = $fs_filename;
        $this->driver = $driver;
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
     * 直接上传内存数据
     */
    public function append_buff() {
        $data = file_get_contents($this->in_path);
        //实际大小与参数不符
        if(strlen($data) != $this->length) 
            return FALSE;
                
        if($this->driver === self::DRIVER_FDFS) {
            if($this->fs_group && $this->fs_filename) {
                $fs_file = fastdfs_storage_append_by_filebuff($data, $this->fs_group, $this->fs_filename);
            } else {
                $fs_file = fastdfs_storage_upload_appender_by_filebuff($data);
                $this->fs_group = $fs_file['group_name'];
                $this->fs_filename = $fs_file['filename'];
            }
        } elseif($this->driver === self::DRIVER_NDCS) {
            $fs_path = $this->_get_ndcs_filename();
            if(!file_exists($fs_path)) {
                $op_mode = 'wb';
            } else {
                $op_mode = 'r+b';
            }
            
            $fh = fopen($fs_path, $op_mode);
            if($fh) {
                fseek($fh,$this->offset);
                $done = fwrite($fh, $data);
                if(!$done || $done != $this->length) {
                    MongoLogger::instance()->log('ndcs_error', "write {$op_mode}: {$fs_path}, result: {$done}, raw length: {$this->length}");
                } else {
                    $fs_file = array('group_name' => $this->fs_group, 'filename' => $this->fs_filename);
                }
                fclose($fh);
            } else {
                MongoLogger::instance()->log('ndcs_error', "open {$op_mode}: {$fs_path}");
            }
        }
        if($fs_file) {
            $this->_update_info();
            $this->offset += $this->length;
            return $fs_file;
        } else {
            return FALSE;
        }
    }
    
    /*
     * 更新元信息的过程
     */
    private function _update_info() {
        if($this->driver === self::DRIVER_FDFS) {
            if($this->fs_group && $this->fs_filename) {
                $fs_file_info = fastdfs_get_file_info($this->fs_group, $this->fs_filename);
                $this->size = $fs_file_info['file_size'];
                $this->ip_addr = $fs_file_info['source_ip_addr'];
                $this->created_at = $fs_file_info['create_timestamp'];
            }
        } elseif($this->driver === self::DRIVER_NDCS) {
            $fh = fopen($this->_get_ndcs_filename(), 'rb');
            if($fh) {
                $fstat = fstat($fh);
                $this->size = $fstat['size'];
                $this->created_at = $fstat['ctime'];
                fclose($fh);
            }else{
                MongoLogger::instance()->log('ndcs_error', 'open rb: ' . $this->_get_ndcs_filename());
            }
        }
    }
    /*
     * 需要下载完整的数据到本地计算
     */
    private function _get_uploader() {
        if(!$this->uploader) {
            //下载到本地临时文件，在创建成功之后需要删除它
            $tmp_filename = Core::tempname(Core::base64url_encode($this->fs_group . '/' . $this->fs_filename));
            
            $uploader = new Uploader();
            if($this->driver === self::DRIVER_FDFS) {
                if($this->fs_group && $this->fs_filename) {
                    if(fastdfs_storage_download_file_to_file($this->fs_group, $this->fs_filename, $tmp_filename)) {
                        $uploader->process($tmp_filename);
                        $this->uploader = $uploader;
                    }
                }
            } elseif($this->driver === self::DRIVER_NDCS) {
                if(!copy($this->_get_ndcs_filename(), $tmp_filename)){
                    MongoLogger::instance()->log('ndcs_error', 'copy: ' . $this->_get_ndcs_filename());
                }else{
                    $uploader->process($tmp_filename);
                    $this->uploader = $uploader;
                }
            }
        }
        return $this->uploader;
    }

    public function get_uploader() {
        return $this->_get_uploader();
    }
    /*
     * 删除文件存储
     */
    public function destroy() {
        if($this->driver === self::DRIVER_FDFS) {
            if($this->fs_group && $this->fs_filename) {
                fastdfs_storage_delete_file($this->fs_group, $this->fs_filename);
            }
        } elseif($this->driver === self::DRIVER_NDCS) {
            @unlink($this->_get_ndcs_filename());
        }
    }

    public function output() {
        if($this->driver === self::DRIVER_FDFS) {
            if($this->fs_group && $this->fs_filename) {
                /*
                $offset=0;
                while($data=fastdfs_storage_download_file_to_buff($this->fs_group,$this->fs_filename,$offset,262144)){
                    echo $data;
                    $offset+=strlen($data);
                    @ob_flush();
                    @flush();
                }
                */
                echo fastdfs_storage_download_file_to_buff($this->fs_group, $this->fs_filename);
            }
        } elseif($this->driver === self::DRIVER_NDCS) {
            $fh = fopen($this->_get_ndcs_filename(), 'rb');
            if($fh) {
                while(!feof($fh)) {
                    echo fread($fh, 4096);
                    @ob_flush();
                    @flush();
                }
                fclose($fh);
            } else {
                MongoLogger::instance()->log('ndcs_error', 'open rb: ' . $this->_get_ndcs_filename());
            }
        }
    }

    public function clear() {
        if($this->fs_group && $this->fs_filename) {
            //清除临时文件
            $tmp_filename = Core::tempname(Core::base64url_encode($this->fs_group . '/' . $this->fs_filename));
            @unlink($tmp_filename);
        }
    }
}
