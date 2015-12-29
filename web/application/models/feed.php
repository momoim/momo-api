<?php
defined ( 'SYSPATH' ) or die ( 'No direct script access.' );

//事件模型


class Feed_Model extends Model {

	public $error_msg = '';
	public $m;
	public static $instances = null;

	public function __construct() {
		// 加载数据库类。以下可以使用 $this->db 操作数据库（如果不要求可以省略）
		parent::__construct ();
		$mg_instance = new MongoClient( Kohana::config ( 'uap.mongodb' ) );
		$this->m = $mg_instance->selectDB ( MONGO_DB_FEED );
		//$feed->drop();
		$this->feed = $this->m->selectCollection ( 'feed_new' );
		$this->aboutme = $this->m->selectCollection ( 'aboutme_new' );
		$this->feed_new = $this->m->selectCollection ( 'feed_new' );
		$this->comment_new = $this->m->selectCollection ( 'comment_new' );
		$this->storage = $this->m->selectCollection ( 'storage_new' );
		$this->friend = Friend_Model::instance ();
	}

	/**
	 * 单例
	 * @return Friend_Model
	 */
	public static function &instance() {
		if (! is_object ( Feed_Model::$instances )) {
			// Create a new instance
			Feed_Model::$instances = new Feed_Model ();
		}
		return Feed_Model::$instances;
	}

	/**
	 * 获取错误信息
	 * @return <type>
	 */
	public function get_error_msg() {
		return $this->error_msg;
	}
	/**
	 * 新增关于我的
	 * @param  $receiver_uid 关于我的接受者uid
	 * @param  $comment_uid 评论者uid
	 * @param  $typeid 动态类型
	 * @param  $commentid 当前评论id
	 * @param  $comment_content 当前评论内容
	 * @param  $comment_at 当前评论at数组
	 * @param  $feedid 动态id
	 * @param  $kind 关于我的类型：1：评论我的动态 2：留言 3：评论中@我 4：赞 5：动态中@我 6：回复
	 * @param  $feed_uid 动态uid
	 * @param  $feed_content 动态内容
	 * @param  $feed_at 动态内容at数组
	 * @param  $reply_commentid 回复评论id
	 * @param  $reply_comment 回复评论数组
	 *
	 * 1表示评论，2表示留言，3评论中@我，4表示赞，5动态中@我，6表示回复
	 * 1：回复我的评论 2：评论我的动态 3：赞我的动态 4：评论中@我 5：动态中@我
	 *
	 *
	 */
	public function addAboutme($receiver_uid, $comment_uid, $typeid, $commentid = 0, $comment_content = '', $comment_at = array(), $feedid, $kind = 2, $reply_comment = array(), $feed_uid = 0, $feed_content = '', $feed_at = array(), $at_all = 0, $source = 0) {
		$allow_send = true;
		$comment_name = sns::getrealname ( $comment_uid );
		$group_type = 0;
		$group_id = 0;
		$group_name = '';
		//如果关于我的接受者是评论者，不发动态
		if (empty ( $feedid )) {
			$this->error_msg = '动态id为空';
			return false;
		}
		//权限校验
		if ($typeid > 30 || $at_all == 1) {
			$allow_send = true;
			$feed_name = sns::getrealname ( $feed_uid );
		} else {
			$feed_row = $this->feed->findOne ( array ('_id' => $feedid ) );
			if (! $feed_row) {
				$this->error_msg = '动态已删除';
				return false;
			}
			$feed_uid = $feed_row ['owner_uid'];
			$feed_name = $feed_row ['owner_name'];
			$feed_content = $feed_row ['text'];
			$feed_at = $feed_row ['at'];
			$group_type = $feed_row ['group_type'];
			$group_id = $feed_row ['group_id'];
			$group_name = $feed_row ['group_name'];
			if (empty ( $reply_comment ) && $receiver_uid == $comment_uid) {
				$this->error_msg = '1';
				return false;
			}
		}
		if ($allow_send == true) {
			//数据校验
			switch ($kind) {
				case 1 :
					$comment_content = '评论道：' . $comment_content;
					break;
				case 3 :
					$comment_content = '在评论中提到我：' . $comment_content;
					break;
				case 4 :
					$comment_content = '认为很赞';
					break;
				case 2 :
				case 5 :
					$comment_content = '提到我';
					break;
				case 6 :
					if (! empty ( $reply_comment )) {
						$reply_id = $reply_comment ['id'];
						$reply_uid = $reply_comment ['uid'];
						$reply_name = $reply_comment ['realname'];
						$reply_content = $reply_comment ['content'];
						$reply_at = $reply_comment ['at'];
						$aboutme_owner = $reply_comment ['uid'];
					}
					$comment_content = '回复：' . $comment_content;
					break;
			}
			$coll = $this->m->selectCollection ( 'aboutme_new' );
			$aboutme_id = md5 ( $aboutme_owner . $comment_uid . microtime () . rand ( 1, 100000 ) );
			$doc = array ('_id' => $aboutme_id, 'uid' => ( int ) $receiver_uid, 'addtime' => microtime ( true ) * 10000, 'new' => 1,'sms'=>0, 'kind' => $kind, 'typeid' => $typeid, 'group_id' => ( int ) $group_id, 'group_type' => $group_type, 'group_name' => $group_name, 'feed_id' => $feedid, 'feed_uid' => ( int ) $feed_uid, 'feed_name' => $feed_name, 'feed_content' => $feed_content, 'feed_at' => $feed_at, 'comment_id' => $commentid, 'comment_uid' => ( int ) $comment_uid, 'comment_name' => $comment_name, 'comment_content' => $comment_content, 'comment_at' => $comment_at, 'reply_id' => $reply_id, 'reply_content' => $reply_content, 'reply_at' => $reply_at, 'reply_uid' => ( int ) $reply_uid, 'reply_name' => $reply_name, 'source' => $source );
			$coll->insert ( $doc );

			$newCount = $this->aboutMeNewCount ( $receiver_uid );

			/*************************/
			//手机端mq推送
			$aboutme_opt = array ();
			if ($kind == 6) {
				$aboutme_opt ['reply_source'] = array ('id' => $reply_id, 'text' => $reply_content, 'at' => $reply_at, 'user' => array ('id' => $reply_uid, 'name' => $reply_name, 'avatar' => sns::getavatar ( $receiver_uid, 'small' ) ) );

			}
			if ($kind == 2) {
				$aboutme_opt ['message'] = array ('text' => strip_tags ( $feed_content ), 'at' => $feed_at );
			}
			if (in_array ( $kind, array (1, 3, 6 ) )) {
				$aboutme_opt ['comment'] = array ('id' => $commentid, 'text' => $comment_content, 'at' => $comment_at );
			}
			if ($kind == 5) {
				$aboutme_opt ['statuses'] = array ('text' => $feed_content, 'at' => $feed_at );
			}
			$mq_msg = array ("kind" => "aboutme", "data" => array ("id" => $aboutme_id, "statuses_id" => $feedid, "user" => array ('id' => ( int ) $comment_uid, 'name' => sns::getrealname ( $comment_uid ), 'avatar' => sns::getavatar ( $comment_uid, 'small' ) ), "created_at" => time (), "kind" => $kind, "text" => $comment_content, "source" => $source, "new" => 1, "opt" => $aboutme_opt ) );

			$group = ($group_type == 1) ? $group_id : '0';
			if ($newCount) {
				$this->mq_send ( '{"kind":"aboutme","group":"' . $group . '","data":' . $newCount . '}', $receiver_uid );
				$this->mq_send ( json_encode ( $mq_msg ), $receiver_uid, 'momo_feed' );
			}
			return $aboutme_id;
		}
		$this->error_msg = '无权限';
		return false;
	}

	public function delAboutme($docid) {
		$coll = $this->m->selectCollection ( 'aboutme' );
		$coll->remove ( array ('_id' => new MongoId ( $docid ) ), true );
	}

	public function findAboutme($new, $pos,$start=0) {
		$uid = ( int ) $this->getUid ();
		if ($new) {
			$cur = $this->aboutme->find ( array ('uid' => $uid, 'new' => 1 ) )->sort ( array ('addtime' => - 1 ) )->skip(intval($start))->limit ( intval ( $pos ) );
		} else {
			$cur = $this->aboutme->find ( array ('uid' => $uid ) )->sort ( array ('addtime' => - 1 ) )->skip(intval($start))->limit ( intval ( $pos ) );
		}
		return $cur;
	}

