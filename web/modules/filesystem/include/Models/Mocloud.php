<?php
namespace Models;

use Driver\MocloudNDFS;
use Imagine\Imagick\Imagine;
use Imagine\Image\ImageInterface;
use Imagine\Image\Box;
use Core;
use Mysql;
use Mongo;

class Mocloud {

    private static $instance;

    private static $db;

    private $fs_source = NULL;

    protected $where = array();

    protected $limit = '';

    protected $order = '';

    protected $select = '*';
    /** fields **/
    private $id = NULL;

    public $type = 0;
    //照片1，视频2，音频3，文件4
    public $uid = 0;

    public $md5 = '';
    //文件md5
    public $md5_thumb = '';
    //客户端缩略图md5
    public $datetime = '';
    //客户端记录时间
    public $name = '';
    //文件名
    public $ext = '';
    //文件扩展名
    public $mime = '';
    //文件mime
    public $size = 0;
    //文件大小
    public $offset = 0;
    //偏移量
    public $created_at = 0;
    //文件原始创建时间
    public $uploaded_at = 0;
    //上传时间
    public $uploaded = 0;
    //是否已经上传完毕
    public $deleted_at = 0;
    //是否删除,删除时间
    public $client_id = 0;
    //平台id
    public $device_id = '';
    //来源设备id
    public $fs_filename = '';
    //fs地址
    public $fs_group = '';

    public $meta = '';

    public function __construct() {
        //所有操作都是限定在当前登录的用户
        $this->uid = Core::getUserID();
    }

    public function get_source() {
        if(!$this->fs_source) {
            //$this->fs_source=new MocloudSource($this->fs_group,$this->fs_filename);
            //$this->fs_source = new MocloudSource(Core::config('ndcs_group'), $this->md5, MocloudSource::DRIVER_NDCS);
            $this->fs_source=new MocloudNDFS($this->fs_group,$this->fs_filename);
        }
        return $this->fs_source;
    }

    protected function get_db() {
        if(!self::$db) {
            self::$db = new Mysql();
            $dbcnf = Core::config('mysql_servers');
            self::$db->connect($dbcnf['host'], $dbcnf['user'], $dbcnf['pwd'], $dbcnf['db'], $dbcnf['pconnect'], true, $dbcnf['charset']);
        }
        return self::$db;
    }

    public function where($cond) {
        if(is_array($cond)) {
            foreach($cond as $field => $val) {
                if(is_string($field)) {
                    if(!is_array($val)) {
                        //字符串要做转义
                        $this->where[] = "`{$field}`=" . (is_string($val) ? Mysql::quote($val) : $val);
                    } else {
                        $items = array();
                        foreach($val as $item) {
                            $items[] = is_string($item) ? Mysql::quote($item) : $item;
                        }
                        $this->where[] = "`{$field}` IN (" . implode(',', $items) . ")";
                    }
                } else {
                    $this->where[] = $val;
                }
            }
        } else {
            $this->where[] = $cond;
        }
        return $this;
    }

    public function limit($limit) {
        $this->limit = $limit;
        return $this;
    }

    public function order($limit) {
        $this->order = $limit;
        return $this;
    }

    public function select($select) {
        $this->select = $select;
        return $this;
    }

    public function get() {
        array_unshift($this->where, "`uid`={$this->uid}");
        return $this->find();
    }

    public function get_all() {
        array_unshift($this->where, "`uid`={$this->uid}");
        return $this->find_all();
    }

    public function find() {
        if($this->where) {
            $cond = implode(' AND ', $this->where);
            $this->where = array();
            $sql = "SELECT * FROM `mocloud` WHERE {$cond} LIMIT 1";
            $db = $this->get_db();
            if($row = $db->fetchFirst($sql)) {
                foreach($row as $field => $val) {
                    $this-> {$field} = $val;
                }
                return $this->id;
            }
        }
        return FALSE;
    }

    public function find_all() {
        $limit = '';
        if($this->limit) {
            $limit = " LIMIT {$this->limit}";
            $this->limit = '';
        }
        $order = '';
        if($this->order) {
            $order = " ORDER BY {$this->order}";
            $this->order = '';
        } else {
            $order = " ORDER BY `id` DESC";
        }
        if($this->where) {
            $cond = implode(' AND ', $this->where);
            $this->where = array();
            $sql = "SELECT {$this->select} FROM `mocloud` WHERE {$cond}{$order}{$limit}";
            $this->select = '*';
            $db = $this->get_db();

            $query = $db->query($sql);
            while($row = $db->fetchArray($query)) {
                $all[] = $row;
            }
            return $all;
        }
        return array();
    }

    public function save($fields) {
        $set = '';
        foreach($fields as $field) {
            if($field == 'appid'){
                $set .= ",`appid`=" . Core::getAPPID();
            }else{
                $set .= ",`$field`=" . Mysql::quote($this-> {$field});
            }
        }
        if($set) {
            $set = ltrim($set, ',');
            $pkval = $this->id;
            $sql = "UPDATE `mocloud` SET {$set} WHERE `id`={$pkval}";
            return $this->get_db()->query($sql);
        }
        return FALSE;
    }

