<?php
/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * 资源存储模型文件支持本地硬盘和gridfs作为临时文件，gridfs方式受chunksize的影响
 * 可使用ini_set('mongo.chunk_size',newsize)设置
 */
namespace Models;
use Core;

class TempSource {

    private $file_schame;
    //fs.files字段
    public $upload_id;

    public $md5;

    public $length;

    public function reset() {
        $this->upload_id = 0;
        $this->md5 = '';
        $this->length = 0;
    }
    /**
     * 
     * 创建临时的gridfs对象
     * @param const $driver 指定驱动（支持本地和gridfs，但是gridfs受chunk_size制约）
     */
    public function __construct() {
        $this->file_schame = Core::config('dir_tmp');
    }
    /**
     * 
     * 临时文件的主键规则，直接用upload_id做主键
     */
    public function getFilename() {
        if($this->upload_id) {
            return 'tmp' . $this->upload_id;
        }
        return NULL;
    }
    /**
     * 
     * 此临时文件对象是否存在
     */
    public function ifExist() {
        if($filename = $this->getFilename()) {
            $filepath = $this->file_schame . $filename;
            if(is_readable($filepath)) {
                $this->md5 = md5_file($filepath);
                $this->length = filesize($filepath);
                return TRUE;
            }
        }
        return FALSE;
    }
    /**
     * 
     * 创建临时文件
     * @param array $updata 要更新的字段
     */
    public function create($updata) {
        $this->reset();
        if($updata['upload_id']) {
            $this->upload_id = $updata['upload_id'];
        } else {
            return FALSE;
        }
        $dest = $this->file_schame . $this->getFilename();
        $f = fopen($dest, 'w');
        if($f) {
            fclose($f);
            $this->md5 = md5('');
            $this->length = 0;
            return TRUE;
        }
        return FALSE;
    }
    /**
     * 
     * 文件追加
     * @param string $source 本地文件地址
     * @param int $offset 偏移量，如果不传则从文件末尾开始追加
     */
    public function appendBuffer($source, $offset = NULL) {
        $f1 = fopen($source, 'r');
        if($f1) {
            $dest = $this->file_schame . $this->getFilename();
            if(is_null($offset)) {
                $append_mode = TRUE;
                $f2 = fopen($dest, 'a');
            } else {
                $append_mode = FALSE;
                $f2 = fopen($dest, 'r+');
            }
            if($f2) {
                if(!$append_mode) {
                    fseek($f2, $offset, SEEK_SET);
                }
                while($data = fread($f1, 8096)) {
                    fwrite($f2, $data);
                }
                $this->md5 = md5_file($dest);
                $stat = fstat($f2);
                $this->length = $stat['size'];
                fclose($f2);
                return TRUE;
            }
            fclose($f1);
        }
        return FALSE;
    }
    /**
     * 
     * 下载临时文件到本地分析mime
     */
    public function downBuffer() {
        if($filename = $this->getFilename()) {
            $filepath = $this->file_schame . $filename;
            if(is_readable($filepath)) 
                return $filepath;
        }
        return NULL;
    }
    /**
     * 
     * 删除临时文件
     */
    public function deleteSource() {
        $done = FALSE;
        if($filename = $this->getFilename()) {
            $filepath = $this->file_schame . $filename;
            if($done = unlink($filepath)) {
                $this->reset();
            }
        }
        return $done;
    }
}
