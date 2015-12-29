<?php
namespace Models;
use Core;
use Uploader;
use Mysql;

class FileDirectory {

    public static $db;

    public $nodesPath = array();

    public $entries = array();

    public function __construct($uid = 0) {
        if($uid) {
            $this->uid = $uid;
        } else {
            $this->uid = Core::getUserID();
        }
    }

    protected function connectDB() {
        if(!self::$db) {
            self::$db = new Mysql();
            $dbcnf = Core::config('mysql_servers');
            self::$db->connect($dbcnf['host'], $dbcnf['user'], $dbcnf['pwd'], $dbcnf['db'], $dbcnf['pconnect'], true, $dbcnf['charset']);
        }
    }

    public function get_fid() {
        return $this->fid;
    }

    public function setFields($row) {
        $this->fid = $row['fid'];
        $this->parent_id = $row['parent_id'];
        $this->uid = $row['uid'];
        $this->type = $row['type'];
        $this->path = $row['path'];
        $this->file_count = $row['file_count'];
        $this->size = $row['size'];
    }

    public function findOne($dirpath) {
        $sql = "SELECT * FROM `fs_fileentry`
				WHERE `type`=2 AND `uid`=" . $this->uid . " AND `path`=" . Mysql::quote($dirpath);
        $this->connectDB();
        if($row = self::$db->fetchFirst($sql)) {
            $this->setFields($row);
            return TRUE;
        } else {
            if($dirpath == '/home') {
                //初始化根目录
                return $this->create('/home', 0);
            }
        }
        return FALSE;
    }

    public function create($dirpath, $parent_id) {
        $dbname = '';
        $time = time();
        $sql = "INSERT INTO `fs_fileentry` (`parent_id`,`uid`,`type`,`path`,`created_at`,`mtime`,`desc`,`file_count`,`size`,`mime`,`ext`)
				VALUES({$parent_id}," . $this->uid . ",2," . Mysql::quote($dirpath) . ",{$time},{$time},'',0,0,'','')";

        $this->connectDB();
        if(self::$db->query($sql)) {
            $row = array('fid' => self::$db->insertID(), 'parent_id' => $parent_id, 'uid' => $this->uid, 'type' => 2, 'path' => $dirpath, 'file_count' => 0, 'size' => 0,);
            $this->setFields($row);

            return TRUE;
        }
        return FALSE;
    }

    public function calculateStat($fid) {
        $sql = "SELECT SUM(`size`) as sumsize,SUM(`file_count`) as sumcount FROM `fs_fileentry` WHERE `parent_id`=" . $fid;
        $this->connectDB();
        if($row = self::$db->fetchFirst($sql)) {
            $sql = "UPDATE `fs_fileentry` SET `size`=" . $row['sumsize'] . ",`file_count`=" . $row['sumcount'] . " WHERE `type`=2 AND `fid`=" . $fid;
            self::$db->query($sql);
        }
    }

    public function updateStat($size_plus, $filecount_plus) {
        if($size_plus == 0) 
            return;
        $allpaths = $this->getAllPaths();
        $this->connectDB();
        foreach($allpaths as $p) {
            $sql = "UPDATE `fs_fileentry` SET `size`=`size`+({$size_plus}),`file_count`=`file_count`+({$filecount_plus}) WHERE `fid`=" . $p['fid'];
            self::$db->query($sql);
        }
    }

    public function getAllPaths() {
        if(!$this->nodesPath) {
            $path_arr = explode('/', trim($this->path, '/'));
            $parentsPath = array();
            while(count($path_arr) > 0) {
                $parentsPath[] = Mysql::quote('/' . implode('/', $path_arr));
                array_pop($path_arr);
            }
            if($parentsPath) {
                $sql = "SELECT * FROM `fs_fileentry`
					WHERE `type`=2 AND `uid`=" . $this->uid . " AND `path` IN (" . implode(',', $parentsPath) . ")";
                $this->connectDB();
                $query = self::$db->query($sql);
                while($row = self::$db->fetchArray($query)) {
                    $this->nodesPath[] = $row;
                }
            }
        }
        return $this->nodesPath;
    }
    /**
     *
     * 检查文件夹命名规范
     * @param unknown_type $dirname
     */
    public function getFilename($filename) {
        $filename = trim($filename);
        if($filename == '' || $filename == '.' || $filename == '..') 
            return FALSE;
        if(preg_match('@\\\|\/|\:|\*|\?|\"|\<|\>|\|@', $filename)) {
            return FALSE;
        }
        return Core::convertToUTF8($filename);
    }