	public function findMyMo($pos,$start,$sms) {
		$uid = ( int ) $this->getUid ();
		if($sms==2) {
			$cur = $this->aboutme->find ( array ('comment_uid' => $uid, 'new' => 1 ) )->sort ( array ('addtime' => - 1 ) )->skip(intval($start))->limit ( intval ( $pos ) );
		} else {
			$cur = $this->aboutme->find ( array ('comment_uid' => $uid, 'new' => 1 ,'sms'=>(int)$sms) )->sort ( array ('addtime' => - 1 ) )->skip(intval($start))->limit ( intval ( $pos ) );
		}

		return $cur;
	}

	public function updateAboutmeRead() {
		$uid = ( int ) $this->getUid ();
		$newdata = array ('$set' => array ("new" => 0 ) );
		$this->aboutme->update ( array ("uid" => $uid ), $newdata, array ("multiple" => true ) );
	}

	public function aboutmeCount($new = 0, $uid = '') {
		if (! $uid) {
			$uid = $this->getUid ();
		}
		$coll = $this->m->selectCollection ( 'aboutme' );
		if ($new) {
			$arr = array ('owner' => $uid, 'new' => 1 );
		} else {
			$arr = array ('owner' => $uid );
		}
		$cur = $coll->find ( $arr );
		$arr = iterator_to_array ( $cur );
		$num = 0;
		foreach ( $arr as $val ) {
			$num += $val ['num'] ? $val ['num'] : $val ['new'];
		}

		return $num;
	}

	/**
	 * 添加新的关于我的消息的统计
	 *
	 */
	public function aboutMeNewCount($uid = 0) {
		if (! $uid) {
			$uid = $this->getUid ();
		}
		$arr = array ('uid' => ( int ) $uid, 'new' => 1 );
		$num = $this->aboutme->find ( $arr )->count ( true );
		return $num;
	}

	public function addStorage($status_id) {
		$doc = array ('feedid' => $status_id, 'uid' => ( int ) $this->getUid (), 'time' => $this->feedTime () );
		$this->storage->insert ( $doc );

		$newdata = array ('$addToSet' => array ("storage" => "" . $this->getUid () ) );
		return $this->feed->update ( array ('id' => $status_id ), $newdata );
	}

	public function delStorage($status_id) {
		$this->storage->remove ( array ('feedid' => $status_id, 'uid' => ( int ) $this->getUid () ), true );

		$newdata = array ('$pull' => array ("storage" => "" . $this->getUid () ) );
		return $this->feed->update ( array ('_id' => $status_id ), $newdata );
	}

	public function findStorageNew($uptime, $pretime, $downtime, $pos) {
		$col = $this->storage->find ( array ('uid' => ( int ) $this->getUid () ), array ('feedid' ) );
		$feedid = array ();
		foreach ( $col as $val ) {
			$feedid [] = $val ['feedid'];
		}

		$uptime = $uptime . '';
		$downtime = $downtime . '';
		$pretime = $pretime . '';
		if ($uptime) {
			$direct = 'up';
		} else if ($downtime) {
			$direct = 'down';
		} else if ($pretime) {
			$direct = 'pre';
		}

		switch ($direct) {
			case 'pre' :
				$condition = array ('_id' => array ('$in' => $feedid, '$nin' => $this->hiddenId () ), 'last_updated' => array ('$gt' => $pretime ) );
				$deltime = $pretime;
				break;
			case 'up' :
				$condition = array ('_id' => array ('$in' => $feedid, '$nin' => $this->hiddenId () ), 'last_updated' => array ('$gt' => $uptime ) );
				$deltime = $uptime;
				break;
			case 'down' :
				$condition = array ('_id' => array ('$in' => $feedid, '$nin' => $this->hiddenId () ), 'last_updated' => array ('$lt' => $downtime ) );
				break;
			default :
				$condition = array ('_id' => array ('$in' => $feedid, '$nin' => $this->hiddenId () ) );
				break;
		}

		if ($direct == 'pre') {
			$col = $this->feed->find ( $condition )->sort ( array ('last_updated' => 1 ) )->limit ( intval ( $pos ) );
		} else {
			$col = $this->feed->find ( $condition )->sort ( array ('last_updated' => - 1 ) )->limit ( intval ( $pos ) );
		}

		$arr = iterator_to_array ( $col );
		$count = count ( $arr );
		if ($count) {
			//print_r($col->explain());
			$res = array ('code' => 200, 'result' => array ('count' => $count, 'data' => $arr ) );
		} else {
			$res = array ('code' => 404, 'result' => array () );
		}

		return $res;
	}

	public function addHidden($statuses_id) {
		$uid = $this->getUid ();
		$docid = $this->feed_id ( $uid, $statuses_id );
		$hidden = $this->m->selectCollection ( 'myhidden' );

		$cur = $this->m->selectCollection ( 'feed_hide' );
		$doc = array ('uid' => intval ( $uid ), 'id' => $docid, 'typeid' => 0, 'objid' => $statuses_id, 'time' => $this->feedTime () );
		$cur->insert ( $doc );

		$newdata = array ('$addToSet' => array ("feedid" => $statuses_id ) );
		return $hidden->update ( array ('uid' => "" . $uid ), $newdata, array ("upsert" => true ) );
	}

	public function delHidden($statuses_id) {
		$uid = $this->getUid ();
		$hidden = $this->m->selectCollection ( 'myhidden' );

		$cur = $this->m->selectCollection ( 'feed_hide' );
		$cur->remove ( array ('uid' => intval ( $uid ), 'objid' => $statuses_id ) );

		$newdata = array ('$pull' => array ("feedid" => $statuses_id ) );
		return $hidden->update ( array ('uid' => "" . $uid ), $newdata );
	}

	public function hiddenId() {

		$hidden = $this->m->selectCollection ( 'myhidden' );
		$myhidden = $hidden->findOne ( array ('uid' => $this->getUid () . '' ), array ('feedid' ) );

		return $myhidden ['feedid'] ? $myhidden ['feedid'] : array ();
	}

	public function findHiddenNew($uptime, $pretime, $downtime, $pos) {
		$feedid = $this->hiddenId ();

		$uptime = $uptime . '';
		$downtime = $downtime . '';
		$pretime = $pretime . '';
		if ($uptime) {
			$direct = 'up';
		} else if ($downtime) {
			$direct = 'down';
		} else if ($pretime) {
			$direct = 'pre';
		}

		switch ($direct) {
			case 'pre' :
				$condition = array ('_id' => array ('$in' => $feedid ), 'last_updated' => array ('$gt' => $pretime ) );
				$deltime = $pretime;
				break;
			case 'up' :
				$condition = array ('_id' => array ('$in' => $feedid ), 'last_updated' => array ('$gt' => $uptime ) );
				$deltime = $uptime;
				break;
			case 'down' :
				$condition = array ('_id' => array ('$in' => $feedid ), 'last_updated' => array ('$lt' => $downtime ) );
				break;
			default :
				$condition = array ('_id' => array ('$in' => $feedid ) );
				break;
		}

		if ($direct == 'pre') {
			$col = $this->feed_new->find ( $condition )->sort ( array ('last_updated' => 1 ) )->limit ( intval ( $pos ) );
		} else {
			$col = $this->feed_new->find ( $condition )->sort ( array ('last_updated' => - 1 ) )->limit ( intval ( $pos ) );
		}

		$arr = iterator_to_array ( $col );
		$count = count ( $arr );
		if ($count) {
			//print_r($col->explain());
			$res = array ('code' => 200, 'result' => array ('count' => $count, 'data' => $arr ) );
		} else {
			$res = array ('code' => 404, 'result' => array () );
		}

		return $res;
	}

	function _parsedata($data, $tpl, $baseurl = '') {
		//$baseurl = url::base();
		$baseurl = Kohana::config ( 'config.site_domain' );
		@extract ( $data );
		$tpl = preg_replace ( '/{\s*foreach\s+(\w+)\s+as\s+(\w+)\s*}/iU', '<?php if (is_array($\\1)) foreach ($\\1 as $\\2) {?>', $tpl );
		$tpl = preg_replace ( '/{\s*endforeach\s*}/i', '<?php }?>', $tpl );
		$tpl = preg_replace ( "/{(.+)}/U", "<?php echo $\\1;?>", $tpl );

		ob_start ();
		eval ( "?>" . $tpl );
		$result = ob_get_contents ();
		ob_end_clean ();

		if (! $result) {
			return NULL;
		}

		//应用网址附加处理
		if ($baseurl) {
			if (substr ( $baseurl, - 1 ) != '/')
				$baseurl .= '/';
			$result = preg_replace ( '/(src|href)\s*=\s*([\'"])(?!http:\/\/)/iU', "\\1=\\2$baseurl", $result );
			$result = preg_replace ( "|(['\"])" . preg_quote ( $baseurl ) . "\\1|i", '""', $result ); //处理空网址
		}

		return $result;
	}

