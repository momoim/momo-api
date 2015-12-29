<?php
namespace Models;
use Core;
use Uploader;
use Mysql;
use Driver\NDCS;

class FileEntry {

    public static $instance;

    public static $db;

    private $fid;

    public $oid;

    public $uid;

    public $path;

    public $created_at;

    public $mtime;

    public $file_count;

    public $size;

    public $type;

    public $mime;

    public $md5;

    public $ext;

    public $cid;

    public $ctrl_type;
    
    public $thumb_id;

    public function __construct($uid = 0) {
        if($uid) {
            $this->uid = $uid;
        } else {
            $this->uid = Core::getUserID();
        }
    }

    public static function instance() {
        if(!self::$instance) 
            self::$instance = new FileEntry();
        return self::$instance;
    }

    protected function connectDB() {
        if(!self::$db) {
            self::$db = new Mysql();
            $dbcnf = Core::config('mysql_servers');
            self::$db->connect($dbcnf['host'], $dbcnf['user'], $dbcnf['pwd'], $dbcnf['db'], $dbcnf['pconnect'], true, $dbcnf['charset']);
        }
    }

    public function checkPerms($session_uid) {
        if(!$this->get_fid()) {
            // 找不到资源
            return FALSE;
        }
        if($this->uid == $session_uid) {
            // 资源是自己的
            return TRUE;
        }
        $sql = "SELECT * FROM `fs_fileentry_sharelog` WHERE `fid` = " . $this->get_fid();
        $this->connectDB();
        $query = self::$db->query($sql);
        $perms_mix_id = array();
        while($row = self::$db->fetchArray($query)) {
            $perms_mix_id[] = $row['mix_id'];
        }
        if(!$perms_mix_id) {
            //没有分享记录
            return FALSE;
        }
        $photoModel = new Photo();
        $perms = $photoModel->getAllMixid(array('group', 'action', 'friend'), $session_uid);
        $permission = FALSE;
        foreach($perms_mix_id as $mix_id) {
            foreach($perms as $perm) {
                if($perm == $mix_id) {
                    $permission = TRUE;
                    //匹配权限
                    break;
                }
            }
            if($permission) 
                break;
        }
        return $permission;
    }

    public function isFileAccess($session_uid) {
        if($this->type != 1) {
            //不是文件
            return FALSE;
        }
        return $this->checkPerms($session_uid);
    }
    //资源链接
    public function geturi($fid, $suffix = '') {
        return Core::config('file_prefix') . Core::getSourceID($fid, Core::getUserID(), time()) . $suffix;
    }
    
    public function geturiByMD5($md5_arr, $self = FALSE){
        $r = array();
        if(!$md5_arr) {
            return $r;
        }
        if(!is_array($md5_arr)) {
            $md5_arr = array($md5_arr);
        }
        $md5_arr = array_map('strtolower', $md5_arr);
        //构建sql查询语句
        $sql = "SELECT `fid`,`md5`,`path`,`mime`,`thumb_id` FROM `fs_fileentry` WHERE 0 ";
        foreach($md5_arr as $md5) {
            $sql .= "OR (`md5`='{$md5}'" . ($self ? (" AND `uid`=" . Core::getUserID()) : "") . ") ";
        }
        $this->connectDB();
        $query = self::$db->query($sql);
        $result = array();
        while($row = self::$db->fetchArray($query)) {
            $md5 = $row['md5'];
            $id = intval($row['fid']);
            $thumb_id = intval($row['thumb_id']);
            $result[$md5] = array('id' => $id, 'src' => $this->geturi($id), 'mime' => $row['mime'], 'name' => $row['name']);
            
            if($thumb_id){
                $photoModel = new Photo();
                if($photoModel->findOne($thumb_id)){
                    $result[$md5]['thumb']['id'] = $photoModel->get_pid();
                    $result[$md5]['thumb']['mime'] = $photoModel->mime;
                    $imgurls = $photoModel->geturi($thumb_id, 130);
                    $result[$md5]['thumb']['src'] = $imgurls[0];
                }
            }
            
        }
        foreach($md5_arr as $md5) {
            $r[] = $result[$md5] ? $result[$md5] : array('id' => 0, 'src' => '');
        }
        return $r;
    }
    //获取资源id
    public function get_fid() {
        return $this->fid;
    }

    public function findOne($filepath) {
        $sql = "SELECT * FROM `fs_fileentry`
				WHERE `type`=1 AND `uid`=" . Core::getUserID() . " AND `path`=" . Mysql::quote($filepath);
        $this->connectDB();
        if($row = self::$db->fetchFirst($sql)) {
            $this->setFields($row);
            return TRUE;
        }
        return FALSE;
    }