    public function makeDir($dirname) {
        if($dir_name = $this->getFilename($dirname)) {
            $dirpath = $this->path . '/' . $dir_name;
            $dirModel = new FileDirectory();
            if($dirModel->findOne($dirpath)) {
                return $dirModel;
            }
            if($dirModel->create($dirpath, $this->get_fid())) {
                return $dirModel;
            }
        }
        return FALSE;
    }
    /**
     *
     * 创建新文件
     * @param FileDirectory $basedir
     * @param Uploader $uploader
     */
    public function makeFile(Uploader $uploader) {
        $filename = $uploader->getTitle();
        if($file_name = $this->getFilename($filename)) {
            $filepath = $this->path . '/' . $file_name;
            preg_match("/\.([a-zA-Z0-9]{2,4})$/", $file_name, $match);
            $ext = $match[1] ? strtolower($match[1]) : '';
            $fileModel = new FileEntry($this->uid);
            if($fileModel->findOne($filepath)) {
                $filepath = $fileModel->getRecoverPath();
                //获取新文件路径名
            }
            //视频截图
            $thumbID = intval($uploader->thumb_id);
            if(!$thumbID){
                $thumbInstance = $uploader->getThumbInstance();
                if($thumbInstance){
                    //更正截图的方向
                    $fileinfo = $uploader->getInfo();
                    if($fileinfo['orientation']){
                        if ($fileinfo['orientation'] < 0){
                            $angle = intval(360+$fileinfo['orientation']);
                        }else{
                            $angle = intval($fileinfo['orientation']);
                        }
                        $imagine = new \Imagine\Imagick\Imagine();
                        $image = $imagine->open($thumbInstance->tmpfile);
                        $image->rotate($angle)->save($thumbInstance->tmpfile);
                    }
                    
                    $photoModel = new Photo();
                    if($photoModel->create($thumbInstance, array('ctrl_type' => 2))) {
                        $thumbID = $photoModel->get_pid();
                    }
                }
            }
            $meta = array('oid' => intval($uploader->oid), 'uid' => $this->uid, 'source' => $uploader->tmpfile, 'size' => $uploader->getLength(), 'mime' => $uploader->getMIME(), 'md5' => $uploader->getMD5(), 'ext' => $ext, 'cid' => $uploader->cid ? $uploader->cid : 0, 'ctrl_type' => $uploader->ctrl_type ? $uploader->ctrl_type : 0, 'thumb_id' => $thumbID, 'meta' => $uploader->getInfo(),);
            if($fileModel->create($filepath, $this->get_fid(), $meta)) {
                $this->updateStat($fileModel->size, 1);
                return $fileModel;
            }
        }
        return FALSE;
    }

    public function getAllEntries() {
        if(!$this->entries) {
            $sql = "SELECT * FROM `fs_fileentry` WHERE `parent_id`=" . $this->get_fid();
            $this->connectDB();
            $query = self::$db->query($sql);
            $entries = array();
            while($row = self::$db->fetchArray($query)) {
                $this->entries[] = $row;
            }
        }
        return $this->entries;
    }

    public function delete($force) {
        if($force) {
            $likepath = $this->path . '/%';
            $fid = $this->fid;
            $sql = "DELETE FROM `fs_fileentry` WHERE `uid`=" . $this->uid . " AND (`fid`={$fid} OR `path` like '{$likepath}')";
        } else {
            if($this->getAllEntries()) {
                return FALSE;
                //还有文件或者文件夹
            } else {
                $sql = "DELETE FROM `fs_fileentry` WHERE `type`=2 AND `uid`=" . $this->uid . " AND `fid`=" . $this->fid;
            }
        }
        $this->connectDB();
        if(self::$db->query($sql)) {
            if($force) {
                $size_plus = 0 - $this->size;
                $filecount_plus = 0 - $this->file_count;
                $this->updateStat($size_plus, $filecount_plus);
            }
            $this->fid = 0;
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function as_array() {
        $this->getAllEntries();
        return get_object_vars($this);
    }
}