	function feed_id($uid, $feed_id) {
		return md5 ( $uid . '_' . $feed_id . '_' . microtime () . '_' . rand ( 1, 1000000 ) );
	}

	function mid($name, $db) {
		$update = array ('$inc' => array ("id" => 1 ) );
		$query = array ('name' => $name );
		$command = array ('findandmodify' => 'ids', 'update' => $update, 'query' => $query, 'new' => true, 'upsert' => true );
		$id = $db->command ( $command );
		return $id ['value'] ['id'];
	}

	/**
	 * 添加动态
	 * @param <type> $uid
	 * @param <type> $typeid
	 * @param <type> $text
	 * @param <type> $source
	 * @param <type> $application
	 * @param <type> $at
	 * @param <type> $images
	 * @param <type> $sync
	 * @param <type> $group_type
	 * @param <type> $group_id
	 * @param <type> $retweet_id
	 * @param <type> $location
	 * @param <type> $long_text
	 * @param <type> $allow_rt
	 * @param <type> $allow_comment
	 * @param <type> $allow_praise
	 * @param <type> $allow_del
	 * @param <type> $allow_hide
	 * @return <type>
	 */
	public function addFeed($uid, $typeid, $text, $source, $application = array(), $at = array(), $images = array(), $sync = array(), $group_type = 0, $group_id = 0, $retweet_id = 0, $location = array(), $long_text = '', $origin_url = array(),$allow_rt = 1, $allow_comment = 1, $allow_praise = 1, $allow_del = 1, $allow_hide = 1) {
		$feedid = $this->feed_id ( $uid, $typeid );
		$group_name = '';
		$application = $application == null ? array () : $application;
		//群组
		if ($group_id > 0) {
			$res = Group_Model::instance()->getGroupInfo ( $group_id );
			$group_name = $res ['gname'];
		}
		$mix_id = $group_id > 0 ?  '1_' . $group_id : $uid;
		$group_type = $group_id > 0 ? 1:0;
		$is_long_text = empty ( $long_text ) ? 0 : 1;
		$synced = empty ( $sync ) ? 0 : 1;
		$allow_rt = isset ( $allow_rt ) ? $allow_rt : 1;
		$doc = array ('_id' => $feedid, 'id' => $feedid, 'typeid' => $typeid, 'allow_rt' => $allow_rt, 'allow_comment' => $allow_comment, 'allow_praise' => $allow_praise, 'allow_del' => $allow_del, 'allow_hide' => $allow_hide, 'text' => $text, 'long_text' => $long_text, 'is_long_text' => $is_long_text, 'at' => $at, 'rt_status_id' => $retweet_id, 'owner_uid' => $uid . '', 'owner_name' => sns::getrealname ( $uid ), 'created_at' => time () . '', 'last_updated' => $this->feedTime (), 'group_type' => $group_type, 'group_id' => $group_id, 'group_name' => $group_name, 'mix_id' => $mix_id . '', 'location' => $location, 'source' => $source, 'like_count' => 0, 'like_list' => array (), 'comment_count' => 0, 'comment_list' => array (), 'synced' => $synced, 'origin_url'=>$origin_url,'sync' => $sync, 'application' => $application, 'accessory' => $images );
		$this->feed->insert ( $doc );
		$this->updateFeed ( $feedid );
		return $feedid;
	}

	public function feedTime() {
		return (microtime ( true ) * 10000) . '';
	}

	public function updateCall($typeid, $objid, $call) {
		$data = array ('typeid' => $typeid, 'objid' => $objid );
		$newdata = array ('$set' => array ('call' => $call ) );
		$this->feed->update ( array ('_id' => $this->feed_id ( $data ) ), $newdata );
	}

	public function updateFeed($feedid, $row = '') {
		if (! $row) {
			$row = $this->feed->findOne ( array ('_id' => $feedid ) );
		}
		if ($row) {
			$uid_string = '';
			$uid = $row ['owner_uid'];
			$uids = array();

			$group_id = (int) $row['group_id'];
			if($group_id > 0) {
				$group_user = Group_Model::instance()->getGroupAllMember($group_id);
				foreach($group_user as $v) {
					$uids[] = $v['uid'];
				}
			} else {
				$uids = $this->friend->getAllFriendIDs ( $uid ,false);
				if($this->uid!=$uid) {
					$uids[] = $uid;
				}
			}

			$mq_msg = '{"kind":"updateFeed","group":'.$group_id.',"data":1}';
			foreach ( $uids as $val ) {
				if($val == $this->uid) continue;
				if ($val == Kohana::config ( 'uap.xiaomo' )) {
					continue;
				}

				if (strlen ( $uid_string ) > 200) {
					$this->mq_send ( $mq_msg, substr ( $uid_string, 0, - 1 ) );
					$uid_string = '';
				}
				$uid_string .= $val . '.';
			}
			$this->mq_send ( $mq_msg, substr ( $uid_string, 0, - 1 ) );
		}
	}

	public function findFeed($id, $all = 1) {
		$val = $this->feed_new->findOne ( array ('_id' => $id ) );
		return $val;
	}

	public function findMoFeed($id, $all = 1) {
		$val = $this->aboutme->findOne ( array ('_id' => $id ) );
		return $val;
	}

	public function isFeedExist($typeid, $objid) {
		$data = array ('typeid' => $typeid, 'objid' => $objid );
		$val = $this->feed->findOne ( array ('_id' => $this->feed_id ( $data ) ) );
		if (empty ( $val )) {
			return false;
		}
		return true;
	}

	public function modifyFeed($typeid, $objid, $title, $body) {
		$data = array ('typeid' => $typeid, 'objid' => $objid );
		$feedid = $this->feed_id ( $data );

		$tpl = $this->getFeedTpl ( $typeid );

		$tmp = array ();
		if ($title) {
			$tmp ['title'] = $this->_parsedata ( $title, $tpl ['title_tpl'] );
		}
		if ($body) {
			$tmp ['body'] = $this->_parsedata ( $body, $tpl ['body_tpl'] );
		}
		$tmp ['lasttime'] = $this->feedTime ();
		$newdata = array ('$set' => $tmp );
		$this->feed->update ( array ('_id' => $feedid ), $newdata );
	}

	public function addFeedComment($status_id, $comment, $isbubble = true) {
		$feed = $this->findFeed ( $status_id );
		if ($feed && !empty($comment)) {
			if ($isbubble) {
				$newdata = array ('$set' => array ('last_updated' => $this->feedTime () ), '$inc' => array ('comment_count' => 1 ), '$addToSet' => array ("comment_list" => $comment ) );
			} else {
				$newdata = array ('$inc' => array ('comment_count' => 1 ), '$addToSet' => array ("comment_list" => $comment ) );
			}

			if ($feed ["comment_count"] >= 5) {
				$this->feed->update ( array ('id' => $status_id ), array ('$pop' => array ('comment_list' => - 1 ) ) );
			}
			$this->feed->update ( array ('id' => $status_id ), $newdata );
		}
	}

	public function addComment($id, $uid, $realname, $content, $status_id, $client_id, $at) {
		$data = array ('act' => 'addComment', 'id' => $id . '', 'uid' => $uid, 'realname' => $realname, 'content' => $content, 'feedid' => $status_id );
		$feedid = $this->feed_id ( $uid, $status_id );

		$newdata = array ('$set' => array ('lasttime' => $this->feedTime () ), '$inc' => array ('commentCount' => 1 ), '$addToSet' => array ("commentList" => array ('id' => $data ['id'], 'uid' => $data ['uid'], 'feedid' => $data ['feedid'], 'realname' => $data ['realname'], 'addtime' => time () . '', 'content' => $data ['content'], 'at' => $at, 'client_id' => intval ( $client_id ), 'im' => 1 ) ) );
		$this->feed->update ( array ('_id' => $feedid ), $newdata );

		$this->updateFeed ( $feedid );
	}

