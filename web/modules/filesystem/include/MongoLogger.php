<?php
class MongoLogger {

    private $conn;

    private static $instance;

    public function __construct() {
        $gridfs_conf = Core::config('gridfs_servers');
        $mongo = Core::getMongo($gridfs_conf['host'], $gridfs_conf['opt']);
        $this->conn = $mongo->selectDB($gridfs_conf['db']['fslog']);
        if(!is_NULL($gridfs_conf['user']) && !is_NULL($gridfs_conf['pwd'])) {
            $this->conn->authenticate($gridfs_conf['user'], $gridfs_conf['pwd']);
        }
    }

    public static function instance() {
        if(!self::$instance) {
            self::$instance = new MongoLogger();
        }
        return self::$instance;
    }

    public function getCollection($col = 'logs') {
        return $this->conn->selectCollection($col);
    }

    public function log($type = 'normal', $data, $fields=array()) {
        $type = (string) $type;
        if(trim($type) == '') {
            return;
        }
        $doc = array('type' => $type, 'content' => json_encode($data), 'ctime' => time());
        $doc = array_merge($doc,$fields);
        $this->getCollection($type)->insert($doc);
    }
}