    public function reset() {
        $public_properties = Core::get_object_public_vars($this);
        foreach(array_keys($public_properties) as $property) {
            $this-> {$property} = '';
        }
        $this->id = NULL;
    }

    public function as_array() {
        $temp = Core::get_object_public_vars($this);
        $temp['id'] = $this->id;
        $temp['appid'] = Core::getAPPID();
        return $temp;
    }

    public static function instance() {
        if(!self::$instance) {
            self::$instance = new Mocloud();
        }
        return self::$instance;
    }

    public function get_id() {
        return $this->id;
    }

    public function create() {
        $time_now = time()+1;
        
        if($this->datetime){
            $this->created_at = strtotime($this->datetime);
        }
        
        $data = $this->as_array();
        //自己的这台设备已经上传过这张照片
        if($this->where(array('md5' => $this->md5, 'device_id' => $this->device_id, 'name' => $this->name))->get()) {
            //如果已经删除重置为上传
            if($this->uploaded && $this->deleted_at > 0) {
                $this->name = $data['name'];
                $this->device_id = $data['device_id'];
                $this->datetime = $data['datetime'];
                $this->md5_thumb = $data['md5_thumb'];
                $this->uploaded_at = $time_now;
                $this->deleted_at = 0;
                $this->created_at = $data['created_at'];
                $this->save(array('name', 'device_id', 'datetime', 'md5_thumb', 'uploaded_at', 'deleted_at', 'created_at','appid'));
                $this->_update_stats($this->size, 1);
            }
            return $this->id;
        }
        //这张照片是已经上传过的，复制而无需重传
        if($this->where(array('md5' => $this->md5, 'uploaded' => 1))->find()) {
            $this->id = NULL;
            $this->uid = Core::getUserID();
            $this->name = $data['name'];
            $this->device_id = $data['device_id'];
            $this->datetime = $data['datetime'];
            $this->md5_thumb = $data['md5_thumb'];
            $this->uploaded_at = $time_now;
            $this->deleted_at = 0;
            $this->created_at = $data['created_at'];
            $this->_update_stats($this->size, 1);
        }
        //没有上传过
        preg_match("/\.([a-zA-Z0-9]{2,4})$/", $this->name, $match);
        $this->ext = $match[1] ? strtolower($match[1]) : '';
        $this->uploaded_at = $time_now;
        $data = $this->as_array();
        foreach($data as $field => $val) {
            if(is_string($val)) 
                $data[$field] = Mysql::quote($val);
            elseif(is_null($val)) 
                $data[$field] = 'NULL';
        }
        $sql = "INSERT INTO `mocloud` (`" . implode('`,`', array_keys($data)) . "`) VALUES (" . implode(',', $data) . ")";
        $db = $this->get_db();
        $db->query($sql);
        return $this->id = $db->insertId();
    }

    public function append($length, $uploader=NULL) {
        if($uploader){
            $in_path = $uploader->tmpfile;
        }else{
            if($_FILES) {
                foreach($_FILES as $upfile) {
                    $in_path = $upfile['tmp_name'];
                }
            } else {
                $in_path = 'php://input';
            }
        }
        if(! $in_path)
            return FALSE;
        
        $mcSource = $this->get_source();
        
        $mcSource->uploader = $uploader;
        //只传数据的引用
        $mcSource->in_path = $in_path;
        $mcSource->length = $length;
        $mcSource->offset = $this->offset;
        if($appender = $mcSource->append_buff()) {
            $this->offset = $mcSource->offset;
            if(!$this->fs_group || !$this->fs_filename) {
                $this->fs_group = $appender['group_name'];
                $this->fs_filename = $appender['filename'];
            }
            $this->save(array('offset', 'fs_group', 'fs_filename'));
            return TRUE;
        }
        return FALSE;
    }

    public function end() {
        $mcSource = $this->get_source();
        $fileinfo = $mcSource->get_info();
        $metadata = $mcSource->get_meta();
        //$this->created_at = $metadata['FileDateTime']; 文件修改时间戳
        //strtotime(DateTimeOriginal),strtotime(DateTimeDigitized) 拍摄时间字符串
        if(isset($metadata['DateTimeOriginal'])){
            $this->created_at = strtotime($metadata['DateTimeOriginal']);
        }else if(isset($metadata['DateTime'])){
            $this->created_at = strtotime($metadata['DateTime']);
        }
        $this->md5 = $mcSource->get_md5();
        $this->mime = $mcSource->get_mime();
        $this->size = $mcSource->get_size();
        $this->client_id = Core::getClientID();
        $meta = array();
        if($this->type == 1) {
            $meta[0] = intval($fileinfo['width']);
            $meta[1] = intval($fileinfo['height']);
            $meta[2] = intval($fileinfo['orientation']);
        }
        $this->meta = implode(',', $meta);
        $this->uploaded = 1;
        
        $done = $this->save(array('md5', 'mime', 'client_id', 'uploaded', 'meta', 'created_at', 'size'));
        $this->_update_stats($this->size, 1);
        
        $thumbnail=$this->set_thumbnail($mcSource);
        $this->set_meta($this->fs_group,$this->fs_filename,$this->md5,$this->size,$metadata,$thumbnail);
        $mcSource->clear();
        
        $this->update_last_upload_time();
        return $done;
    }
    
