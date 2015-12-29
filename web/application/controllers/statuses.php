<?php
/*
* [UAP Server] (C)1999-2009 ND Inc.
* UAP MOBILE 手机注册器
* @TODO：短消息列表,获取指定短消息,删除短消息,发送新短消息,发送公共短消息
*/
defined ( 'SYSPATH' ) or die ( 'No direct access allowed.' );

class Statuses_Controller extends Controller {

    private $feedType = 'all'; //自己事件
    private $type_id = 0;
    private $new_format = 0;
    /**
     * @var Feed_Model
     */
    private $feedModel = NULL;

    public function __construct() {
        parent::__construct ();
        $this->uid = $this->getUid ();
        $this->feedModel = Feed_Model::instance ();
        $this->new_format = $this->input->get ( 'new_format', 0 );
        $this->friendModel = Friend_Model::instance ();
        $this->groupModel = Group_Model::instance ();
        $this->activityModel = Activity_Model::instance ();
    }

    /**
     * 获取单条动态
     * @param <string> $id
     */
    public function im($id = NULL) {
        if (request::method () != 'get') {
            $this->send_response ( 400, NULL, '400:无法接受GET以外请求方式' );
        }

        if (! $id) {
            $this->send_response ( 400, NULL, '400:动态ID为空' );
        }
        $urlcode = $this->input->get ( 'urlcode', '' );

        $feedModel = new Feed_Model ();

        $val = $feedModel->findFeed ( $id );
        if (empty ( $val )) {
            $this->send_response ( 404, NULL, '动态不存在' );
        }
        $post_uid = $val ['owner_uid'];
        $have_permision = false;
        if($urlcode) {
        	$url_info = Url_Model::instance()->get(trim($urlcode));
        	if($url_info) {
        		if($url_info['status_id'] == $id && $url_info['receiver_uid'] == $this->uid) {
        			$session = Session::instance();
	        		$session->set('url_login',true);
        			$have_permision = true;
        		}
        	}
        }

        $val = $feedModel->new_feedview ( $val );
        if (empty ( $val )) {
            $this->send_response ( 404, NULL, '动态不存在' );
        }
        unset ( $val ['comment_list'] );
        $this->send_response ( 200, $val );
    }

    /**
     * 获取长文本广播
     */
    public function long_text() {
        if (request::method () != 'get') {
            $this->send_response ( 400, NULL, '400:无法接受GET以外请求方式' );
        }

        $statuses_id = $this->input->get ( 'statuses_id', '' );
        $pagesize = $this->input->get ( 'pagesize', 0 );
        $page = $this->input->get ( 'page', 1 );
        $pagepad = $page>1?($page-1)*$pagesize:0;
        if($statuses_id) {
            $feed = $this->feedModel->findFeed ( $statuses_id );
            $str_count = str::strLen($feed['long_text']);
            if($pagesize && $str_count > $pagesize) {
                $tmp_text = str::cnSubstr($feed['long_text'], $pagepad,$pagesize);
            } else {
                $tmp_text = $feed['long_text'];
            }
        }
        $data = array ('id' => $statuses_id, 'pagesize' => $pagesize,'page'=>$page,'total'=>$str_count,'text'=>$tmp_text,'at'=>$feed['at']);
        $this->send_response ( 200, $data );
    }

    /**
     *
     * 长文本字符解析
     * @param string $long_text
     * @param array $at
     */
    private function format_long_text($long_text,$at) {
    	$long_text = $this->atLink($long_text,$at);
    	return nl2br($long_text);
    }

    /**
     *
     * 解析长文本中的@
     * @param unknown_type $content
     * @param unknown_type $at
     */
    public function atLink($content, $at){
		if($at && $content){
			foreach($at as $k => $v){
				$link = '<a target="_blank" data-uid="'.$v['id'].'" href="momo://user='.$v['id'].'">@'.$v['name'].'</a>';
				$str = '[@'.$k.']';
				$content = str_replace($str, $link, $content);
			}
		}
		return $content;
	}

    /**
     * 展示长文本广播
     * @param <string> $id
     */
    public function show_text($id = NULL) {
        if(!empty($id)) {
            $doc = $this->feedModel->findFeed ( $id );
            if (! $doc) {
                die('该动态不存在');
            }
            echo $doc['long_text']?$this->format_long_text($doc['long_text'],$doc['at']):$this->format_long_text($doc['text'],$doc['at']);
            exit;
        }
        die('参数不正确');
    }