	/**
	 *
	 * 删除评论
	 * @param string $comment_id
	 * @param string $statuses_id
	 */
	public function deleteComment($comment_id, $statuses_id) {
		if($comment_id && $statuses_id) {
			$feed = $this->findFeed ( $statuses_id );
			$comment_data = array();
			if ($feed ["comment_count"] > 1 && count($feed ["comment_list"])==1) {
				$comment_col = $this->comment_new->find(array('feedid'=>$statuses_id))->sort ( array ('addtime' => -1 ) )->limit ( 2);
				$comment_arr = iterator_to_array ( $comment_col );
				$comment_arr = array_values($comment_arr);
				if(isset($comment_arr[1]) && count($comment_arr[1])>0) {
					$comment_data = array ('id' => $comment_arr[1]['id'], 'uid' => $comment_arr[1]['uid'],  'name' => $comment_arr[1]['realname'], 'created_at' => floor($comment_arr[1]['addtime']/10000), 'text' => $comment_arr[1] ['content'], 'at' => $comment_arr[1]['at'], 'source' => $comment_arr[1]['client_id'], 'im' => (int)$comment_arr[1]['im'] );
				}
			}
			if(count($comment_data)>0) {
				$this->feed->update ( array ('_id' => $statuses_id ),array('$addToSet'=>array ("comment_list" =>$comment_data)));
			}
			$newdata = array ('$inc' => array ('comment_count' => - 1 ), '$pull' => array ("comment_list" => array ('id' =>$comment_id ) ) );
			$this->feed->update ( array ('_id' => $statuses_id ), $newdata );

			$this->aboutme->remove ( array ('feed_id' => $statuses_id,'comment_id'=>$comment_id) );
			$this->updateFeed ( $statuses_id );

		}
	}

	public function deleteItem($id, $typeid, $objid) {
		$data = array ('typeid' => $typeid, 'objid' => $objid );
		$newdata = array ('$pull' => array ("title_source.item" => array ('id' => $id . '' ) ) );
		$this->feed->update ( array ('_id' => $this->feed_id ($objid,$data ) ), $newdata );

		$doc = $this->findFeed ( $typeid, $objid );
		if (empty ( $doc ['title_source'] ['item'] )) {
			$this->delFeed ( $typeid, $objid );
		}

		$id = ($typeid == 32) ? "7_" . $id : "15_" . $id;
		$msg = json_encode ( array ("kind" => "group_remove", "data" => $id ) );
		$this->_uc_fopen ( Kohana::config ( 'uap.http_push' ) . $objid, 0, $msg, 'POST' );
		$this->mq_send ( $msg, $objid );

	}

	public function addLike($uid, $name, $objid, $is_bubble = true) {
		$data = array ('id' => $objid );
		if ($is_bubble == true) {
			$newdata = array ('$set' => array ('last_updated' => $this->feedTime () ), '$inc' => array ('like_count' => 1 ), '$addToSet' => array ("like_list" => array ('id' => $uid, 'name' => $name ) ) );
		} else {
			$newdata = array ('$inc' => array ('like_count' => 1 ), '$addToSet' => array ("like_list" => array ('id' => $uid, 'name' => $name ) ) );
		}
		$this->feed->update ( array ('_id' => $objid ), $newdata );

		$this->updateFeed ( $objid );
	}

	/*
	 * 删除事件
	 * @param  int 事件模板ID
	 * @param  int 产生事件的对象ID
	 * @return boolea
    */
	public function delFeed($statuses_id) {
		$row = $this->feed->findOne ( array ('_id' => $statuses_id ) );
		$textContent = $row['long_text']?$row['long_text']:$row['text'];

		if ($row ['_id']) {
			$cur = $this->m->selectCollection ( 'feed_del' );
			$doc = array ('qid' => $row ['mix_id'], 'typeid' => $row ['typeid'], 'objid' => $statuses_id, 'time' => $this->feedTime () );
			$cur->insert ( $doc );

			$this->feed->remove ( array ('_id' => $statuses_id ), true );
			$this->delStorage ( $statuses_id );
			$this->delHidden ( $statuses_id );
			$coll = $this->m->selectCollection ( 'aboutme_new' );
			$coll->remove ( array ('feed_id' => $statuses_id ) );

			$coll = $this->m->selectCollection ( 'comment_new' );
			$coll->remove ( array ('feedid' => $statuses_id ) );
			$this->updateFeed ( '', $row );
		}
		return $textContent;
	}

	/**
	 *
	 * 获取用户事件
	 * @param int $uid
	 * @param int $uptime
	 * @param int $pretime
	 * @param int $downtime
	 * @param int $pos
	 */
	public function getUserFeedNew($uid, $uptime, $pretime, $downtime, $pos, $type_id = 0) {
		$uptime = $uptime . '';
		$downtime = $downtime . '';
		$pretime = $pretime . '';
		$id_del = array ();
		if ($uptime && $downtime) {
			$direct = 'middle';
		} else if ($uptime) {
			$direct = 'up';
		} else if ($downtime) {
			$direct = 'down';
		} else if ($pretime) {
			$direct = 'pre';
		}

		switch ($direct) {
			case 'pre' :
				$condition = array ('mix_id' => $uid . '', '_id' => array ('$nin' => $this->hiddenId () ), 'last_updated' => array ('$gt' => $pretime ) );
				$deltime = $pretime;
				break;
			case 'up' :
				$condition = array ('mix_id' => $uid . '', '_id' => array ('$nin' => $this->hiddenId () ), 'last_updated' => array ('$gt' => $uptime ) );
				$deltime = $uptime;
				break;
			case 'down' :
				$condition = array ('mix_id' => $uid . '', '_id' => array ('$nin' => $this->hiddenId () ), 'last_updated' => array ('$lt' => $downtime ) );
				break;
			default :
				$condition = array ('mix_id' => $uid . '', '_id' => array ('$nin' => $this->hiddenId () ) );
				break;
		}

		$id_del = array ();
		if ($direct == 'pre' || $direct == 'up') {
			$con_del = array ('qid' => $uid . '', 'time' => array ('$gt' => $deltime ) );
			$cur = $this->m->selectCollection ( 'feed_del' );
			$arr_del = iterator_to_array ( $cur->find ( $con_del ) );
			foreach ( $arr_del as $row_del ) {
				if ($row_del ['objid']) {
					$id_del [] = array ('id' => $row_del ['objid'] );
				}
			}

			$con_del = array ('uid' => ( int ) $this->uid, 'time' => array ('$gt' => $deltime ) );
			$cur = $this->m->selectCollection ( 'feed_hide' );
			$arr_del = iterator_to_array ( $cur->find ( $con_del ) );
			foreach ( $arr_del as $row_del ) {
				if ($row_del ['objid']) {
					$id_del [] = array ('id' => $row_del ['objid'] );
				}
			}
		}

		if ($type_id) {
			$condition ['typeid'] = intval ( $type_id );
		}

		if ($direct == 'pre') {
			$col = $this->feed_new->find ( $condition )->sort ( array ('last_updated' => 1 ) )->limit ( intval ( $pos ) );
		} else {
			$col = $this->feed_new->find ( $condition )->sort ( array ('last_updated' => - 1 ) )->limit ( intval ( $pos ) );
		}

		$arr = iterator_to_array ( $col );
		$count = count ( $arr );
		if ($count) {
			$res = array ('code' => 200, 'result' => array ('count' => $count, 'data' => $arr, 'delete' => $id_del ) );
		} else {
			$res = array ('code' => 404, 'result' => array ('delete' => $id_del ) );
		}
		return $res;
	}

