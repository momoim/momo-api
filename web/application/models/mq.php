<?php
defined ( 'SYSPATH' ) or die ( 'No direct script access.' );

//MQ模型

class Mq_Model extends Model {
	
	public $error_msg = '';
	public static $instances = null;
	
	public function __construct() {
		parent::__construct ();
		$mg_instance = new MongoClient ( Kohana::config ( 'uap.mongodb' ) );
		$this->m = $mg_instance->selectDB ( MONGO_DB_FEED );
		$this->mq = $this->m->selectCollection ( 'mq' );
	
	}
	
	/**
	 * 单例
	 * @return Friend_Model
	 */
	public static function &instance() {
		if (! is_object ( Mq_Model::$instances )) {
			// Create a new instance
			Mq_Model::$instances = new Mq_Model ();
		}
		return Mq_Model::$instances;
	}
	
	/**
	 * 
	 * 新增mq队列
	 * @param string $qname
	 * @param string $oauth_token
	 * @param int $addtime
	 * @param int $source
	 */
	public function add($qname,$uid,$mq_token,$oauth_token,$addtime,$source,$master=0) {
		$doc = array ('_id' => $mq_token,'uid' => $uid, 'qname' => $qname, 'oauth_token' => $oauth_token, 'addtime' => $addtime,'source'=>(int)$source,'master'=>(int)$master);
		if(!$this->mq->find ( $doc)->count ( true )) {
			return $this->mq->insert ( $doc );
		}
		return true;
	}
	
	/**
	 * 
	 * 删除mq队列
	 * @param string $oauth_token
	 * @param int $uid
	 * @param int $source
	 */
	public function del($uid,$oauth_token,$source=array()) {
		if(is_array($source) && count($source) > 0) {
			foreach($source as $k => $v) {
				if($oauth_token) {
					$this->mq->remove ( array ('_id' => $oauth_token ,'uid' => $uid.'','source'=>(int)$v) );	
				} else {
					$this->mq->remove ( array ('uid' => $uid.'','source'=>(int)$v) );
				}
			}
		}
		return true;
	}
	
	/**
	 * 
	 * 根据token统计mq队列数
	 * @param string $token
	 */
	public function count_by_token($token) {
		$arr = array ('_id' => $token );
		return $this->mq->find ( $arr )->count ( true );
	}
	
	/**
	 * 
	 * 根据token查询队列名
	 * @param string $token
	 */
	public function find($token) {
		$arr = array ('_id' => $token );
		return $this->mq->findOne ( $arr );
	}
	

	/**
	 * 
	 * 删除mq队列
	 * @param string $oauth_token
	 * @param int $uid
	 * @param int $source
	 */
	public function del_by_uid($uid) {
		$this->mq->remove (array('uid' => $uid.''));
		return true;
	}
}