    /**
     * 获取动态
     * @return <type>
     */
    public function index($uid = 0) {
        if (! $uid) {
            $uid = $this->uid;
        }
        $uptime = $this->input->get ( 'uptime', 0 );
        $pretime = $this->input->get ( 'pretime', 0 );
        $downtime = $this->input->get ( 'downtime', 0 );
        $pos = $this->input->get ( 'pagesize', 20 );

        $html = '';
        $site = url::base ();

        switch ($this->feedType) {
            case 'all' :
                $data = $this->feedModel->getFriendFeedNew ( $uid, $uptime, $pretime, $downtime, $pos, $this->type_id);
                break;
            case 'storage' :
                $data = $this->feedModel->findStorageNew ( $uptime, $pretime, $downtime, $pos );
                break;
            case 'hidden' :
                $data = $this->feedModel->findHiddenNew ( $uptime, $pretime, $downtime, $pos );
                break;
            case 'single' :
                $data = $this->feedModel->getUserFeedNew ( $uid, $uptime, $pretime, $downtime, $pos ,$this->type_id);
        }

        //出错处理
        $html = array ();
        if ($data ['code'] == 200) {
            if ($data ['result'] ['count']) {
               // $tpldata = $this->feedModel->getFeedTpl ();
                foreach ( $data ['result'] ['data'] as $key => $val ) {
                    if ($val ['last_updated'] == $uptime || $val ['last_updated'] == $downtime) {
                        continue;
                    }
                    if (($val ['uid'] != $this->uid) && $val ['call'] && ! in_array ( $this->uid, $val ['call'] )) {
                        continue;
                    }
                    $html [] = $this->feedModel->new_feedview ( $val ,1,$this->source);
                }
            }
            unset ( $val );
        }

        $data = ($this->feedType == 'all' || $this->feedType == 'single') ? array ('data' => $html, 'delete' => $data ['result'] ['delete'] ) : array ('data' => $html );
        $this->send_response ( 200, $data );
    }

    /**
     * 格式化类型
     * @return <type>
     */
    public function type() {
        $this->type_id = $this->input->get ( 'type_id', 0 );
        $this->new_format = $this->input->get ( 'new_format', 0 );
        $this->feedType = 'all';
        return self::index ();
    }

    /**
     * 获取用户动态
     * @return <type>
     */
    public function user() {
        $uid = $this->input->get ( 'user_id', 0 );
        $this->new_format = $this->input->get ( 'new_format', 0 );
        $this->feedType = 'single';
        return self::index ( $uid );
    }

    /**
     * 获取群组动态
     * @return <type>
     */
    public function group() {
        $group_id = $this->input->get ( 'group_id', 0 );
        $type = $this->input->get ( 'type', '' );
        if($type)
        	$this->type_id = $this->format_type( $type);
        if ($this->groupModel->getMembergrade ( $group_id, $this->uid ) < 1) {
            $this->send_response ( 403, NULL, '无权限查看' );
        }
        if($group_id)
        	Tab_Model::instance()->lastModify($this->uid,1,$group_id);
        $this->feedType = 'single';
        return self::index ( '1_' . $group_id );
    }

    /**
     * 获取活动动态
     * @return <type>
     */
    public function action() {
        $action_id = $this->input->get ( 'action_id', 0 );
        if (! $this->activityModel->getMemberGrade ( $action_id, $this->uid )) {
            $this->send_response ( 403, NULL, '无权限查看' );
        }
        $this->feedType = 'single';
        return self::index ( '2_' . $action_id );
    }

    /**
     * 获取收藏动态
     * @return <type>
     */
    public function storage() {
        $this->feedType = 'storage';
        $this->new_format = $this->input->get ( 'new_format', 0 );
        return self::index ();
    }

    /**
     * 获取隐藏动态
     * @return <type>
     */
    public function hidden() {
        $this->feedType = 'hidden';
        return self::index ();
    }