	//获取好友事件
	public function getFriendFeedNew($uid, $uptime, $pretime, $downtime, $pos, $type_id = 0) {
		$uptime = $uptime . '';
		$downtime = $downtime . '';
		$pretime = $pretime . '';
		$direct = '';
		if ($uptime && $downtime) {
			$direct = 'middle';
		} else if ($uptime) {
			$direct = 'up';
		} else if ($downtime) {
			$direct = 'down';
		} else if ($pretime) {
			$direct = 'pre';
		}
		$uids = null;
		if (! $uids) {
			$uids = $this->friend->get_user_link_cache ( $uid );
			$uids [] = $uid . '';
			$uids [] = Kohana::config ( 'uap.xiaomo' );

			//群组动态
			$group_array = Group_Model::instance()->getUserAllGroupId($uid);
			if($group_array) {
				foreach ($group_array as $group)
					$uids[] = '1_'.$group['gid'];
			}
		}

		switch ($direct) {
			case 'pre' :
				$condition = array ('mix_id' => array ('$in' => $uids ), 'last_updated' => array ('$gt' => $pretime ), '_id' => array ('$nin' => $this->hiddenId () ) );
				$deltime = $pretime;
				break;
			case 'up' :
				$condition = array ('mix_id' => array ('$in' => $uids ), 'last_updated' => array ('$gt' => $uptime ), '_id' => array ('$nin' => $this->hiddenId () ) );
				$deltime = $uptime;
				break;
			case 'down' :
				//$pos += 1;
				$condition = array ('mix_id' => array ('$in' => $uids ), 'last_updated' => array ('$lt' => $downtime ), '_id' => array ('$nin' => $this->hiddenId () ) );
				break;
			case 'middle' :
				$condition = array ('mix_id' => array ('$in' => $uids ), 'last_updated' => array ('$lt' => $uptime, '$gt' => $downtime ), '_id' => array ('$nin' => $this->hiddenId () ) );
				break;
			default :
				$condition = array ('mix_id' => array ('$in' => $uids ), '_id' => array ('$nin' => $this->hiddenId () ) );
				break;
		}
		$id_del = array ();
		if ($direct == 'pre' || $direct == 'up') {
			$con_del = array ('qid' => array ('$in' => $uids ), 'time' => array ('$gt' => $deltime ) );
			$cur = $this->m->selectCollection ( 'feed_del' );
			$arr_del = iterator_to_array ( $cur->find ( $con_del ) );
			foreach ( $arr_del as $row_del ) {
				if ($row_del ['objid']) {
					$id_del [] = array ('id' => $row_del ['objid'] );
				}
			}

			$con_del = array ('uid' => intval ( $uid ), 'time' => array ('$gt' => $deltime ) );
			$cur = $this->m->selectCollection ( 'feed_hide' );
			$arr_del = iterator_to_array ( $cur->find ( $con_del ) );
			foreach ( $arr_del as $row_del ) {
				if ($row_del ['objid']) {
					$id_del [] = array ('id' => $row_del ['objid'] );
				}
			}
		}

		if ($type_id) {
			$condition ['typeid'] = intval ( $type_id );
		}

		if ($direct == 'pre') {
			$col = $this->feed_new->find ( $condition )->sort ( array ('last_updated' => 1 ) )->limit ( intval ( $pos ) );
		} else {
			$col = $this->feed_new->find ( $condition )->sort ( array ('last_updated' => - 1 ) )->limit ( intval ( $pos ) );
		}
		$arr = iterator_to_array ( $col );
		if ($direct == 'pre') {
			$arr = array_reverse ( $arr );
		}
		$count = count ( $arr );
		if ($count) {
			$res = array ('code' => 200, 'result' => array ('count' => $count, 'data' => $arr, 'delete' => $id_del ) );
		} else {
			$res = array ('code' => 404, 'result' => array ('delete' => $id_del ) );
		}
		return $res;
	}

	/**
	 * 获取好友动态
	 * @param <type> $uid
	 * @param <type> $uptime
	 * @param <type> $pretime
	 * @param <type> $downtime
	 * @param <type> $pos
	 * @param <type> $type_id
	 * @return <type>
	 */
	public function getFriendFeedLast($uid, $uptime, $pretime, $downtime, $pos, $type_id = 0) {
		$uptime = $uptime . '';
		$downtime = $downtime . '';
		$pretime = $pretime . '';
		$direct = '';
		if ($uptime && $downtime) {
			$direct = 'middle';
		} else if ($uptime) {
			$direct = 'up';
		} else if ($downtime) {
			$direct = 'down';
		} else if ($pretime) {
			$direct = 'pre';
		}
		$uids_str = Cache::instance ()->get ( "feed_uids_" . $uid );
		if (! $uids_str) {
			$uids_arr = $this->friend->get_user_link_cache ( $uid );
			$uids_arr [] = $uid . '';
			$uids_str = join ( ',', $uids_arr );
			Cache::instance ()->set ( "feed_uids_" . $uid, $uids_str, null );
		}

		$hiddenid_str = Cache::instance ()->get ( "hiddenid_" . $uid );
		if (! $hiddenid_str) {
			$hiddenid_arr = $this->hiddenId ();
			if (count ( $hiddenid_arr ) > 0) {
				foreach ( $hiddenid_arr as $hiddenid ) {
					$hiddenid_str .= '"' . $hiddenid . '",';
				}
				$hiddenid_str = trim ( ',', $hiddenid_str );
				Cache::instance ()->set ( "hiddenid_" . $uid, $hiddenid_str, null );
			}
		}

		$sql = "SELECT feed_id FROM feed WHERE owner_uid in ($uids_str) ";
		if ($hiddenid_str) {
			$sql .= " AND feed_id not in ($hiddenid_str) ";
		}

		switch ($direct) {
			case 'pre' :
				$sql .= "AND last_updated > $pretime ORDER BY last_updated DESC ";
				$deltime = $pretime;
				break;
			case 'up' :
				$sql .= "AND last_updated > {$uptime} ORDER BY last_updated DESC ";
				$deltime = $uptime;
				break;
			case 'down' :
				$sql .= "AND last_updated < {$downtime} ORDER BY last_updated DESC ";
				break;
			case 'middle' :
				$sql .= "AND last_updated > {$downtime} AND last_updated < {$uptime} ORDER BY last_updated DESC ";
				break;
			default :
				$sql .= "ORDER BY last_updated DESC ";
				break;
		}
		if ($direct == 'pre' || $direct == 'up') {
			$id_del = Cache::instance ()->get ( "delid_" . $uid );
			if (! $id_del) {
				$con_del = array ('qid' => array ('$in' => $uids ), 'time' => array ('$gt' => $deltime ) );
				$cur = $this->m->selectCollection ( 'feed_del' );
				$arr_del = iterator_to_array ( $cur->find ( $con_del ) );
				foreach ( $arr_del as $row_del ) {
					if ($row_del ['objid']) {
						$id_del [] = array ('id' => $row_del ['objid'] );
					}
				}

				$con_del = array ('uid' => intval ( $uid ), 'time' => array ('$gt' => $deltime ) );
				$cur = $this->m->selectCollection ( 'feed_hide' );
				$arr_del = iterator_to_array ( $cur->find ( $con_del ) );
				foreach ( $arr_del as $row_del ) {
					if ($row_del ['objid']) {
						$id_del [] = array ('id' => $row_del ['objid'] );
					}
				}
				Cache::instance ()->set ( "delid_" . $uid, $id_del, null );
			}
		}
		$sql .= "LIMIT {$pos}";
		$query = $this->db->query ( $sql );
		$result = $query->result_array ( FALSE );
		$count = count ( $result );
		$mongo_result = $this->_feed_result_mongo ( $result );
		if ($count) {
			$res = array ('code' => 200, 'result' => array ('count' => $count, 'data' => $mongo_result, 'delete' => $id_del ) );
		} else {
			$res = array ('code' => 404, 'result' => array ('delete' => $id_del ) );
		}
		return $res;
	}

	/**
	 * 根据feedid查询mongo中的数据
	 */
	public function _feed_result_mongo($result) {
		if ($result) {
			$feed_id = array ();
			foreach ( $result as $res ) {
				$feed_id [] = $res ['feed_id'];
			}
			$condition = array ('id' => array ('$in' => $feed_id ) );
			$feed_arr = $this->feed->find ( $condition )->sort ( array ('last_updated' => 1 ) );
			return $feed_arr;
		}
		return array ();
	}

	//获取事件模板
	public function getFeedTpl($tplid = 0, $name = '', $appid = 0) {
		$feedtpl = Cache::instance ()->get ( "feed/feedtpl" );
		if (! $feedtpl) {
			$feedtpl = Kohana::config ( 'feedtpl.all' );
			Cache::instance ()->set ( "feed/feedtpl", $feedtpl, null ); //缓存事件模板
		}

		if ($tplid > 0) {
			foreach ( $feedtpl as $val ) {
				if ($val ['typeid'] == $tplid) {
					return $val;
				}
			}
		}

		if ($name) {
			foreach ( $feedtpl as $val ) {
				if ($val ['typename'] == $name) {
					return $val;
				}
			}
		}

		if ($appid > 0) {
			while ( list ( $key, $val ) = each ( $feedtpl ) ) {
				if ($val ['appid'] != $appid) {
					unset ( $feedtpl [$key] );
				}
			}
		}
		return $feedtpl;
	}

	//获取事件模板ID
	public function getFeedTplId($tplname) {
		$feedtpl = Cache::instance ()->get ( "feed/feedtpl/keyname" );
		if (! $feedtpl) {
			$feedtpl = self::getFeedTpl ();
			while ( list ( $key, $val ) = each ( $feedtpl ) ) {
				unset ( $feedtpl [$key] ['appid'], $feedtpl [$key] ['icon'], $feedtpl [$key] ['title_tpl'], $feedtpl [$key] ['body_tpl'] );
			}
			Cache::instance ()->set ( "feed/feedtpl/keyname", $feedtpl, null ); //缓存事件模板tplid->tplname
		}

		foreach ( $feedtpl as $val ) {
			if ($val ['typename'] == $tplname) {
				return $val ['typeid'];
			}
		}
	}

	/*
     * @param array 要判断的用户ID组
     * @return array
    */
	public function getCommentRights($uids) {
		$return = FALSE;
		$result = $this->db->select ( array ('uid', 'allowcomment' ) )->in ( 'uid', $uids )->get ( 'userinfo' );
		foreach ( $result as $row )
			$return [$row->uid] = $row->allowcomment;
		return $return;
	}

