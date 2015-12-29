<?php
/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * 图片模型文件
 */
namespace Models;
use Core;
use Uploader;
use Models\ PhotoSource;
use Mysql;

class Photo {

    public static $instance = NULL;

    public static $db;

    public $photo_source;
    /** photo表字段 start*/
    public $table = 'fs_photo';

    private $pid;

    public $oid;

    public $uid;

    public $created_at;

    public $mtime;

    public $name;

    public $title;

    public $desc;

    public $mime;

    public $size;

    public $md5;

    public $direction;

    public $width;

    public $height;

    public $is_animated;

    public $cid;

    public $ctrl_type;
    /** photo表字段 end*/
//    public static function instance() {
//        if(!is_object(Photo::$instance))
//            Photo::$instance = new Photo();
//        return Photo::$instance;
//    }

    /**
     * 单例
     * @return Photo
     */
    public static function &instance()
    {
        if (!is_object(Photo::$instance)) {
            // Create a new instance
            Photo::$instance = new Photo();
        }
        return Photo::$instance;
    }

    protected function connectDB() {
        if(!self::$db) {
            self::$db = new Mysql();
            $dbcnf = Core::config('mysql_servers');

            self::$db->connect($dbcnf['host'], $dbcnf['user'], $dbcnf['pwd'], $dbcnf['db'], $dbcnf['pconnect'], true, $dbcnf['charset']);
        }
    }

    protected function setFields($data) {
        $this->pid = $data['pid'];
        $this->oid = $data['oid'];
        $this->uid = $data['uid'];
        $this->created_at = $data['created_at'];
        $this->mime = $data['mime'];
        $this->name = $data['name'];
        $this->title = $data['title'];
        $this->desc = $data['desc'];
        $this->mtime = $data['mtime'];
        $this->size = $data['size'];
        $this->md5 = $data['md5'];
        $this->direction = $data['direction'];
        $this->width = $data['width'];
        $this->height = $data['height'];
        $this->is_animated = $data['is_animated'];
        $this->cid = $data['cid'];
        $this->ctrl_type = $data['ctrl_type'];
    }

    private function _valid($needdata) {
        $data = array();
        foreach($needdata as $k => $v) {
            if($this->$k != $v) {
                $this->$k = $v;
                $data[$k] = Mysql::quote($this->$k);
            }
        }
        return $data;
    }

    public function get_pid() {
        return $this->pid;
    }
    /**
     *
     * 获取缩略图的资源对象
     * @param int $pid 图片id
     * @param int $type 图片尺寸
     */
    public function getSource($type = 0, $is_animate=FALSE) {
        if(!$this->photo_source) {
            $source = new PhotoSource();
            $source->setFilename($this->mime, $this->size, $this->md5, $this->direction, $type, $is_animate);
            if($source->ifExist()) {
                $this->photo_source = $source;
            }
        }
        return $this->photo_source;
    }