    /**
     * 收藏动态
     * @return <type>
     */
    public function store() {
        $post = $this->get_data ();

        $statuses_id = $post ['id'];
        if (empty ( $statuses_id )) {
            $this->send_response ( 400, NULL, '400:对象id为空' );
        }

        $doc = $this->feedModel->findFeed ( $statuses_id );
        if (! $doc) {
            $this->send_response ( 404, NULL, '该动态不存在' );
        }

        $act = $post ['act'];

        if ($act != 'add' && $act != 'del') {
            $this->send_response ( 400, NULL, '400:参数不正确' );
        }

        if ($act == 'add') {
            if ($this->feedModel->addStorage ( $statuses_id )) {
                $this->send_response ( 200 );
            }
            $this->send_response ( 400, NULL, '收藏失败' );
        } else {
            if ($this->feedModel->delStorage ( $statuses_id )) {
                $this->send_response ( 200 );
            }
            $this->send_response ( 400, NULL, '取消收藏失败' );
        }
    }

    /**
     * 隐藏动态
     * @return <type>
     */
    public function hide() {
        $post = $this->get_data ();

        $statuses_id = $post ['id'];
        if (empty ( $statuses_id )) {
            $this->send_response ( 400, NULL, '400:对象id为空' );
        }

        $doc = $this->feedModel->findFeed ( $statuses_id );
        if (! $doc) {
            $this->send_response ( 404, NULL, '该动态不存在' );
        }

        $act = $post ['act'];

        if ($act != 'add' && $act != 'del') {
            $this->send_response ( 400, NULL, '400:参数不正确' );
        }

        if ($act == 'add') {
            if ($this->feedModel->addHidden ( $statuses_id )) {
                $this->send_response ( 200 );
            }
            $this->send_response ( 500, NULL, '隐藏失败' );
        } else {
            if ($this->feedModel->delHidden ( $statuses_id )) {
                $this->send_response ( 200 );
            }
            $this->send_response ( 500, NULL, '取消隐藏失败' );
        }
    }

    /**
     * 删除动态
     * @return <type>
     */
    public function destroy($id = NULL) {
        if (! $id) {
            $this->send_response ( 400, NULL, '400:动态ID为空' );
        }

        $doc = $this->feedModel->findFeed ( $id );
        if (empty ( $doc )) {
            $this->send_response ( 404, NULL, '动态不存在' );
        }
        if ($doc ['owner_uid'] == $this->uid) {
            $content = $this->feedModel->delFeed($id);
		$content_md5 = md5($this->uid.'_'.$content);
		$count = Cache::instance()->delete($content_md5);
            $this->send_response ( 200 );
        }
        $this->send_response ( 400, NULL, '无权限删除' );
    }

    /**
     * 获取关于我的动态，提供j2me,s60使用
     */
    public function aboutme() {
        $pos = $this->input->get ( 'pagesize', 20 );
        $start = $this->input->get ( 'page', 1 );
        $new = $this->input->get ( 'new', 0 );
        $read = $this->input->get ( 'read', 1 );
        $pos = $pos > 100 ? 100 : $pos;
        $start = abs ( ($start - 1) * $pos );
        $arr = $this->feedModel->findAboutme ( $new, $pos );
        $res = array ();

        if ($arr) {
            $i = 0;
            foreach ( $arr as $row ) {
                $res [$i] ['id'] = $row ['_id'];
                if ($row ['reply_id']) {
                    $res [$i] ['text_reply'] = array ('text' => $this->_st ( $this->feedModel->format_title($row ['reply_content'] ,$this->source), '' ), 'at' => $this->_st ( $row ['reply_at'], array () ), 'user' => array ('id' => $this->_st ( $row ['reply_uid'], 0 ), 'name' => $this->_st ( $row ['reply_name'], 0 ) ) );
                } else {
                    $res [$i] ['text_reply'] = array ('text' => $this->_st ( $this->feedModel->format_title($row ['feed_content'],$this->source ), '' ), 'at' => $this->_st ( $row ['feed_at'], array () ), 'user' => array ('id' => $this->_st ( $row ['feed_uid'], 0 ), 'name' => $this->_st ( $row ['feed_name'], 0 ) ) );
                }
                $res [$i] ['text'] = $this->_st ( $this->feedModel->format_title( $row ['comment_content'],$this->source), '' );
                $res [$i] ['at'] = $this->_st ( $row ['comment_at'], array () );
                $res [$i] ['user'] = array ('id' => $this->_st ( $row ['comment_uid'], 0 ), 'name' => $this->_st ( sns::getrealname ( $row ['comment_uid'] ) ), 'avatar' => sns::getavatar ( $row ['comment_uid'], 'small' ) );
                $res [$i] ['statuses'] = array ('id' => $this->_st ( $row ['feed_id'], '' ), 'user_id' => $this->_st ( $row ['feed_uid'], 0 ), 'group' => array ('id' => $this->_st ( $row ['group_id'], 0 ), 'name' => $this->_st ( $row ['group_name'], 0 ) ) );
                $res [$i] ['reply'] = $row ['kind'] == 4 ? 0 : 1;
                $res [$i] ['comment_ids'] [] = $row ['comment_id'];
                $res [$i] ['typeid'] = $row ['typeid'];
                $res [$i] ['created_at'] = ceil ( $row ['addtime'] / 10000 );
                $res [$i] ['new'] = $row ['new'];
                $i ++;
            }
            if($read) {
            	$this->feedModel->updateAboutmeRead ();
            }
        }
        $this->send_response ( 200, $res );
    }