    public function update_last_upload_time(){
        $db = $this->get_db();
        $db->query("UPDATE `mocloud_device` SET last_upload_time=".time()." WHERE uid=".$this->uid." AND device_id='{$this->device_id}'");
    }
    
    public function set_thumbnail($mcSource,$size=''){
        $imagine = new Imagine();
        //Imagick Imagine 的 save() 参数已被修改过加入了 orientation 项的支持
        //Imagick 只能对已经存在exif的图片去update orientation信息不能 add 一个exif项 改为其他第三方实现
        
        $humbnail=array();
        //上传成功就生成1024的图
        if($size==''){
            $uploader = $mcSource->get_uploader();
            if(! file_exists($uploader->tmpfile)){
                return FALSE;
            }
            $image = $imagine->open($uploader->tmpfile);
            $thumb_1024 = Core::tempname($this->md5 . '_tn-1024.jpg');
            $image->thumbnail(new Box(1024, 1024))->save($thumb_1024);
            //Core::attach_exif($thumb_480, array('orientation' => $fileinfo['orientation']));
            
            $humbnail['tn-1024']=$this->upload_thumbnail($thumb_1024);
        }else{
            $meta=$this->get_meta();
            if($size == 'tn-100'){
                $thumb_fs_filename=$meta['thumbnail']['tn-150'];
            }elseif($size == 'tn-150'){
                $thumb_fs_filename=$meta['thumbnail']['tn-480'];
            }else{
                $thumb_fs_filename=$meta['thumbnail']['tn-1024'];
            }
            
            if($thumb_fs_filename){
                $source=new MocloudNDFS('',$thumb_fs_filename);
                $uploader = $source->get_uploader();
            }else{
                $uploader = $mcSource->get_uploader();
            }
            
            if(! file_exists($uploader->tmpfile)){
                return FALSE;
            }
            
            $size_wh = substr($size, 3);
            $image = $imagine->open($uploader->tmpfile);
            $thumb = Core::tempname($this->md5 . '_' . $size . '.jpg');
            
            if(substr($size, 0, 2) == 'sq'){
                $image->thumbnail(new Box($size_wh, $size_wh), ImageInterface::THUMBNAIL_OUTBOUND)->save($thumb);
            }else{
                $image->thumbnail(new Box($size_wh, $size_wh))->save($thumb);
            }
            //Core::attach_exif($thumb_480, array('orientation' => $fileinfo['orientation']));
            
            $humbnail[$size]=$this->upload_thumbnail($thumb);
        }

        return $humbnail;
    }
    
    public function upload_thumbnail($thumbfile){
        $source=new MocloudNDFS();
        $source->in_path=$thumbfile;
        $source->offset=0;
        $source->length=filesize($thumbfile);
        if($fs_file=$source->append_buff()){
            return $fs_file['filename'];
        }
        
        return '';
    }
    
    private $mongo=NULL;
    private function get_mongo(){
        if(! $this->mongo){
            $gridfs_conf = Core::config('gridfs_servers');
            $mongo = new Mongo($gridfs_conf['host'], $gridfs_conf['opt']);
            $mongodb = $mongo->selectDB($gridfs_conf['db']['pcloud']);
            if(!is_NULL($gridfs_conf['user']) && !is_NULL($gridfs_conf['pwd'])) {
                $mongodb->authenticate($gridfs_conf['user'], $gridfs_conf['pwd']);
            }
            $this->mongo=$mongodb->selectCollection('fs.files');
        }
        return $this->mongo;
    }
    
    public function set_meta($fs_group,$fs_filename,$md5,$size,$meta,$thumbnail){
        $doc=array(
            'md5'=>$md5,
            'length'=>$size,
            'fs_group_name'=>$fs_group,
            'fs_filename'=>(string)$fs_filename,
            'meta'=>json_encode($meta),
            'thumbnail'=>$thumbnail
        );
        
        $r = $this->get_mongo()->insert($doc, TRUE);
    }
    
    public function get_meta(){
        if($this->fs_filename){
            if($doc = $this->get_mongo()->findOne(array('fs_filename' => (string)$this->fs_filename))) {
                return $doc;
            }
        }
        return array();
    }
    