	public function addIm($uid, $name, $ownerid, $fid, $content, $time) {
		$im = $this->m->selectCollection ( 'im' );
		$id = $this->mid ( 'chat', $this->m );
		$doc = array ('id' => $id, 'uid' => intval ( $uid ), 'name' => $name, 'ownerid' => intval ( $ownerid ), 'fid' => intval ( $fid ), 'content' => $content, 'addtime' => intval ( $time ) );
		$im->insert ( $doc );
	}

	public function updateImNew($ownerid, $fid, $num) {
		if ($ownerid != $fid) {
			$col = $this->m->selectCollection ( 'im_new' );
			$doc = $num ? array ('$inc' => array ('num' => 1 ) ) : array ('$set' => array ('num' => 0 ) );

			$col->update ( array ('ownerid' => intval ( $ownerid ), 'fid' => intval ( $fid ) ), $doc, array ("upsert" => true ) );
		}
	}

	public function findImNew() {
		$col = $this->m->selectCollection ( 'im_new' );
		$rs = $col->find ( array ('ownerid' => intval ( $this->getUid () ), 'num' => array ('$gt' => 0 ) ) );
		$arr = iterator_to_array ( $rs );
		$str = '';
		if ($arr) {
			foreach ( $arr as $val ) {
				$str .= $val ['fid'] . ',';
			}
			$str = substr ( $str, 0, - 1 );
		}

		return $str;
	}

	public function new_feedview($val, $all = 1,$source=0) {
		$item = array ();
		$item ['id'] = $val ['_id'];
		$item ['type'] = $this->format_type ( $val ['typeid'] );
		$item ['allow_rt'] = $val ['allow_rt'];
		$item ['allow_comment'] = $val ['allow_comment'];
		$item ['allow_praise'] = $val ['allow_praise'];
		$item ['allow_del'] = $this->format_del_permission ( $val ['allow_del'], $val ['owner_uid'] );
		$item ['allow_hide'] = $this->format_hide_permission ( $val ['allow_hide'], $val ['owner_uid'] );
		$item ['allow_at_sms'] = (int) $val ['owner_uid'] == (int)$this->uid?1:0;
		$item ['rt_status_id'] = $val ['rt_status_id'] == null ? '' : $val ['rt_status_id'];
		$item ['user'] = array ('id' => ( int ) $val ['owner_uid'], 'name' => $this->_str ( $val ['owner_name'] ), 'avatar' => sns::getavatar ( $val ['owner_uid'], 'small' ) );
		$item ['text'] = $this->format_title ( $val ['text'] ,$source);
		$item ['location'] = (isset ( $val ['location'] ) && !empty($val ['location'])) ? $val ['location'] : new stdClass ();
		$item ['is_long_text'] = isset ( $val ['is_long_text'] ) ?(int) $val ['is_long_text'] : 0;
		$item ['long_text_url'] = '';
		$item ['at'] = $this->format_at ( $val ['at'] );
		$item ['created_at'] = ( int ) $val ['created_at'];
		$item ['storaged'] = (isset ( $val ['storage'] ) && in_array ( $this->getUid (), $val ['storage'] )) ? true : false;
		$item ['modified_at'] = ( float ) $val ['last_updated'];
		$item ['group'] = array ('app_id' => $val ['group_type'], 'id' => empty ( $val ['group_type'] ) ? 0 : $val ['group_id'], 'name' => $this->_str ( $val ['group_name'] ) );
		$item ['source_name'] = empty($val ['source'])?'':$this->client_from ( $val ['source'] );
		$item ['liked'] = false;
		$xiaomi_feed = ($val ['owner_uid'] == Kohana::config ( 'uap.xiaomo' ) && $this->uid != Kohana::config ( 'uap.xiaomo' )) ? true : false;
		$item ['like_list'] = array ();
		$like_count = 0;
		if (is_array ( $val ['like_list'] ) && count ( $val ['like_list'] ) > 0) {
			$like_me = array ();
			$like_new = array ();
			foreach ( $val ['like_list'] as $likekey => $likeval ) {
				$like_uid = empty ( $likeval ['uid'] ) ? $likeval ['id'] : $likeval ['uid'];
				if ($xiaomi_feed) {
					if (( int ) $like_uid == ( int ) $this->uid) {
						$like_count ++;
						if (( int ) $like_uid == ( int ) $this->getUid ()) {
							$item ['liked'] = true;
							$item ['allow_praise'] = 0;
							$likeval ['name'] = '我';
							$like_me [] = array ('uid' => isset ( $likeval ['id'] ) ? ( int ) $likeval ['id'] : ( int ) $likeval ['uid'], 'name' => $this->_str ( $likeval ['name'] ) );
						} elseif ($likekey < 3) {
							$like_new [] = array ('uid' => isset ( $likeval ['id'] ) ? ( int ) $likeval ['id'] : ( int ) $likeval ['uid'], 'name' => $this->_str ( $likeval ['name'] ) );
						}
					}
				} else {
					if (( int ) $like_uid == ( int ) $this->getUid ()) {
						$item ['liked'] = true;
						$item ['allow_praise'] = 0;
						$likeval ['name'] = '我';
						$like_me [] = array ('uid' => isset ( $likeval ['id'] ) ? ( int ) $likeval ['id'] : ( int ) $likeval ['uid'], 'name' => $this->_str ( $likeval ['name'] ) );
					} elseif ($likekey < 3) {
						$like_new [] = array ('uid' => isset ( $likeval ['id'] ) ? ( int ) $likeval ['id'] : ( int ) $likeval ['uid'], 'name' => $this->_str ( $likeval ['name'] ) );
					}
				}
			}
			$item ['like_list'] = array_merge ( $like_me, $like_new );
		}
		$item ['like_count'] = $xiaomi_feed ? $like_count : count ( $val ['like_list'] );
		$comment_count = 0;
		$comment_list = array ();
		if ($xiaomi_feed) {
			$j = 0;
			$col = $this->comment_new->find ( array ('feedid' => $item ['id'] ) );
			foreach ( $col as $v ) {
				$xiaomi_comment = false;
				if (( int ) $v ['uid'] == Kohana::config ( 'uap.xiaomo' )) {
					if (! empty ( $v ['at'] )) {
						foreach ( $v ['at'] as $r ) {
							if (( int ) $r ['id'] == ( int ) $this->uid) {
								$xiaomi_comment = true;
							}
						}
					}
				}
				if (( int ) $v ['uid'] == ( int ) $this->uid || $xiaomi_comment) {
					$comment_list [$i] ['id'] = $v ['id'];
					$comment_list [$i] ['uid'] = $v ['uid'];
					$comment_list [$i] ['name'] = $v ['realname'];
					$comment_list [$i] ['created_at'] = $v ['addtime'];
					$comment_list [$i] ['text'] = $this->format_title($v ['content'],$source);
					$comment_list [$i] ['at'] = $v ['at'];
					$comment_list [$i] ['source'] = $v ['client_id'];
					$comment_list [$i] ['im'] = 1;
					$comment_count ++;
					$j ++;
				}
			}
		}
		$item ['comment_count'] = $xiaomi_feed ? $comment_count : $val ['comment_count'];
		$item ['comment_list'] = $this->format_comment_list ( $xiaomi_feed ? $comment_list : $val ['comment_list'],$source );
		if(empty($source))
			$item ['origin_url'] = isset($val['origin_url'])?$val['origin_url']:array();
		$item ['sync'] = $this->format_sync ( $val ['sync'] );
		//$item['application'] = $this->format_application($val['application']);
		if ($item ['typeid'] == 9 && $item ['application'] ['id']) { //文件
			$item ['application'] ['url'] = File_Controller::geturl ( $item ['application'] ['id'] );
		}
		$item ['accessory'] = $this->format_accessory ( $val ['accessory'] );
		return $item;
	}

	public function format_type($typeid) {
		if (is_numeric ( $typeid )) {
			switch ($typeid) {
				default :
				case 1 :
				case 3 :
					return 'pic';
					break;
				case 4 :
					return 'text';
					break;
				case 2 :
				case 9 :
					return 'file';
					break;
			}
		}
		return $typeid;
	}

	public function format_at($at) {
		$return = array ();
		if (! empty ( $at ) && count ( $at ) > 0) {
			foreach ( $at as $k => $v ) {
				$return [$k] = array ('id' => ( int ) $v ['id'], 'name' => $v ['name'] );
			}
		}
		return $return;
	}

	public function _str($str) {
		return $str == null ? '' : trim ( strip_tags ( $str ) );
	}

