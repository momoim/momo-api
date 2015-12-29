<?php
/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * 图片模型文件
 */
namespace Models;
use Core;
use Models\ TempSource;
use Mysql;

class Temp {

    public static $instance;

    public static $db;

    public $table = 'fs_temp';
    /** temp表字段 start*/
    private $upload_id;

    public $md5;

    public $size;

    public $type;
    //1图片，2音频，5文件
    public $uid;

    public $cid;

    public $basedir;

    public $filename;
    /** temp表字段 end*/
    public $tmp_source;

    public static function instance() {
        if(!self::$instance) 
            self::$instance = new Temp();
        return self::$instance;
    }

    public function __construct() {
    }

    protected function connectDB() {
        if(!self::$db) {
            self::$db = new Mysql();
            $dbcnf = Core::config('mysql_servers');
            self::$db->connect($dbcnf['host'], $dbcnf['user'], $dbcnf['pwd'], $dbcnf['db'], $dbcnf['pconnect'], true, $dbcnf['charset']);
        }
    }

    protected function setFields($data) {
        $this->upload_id = $data['upload_id'];
        $this->md5 = $data['md5'];
        $this->size = $data['size'];
        $this->type = $data['type'];
        $this->uid = $data['uid'];
        $this->cid = $data['cid'];
        $this->basedir = $data['basedir'];
        $this->filename = $data['filename'];
    }

    private function _getValid() {
        $data['md5'] = Mysql::quote($this->md5);
        $data['size'] = Mysql::quote($this->size);
        $data['type'] = Mysql::quote($this->type);
        $data['uid'] = Core::getUserID();
        $data['cid'] = Mysql::quote($this->cid);
        $data['basedir'] = Mysql::quote($this->basedir);
        $data['filename'] = Mysql::quote($this->filename);
        return $data;
    }

    public function get_upload_id() {
        return $this->upload_id;
    }
    //获取临时文件
    public function getTemp($upload_id) {
        $row_exist = $this->findOne($upload_id);
        $source = new TempSource();
        $source->upload_id = $upload_id;
        $source_exist = $source->ifExist();
        if($row_exist && $source_exist) {
            $this->tmp_source = $source;
            return TRUE;
        } else {
            if($source_exist) 
                $source->deleteSource();
            if($row_exist) 
                $this->delete($upload_id);
        }
        return FALSE;
    }
    //创建临时文件记录
    public function create() {
        if($this->md5 && $this->size) {
            $result = $this->_getValid();
            $fields = implode('`,`', array_keys($result));
            $vals = implode(',', array_values($result));
            $sql = "INSERT INTO {$this->table} (`{$fields}`) VALUES ({$vals})";
            $this->connectDB();
            self::$db->query($sql);
            $this->upload_id = self::$db->insertId();
            if($this->upload_id) {
                $tempsource = new TempSource();
                $updata['upload_id'] = $this->upload_id;
                return $tempsource->create($updata);
            }
        }
        return 0;
    }
    /**
     * 
     * 追加文件
     * @param string $in_path 本地路径
     * @param int $offset 偏移量
     */
    public function append($in_path, $offset) {
        if($this->tmp_source) {
            return $this->tmp_source->appendBuffer($in_path, $offset);
        }
        return FALSE;
    }
    //查找一条指定id的临时文件记录
    public function findOne($upload_id) {
        $user_id = Core::getUserID();
        $sql = "SELECT `md5`,`size`,`type`,`cid`,`basedir`,`filename` FROM {$this->table} WHERE `upload_id`='{$upload_id}' AND `uid`='{$user_id}'";
        $this->connectDB();
        $result = self::$db->fetchFirst($sql);
        if($result) {
            $result['upload_id'] = $upload_id;
            $result['uid'] = $user_id;
            $this->setFields($result);
            return $result;
        }
        return FALSE;
    }
    //删除指定id的临时文件记录
    public function delete($upload_id) {
        $sql = "DELETE FROM {$this->table} WHERE upload_id='{$upload_id}'";
        $this->connectDB();
        return self::$db->query($sql);
    }
    //删除当前记录和临时文件
    public function destroy() {
        $this->delete($this->upload_id);
        $this->upload_id = NULL;
        $this->tmp_source->deleteSource();
        $this->tmp_source = NULL;
    }
}