    public function update_meta($data){
        if($this->fs_filename){
            return $this->get_mongo()->update(array('fs_filename' => (string)$this->fs_filename), array('$set' => $data));
        }
    }
    /**
     * 
     * 获取用户所有的上传信息
     */
    public function get_upload_info_all($dateline, $pagesize) {
        $result = array();
        $max_dateline = 0;
        if($rowset = $this->where("uploaded_at>{$dateline}")->order('uploaded_at ASC')->limit($pagesize)->get_all()) {
            foreach($rowset as $row) {
                $newrow = array('name' => $row['name'], 'md5' => $row['md5'], 'md5_thumb' => $row['md5_thumb'], 'datetime' => $row['datetime']);
                if($row['uploaded']) {
                    $newrow['uploaded'] = TRUE;
                    $newrow['deleted_at'] = $row['deleted_at'];
                    $newrow['id'] = $row['id'];
                } else {
                    $newrow['uploaded'] = FALSE;
                    $newrow['offset'] = $row['offset'];
                    $newrow['upload_id'] = $row['id'];
                }
                $result[] = $newrow;
                $row['uploaded_at'] > $max_dateline && $max_dateline = $row['uploaded_at'];
            }
        }
        return array('dateline' => $max_dateline, 'data' => $result);
    }

    public function get_upload_info_all2($dateline, $device_id) {
        $result = array();
        $where = array("uploaded_at>{$dateline}", "deleted_at=0");
        if($device_id) {
            $where['device_id'] = $device_id;
        }
        if($rowset = $this->where($where)->get_all()) {
            foreach($rowset as $row) {
                $newrow = array('name' => $row['name'], 'md5' => $row['md5'], 'md5_thumb' => $row['md5_thumb'], 'datetime' => $row['datetime']);
                if($row['uploaded']) {
                    $newrow['uploaded'] = TRUE;
                    $newrow['deleted_at'] = $row['deleted_at'];
                    $newrow['id'] = $row['id'];
                } else {
                    $newrow['uploaded'] = FALSE;
                    $newrow['offset'] = $row['offset'];
                    $newrow['upload_id'] = $row['id'];
                }
                $result[] = $newrow;
            }
        }
        $result_del = array();
        $where = array("deleted_at>{$dateline}");
        if($device_id) {
            $where['device_id'] = $device_id;
        }
        if($rowset = $this->where($where)->get_all()) {
            foreach($rowset as $row) {
                $newrow = array('name' => $row['name'], 'md5' => $row['md5'], 'md5_thumb' => $row['md5_thumb'], 'datetime' => $row['datetime'], 'uploaded' => TRUE, 'deleted_at' => $row['deleted_at'], 'id' => $row['id']);
                $result_del[] = $newrow;
            }
        }
        return array('dateline' => time(), 'add' => $result, 'del' => $result_del);
    }
    /**
     * 
     * 根据md5判断是否上传以及是否上传完整
     * @param array $md5_list
     * @param string $field
     */
    public function get_upload_info($md5_list, $field = 'md5') {
        if(empty($md5_list)) 
            return array();
        $result = array();
        if($rowset = $this->where(array($field => $md5_list))->get_all()) {
            foreach($rowset as $row) {
                $newrow = array('name' => $row['name'], 'md5' => $row['md5'], 'md5_thumb' => $row['md5_thumb'], 'datetime' => $row['datetime']);
                if($row['uploaded']) {
                    $newrow['uploaded'] = TRUE;
                    $newrow['deleted_at'] = $row['deleted_at'];
                    $newrow['id'] = $row['id'];
                } else {
                    $newrow['uploaded'] = FALSE;
                    $newrow['offset'] = $row['offset'];
                    $newrow['upload_id'] = $row['id'];
                }
                $result[$row[$field]] = $newrow;
            }
        }
        $r = array();
        foreach($md5_list as $md5) {
            $r[] = $result[$md5];
        }
        return $r;
    }
    /**
     * 
     * 获取上传列表
     * @param int $type
     * @param string $asc
     * @param string $desc
     * @param int $page
     * @param int $pagesize
     * @param string $device_id
     * @param string $keyword
     * @param int $otime_start
     * @param int $otime_end
     */
    public function get_upload_list($type, $asc, $desc, $page, $pagesize, $device_id, $keyword, $otime_start, $otime_end, $star, $utime_start, $utime_end) {
        $order_field = array('orig_time' => 'created_at', 'name' => 'name');
        $orderby = '';
        if($asc) {
            if($asc = $order_field[$asc]) 
                $orderby = "`$asc` ASC";
        }
        if($desc) {
            if($desc = $order_field[$desc]) 
                $orderby = "`$desc` DESC";
        }
        $limit = (($page - 1) * $pagesize) . ',' . $pagesize;
        //已上传且没有删除
        $where = array('type' => $type, 'uploaded' => 1, 'deleted_at' => 0);
        if($device_id) 
            $where['device_id'] = $device_id;
        if($star)
            $where[] = "`star` = 1";
        if($otime_start) 
            $where[] = "`created_at` >= {$otime_start}";
        if($otime_end) 
            $where[] = "`created_at` < {$otime_end}";
        if($utime_start)
            $where[] = "`uploaded_at` >= {$utime_start}";
        if($utime_end)
            $where[] = "`uploaded_at` < {$utime_end}";
        if($keyword) 
            $where[] = "`name` LIKE '%{$keyword}%'";
        $result = array();
        $count = 0;
        if($rowset = $this->where($where)->order($orderby)->limit($limit)->select('SQL_CALC_FOUND_ROWS *')->get_all()) {
            $count = $this->get_db()->resultFirst("SELECT FOUND_ROWS()");
            foreach($rowset as $row) {
                if($row['type'] == 1) {
                    if(!$row['meta']) {
                        $imagesize = getimagesize($this->get_uri($row, 'o'));
                        $row['meta'] = $imagesize[0] . ',' . $imagesize[1];
                        $this->get_db()->query("UPDATE `mocloud` SET `meta`='{$row['meta']}' WHERE `id`={$row['id']}");
                    }
                    $imagesize = explode(',', $row['meta']);
                    $meta = array('width' => $imagesize[0], 'height' => $imagesize[1]);
                } else {
                    $meta = array();
                }
                $result[] = array('id' => $row['id'], 'name' => $row['name'], 'star'=>$row['star'], 'size' => $row['size'], 'mime' => $row['mime'], 'md5' => $row['md5'], 'src' => $this->get_uri($row), 'ext' => $row['ext'], 'orig_time' => $row['created_at'], 'device_id' => $row['device_id'], 'meta' => $meta);
            }
        }
        return array('count' => $count, 'data' => $result);
    }
    