	public function format_del_permission($allow_del, $owner_uid) {
		if ($allow_del && $owner_uid != $this->uid) {
			return 0;
		}
		return $allow_del;
	}

	public function format_hide_permission($allow_hide, $owner_uid) {
		if ($allow_hide && $owner_uid == $this->uid) {
			return 0;
		}
		return $allow_hide;
	}

	public function format_sync($sync) {
		$data = array ();
		if(count($sync) > 0 ) {
			foreach ( $sync as $k => $v ) {
				if ($v == 0 || empty ( $v ))
					continue;
				$v = $v == 2 ? 0 : $v;
				$data [] = array ('name' => $k, 'is_sync' => $v );
			}
		}
		return $data;
	}

	public function format_accessory($accessory) {
		$data = array ();
		if (is_array ( $accessory ) && count ( $accessory ) > 0) {
			foreach ( $accessory as $k => $v ) {
				$data [$k] ['title'] = '';
				$data [$k] ['meta'] = new stdClass ();
				if ($v ['typeid'] == 2 || $v ['type'] == 'file') {
					$data [$k] ['title'] = $v ['title'];
					$data [$k] ['meta'] = array ('size' => $v ['meta'] ['size'], 'mime' => $v ['meta'] ['ext'] );
					$res [0] = File_Controller::geturl ( $v ['id'] );
				} else {
					if($v ['id']) {
						$res = Photo_Controller::geturl ( $v ['id'],130);
						$size = Photo_Controller::getinfo ( $v ['id']);
						if($size) {
							$data [$k] ['meta'] = array ('width' => $size[0]['width'], 'height' => $size[0]['height']);
						}

						$data [$k] ['status_id'] = $v ['status_id'];
					} elseif(!empty($v['url'])) {
						list($width, $height, $type, $attr) = @getimagesize($v['url']);
						$data [$k] ['meta'] = array ('width' => $width, 'height' => $height);
						$data [$k] ['status_id'] = $v ['status_id'];
					}
				}
				if (! isset ( $res [0] ) && empty ( $res [0] )) {
					$url = '';
				} else {
					$url = $res [0];
				}
				if ($v ['type']) {
					$data [$k] ['type'] = $v ['type'];
				} else {
					$data [$k] ['type'] = $this->format_type ( intval ( $v ['typeid'] ) );
				}
				$data [$k] ['id'] = $v ['id'] ? $v ['id'] : 0;
				$data [$k] ['url'] = $v ['id'] ? $url : $v ['url'];
			}
		}

		return $data;
	}

	public function format_application($application = array()) {
		if ($application ['title']) {
			$application ['title'] = strip_tags ( $application ['title'] );
		}
		return $application;
	}

	public function format_title($text,$source=0) {
		if ($text == null)
			return '';
		$text = $this->clear_link($text);
		if(empty($source)) {
			$text = htmlspecialchars ( $text);
		} else {
			$text = str::unhtmlspecialchars ( $text);
		}
		return trim ( $text );
	}

	private function format_link($text,$source=0) {
		if(empty($source)) {
			$text = preg_replace_callback('#\b(https?)://[-A-Z0-9+&@\#/%?=~_|!:,.;]*[-A-Z0-9+&@\#/%=~_|]#i', array($this,'bulid_hyperlinks'), $text);
		}
		return $text;
	}

	private function clear_link($text) {
		return preg_replace("/<a [^>]*>|<\/a>/is","",$text);
	}

	private function bulid_hyperlinks(&$matches) {

        $matchUrl = str_replace('&amp;', '&', $matches[0]);
        $tmp      = preg_split ("/(&#039|&quot)/", $matchUrl);
        $debris   = isset($tmp[1]) ? substr($matchUrl, strlen($tmp[0])) : "";
        $matchUrl = $tmp[0];
        if($matchUrl) {
        	return '<a href="'.$matchUrl.'" target="_blank">'.$matchUrl.'</a>';
        }
    }

	public function format_comment_list($comment_lists,$source) {
		$data = array ();
		if (count ( $comment_lists ) > 0 && is_array ( $comment_lists )) {
			$comment_lists = array_reverse ( $comment_lists );
			foreach ( $comment_lists as $k => $v ) {
				if(empty($source))  {
					if($k > 3) break;
					$data [$k]['id'] = $v ['id'];
					$data [$k]['user'] ['id'] = intval ( $v ['uid'] );
					$data [$k]['user'] ['name'] = $this->_str ( $v ['name'] );
					$data [$k]['user'] ['avatar'] = sns::getavatar ( $v ['uid'], 'small' );
					$data [$k]['text'] = $this->format_title( $v ['text'],$source);
					$data [$k]['at'] = $v ['at'] ? $v ['at'] : array ();
					$data [$k]['created_at'] = intval ( $v ['created_at'] );
					$data [$k]['source_name'] = $this->client_from ( $v ['source'] );
					$k ++;
				} else {
					if($k > 0) break;
					$data ['id'] = $v ['id'];
					$data ['user'] ['id'] = intval ( $v ['uid'] );
					$data ['user'] ['name'] = $this->_str ( $v ['name'] );
					$data ['user'] ['avatar'] = sns::getavatar ( $v ['uid'], 'small' );
					$data ['text'] = $this->format_title ( $v ['text'], $source );
					$data ['at'] = $v ['at'] ? $v ['at'] : array ();
					$data ['created_at'] = intval ( $v ['created_at'] );
					$data ['source_name'] = $this->client_from ( $v ['source'] );
					$k ++;
				}
			}
		}
		return empty ( $data ) ? new stdClass () : $data;
	}

	public function get_id($typeid, $objid) {
		return $typeid . '_' . $objid;
	}

	public function set_id($id) {
		$pos = strpos ( $id, '_' );
		$arr ['typeid'] = substr ( $id, 0, $pos );
		$arr ['objid'] = substr ( $id, $pos + 1 );

		return $arr;
	}

	function getFeedById($feed_id) {
		$col = $this->feed->find ( array ('_id' => $feed_id ) );
		$arr = iterator_to_array ( $col );
		return $arr;
	}

	public function addTab($id, $name, $typeid, $objid) {
		if ($typeid != 7 && $typeid != 15) {
			return;
		}
		$id = $typeid . "_" . $id;
		$midName = html::specialchars ( api::cutFixLen ( $name, 20, true ) );
		$subName = html::specialchars ( api::cutFixLen ( $name, 10, true ) );
		$name = html::specialchars ( $name );
		$msg = json_encode ( array ("kind" => "group_add", "data" => $id, 'name' => $name, 'midName' => $midName, 'subName' => $subName ) );
		$this->mq_send ( $msg, $objid );
	}

	/**
	 *
	 * 发送mo短信分享
	 * @param string $type
	 * @param string $objid
	 * @param array $at
	 */
	public function mo_sms($type, $feedid,$commentid, $at2mo,$moid='',$mouid=0,$auto=false) {
		$at_count = count($at2mo);
		if(count($at2mo) > 0) {
			$sms_count = sns::getsmscount($this->uid);
			if($at_count > $sms_count) {
				return false;
			}
			$sent_mo = array();
			foreach($at2mo as $k => $v) {
				if($mouid) {
					if($mouid!=$v['id']) continue;
				}
				if ($v['id'] == Kohana::config ( 'uap.xiaomo' )) {
					continue;
				}
				if(!in_array($v['id'],$sent_mo)) {
					$sent_mo[] = $v['id'];
					//动态mo短信
					if ($type == 'feed') {
						$this->mo_sms_feed ( $feedid, $v['id'],$v['name'],$moid,$auto);
					}
					//评论mo短信
					if ($type == 'comment') {
						$this->mo_sms_comment ($commentid,$feedid,$v['id'],$v['name'],$moid,$auto);
					}
				}
			}
			return true;
		}
	}