    public function getByFiletype($filetype) {
        if(!$this->get_fid()) 
            return NULL;
        if(trim($filetype) == '') 
            return NULL;
        if($this->oid > 0) 
            return NULL;
        //此文件不是原始上传文件
        $sql = "SELECT * FROM `fs_fileentry`
				WHERE `oid`=" . $this->fid . " AND `ext`='$filetype'";
        $this->connectDB();
        if($row = self::$db->fetchFirst($sql)) {
            $newFileEntry = new FileEntry();
            $newFileEntry->setFields($row);
            return $newFileEntry;
        }
        return NULL;
    }

    public function putByFiletype($filetype, $source) {
        if(!$this->get_fid()) 
            return NULL;
        if(trim($filetype) == '') 
            return NULL;
        if($this->oid > 0) 
            return NULL;
        //此文件不是原始上传文件
        $uploader = new Uploader();
        $uploader->process($source);
        $uploader->ctrl_type = $this->ctrl_type;
        $uploader->filename = $this->getFilename() . '.' . $filetype;
        $uploader->oid = $this->fid;
        $uploader->thumb_id = $this->thumb_id;
        
        $basedir = '/home';
        $basedirModel = new FileDirectory($this->uid);
        if(!$basedirModel->findOne($basedir)) {
            Core::header('x-apiperf-nondir:' . json_encode($basedir));
            Core::fault(500);
        }
        if($newFileEntry = $basedirModel->makeFile($uploader)) {
            return $newFileEntry;
        }
        return FALSE;
    }

    public function getRecoverPath() {
        if(!$this->get_fid()) 
            return '';
        preg_match("/(\.[a-zA-Z0-9]{2,4})$/", $this->path, $m);
        $suffix = $m[1] ? $m[1] : '';
        $path = preg_replace('/' . preg_quote($suffix) . '$/', '', $this->path);
        $pathpattern = '^' . preg_quote($path) . '\(([0-9]+)\)' . preg_quote($suffix) . '$';
        $cur = 0;
        $sql = "SELECT `path` FROM `fs_fileentry`
				WHERE `type`=1 AND `uid`=" . Core::getUserID() . " AND `path` REGEXP " . Mysql::quote($pathpattern);
        $this->connectDB();
        $query = self::$db->query($sql);
        while($row = self::$db->fetchArray($query)) {
            preg_match('|' . $pathpattern . '|', $row['path'], $match);
            if(isset($match[1])) {
                if($match[1] > $cur) 
                    $cur = $match[1];
            }
        }
        return $path . '(' . ($cur + 1) . ')' . $suffix;
    }
    /**
     *
     * 直接开放使用
     * @param bigint $fid
     */
    public function findOneByFID($fid) {
        $sql = "SELECT * FROM `fs_fileentry`
				WHERE `fid`=" . $fid;
        $this->connectDB();
        if($row = self::$db->fetchFirst($sql)) {
            $this->setFields($row);
            return TRUE;
        }
        return FALSE;
    }

    public function getMeta() {
        $source = new FileSource();
        $source->setFilename($meta['mime'], $meta['size'], $meta['md5']);
        if($source->ifExist()) {
            return $source->meta;
        } else {
            return array();
        }
    }

    public function getAccessory($fid) {
        $info = array();
        if($this->findOneByFID($fid)) {
            $pathinfo = pathinfo($this->path);
            $info = array(
                'typeid' => 2, //1图片，2文件
                'id' => $this->get_fid(), 
                'title' => $pathinfo['filename'], 
                'meta' => array('ext' => $this->ext, 'size' => $this->size)
            );
        }
        return $info;
    }

    public function createSource($meta) {
        $source = new FileSource();
        $source->setFilename($meta['mime'], $meta['size'], $meta['md5']);
        if($source->ifExist()) {
            //找到相同的文件资源
            $source->ref = $source->ref + 1;
            $source->save();
            return $source;
        } else {
            if($source->upBuffer($meta['source'], $meta)) {
                return $source;
            } else {
                return NULL;
            }
        }
    }

    public function create($filepath, $parent_id, $meta) {
        if($source = $this->createSource($meta)) {
            $time = time();
            $sql = "INSERT INTO `fs_fileentry` (`oid`,`parent_id`,`uid`,`type`,`path`,`created_at`,`mtime`,`desc`,`file_count`,`size`,`mime`,`md5`,`ext`,`cid`,`ctrl_type`,`thumb_id`)
					VALUES({$meta['oid']},{$parent_id}," . $this->uid . ",1," . Mysql::quote($filepath) . ",{$time},{$time},'',1," . $meta['size'] . ",'" . $meta['mime'] . "','" . $meta['md5'] . "','" . $meta['ext'] . "','" . $meta['cid'] . "','" . $meta['ctrl_type'] . "'," . intval($meta['thumb_id']) . ")";

            $this->connectDB();
            if(self::$db->query($sql)) {
                $row = array('fid' => self::$db->insertID(), 'oid' => $meta['oid'], 'parent_id' => $parent_id, 'uid' => $this->uid, 'type' => 1, 'path' => $filepath, 'file_count' => 1, 'size' => $meta['size'], 'mime' => $meta['mime'], 'md5' => $meta['md5'], 'ext' => $meta['ext'], 'cid' => $meta['cid'], 'ctrl_type' => $meta['ctrl_type'], 'thumb_id' => intval($meta['thumb_id']));
                $this->setFields($row);

                return TRUE;
            }
        }
        return FALSE;
    }