    /**
     * 按日期分组
     * @param int $type
     */
    public function get_upload_list_group_by_date($type, $device_id, $star){
        $cond = '`uploaded`=1 AND `deleted_at`=0';
        if($device_id) $cond .= " AND `device_id`=".Mysql::quote($device_id);
        if($star) $cond .= " AND `star`=1";
        $sql = "SELECT *,COUNT(id) AS total FROM 
                    (SELECT id,name,FROM_UNIXTIME(uploaded_at,'%Y%m') AS upload_date FROM `mocloud` 
                        WHERE `uid`={$this->uid} AND {$cond} ORDER BY uploaded_at DESC) 
                AS tmp GROUP BY upload_date";
        
        $db=$this->get_db();
        $query = $db->query($sql);
        $result = array();
        while($row=$db->fetchArray($query)){
            if(!$row['id']){
                continue;
            }
            $year = substr($row['upload_date'], 0, 4);
            $month = substr($row['upload_date'], 4, 2);
            
            $result[$year][] = array(
                    'month' => $month,
                    'src' => $this->get_uri($row), 
                    'count' => $row['total']
                    );
        }
        
        return $result;
    }
    
    public function get_upload_list_recent($type, $page, $pagesize){
        $sql = "SELECT `uploaded_at`,`device_id` FROM `mocloud` WHERE `uid`={$this->uid} AND `uploaded`=1 AND `deleted_at`=0 ORDER BY `id` DESC LIMIT 1";
        $db = $this->get_db();
        $query = $db->query($sql);
        $row = $db->fetchArray($query);

        if($row){
            $where = array('type' => $type, 'uploaded' => 1, 'deleted_at' => 0);
            $where['device_id'] = $row['device_id'];
            $where[] = "from_unixtime(`uploaded_at`,'%Y%m%d') = '".date('Ymd', $row['uploaded_at'])."'";
            
            $limit = (($page - 1) * $pagesize) . ',' . $pagesize;
            
            $result = array();
            $count = 0;
            if($rowset = $this->where($where)->order("`id` DESC")->limit($limit)->select('SQL_CALC_FOUND_ROWS *')->get_all()) {
                $count = $this->get_db()->resultFirst("SELECT FOUND_ROWS()");
                foreach($rowset as $row) {
                    if($row['type'] == 1) {
                        if(!$row['meta']) {
                            $imagesize = getimagesize($this->get_uri($row, 'o'));
                            $row['meta'] = $imagesize[0] . ',' . $imagesize[1];
                            $this->get_db()->query("UPDATE `mocloud` SET `meta`='{$row['meta']}' WHERE `id`={$row['id']}");
                        }
                        $imagesize = explode(',', $row['meta']);
                        $meta = array('width' => $imagesize[0], 'height' => $imagesize[1]);
                    } else {
                        $meta = array();
                    }
                    $result[] = array('id' => $row['id'], 'name' => $row['name'], 'star'=>$row['star'], 'size' => $row['size'], 'mime' => $row['mime'], 'md5' => $row['md5'], 'src' => $this->get_uri($row), 'ext' => $row['ext'], 'orig_time' => $row['created_at'], 'device_id' => $row['device_id'], 'meta' => $meta);
                }
            }
            return array('count' => $count, 'data' => $result);
        }
        return array('count' => 0, 'data' => array());
    }
    /**
     * 
     * 获取统计
     */
    public function get_stats() {
        $result = array();
        $quota_all = Core::config('quota_mocloud');
        $sql = "SELECT * FROM `mocloud_stats` WHERE `uid`={$this->uid} LIMIT 1";
        $db = $this->get_db();
        if($row = $db->fetchFirst($sql)) {
            $quota_all = $row['quota_all'];
            //缓存一天
            if($row['updated_at'] + 86400 > time()) {
                $result = array('quota_used' => $row['quota_used'], 'quota_all' => $quota_all, 'count_photo' => $row['count_photo']);
            }
        } else {
            $this->_insert_stats($this->uid);
        }
        if(!$result) {
            list($quota, $count) = $this->get_stats_latest();
            $result = array('quota_used' => array_sum($quota), 'quota_all' => $quota_all, 'count_photo' => intval($count[1]));
        }
        return $result;
    }
    /**
     * 
     * 重新统计
     */
    public function get_stats_latest($update_cache = TRUE) {
        $file_quota = $file_count = $backup_time = array();
        $sql = "SELECT MAX(`uploaded_at`) AS backup_time,SUM(`size`) AS file_quota,COUNT(`id`) AS file_count,`type` FROM `mocloud` 
            WHERE `uid`={$this->uid} AND `uploaded`=1 AND `deleted_at`=0";
        $db = $this->get_db();
        $query = $db->query($sql);
        
        $row = $db->fetchArray($query);
        $file_quota[1] = $row['file_quota'];
        $file_count[1] = $row['file_count'];
        $backup_time[1] = $row['backup_time'];
//         while($row = $db->fetchArray($query)) {
//             $file_quota[$row['type']] = $row['file_quota'];
//             $file_count[$row['type']] = $row['file_count'];
//             $backup_time[$row['type']] = $row['backup_time'];
//         }
        //自动刷新缓存
        if($update_cache) {
            $quota_used = array_sum($file_quota);
            $count_photo = intval($file_count[1]);
            $backup_time_photo = intval($backup_time[1]);
            $sql = "UPDATE `mocloud_stats` 
                SET `quota_used`={$quota_used},`count_photo`={$count_photo},`updated_at`=" . time() . " 
                WHERE `uid`={$this->uid}";
            $db->query($sql);
        }
        return array($file_quota, $file_count);
    }
    