	/**
	 *
	 * 动态mo短信
	 * @param string $feedid
	 * @param array $at
	 */
	private function mo_sms_feed($feedid,$receiver_uid,$receiver_name,$moid='',$auto=false) {
		$type = $this->format_type($typeid);
		$sender_uid = $this->uid;

		$sender_name = sns::getrealname($this->uid);
		//检查接收者是否在自己联系人中，并且接收者级别<3
		if(Friend_Model::instance()->check_iscontact($sender_uid,$receiver_uid)) {
			$receiver_status = sns::getstatus($receiver_uid);
			if($receiver_status>=3 && $auto==true) {
				return false;
			}
		} else {
            return false;
		}
		//同一条动态下未发过mo短信
		if(!$this->is_mo_sms_sent('feed',$feedid,$sender_uid,$receiver_uid,$moid)) {
			$receiver_info = sns::getuser($receiver_uid);
			$feed_row = $this->feed->findOne ( array ('id' => $feedid ) );
			$type = $this->format_type($feed_row['typeid']);
			$url_code = Url_Model::instance()->create('status',$sender_uid,$sender_name,$receiver_uid,$receiver_name,$receiver_info['mobile'],$receiver_info['zone_code'],$feedid);
			$short_url = MO_SMS_JUMP.$url_code;
			if($feed_row) {
				$content = str::cnSubstr($this->feed_at_format($feed_row['text'], $feed_row['at']),0,15).'..';
				$data = array();
				$data['sender']['id'] = $sender_uid;
				$data['sender']['name'] = $sender_name;
				$data['receiver'][] = $receiver_uid;
				$data['timestamp'] = time();
				if($type=='text') {
					$sender_status = sns::getstatus($sender_uid);
					if($sender_status > 0) {
						$data['content']['text'] = $sender_name.'分享了：'.$content.',点开互动: '.$short_url;
					} else {
						$data['content']['text'] = $sender_name.'分享了动态给您,点开互动: '.$short_url;
					}

				}elseif($type=='pic') {
					$img_num = count($feed_row['accessory']);
					$img_num = $img_num?$img_num:1;
					$data['content']['text'] = $sender_name.'分享了'.$img_num.'张照片给您,点开互动: '.$short_url;
				}elseif($type=='file') {
					$data['content']['text'] = $sender_name.'分享了文件给您,点开互动: '.$short_url;
				}

		       	$mq_msg = array("kind" => "mobile_sms", "data" => $data);
		        $this->mq_send(json_encode($mq_msg), $this->uid, 'momo_im');
				$this->mo_sms_log('feed', '', $feedid, $this->uid, $receiver_uid,$moid);
				if($moid) {
					$this->update_my_mo($moid);
				}
				return true;
			}
		}
	}


	/**
	 *
	 * 评论mo短信
	 * @param string $feedid
	 * @param array $at
	 */
	private function mo_sms_comment($commentid,$feedid,$receiver_uid,$receiver_name,$moid='',$auto) {
		$type = $this->format_type($typeid);
		$sender_uid = $this->uid;
		$sender_name = sns::getrealname($this->uid);
		//检查接收者是否在自己联系人中，并且接收者级别<3
		if(Friend_Model::instance()->check_iscontact($sender_uid,$receiver_uid)) {
			$receiver_status = sns::getstatus($receiver_uid);
			if($receiver_status>=3 && $auto==true) {
				return false;
			}
		} else {
            return false;
		}
		//接收者的用户基本要<2x
		if(!$this->is_mo_sms_sent('comment',$feedid,$sender_uid,$receiver_uid,$moid)) {
			$receiver_info = sns::getuser($receiver_uid);
			$url_code = Url_Model::instance()->create('status',$sender_uid,$sender_name,$receiver_uid,$receiver_name,$receiver_info['mobile'],$receiver_info['zone_code'],$feedid);
			$short_url = MO_SMS_JUMP.$url_code;
			$comment_row = $this->comment_new->findOne(array('id'=>$commentid));
			if($comment_row) {
				$content = str::cnSubstr($this->feed_at_format($comment_row['content'], $comment_row['at']),0,15).'..';
				$data = array();
				$data['sender']['id'] = $sender_uid;
				$data['sender']['name'] = $sender_name;
				$data['receiver'][] = $receiver_uid;
				$data['timestamp'] = time();
				$data['content']['text'] = $sender_name.'分享了:'.$content.',点开互动: '.$short_url;

		       	$mq_msg = array("kind" => "mobile_sms", "data" => $data);
		        $this->mq_send(json_encode($mq_msg), $this->uid, 'momo_im');
				$this->mo_sms_log('comment', $commentid, $feedid, $this->uid, $receiver_uid,$moid);
				if($moid) {
					$this->update_my_mo($moid);
				}
				return true;
			}
		}
	}

	/**
	 *
	 * 更新我mo的短信发送状态
	 * @param unknown_type $moid
	 */
	private function update_my_mo($moid) {
		$newdata = array ('$set' => array ("sms" => 1,"new"=>0 ) );
		$this->aboutme->update ( array ("_id" => $moid ), $newdata);
	}

	/**
	 *
	 * 检查短信发送记录
	 * @param unknown_type $type
	 * @param unknown_type $feed_id
	 * @param unknown_type $sender_uid
	 * @param unknown_type $receiver_uid
	 */
	private function  is_mo_sms_sent($type,$feed_id,$sender_uid,$receiver_uid,$moid='') {
		if($type=='comment') {
			$sql = "select count(*) as count from mo_sms_log where type=1 and feed_id='{$feed_id}' and sender_uid='{$sender_uid}' and receiver_uid='{$receiver_uid}'  and mo_id='{$moid}'";
		} else {
			$sql = "select count(*) as count from mo_sms_log where type=2 and feed_id='{$feed_id}' and sender_uid='{$sender_uid}' and receiver_uid='{$receiver_uid}'  and mo_id='{$moid}'";
		}
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
	    return $result[0]['count'];
	}

	/**
	 *
	 * 记录短信发送日志
	 * @param string $type
	 * @param string $comment_id
	 * @param string $feed_id
	 * @param int $sender_uid
	 * @param int $receiver_uid
	 */
	private function mo_sms_log($type,$comment_id,$feed_id,$sender_uid,$receiver_uid,$moid='') {
		$type = $type=='comment'?1:2;
		$this->db->insert('mo_sms_log', array("type"=>$type ,"sender_uid" => $sender_uid, "receiver_uid"=>$receiver_uid, "send_time"=>time(), "type"=>$type, "comment_id"=>$comment_id,"feed_id"=>$feed_id,"mo_id"=>$moid));
	}


	/**
	 *
	 * 格式化@
	 * @param string $content
	 * @param array $at
	 */
	private function feed_at_format($content,$at) {
		if(count($at) > 0) {
			foreach($at as $k => $v) {
				$content = str_replace('[@'.$k.']',$v['name'].' ',$content);
			}
		}
		return $content;
	}


	/**
	 * 对单张照片进行评论
	 * @param md5 $org_objid 多图动态id
	 * @param int $pid 照片id
	 */
	public function getFeedObjIDOfPID($org_objid,$pid){
    	$feed=$this->feed->findOne(array('_id'=>$org_objid));
        if($feed){//是否存在此动态
        	$accessory=$feed['accessory'];
        	//多图附件
            if($accessory && count($accessory)>1){
            	$accessory_pic_idx=NULL;
	            foreach($accessory as $k=>$ass){
	            	if($ass['id']==$pid){
	            		//pid的单贴动态id
	            		$status_id=$ass['status_id'];
	            		//pid在原帖的索引
	            		$accessory_pic_idx=$k;
	            		break;
	            	}
	        	}
	        	//pid不存在在此动态中
	        	if(is_null($accessory_pic_idx)) return NULL;

	        	$feed_isnew = FALSE;
				if($status_id){
					//如果pid的单贴动态存在
					$status_feed=$this->feed->findOne(array('_id'=>$status_id), array('_id'));
					if($status_feed){
						$objid=$status_id;
					}
				}
				//如果pid的单贴动态不存在
				if(! $status_feed){
					$feed_isnew = TRUE;

					$images[]=array('id'=>$pid,'status_id'=>$org_objid);

					$objid=$this->addFeed( $feed['owner_uid'], 3, '照片收到评论', 0,
									array(), array(), $images, array(), $feed['group_type'], $feed['group_id']);

					//更新旧动态
					$accessory[$accessory_pic_idx]['status_id']=$objid;
					$newdata = array('$set' => array('accessory' => $accessory));
					$this->feed->update(array('_id' => $org_objid), $newdata);
				}

				return array('objid'=>$objid, 'isnew'=>$feed_isnew);
			}
		}

		return NULL;
	}

	/**
	 *
	 * 增加错误日志
	 * @param string $content
	 */
	public function add_log($content) {
		$uid = $this->getUid ();
		try {
			$cur = $this->m->selectCollection ( 'log' );
			$doc = array ('uid' => $uid, 'content' => $content, 'add_time' => time());
			$cur->insert ( $doc );
		} catch (Exception $e) {

		}

	}


	/**
	 *
	 * 写mongo日志
	 * @param unknown_type $collection
	 * @param unknown_type $doc
	 */
	public function mongo_log($collection,$doc) {
		try {
			$cur = $this->m->selectCollection ($collection);
			$cur->insert ( $doc );
		} catch (Exception $e) {

		}
	}

	public function text_search($str) {
		$query=array("text"=>new MongoRegex("/.*".$str.".*/i"));
		$cur = $this->feed->find($query);
		return $cur;
	}
}