    public function getRawSource($fields) {
        $source = new PhotoSource();
        $source->setFilename($fields['mime'], $fields['size'], $fields['md5'], $fields['direction'], $fields['type']);
        if($source->ifExist()) {
            return $source;
        }
        return FALSE;
    }
    /**
     *
     * 上传并获得存储信息
     */
    public function createSource(Uploader $uploader) {
        $source = new PhotoSource();
        $source->setFilename($uploader->getMIME(), $uploader->getLength(), $uploader->getMD5());
        if($source->ifExist()) {
            //找到相同的文件资源
            $source->ref = $source->ref + 1;
            $source->save();
            return $source;
        } else {
            $updata['fmime'] = $uploader->getMIME();
            $updata['flength'] = $uploader->getLength();
            $updata['fmd5'] = $uploader->getMD5();
            $updata['mime'] = $updata['fmime'];
            $updata['meta'] = $uploader->getMeta();
            //error when non-utf8 string
            $photoinfo = $uploader->getInfo();
            $updata['width'] = $photoinfo['width'];
            $updata['height'] = $photoinfo['height'];
            if($source->upBuffer($uploader->tmpfile, $updata)) {
                return $source;
            } else {
                return NULL;
            }
        }
    }
    //创建资源文件
    public function create(Uploader $uploader, $updata = array()) {
        $source = $this->createSource($uploader);
        if($source && $source->getID()) {
            if(isset($updata['pid'])) {
                $pid = intval($updata['pid']);
                $data['pid'] = $pid ? $pid : 'NULL';
            }
            isset($updata['oid']) && $data['oid'] = $updata['oid'];
            $data['uid'] = isset($updata['uid']) ? $updata['uid'] : Core::getUserID();
            $data['created_at'] = isset($updata['created_at']) ? $updata['created_at'] : time();
            $data['mtime'] = isset($updata['mtime']) ? $updata['mtime'] : $data['created_at'];
            isset($updata['name']) && $data['name'] = $updata['name'];
            $data['desc'] = isset($updata['desc']) ? $updata['desc'] : $uploader->getTitle();
            $data['mime'] = strtolower($uploader->getMIME());
            $data['size'] = intval($uploader->getLength());
            $data['md5'] = strtolower($uploader->getMD5());
            $data['width'] = $source->width;
            $data['height'] = $source->height;
            $data['is_animated'] = isset($updata['is_animated']) ? $updata['is_animated'] : $uploader->isAnimated();
            isset($updata['cid']) && $data['cid'] = $updata['cid'];
            isset($updata['ctrl_type']) && $data['ctrl_type'] = $updata['ctrl_type'];
            $result = $this->_valid($data);
            $fields = implode('`,`', array_keys($result));
            $vals = implode(',', array_values($result));
            $this->connectDB();
            $sql = "INSERT INTO {$this->table} (`{$fields}`) VALUES ({$vals})";

            self::$db->query($sql);
            $pid = self::$db->insertId();
            if($pid) {
                $this->setFields($data);
                $this->pid = $pid;

                if($data['cid'] == 1) {
                    //如果是上传头像图片
                    $sql = "SELECT * FROM `fs_photo_avatar` WHERE `uid`=" . $data['uid'];
                    if(!self::$db->fetchFirst($sql)) {
                        $sql = "INSERT INTO `fs_photo_avatar` (`uid`,`oid`,`mtime`) VALUES (" . $data['uid'] . "," . $pid . "," . $data['mtime'] . ")";
                        return self::$db->query($sql);
                    }
                }
                return TRUE;
            }
        }
        return FALSE;
    }

    public function setAvatar($pid, $oid, $mtime) {
        //上传头像大图的时候如果没有记录自动创建记录
        $sql = "SELECT * FROM fs_photo_avatar WHERE uid=" . Core::getUserID();
        $this->connectDB();
        if($row = self::$db->fetchFirst($sql)) {
            $first_time = $row['pid'] == 0;
            $sql = "UPDATE fs_photo_avatar SET `pid`={$pid},`oid`={$oid},`mtime`={$mtime} WHERE `uid`=" . Core::getUserID() . " LIMIT 1";
            if(self::$db->query($sql)) {
                return array(TRUE, $first_time);
            }
        }
        return array(FALSE, FALSE);
    }

    public function findOne($pid, $fields_custom = array()) {
        $result = $this->findAllAssoc(array('pid' => $pid), $fields_custom);
        if($row = $result[$pid]) {
            $this->setFields($row);
            return $row;
        }
        return FALSE;
    }
    /**
     *
     * 批量查询数据
     * @param array $where
     * @param array $fields_custom
     * @todo 从memcache直接查询数据
     */
    public function findAllAssoc($where, $fields_custom = array(), $limit = '') {
        $fields = array('pid', 'mime', 'size', 'md5', 'direction');
        $fields = array_unique(array_merge($fields, $fields_custom));
        $fields_str = implode('`,`', $fields);
        $cond = '';
        foreach($where as $key => $val) {
            $cond .= '`' . $key . '`';
            if(is_array($val)) {
                foreach($val as $v) {
                    $valstr .= ',' . Mysql::quote($v);
                }
                $cond .= ' IN (' . ltrim($valstr, ',') . ')';
            } else {
                $cond .= ' =' . Mysql::quote($val);
            }
            $cond .= ' AND';
        }
        if($cond) {
            $cond = 'WHERE ' . substr($cond, 0, - 4);
        } else {
            return FALSE;
        }
        $sql = "SELECT `{$fields_str}` FROM {$this->table} {$cond} {$limit}";
        $this->connectDB();
        $query = self::$db->query($sql);
        while($row = self::$db->fetchArray($query)) {
            $result[$row['pid']] = $row;
        }
        if($result) {
            return $result;
        }
        return FALSE;
    }