    public function get_stats_device(){
        $sql = "SELECT `device_id`,MAX(`uploaded_at`) AS backup_time,SUM(`size`) AS total_size,COUNT(`id`) AS file_count
            FROM `mocloud` WHERE `uid`={$this->uid} AND `uploaded`=1 AND `deleted_at`=0 GROUP BY `device_id`";
        $db = $this->get_db();
        $query = $db->query($sql);
        $result = array();
        while($row = $db->fetchArray($query)) {
            $result[] = array('device_id' => $row['device_id'], 'backup_time' => $row['backup_time'], 'count_photo' => $row['file_count'], 'quota_used' => $row['total_size'],);
        }
    
        $bind_devices = $this->get_bind_device(NULL, FALSE);
        $bind_devices_kv = array();
        foreach ($bind_devices as $row){
            $bind_devices_kv[$row['device_id']] = $row;
        }
    
        $r = array();
        foreach ($result as $row){
            $row['device_name'] = $bind_devices_kv[$row['device_id']]['name'];
            $row['device_model'] = $bind_devices_kv[$row['device_id']]['model'];
            $row['device_type'] = $this->_get_device_type($bind_devices_kv[$row['device_id']]['model']);
            $row['last_upload_time'] = $bind_devices_kv[$row['device_id']]['last_upload_time'] 
                                        ? $bind_devices_kv[$row['device_id']]['last_upload_time'] 
                                        : $row['backup_time'];
            $row['device_alias'] = \Brand_Model::instance()->get_by_model($bind_devices_kv[$row['device_id']]['model']);
            $r[] = $row;
        }
        return $r;
    }
    
    private function _get_device_type($device_model){
        if(preg_match('/web/is', $device_model)){
            return 'web';
        }elseif(preg_match('/iphone|ipod/is', $device_model)){
            return 'iphone';
        }elseif(preg_match('/ipad/is', $device_model)){
            return 'ipad';
        }else{
            return 'android';
        }
    }
    
    public function get_stats_star(){
        $sql = "SELECT MAX(`uploaded_at`) AS backup_time,SUM(`size`) AS total_size,COUNT(`id`) AS file_count
            FROM `mocloud` WHERE `uid`={$this->uid} AND `uploaded`=1 AND `deleted_at`=0 AND `star`=1";
        $db = $this->get_db();
        $query = $db->query($sql);
        $row = $db->fetchArray($query);
        return array('backup_time' => $row['backup_time'], 'count_photo' => $row['file_count'], 'quota_used' => $row['total_size']);
    }

    private function _update_stats($quota_used, $count_photo) {
        $db = $this->get_db();
        $sql = "UPDATE `mocloud_stats` 
                SET `quota_used`=`quota_used`+({$quota_used}),`count_photo`=`count_photo`+({$count_photo}) 
                WHERE `uid`={$this->uid}";
        $db->query($sql);
    }
    
