<?php
class Log_Model extends Model {
	
	public $error_msg = '';
	public $m;
	public static $instances = null;
	
	public function __construct() {
		// 加载数据库类。以下可以使用 $this->db 操作数据库（如果不要求可以省略）
		parent::__construct ();
		$mg_instance = new Mongo ( Kohana::config ( 'uap.mongodb' ) );
		$this->m = $mg_instance->selectDB ( MONGO_DB_FEED );
		//$feed->drop();
		$this->mongoLog = $this->m->selectCollection ( 'log' );
	}
	
	/**
	 * 单例
	 * @return Log_Model
	 */
	public static function &instance() {
		if (! is_object ( Log_Model::$instances )) {
			// Create a new instance
			Log_Model::$instances = new Log_Model ();
		}
		return Log_Model::$instances;
	}
	
	public function addCrashLog($data) 
	{
		$uid = $this->getUid ();
		try {
			$cur = $this->mongoLog;
			$doc = array();
			if (is_array($data)) {
				foreach ($data as $key => $value) {
					if (isset($value) && !empty($value))
						$doc[$key] = $value;
				}
				$doc['uid'] = $uid;
				$doc['add_time'] = time();
				$cur->insert ( $doc );
			} elseif (is_string($data)) {
				$doc = array ('uid' => $uid, 'content' => $data, 'add_time' => time());
				$cur->insert ( $doc );
			}
		} catch (Exception $e) {
			 //echo $e->getMessage();
			 return 0;
		}
	}
	
	public function listsCrashLog($appid) {
		$condition = array('appid'=>$appid);
		$col = $this->mongoLog->find ( $condition )->sort ( array ('add_time' => -1 ) );
		$arr = iterator_to_array ( $col );
		return $arr;
	}
}