    /**
     * 获取我mo的
     */
    public function my_mo() {
    	$pos = $this->input->get ( 'pagesize', 20 );
        $start = $this->input->get ( 'page', 1 );
        $sms = $this->input->get ( 'sms', 0 );
        $pos = $pos > 100 ? 100 : $pos;
        $start = abs ( ($start - 1) * $pos );
        $arr = $this->feedModel->findMyMo( $pos ,$start,$sms);
        $res = array ();
        if ($arr) {
            $i = 0;
            foreach ( $arr as $row ) {
            	if($row ['kind'] == 4 || $row ['uid']==Kohana::config('uap.xiaomo')) continue;
                $res [$i] ['id'] = $row ['_id'];
                $res [$i] ['statuses_id'] = $row ['feed_id'];
                $res [$i] ['kind'] = $row ['kind'];
                $res [$i] ['sms'] = $this->_st($row ['sms'],0);
                $res [$i] ['text'] = $this->_st ( strip_tags ( str_replace ( '提到我', '提到TA',$row ['comment_content'] )));
                $res [$i] ['source'] = $row ['source'] == 0 ? '' : $this->get_source ( $row ['source'] );
                $res [$i] ['user'] = array ('id' => $this->_st ( $row ['uid'], 0 ), 'name' => $this->_st ( sns::getrealname ( $row ['uid'] ) ), 'avatar' => sns::getavatar ( $row ['uid'], 'small' ) );
                $res [$i] ['created_at'] = ceil ( $row ['addtime'] / 10000 );
                $aboutme_opt = array ();
                if ($row ['kind'] == 6) {
                    $content = $this->_st ( strip_tags ( $row ['reply_content'] ), '' );
                    $aboutme_opt ['reply_source'] = array ('id' => $row ['reply_id'], 'text' => $content, 'at' => $row ['reply_at'], 'user' => array ('id' => $row ['reply_uid'], 'name' => $row ['reply_name'], 'avatar' => sns::getavatar ( $row ['reply_uid'], 'small' ) ) );
                } elseif ($row ['kind'] == 2) {
                    $aboutme_opt ['message'] = array ('text' => $this->_st ( strip_tags ( $row ['feed_content'] ), '' ), 'at' => $row ['feed_at'] );
                } else {
                    $content = $this->_st ( strip_tags ( $row ['feed_content'] ), '' );
                    $aboutme_opt ['statuses'] = array ('text' => $content, 'at' => $row ['feed_at'] ,'uid'=>$row['feed_uid'],'name'=>$row['feed_name']);
                }
                if (in_array ( $row ['kind'], array (1, 3, 6 ) )) {
                    $aboutme_opt ['comment'] = array ('id' => $row ['comment_id'], 'text' => $this->_st ( strip_tags ( str_replace ( array ('在评论中提到我：','回复：'), array('',''), $row ['comment_content'] ) ), '' ), 'at' => $row ['comment_at'] );
                }
                $res [$i] ['opt'] = $aboutme_opt;
                $i ++;
            }
        }
        $this->send_response ( 200, $res );
    }