    private function _insert_stats($uid, $client_id=0, $appid=0, $created_at=0){
        $db = $this->get_db();
        $quota_all= Core::config('quota_mocloud');
        $sql = "INSERT INTO `mocloud_stats`
                (`uid`,`quota_used`,`quota_all`,`count_photo`,`updated_at`,`client_id`,`appid`,`created_at`)
                VALUES ({$uid},0,{$quota_all},0,0,{$client_id},{$appid},{$created_at})";
        $db->query($sql);
    }
    
    private function _register_user(){
        $client_id = Core::getClientID();
        $appid = Core::getAPPID();
        $created_at = time();
        $sql = "SELECT `created_at` FROM `mocloud_stats` WHERE `uid`={$this->uid} LIMIT 1";
        $db = $this->get_db();
        if($row = $db->fetchFirst($sql)) {
            if($row['created_at']>0){
                return;
            }else{
                $sql = "UPDATE `mocloud_stats` SET `client_id`={$client_id},`appid`={$appid},`created_at`={$created_at} WHERE `uid`={$this->uid} LIMIT 1";
                $db->query($sql);
            }
        } else {
            $this->_insert_stats($this->uid, $client_id, $appid, $created_at);
        }
    }
    /**
     * 
     * 设置绑定信息
     * @param string $device_id
     * @param array $device_info
     */
    public function set_bind_device($device_id, $device_info) {
        $db = $this->get_db();
        //解除绑定
        if($device_info == NULL) {
            $sql = "UPDATE `mocloud_device` SET `deleted_at`=" . time() . " WHERE `uid`={$this->uid} AND `device_id`=" . Mysql::quote($device_id);
            return $db->query($sql);
        } else {
            //已绑定过再绑定
            if($my_device=$this->get_bind_device($device_id, FALSE)) {
                if($my_device['deleted_at']==0){
                    return TRUE;
                }else{
                    $sql = "UPDATE `mocloud_device` SET `appid`=" . Core::getAPPID() . ",`deleted_at`=0,`name`=" . Mysql::quote($device_info['device_name']) . " WHERE `uid`={$this->uid} AND `device_id`=" . Mysql::quote($device_id);
                    return $db->query($sql);
                }
            }
            //第一次绑定
            $this->_register_user();//创建唯一一条用户使用记录
            
            $device_id = Mysql::quote($device_id);
            foreach($device_info as $k => $v) {
                $device_info[$k] = Mysql::quote($v);
            }
            $client_id = Core::getClientID();
            $sql = "INSERT IGNORE INTO `mocloud_device` 
                (`uid`,`device_id`,`name`,`os`,`model`,`sync`,`client_id`,`created_at`,`appid`) 
                VALUES ({$this->uid},{$device_id},{$device_info['device_name']},{$device_info['device_os']},{$device_info['device_model']},0,{$client_id}," . time() . "," . Core::getAPPID() . ")";
            return $db->query($sql);
        }
    }

    public function upd_bind_device($device_id, $device_info) {
        $sync = $device_info['device_sync'] ? 1 : 0;
        $sql = "UPDATE `mocloud_device` SET `sync`={$sync} WHERE `uid`={$this->uid} AND `device_id`=" . Mysql::quote($device_id);
        return $this->get_db()->query($sql);
    }

    /**
     * 获取绑定设备
     * @param string $device_id 设备id，NULL则取全部绑定
     * @param bool $exclude_del 是否过滤掉已经解除绑定的设备
     */
    public function get_bind_device($device_id = NULL, $exclude_del = TRUE) {
        $db = $this->get_db();
        
        $cond='';
        if($exclude_del){
            $cond=' AND `deleted_at`=0';
        }
        if(!$device_id) {
            $result = array();
            $sql = "SELECT * FROM `mocloud_device` WHERE `uid`={$this->uid}{$cond}";
            $query = $db->query($sql);
            while($row = $db->fetchArray($query)) {
                $result[] = $row;
            }
            return $result;
        } else {
            $sql = "SELECT * FROM `mocloud_device` WHERE `uid`={$this->uid}{$cond} AND `device_id`=" . Mysql::quote($device_id);
            return $db->fetchFirst($sql);
        }
    }

    public function get_history() {
        $sql = "SELECT `device_id`,`uploaded_at` AS backup_time,COUNT(`id`) AS file_count,SUM(`size`) AS total_size FROM 
            (SELECT `id`,`device_id`,`uploaded_at`,from_unixtime(`uploaded_at`,'%Y%m%d') AS uploaded_line,`size` 
            FROM `mocloud` WHERE `uid`={$this->uid} AND `uploaded`=1 ORDER BY `id` DESC LIMIT 1000) tmp 
            GROUP BY uploaded_line,device_id ORDER BY backup_time DESC";
        $db = $this->get_db();
        $query = $db->query($sql);
        $result = array();
        while($row = $db->fetchArray($query)) {
            $result[] = array('device_id' => $row['device_id'], 'backup_time' => $row['backup_time'], 'count' => $row['file_count'], 'total_size' => $row['total_size'],);
        }
        return $result;
    }
    