    public function update($data) {
        unset($data['pid']);
        unset($data['mime']);
        unset($data['size']);
        unset($data['md5']);
        unset($data['created_at']);
        unset($data['uid']);
        $set = '';
        foreach($data as $f => $val) {
            $set .= "`{$f}`=" . Mysql::quote($val) . ",";
        }
        if($set) {
            $set = rtrim($set, ',');
            $sql = "UPDATE {$this->table} SET {$set} WHERE `pid`='{$this->pid}' AND `uid`=" . Core::getUserID();
            $this->connectDB();
            if(self::$db->query($sql)) {
                foreach($data as $k => $v) {
                    $this->$k = $v;
                }
                return TRUE;
            }
        }
        return FALSE;
    }

    public function delete() {
        $sql = "DELETE FROM {$this->table} WHERE `pid`='{$this->pid}' AND `uid`=" . Core::getUserID();
        $this->connectDB();
        return self::$db->query($sql);
    }

    public function deleteBatch($pidarr) {
        if(!is_array($pidarr)) {
            $pidarr = array($pidarr);
        }
        $delstr = '';
        foreach($pidarr as $pid) {
            $delstr .= ',' . Mysql::quote($pid);
        }
        if($delstr) {
            $delstr = ltrim($delstr, ',');
            $sql = "DELETE FROM {$this->table} WHERE `pid` IN ({$delstr}) AND `uid`=" . Core::getUserID();
            $this->connectDB();
            return self::$db->query($sql);
        }
        return FALSE;
    }
    /**
     *
     * 批量获取图片信息
     * @param mixed $pid
     */
    public function getInfo($pid) {
        if(!is_array($pid)) {
            $pid = array($pid);
        }
        foreach($pid as $id) {
            $pid_set[] = Mysql::quote($id);
        }
        $pid_str = implode(',', $pid_set);
        $sql = "SELECT * FROM {$this->table} WHERE `pid` IN ({$pid_str})";
        $this->connectDB();
        $query = self::$db->query($sql);
        $result = $rowset = array();
        while($row = self::$db->fetchArray($query)) {
            $row['src'] = $this->_genUrl($row, 780);
            $rowset[$row['pid']] = $row;
        }
        foreach($pid as $id) {
            $result[] = $rowset[$id];
        }
        return $result;
    }
    /**
     * 分页获取指定uid的照片列表
     * @param int $uid
     * @param array $get
     */
    public function getList($uid, $get) {
        $cond = '';
        if($get['cid'] == 1)
            $cond = 'AND ctrl_type=0';
        //无需列出以裁剪的头像
        $offset = $get['pagesize'] * ($get['page'] - 1);
        $sql1 = "SELECT SQL_CALC_FOUND_ROWS * 
				FROM {$this->table} 
				WHERE `uid`={$uid} AND `cid`={$get['cid']} {$cond} 
				ORDER BY `created_at` DESC 
				LIMIT {$offset},{$get['pagesize']}";
        $this->connectDB();
        $query = self::$db->query($sql1);
        $data = array();
        while($row = self::$db->fetchArray($query)) {
            $row['src'] = $this->_genUrl($row, 130);
            $data[] = $row;
        }
        $sql2 = "SELECT FOUND_ROWS()";
        $count = self::$db->resultFirst($sql2);
        return array('count' => $count, 'data' => $data);
    }
    /**
     *
     * 获取自己可以看到的图片分享
     * @param array $get
     */
    public function getShare($get) {
        $offset = $get['pagesize'] * ($get['page'] - 1);
        switch($get['type']) {
            case 'all':
            //好友公开动态、群、活动
            $mixid = $this->getAllMixid(array('friend', 'group', 'action'));
            if(!$mixid)
                return FALSE;
            $cond = "`mix_id` IN ('" . implode("','", $mixid) . "')";
            break;
            case 'action':
            //自己参加的活动
            $mixids = $this->getAllMixid(array('action'));
            if($mixids && $get['id']) {
                $mix_id = '2_' . $get['id'];
                if(!in_array($mix_id, $mixids))
                    $mixid = 0;
                else
                    $mixid = array($mix_id);
            } else {
                $mixid = $mixids;
            }
            if(!$mixid)
                return FALSE;
            $cond = "`mix_id` IN ('" . implode("','", $mixid) . "')";
            break;
            case 'group':
            //自己参加的群动态
            $mixids = $this->getAllMixid(array('group'));
            if($mixids && $get['id']) {
                $mix_id = '1_' . $get['id'];
                if(!in_array($mix_id, $mixids))
                    $mixid = 0;
                else
                    $mixid = array($mix_id);
            } else {
                $mixid = $mixids;
            }
            if(!$mixid)
                return FALSE;
            $cond = "`mix_id` IN ('" . implode("','", $mixid) . "')";
            break;
            case 'friend':
            //好友发的公开动态
            $mixids = $this->getAllMixid(array('friend'));
            if($mixids && $get['id']) {
                $mixids[] = Core::getUserID();
                $mix_id = $get['id'];
                if(!in_array($mix_id, $mixids))
                    $mixid = 0;
                else
                    $mixid = array($mix_id);
                if(!$mixid)
                    return FALSE;
                $cond = "`mix_id` IN ('" . implode("','", $mixid) . "') AND p.`uid` = " . $mix_id;
            } else {
                $mixid = $mixids;
                if(!$mixid)
                    return FALSE;
                $cond = "`mix_id` IN ('" . implode("','", $mixid) . "')";
            }
            break;
            default:
            //自己发的所有动态
            $cond = "`owner_uid`=" . Core::getUserID();
            break;
        }
        /*
        $sql1="SELECT SQL_CALC_FOUND_ROWS * 
        		FROM `fs_photo` 
        		WHERE `pid` IN (
        			SELECT DISTINCT `pid` 
        			FROM `fs_photo_sharelog` 
        			WHERE " . $cond . " 
        		) 
        		ORDER BY `pid` DESC
        		LIMIT {$offset},{$get['pagesize']}";
        */
        $sql1 = "SELECT SQL_CALC_FOUND_ROWS DISTINCT l.`pid`,p.* 
			FROM `fs_photo_sharelog` l 
			RIGHT JOIN `fs_photo` p 
			ON (l.`pid` = p.`pid`) 
			WHERE l." . $cond . " 
			ORDER BY l.`ctime` DESC 
			LIMIT {$offset},{$get['pagesize']}";
        trace($sql1);
        $this->connectDB();
        $query = self::$db->query($sql1);
        $data = array();
        while($row = self::$db->fetchArray($query)) {
            $row['src'] = $this->_genUrl($row, 130);
            $data[] = $row;
        }
        $sql2 = "SELECT FOUND_ROWS()";
        $count = self::$db->resultFirst($sql2);
        return array('count' => $count, 'data' => $data);
    }