    public function setFields($row) {
        $this->fid = $row['fid'];
        $this->oid = $row['oid'];
        $this->parent_id = $row['parent_id'];
        $this->uid = $row['uid'];
        $this->type = $row['type'];
        $this->path = $row['path'];
        $this->file_count = $row['file_count'];
        $this->size = $row['size'];
        $this->mime = $row['mime'];
        $this->md5 = $row['md5'];
        $this->ext = $row['ext'];
        $this->cid = $row['cid'];
        $this->ctrl_type = $row['ctrl_type'];
        $this->thumb_id = $row['thumb_id'];
    }

    public function deleteBatch($path_arr) {
        if(!is_array($path_arr)) {
            $path_arr = array($path_arr);
        }
        $delstr = '';
        foreach($path_arr as $path) {
            $delstr .= ',' . Mysql::quote($path);
        }
        if($delstr) {
            $delstr = ltrim($delstr, ',');
            $sql = "DELETE FROM {$this->table} WHERE `pid` IN ({$delstr}) AND `type`=1 AND `uid`=" . Core::getUserID();
            $this->connectDB();
            return self::$db->query($sql);
        }
        return FALSE;
    }

    public function get_special_mime($ext) {
        switch($ext) {
            case 'apk':
            $themime = 'application/vnd.android.package-archive';
            break;
            case 'sisx':
            case 'sis':
            $themime = 'application/vnd.symbian.install';
            break;
            case 'jar':
            $themime = 'application/x-java-archive';
            break;
            case 'jad':
            $themime = 'text/vnd.sun.j2me.app-descriptor';
            break;
            case 'ipk':
            $themime = 'application/vnd.webos.ipk';
            break;
            default:
            $themime = '';
            break;
        }
        return $themime;
    }

    /**
     * 下载同源文件
     */
    public function output() {
        $fileSource = new FileSource();
        $fileSource->setFilename($this->mime, $this->size, $this->md5);
        if(!$fileSource->ifExist()) {
            //找不到源文件
            Core::fault(404);
        }
        $size = $fileSource->length;
        
        $pathinfo = pathinfo($this->path);
        //根据扩展名设置特殊的mime
        $themime = $this->get_special_mime($pathinfo['extension']);
        if(!$themime) {
            $themime = $fileSource->mime;
        }
        
        Core::header("Content-Type: " . $themime . "; charset=UTF-8");
        Core::header('Cache-Control: public'); //若去掉则ie无法弹出下载框
        
        //以附件下载显示的文件名
        $filename = $pathinfo['basename'];
        Core::header_disposition($filename);
        
        Core::header_range($size, $start, $end, $length);
        
        $fileSource->output($start,$length);
    }

    /**
     * 
     * @param array('mp3','m4r','mp4') $filetype 不指定就是下载同源文件；指定了就是下载同源不同格式的文件
     */
    public function download($filetype = '') {
        //非mp3的音频原文件需要转化为mp3
        if($this->ext != 'mp3' && $this->ctrl_type == 1 && $this->oid == 0 && $filetype == 'mp3') {
            $this->output_convert('mp3');
        }
        //非m4r的音频原文件需要转化为m4r
        if($this->ext != 'm4r' && $this->ctrl_type == 1 && $this->oid == 0 && $filetype == 'm4r') {
            $this->output_convert('m4r');
        }
        //对视频原文件以mp4格式输出
        if($this->ctrl_type == 2 && $this->oid == 0 && $filetype == 'mp4'){
            $this->output_convert('mp4');
        }
        //对视频原文件以gif格式输出
        if($this->ctrl_type == 2 && $this->oid == 0 && $filetype == 'gif'){
            $this->output_convert('gif');
        }

        $this->output();
    }
    