    public function set_star($idarr, $op){
        $ids = array();
        foreach($idarr as $id){
            $id = (int)$id;
            if($id) $ids[] = $id;
        }
        $sql = "UPDATE `mocloud` SET `star`={$op} WHERE `uid`={$this->uid} AND `id` IN (".implode(',', $ids).")";
        $db = $this->get_db();
        if($db->query($sql)){
            return TRUE;
        }else{
            return FALSE;
        }
    }
    
    /**
     * 
     * 图片预览地址
     * @param array $row
     * @param string $size 'sq-150','tn-100'...
     */
    public function get_uri($row, $size = '') {
        //如果是图片key与pid和ver有关
        $source_key = Core::config('source_key_mocloud');
        $media_prefix = Core::config('media_mocloud');
        $timestamp = intval(time()/Core::config('expire_mocloud'));
        if(!in_array($size, array('sq-150', 'o', 'tn-100'))) {
            $size = 'sq-150';
        }
        $key = Core::authcode("{$row['id']}\t{$timestamp}", 'ENCODE', $source_key);
        $image_info = pathinfo($row['name']);
        $image_name = $image_info['basename'];
        return $media_prefix . $key . "/{$size}/{$image_name}";
    }

    public function extra_uri($key) {
        $source_key = Core::config('source_key_mocloud');
        $str = Core::authcode($key, 'DECODE', $source_key);
        if($str) {
            if($arr = explode("\t", $str)) {
                return array('id' => $arr[0], 'timestamp' => $arr[1],);
            }
        }
        return NULL;
    }

    public function output($offset=0) {
        Core::header('Content-Type:' . $this->mime);
        //Core::header("Content-Length: ".$this->size);
        $this->get_source()->output($offset);
        Core::quit();
    }
    
    public function output_thumbnail($size,$offset=0){
        if(! in_array($size, array('tn-480','tn-100','tn-150','sq-150'))){
            return;
        }
        
        $thumb_file = Core::tempname($this->md5 . '_' . $size . '.jpg');
        
        if(! file_exists($thumb_file)) {
            $meta=$this->get_meta();
            if($thumb_fs_filename=$meta['thumbnail'][$size]){//ndfs缓存
                $source=new MocloudNDFS('',$thumb_fs_filename);
                Core::header("Content-Type:image/jpeg");
                $source->output($offset);
                Core::quit();
            }else{
                $thumbnail=$this->set_thumbnail($this->get_source(), $size);
                if($thumbnail){//重新做缩略图
                    $thumbnail = array_merge($meta['thumbnail'], $thumbnail);
                    $this->update_meta(array('thumbnail'=>$thumbnail));
                    Core::header("Content-Type:image/jpeg");
                    //readfile($thumb_file);
                    $this->_readfile($thumb_file,$offset);
                    Core::quit();
                }
            }
        }else{//本地缓存
            Core::header("Content-Type:image/jpeg");
            //readfile($thumb_file);
            $this->_readfile($thumb_file,$offset);
            Core::quit();
        }
    }
    
    private function _readfile($thumb_file,$offset=0){
        $size = filesize($thumb_file);
        
        if($offset > 0){
            Core::header("Content-Length:".($size-$offset));
            Core::header("Content-Range:bytes {$offset}-".($size-1)."/{$size}");
        }else{
            Core::header("Content-Length:$size");
        }
        
        $file = fopen($thumb_file, 'rb');
        if($file){
            fseek($file, $offset);
            while (!feof($file)){
                echo fread($file, 8192);
            }
        }
    }

    public function delete($arr, $field = 'id') {
        if(!$arr) 
            return TRUE;
        $newarr = array();
        foreach($arr as $val) {
            $newarr[] = Mysql::quote($val);
        }

        if(in_array($field, array('id','device_id','md5'))) {
            $query = $this->get_db()->query("SELECT SUM(`size`),COUNT(`id`) FROM `mocloud` WHERE `uid`={$this->uid} AND `uploaded`=1 AND `deleted_at`=0 AND `{$field}` IN (" . implode(',', $newarr) . ")");
            $row = $this->get_db()->fetchRow($query);
            
            $sql = "UPDATE `mocloud` SET `deleted_at`=" . time() . " WHERE `uid`={$this->uid} AND `uploaded`=1 AND `deleted_at`=0 AND `{$field}` IN (" . implode(',', $newarr) . ")";
            if($this->get_db()->query($sql)) {
                //更新统计
                if (function_exists('fastcgi_finish_request')){
                   fastcgi_finish_request();
                }
                $this->get_stats_latest();
                //$this->_update_stats(-$row[0], -$row[1]);
                return TRUE;
            } else {
                return FALSE;
            }
        } else {
            return TRUE;
        }
    }
    /**
     * 
     * 只能删除未上传完整的资源
     */
    public function destroy() {
        $sql = "DELETE FROM `mocloud` WHERE `id`={$this->id} AND `uploaded`=0";
        if($this->get_db()->query($sql)) {
            $mcSource = $this->get_source();
            $mcSource->destroy();
        }
    }
}