    public function getAllMixid($type = array(), $uid = '') {
        $mixids = array();
        $this->connectDB();
        if(in_array('friend', $type)) {
            $sql1 = "SELECT `fid` FROM `friends` WHERE `uid`=" . ($uid ? $uid : Core::getUserID());
            $query = self::$db->query($sql1);
            while($row = self::$db->fetchArray($query)) {
                $mixids[] = $row['fid'];
            }
        }
        if(in_array('group', $type)) {
            $sql2 = "SELECT `gid` FROM `group_member` WHERE `uid`=" . ($uid ? $uid : Core::getUserID());
            $query = self::$db->query($sql2);
            while($row = self::$db->fetchArray($query)) {
                $mixids[] = '1_' . $row['gid'];
            }
        }
        if(in_array('action', $type)) {
            $sql3 = "SELECT `aid` FROM `action_member` WHERE `uid`=" . ($uid ? $uid : Core::getUserID()) . " AND `apply_type`=1";
            $query = self::$db->query($sql3);
            while($row = self::$db->fetchArray($query)) {
                $mixids[] = '2_' . $row['aid'];
            }
        }
        return $mixids;
    }
    /**
     *
     * 根据uid批量获取头像数据
     * @param mixed $uid 用户id
     * @param int $size 头像大小
     * @param boolean $needmeta 是否取得头像的元数据
     */
    public function getavatar($uid, $size = 130, $needmeta = FALSE) {
        if($size)
            $size = '_' . intval($size);
        else
            $size = '';
        if(!is_array($uid)) {
            $single = TRUE;
            $uid = array($uid);
        }
        $uid = array_map('intval', $uid);
        $sql = "SELECT * FROM `fs_photo_avatar` WHERE `uid` IN ('" . implode("','", $uid) . "')";
        $this->connectDB();

        $query = self::$db->query($sql);
        $result_meta = $result_url = array();
        while($row = self::$db->fetchArray($query)) {
            //已有头像
            $url = '';
            if($row['pid'])
                $url = Core::config('avatar_prefix') . Core::getSourceID($row['pid'], $row['mtime'], $row['oid']) . $size . '.jpg';
            if($needmeta) {
                $org_url = '';
                if($row['oid'])
                    $org_url = Core::config('photo_prefix') . Core::getSourceID($row['oid'], $row['mtime']) . $size . '.jpg';
                $meta = array('avatar_id' => $row['pid'], 'origin_id' => $row['oid'], 'avatar' => $url, 'origin' => $org_url);
                $result_meta[$row['uid']] = $meta;
            } else {
                $result_url[$row['uid']] = $url;
            }
        }
        $r = array();
        if($needmeta) {
            foreach($uid as $id)
                $r[] = $result_meta[$id];
        } else {
            foreach($uid as $id)
                $r[] = $result_url[$id];
        }
        if($single)
            return $r[0];
        else
            return $r;
    }
    /**
     *
     * 批量获取图片地址
     * @param mixed $pid
     * @param int $size
     */
    public function geturi($pid, $size = '') {
        $r = array();
        if(!$pid) {
            return $r;
        }
        if(!is_array($pid)) {
            $pid = array($pid);
        }
        $result = $this->findAllAssoc(array('pid' => $pid), array('mtime', 'oid', 'ctrl_type'));
        foreach($pid as $id) {
            $row = $result[$id];
            if($row) {
                $r[] = $this->_genUrl($row, $size);
            } else {
                $r[] = '';
            }
        }
        return $r;
    }
    /**
     *
     * 根据MD5批量获取图片地址
     * @param mixed $md5_arr
     * @param int $size
     */
    public function geturiByMD5($md5_arr, $size = '', $self = FALSE) {
        $r = array();
        if(!$md5_arr) {
            return $r;
        }
        if(!is_array($md5_arr)) {
            $md5_arr = array($md5_arr);
        }
        $md5_arr = array_map('strtolower', $md5_arr);
        //获取原图的元数据
        $source = new PhotoSource();
        $origin = $source->getOriginByMD5($md5_arr);
        //构建sql查询语句
        $sql = "SELECT `ctrl_type`,`pid`,`mtime`,`oid`,`md5`,`direction`,`mime` FROM {$this->table} WHERE 0 ";
        $tmp = array();
        foreach($origin as $md5 => $filemeta) {
            $fmd5 = $filemeta[2];
            $direction = intval($filemeta[3]);
            $sql .= "OR (`md5`='{$fmd5}' AND `direction`={$direction}" . ($self ? (" AND `uid`=" . Core::getUserID()) : "") . ") ";
            $key = $fmd5 . '_' . $direction;
            $tmp[$key] = $md5;
        }
        $this->connectDB();
        $query = self::$db->query($sql);
        while($row = self::$db->fetchArray($query)) {
            $key = $row['md5'] . '_' . $row['direction'];
            $md5 = $tmp[$key];
            $id = intval($row['pid']);
            $result[$md5] = array('id' => $id, 'src' => $this->_genUrl($row, $size), 'mime' => $row['mime']);
        }
        foreach($md5_arr as $md5) {
            $r[] = $result[$md5] ? $result[$md5] : array('id' => 0, 'src' => '');
        }
        return $r;
    }