    private function _convert_gif($orifile, $convertfile){
//         $cmd1 = Core::config('ffmpeg_binary') . ' -i \'' . $orifile . '\'';
//         $tmp1 = Core::cmdRun($cmd1, $code1);
//         $is_match = preg_match('/(\d+) fps/', $tmp1, $match);
//         $raw_rate = intval($match[1]);
//         if(!$raw_rate){
//             Core::header('x-apiperf-vinfo:' . json_encode($tmp1));
//             Core::fault(500);
//         }
//         $rate = intval($raw_rate/2);
        $rate = 6;
        $raw_rate = 10;
        $output_dir = $orifile . '.tmp';
        $gif_files = $output_dir . '/o%04d.gif';
        
        Core::cmdRun('mkdir \'' . $output_dir . '\'', $code2);
        
        $cmd3 = Core::config('ffmpeg_binary') . ' -i \'' . $orifile . '\' -r ' . $rate . ' -s 320x320 \'' . $gif_files . '\'';
        Core::cmdRun($cmd3, $code3);
        
        $cmd = Core::config('imagick_convert_cmd') . ' -loop 99 -delay ' . $raw_rate . ' \'' . ($output_dir . '/*') . '\' \'' . $convertfile . '\'';
        $done = Core::cmdRun($cmd, $code);
        
        Core::cmdRun('rm -rf \'' . $output_dir . '\'', $code4);
        
        return array($done, $code);
    }
    
    /**
     * 同源文件格式转换
     * 已经转换过直接读取转换的文件；
     * 未转换实时转换并保存
     * @param array('mp3','m4r','mp4', 'gif') $type
     */
    public function output_convert($type){
        //加入随机数到文件名避免线程冲突
        $orifile = Core::tempname(str_replace("'", "", rand(1, 100000).'_'.$this->fid.'_'.$this->getFilename()));
        $convertfile = Core::tempname(str_replace("'", "", rand(1, 100000).'_'.$this->fid.'_'.$this->getFilename()) . '.' . $type);

        if($newfile = $this->getByFiletype($type)) {
            $newfile->output();
        } else {
            $fileSource = new FileSource();
            $fileSource->setFilename($this->mime, $this->size, $this->md5);
            if(!$fileSource->ifExist()) {
                Core::fault(404);
            }

            //$fdfs_result = fastdfs_storage_download_file_to_file($fileSource->fs_group_name, $fileSource->fs_filename, $orifile);
            $fdfs_result = NDCS::download_file_to_file($fileSource->fs_group_name, $fileSource->fs_filename, $orifile);
            
            if (!$fdfs_result){
                GridFS::fdfs_error($fileSource->fs_group_name, $fileSource->fs_filename);
            }
            
            if($type == 'mp3'){
                $cmd = Core::config('ffmpeg_binary') . ' -i \'' . $orifile . '\' -ar 20050 \'' . $convertfile . '\'';
            }elseif($type == 'm4r'){
                $cmd = Core::config('ffmpeg_binary') . ' -i \'' . $orifile . '\' -ab 128000 -f mp4 -acodec libfaac \'' . $convertfile . '\'';
            }elseif($type == 'mp4'){
                $cmd = Core::config('ffmpeg_binary') . ' -y -i \'' . $orifile . '\' -b 384k -vcodec libx264 -flags +loop+mv4 -cmp 256 -partitions +parti4x4+parti8x8+partp4x4+partp8x8 -subq 6 -trellis 0 -refs 5 -bf 0 -flags2 +mixed_refs -coder 0 -me_range 16 -g 250 -keyint_min 25 -sc_threshold 40 -i_qfactor 0.71 -qmin 10 -qmax 51 -qdiff 4 -acodec libfaac -ac 1 -ar 16000 -r 13 -ab 32000 \'' . $convertfile . '\'';
//                 $logit = true;
            }
            if($type == 'gif'){
                list($done, $code) = $this->_convert_gif($orifile, $convertfile);
            }else{
                $done = Core::cmdRun($cmd, $code);
            }
//             if ($logit){
//                 \MongoLogger::instance()->log('mp4_convert_debug',$done,array('fid'=>$this->fid,'code'=>$code,'md5'=>@md5_file($convertfile)));
//             }
            if($code) {
                Core::header('x-apiperf-cvtr:' . json_encode($done));
                Core::fault(500);
            }
            $filesize = filesize($convertfile);
            if($filesize>1){
                if($newfile = $this->putByFiletype($type, $convertfile)) {
                    //$newfile->output();
                } else {
                    Core::header('x-apiperf-puterr:1');
                }
            }else{
                Core::header('x-apiperf-cvtr:' . json_encode($convertfile));
                Core::fault(500);
            }

            Core::header("Content-Type: " . $newfile->mime . "; charset=UTF-8");
            Core::header('Cache-Control: public'); //若去掉则ie无法弹出下载框
            
            //以附件下载显示的文件名
            Core::header_disposition($convertfile);
            Core::header_range($filesize, $start, $end, $length);
            Core::readfile($convertfile,$start,$length);
            Core::quit();
        }
    }

    public function getFilename() {
        if($this->path) {
            preg_match('/([^\/]+)$/', $this->path, $match);
            if($match[1]) 
                return $match[1];
        }
        return '';
    }
}