    /**
     * 检查用户查看权限
     * @return <type>
     */
    public function checkPermission($fid, $content) {
        if ($this->uid != $fid) {
            $friendModel = Friend_Model::instance ();
            if (! $friendModel->check_isfriend ( $this->uid, $fid )) {
                return '对方不是你的好友，无权限查看源内容';
            }
        }
        return $content;
    }

    /**
     *
     * 给动态的at列表发送短信推送
     */
    public function at_sms() {
    	$post = $this->get_data ();
        $statuses_id = $post ['statuses_id'];
        $at2mo = array();
        if (empty ( $statuses_id )) {
            $this->send_response ( 400, NULL, '402001:动态不存在' );
        }
    	$feed = $this->feedModel->findMoFeed ( $statuses_id );
        if (empty ( $feed )) {
            $this->send_response ( 400, NULL, '402002:动态不存在' );
        }
        //if ((int)$feed['owner_uid'] != (int)$this->uid) {
        //    $this->send_response ( 400, NULL, '402004:不能给非自己的动态推MO短信' );
        //}
        if($feed['kind']==1 ){
        	$at2mo = array(0=>array('id'=>$feed['feed_uid'],'name'=>$feed['feed_name']));
        } else {
        	if(empty($feed['comment_at'])) {
        		$this->send_response ( 400, NULL, '402005:动态无可推送的好友' );
        	}
        	$at2mo = $feed['comment_at'];
        }
        if($feed['kind'] ==3 || $feed['kind'] ==6) {
        	$type= 'comment';
        } else {
        	$type='feed';
        }
        if($this->feedModel->mo_sms($type,$feed['feed_id'],$feed['comment_id'],$at2mo,$feed['_id'],$feed['uid'])) {
        	$this->send_response ( 200);
        }
        $this->send_response ( 400, NULL, '402006:短信发送失败' );
    }

    /**
     * 格式化内容输出
     * @return <type>
     */
    public function _st($content, $return = '') {
        return empty ( $content ) ? $return : $content;
    }

    /**
     * 获取关于我的动态，提供iphone,andriod使用
     * @return <type>
     */
    public function aboutme_alone() {
        $pos = $this->input->get ( 'pagesize', 20 );
        $start = $this->input->get ( 'page', 1 );
        $new = $this->input->get ( 'new', 0 );
        $pos = $pos > 100 ? 100 : $pos;
        $start = abs ( ($start - 1) * $pos );
        $arr = $this->feedModel->findAboutme ( $new, $pos ,$start);
        $res = array ();
        if ($arr) {
            $i = 0;
            foreach ( $arr as $row ) {
                $res [$i] ['id'] = $row ['_id'];
                $res [$i] ['statuses_id'] = $row ['feed_id'];
                $res [$i] ['kind'] = $row ['kind'];
				$res [$i] ['group'] = array ('id' => $this->_st ( $row ['group_id'], 0 ), 'name' => $this->_st ( $row ['group_name'], '') );
                $res [$i] ['text'] = $this->_st ( $this->feedModel->format_title ( $row ['comment_content'],$this->source ), '' );
                $res [$i] ['source'] = $row ['source'] == 0 ? '' : $this->get_source ( $row ['source'] );
                $res [$i] ['user'] = array ('id' => $this->_st ( $row ['comment_uid'], 0 ), 'name' => $this->_st ( sns::getrealname ( $row ['comment_uid'] ) ), 'avatar' => sns::getavatar ( $row ['comment_uid'], 'small' ) );
                $res [$i] ['new'] = $row ['new'] == 1 ? true : false;
                $res [$i] ['created_at'] = ceil ( $row ['addtime'] / 10000 );
                $aboutme_opt = array ();
                if ($row ['kind'] == 6) {
                    $content = $this->_st ( strip_tags ( $row ['reply_content'] ), '' );
                    $aboutme_opt ['reply_source'] = array ('id' => $row ['reply_id'], 'text' => $this->feedModel->format_title($content,$this->source), 'at' => $row ['reply_at'], 'user' => array ('id' => $row ['reply_uid'], 'name' => $row ['reply_name'], 'avatar' => sns::getavatar ( $row ['reply_uid'], 'small' ) ) );
                } elseif ($row ['kind'] == 2) {
                    $aboutme_opt ['message'] = array ('text' => $this->_st ( $this->feedModel->format_title ( $row ['feed_content'] ,$this->source), '' ), 'at' => $row ['feed_at'] );
                } else {
                    $content = $this->_st ( strip_tags ( $row ['feed_content'] ), '' );
                    $aboutme_opt ['statuses'] = array ('text' => $this->feedModel->format_title($content,$this->source), 'at' => $row ['feed_at'] ,'uid' => $this->_st ( $row ['feed_uid'], 0 ),'name' => $this->_st ( $row ['feed_name']));
                }
                if (in_array ( $row ['kind'], array (1, 3, 6 ) )) {
                    $aboutme_opt ['comment'] = array ('id' => $row ['comment_id'], 'text' => $this->_st ( $this->feedModel->format_title ( str_replace ( array ('评论道：', '回复：' ), '', $row ['comment_content'] ) ,$this->source), '' ), 'at' => $row ['comment_at'] );
                }
                $res [$i] ['opt'] = $aboutme_opt;
                $i ++;
            }
            $this->feedModel->updateAboutmeRead ();
        }
        $this->send_response ( 200, $res );
    }