    public function getOriginMD5($url) {
        $result = array();
        if(!is_array($url)) {
            $url = array($url);
        }
        $pid = array();
        foreach($url as $u) {
            $pid[] = $this->getPidByURL($u);
        }
        if($pid) {
            $sql = "SELECT `pid`,`md5` FROM `fs_photo` WHERE `pid` IN (" . implode(',', $pid) . ")";
            $this->connectDB();
            $query = self::$db->query($sql);
            $rowset = array();
            while($row = self::$db->fetchArray($query)) {
                $rowset[$row['pid']] = $row['md5'];
            }
            foreach($pid as $id) {
                $result[] = $rowset[$id] ? $rowset[$id] : '';
            }
        }
        return $result;
    }

    public function getPidByURL($url) {
        if(preg_match('@/(\d+)_[^/]+$@', $url, $match)) {
            return $match[1];
        }
        return 0;
    }

    private function _genUrl($row, $size = '') {
        if($size)
            $size = '_' . intval($size);
        else
            $size = '';
        $src_prefix = Core::config('photo_prefix');
        $avatar_prefix = Core::config('avatar_prefix');
        $thumb_prefix = Core::config('thumb_prefix');
        if($row['ctrl_type'] == 1) {
            $url = $avatar_prefix . Core::getSourceID($row['pid'], $row['mtime'], $row['oid']) . $size . '.jpg';
        } elseif($row['ctrl_type'] == 2){
            $url = $thumb_prefix . Core::getSourceID($row['pid'], $row['mtime'], $row['oid']) . $size . '.jpg';
        }else {
            $url = $src_prefix . Core::getSourceID($row['pid'], $row['mtime']) . $size . '.jpg';
        }
        return $url;
    }
}