    /**
     * 获取关于我的用户动态
     * @return <type>
     */
    public function aboutme_read() {
        $post = $this->get_data ();
        $timeline = isset ( $post ['timeline'] ) ? (int)$post ['timeline']: 0;
        if (empty ( $timeline )) {
            $this->send_response ( 400, NULL, '400130:时间戳为空' );
        }
        $timeline = ($timeline+1).'9999';
        $newdata = array ('$set' => array ("new" => 0 ) );
        $this->feedModel->aboutme->update ( array ("uid" => $this->uid, "addtime" => array ('$lte' => (float)$timeline ) ), $newdata, array ("multiple" => true ) );
        $this->send_response ( 200 );
    }

    /**
     * 动态统计
     * @return <type>
     */
    public function new_count() {
        $start = $this->input->get ( 'modified_at', 0 );
        if (empty ( $start )) {
            $this->send_response ( 400, NULL, '400:modified_at为空' );
        }
        $data = $this->feedModel->getFriendFeedNew ( $this->uid, $start, 0, 0, 1000 );
        if ($data ['code'] == 404) {
            $num = 0;
        } else {
            $num = $data ['result'] ['count'];
        }
        $this->send_response ( 200, array ('statuses_count' => $num ) );
    }

    /**
     * 系统消息统计
     * @return <type>
     */
    public function counts() {
        $messageModel = new Message_Model ();
        $res = $messageModel->getNewNoticeNum ( $this->uid ); //获取新通知条数
        if ($res) {
            $noticeNum = ( int ) $res;
        } else {
            $noticeNum = 0;
        }

        $aboutmeNum = $this->feedModel->aboutMeNewCount (); // 取得新的消息的统计
        $smsNum = User_Model::instance()->get_sms_count($this->uid);
        $this->send_response ( 200, array ('message_count' => $noticeNum, 'aboutme_count' => $aboutmeNum,'sms_count'=>$smsNum ) );
    }

    /**
     * 清除缓存
     * @return <type>
     */
    public function clear_cache() {
        return Cache::instance()->delete_all();
    }

	/**
	 * 对单张照片进行评论
	 * @param md5 $org_objid 多图动态id
	 * @param int $pid 照片id
	 */
	public function getFeedObjIDOfPID(){
		$org_objid=$this->input->get('feed_id');
		$pid=$this->input->get('pic_id');
    	$r=$this->feedModel->getFeedObjIDOfPID($org_objid,$pid);

		if($r){
			$this->send_response( 200, $r );
		}else{
			$this->send_response( 400, NULL, '400:获取数据失败');
		}
	}

	public function format_type($type) {
		switch ($type) {
			default:
			case 'text':
				return 4;
				break;
			case 'pic':
				return 3;
				break;
			case 'file':
				return 9;
				break;

		}
	}

	public function find($str='') {
		$result = $this->feedModel->text_search($str);
		$result = iterator_to_array ( $result );
		$this->send_response( 200, $result );
	}

}
?>
