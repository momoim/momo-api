<?php
/*
* [UAP Server] (C)1999-2009 ND Inc.
* UAP MOBILE 手机注册器
* @TODO：短消息列表,获取指定短消息,删除短消息,发送新短消息,发送公共短消息
*/
defined('SYSPATH') OR die('No direct access allowed.');

class Comment_Controller extends Controller {

    public function __construct() {
        $this->dbm = new Comment_Model();
        parent::__construct();
        $this->uid = $this->getUid();
        $this->dbm->setUid($this->uid);
        $this->friendModel = Friend_Model::instance();
    }

    /**
     * 获取动态评论
     * @return <type>
     */
    public function index() {
        $limit   = $this->input->get('pagesize',10);
        $start   = $this->input->get('start',0);
        $next   = $this->input->get('next',0);
        $pre   = $this->input->get('pre',0);
		
        $statuses_id = $this->input->get('statuses_id');
        if(empty($statuses_id)) {
            $this->send_response(400, NULL, '400:动态id为空');
        }
        $addfeed = new Feed_Model;
        $doc = $addfeed->findFeed($statuses_id);
        $owner_uid = $doc['owner_uid'];
        $xiaomi_feed = ($owner_uid == Kohana::config('uap.xiaomo') && $this->uid != Kohana::config('uap.xiaomo'))?true:false;
        if(!$doc) {
            $this->send_response(404, NULL, '该动态不存在');
        }
        $exit_time = 0;

        if ($statuses_id) {
            $tmp = array();
            if($start){
                    $arr = array('feedid'=>$statuses_id, 'addtime' => array('$gt' => (float)($start)));
                    $exit_time = (float) $start;
            }elseif($next){
                    $arr = array('feedid'=>$statuses_id, 'addtime' => array('$lt' => (float)($next)));
                    $exit_time = (float) $next;
                    $sort = -1;
            }elseif($pre){
                    $arr = array('feedid'=>$statuses_id, 'addtime' => array('$gt' => (float)($pre)));
                    $exit_time = (float) $pre;
                    $sort = 1;
            }else{
                    $arr = array('feedid'=>$statuses_id);
                    $sort = -1;
            }
            if($xiaomi_feed) {
            	$limit = 500;
            }
            $col = $this->dbm->comment_new->find($arr)->sort(array('addtime'=>$sort))->skip(intval($start))->limit(intval($limit));
            
            
            if($col->count()){
                $i=0;
                foreach($col as $val) {
                    if($val['id'] == null || (string)$val['addtime']==$exit_time) continue;
                    
                	if($this->uid != Kohana::config('uap.xiaomo')) {
    					if($owner_uid == Kohana::config('uap.xiaomo')) {
	    					$xiaomi_comment = false;
		            		if((int)$val['uid'] == Kohana::config('uap.xiaomo')) {
			            		if(!empty($val['at'])) {
			            			foreach($val['at'] as $r) {
			            				if((int)$r['id'] == (int)$this->uid) {
			            					$xiaomi_comment = true;
			            				}
			            			}
			            		} 
		            		}
    						if($val['uid'] == $this->uid || $xiaomi_comment) {
    							$tmp[] = $this->formatComment($val);
		                    	$i++;
    						}
    					} else {
    						$tmp[] = $this->formatComment($val);
		                	$i++;
    					}
    				} else {
    					$tmp[] = $this->formatComment($val);
		                $i++;
    				}
                }
                if(!$pre){
                        $tmp = array_reverse($tmp);
                }
            }
            $this->send_response(200, $tmp);
        }
    }
    
    public function formatComment($val) {
        $client_id = $this->get_source();
        $item = array();
		$item['id'] = $val['id'];
        $item['user'] = array('id' => $val['uid'], 'name' => $val['realname'], 'avatar' => sns::getavatar($val['uid'],'small'));
        $item['text'] = Feed_Model::instance()->format_title($val['content'],$client_id);
        $item['at'] = $val['at'] ? $val['at'] : array();
        $item['created_at'] = $val['addtime'];
        $item['source_name'] = $this->dbm->client_from($val['client_id']);
        return $item;
    }

    public function create() {
        $post = $this->get_data();

        $statuses_id = $post['statuses_id'];
        if(empty($statuses_id)) {
            $this->send_response(400, NULL, '400321:对象id为空');
        }
        $addfeed = new Feed_Model;
        $doc = $addfeed->findFeed($statuses_id.'');
        if(!$doc) {
            $this->send_response(404, NULL, '400322:该动态不存在');
        }
        $post_uid = $doc['owner_uid'];
        $content = trim($post['text']);

        $client_id = $this->get_source();
        $pid = isset($post['comment_id'])?$post['comment_id']:0;
        $objid = $objid.'';

        if($pid) {
            if(!$this->dbm->findComment($pid)) {
                $this->send_response(404, NULL, '400323:该评论不存在');
            }
        }
    
        $group_member = array();
    	if($doc['group_type']>0 && $doc['group_id']) {
			$grade = Group_Model::instance()->getMemberGrade($doc['group_id'], $this->uid);
			if($grade < 1)
		    	$this->send_response(400, NULL, '400324:你不是该群成员，无权限发广播');
    		$group_member = Group_Model::instance()->getGroupAllMember($doc['group_id']);
		}
		
        if(empty($content)) {
            $this->send_response(400, NULL, '400325:评论内容不能为空');
        }
        if ($this->cnStrlen($content)>500) {
            $this->send_response(400, NULL, '400326:字数超出500个字');
        }


        $rs = $this->dbm->saveComment($statuses_id, $content, $post_uid, $client_id, $pid,0,$group_member);

        if ($rs['success'])
        {
        	if($doc['group_type']==1 && $doc['group_id'])
            	Tab_Model::instance()->lastModify($this->uid,1,$doc['group_id']);
            $this->send_response(200, $rs['data']);
        }
        $this->send_response(500, NULL, '发表评论失败');
    }

    public function destroy($id = NULL) {
    	if(!$id) {
           $this->send_response(400, NULL, '400:ID为空');
        }
		
        $comment_id = $id;
        $comment = $this->dbm->getCommentById($id);
    	if(!$comment) {
           $this->send_response(404, NULL, '评论不存在');
        }
        $addfeed = new Feed_Model;
        $feed = $addfeed->findFeed($comment['feedid']);
        if(!$feed) {
            $this->send_response(404, NULL, '动态不存在');
        }
        if($comment['uid'] == $this->uid || $feed['owner_uid'] == $this->uid) {
            $typeid = $comment['typeid'];
            $objid = $comment['objid'];

            $addfeed->deleteComment($comment_id, $comment['feedid']);
            $return = $this->dbm->delComment($comment_id,$this->uid);

            $this->send_response(200);
        } else {
            $this->send_response(400, NULL, '删除失败,无权限删除');
        }
        
        
    }
    
    /**
     * 获取动态评论
     * @return <type>
     */
    public function lists() {
		if ($this->get_method () != 'GET') {
			$this->send_response ( 405, NULL, '请求的方法不存在' );
		}
		$limit = $this->input->get ( 'pagesize', 10 );
		$page = $this->input->get ( 'page', 1 );
		$start = ($page - 1) * $limit;
		if ($start < 0) {
			$start = 0;
		}
		$statuses_id = $this->input->get ( 'statuses_id' );
		if (empty ( $statuses_id )) {
			$this->send_response ( 400, NULL, '400:动态id为空' );
		}
		$addfeed = new Feed_Model ();
		$doc = $addfeed->findFeed ( $statuses_id );
		$owner_uid = $doc ['owner_uid'];
		$xiaomi_feed = ($owner_uid == Kohana::config ( 'uap.xiaomo' ) && $this->uid != Kohana::config ( 'uap.xiaomo' )) ? true : false;
		if (! $doc) {
			$this->send_response ( 404, NULL, '该动态不存在' );
		}
		$exit_time = 0;
		
		$tmp = array ();
		$arr = array ('feedid' => $statuses_id );
		$sort = - 1;
		$col = $this->dbm->comment_new->find ( $arr )->sort ( array ('addtime' => $sort ) )->skip ( intval ( $start ) )->limit ( intval ( $limit ) );
		if ($col->count ()) {
			$i = 0;
			foreach ( $col as $val ) {
				if ($val ['id'] == null)
					continue;
				if ($this->uid != Kohana::config ( 'uap.xiaomo' )) {
					if ($owner_uid == Kohana::config ( 'uap.xiaomo' )) {
						$xiaomi_comment = false;
						if (( int ) $val ['uid'] == Kohana::config ( 'uap.xiaomo' )) {
							if (! empty ( $val ['at'] )) {
								foreach ( $val ['at'] as $r ) {
									if (( int ) $r ['id'] == ( int ) $this->uid) {
										$xiaomi_comment = true;
									}
								}
							}
						}
						if ($val ['uid'] == $this->uid || $xiaomi_comment) {
							$tmp [] = $this->formatComment ( $val );
							$i ++;
						}
					} else {
						$tmp [] = $this->formatComment ( $val );
						$i ++;
					}
				} else {
					$tmp [] = $this->formatComment ( $val );
					$i ++;
				}
			}
		}
		$this->send_response ( 200, $tmp );
    }


    private static function cnStrlen($str) {
        $i = 0;
        $str = preg_replace('#^(https?)://[-A-Z0-9+&@\#/%?=~_|!:,.;]*[-A-Z0-9+&@\#/%=~_|]#i', '', trim($str));
        $str = preg_replace_callback(
                '#\b(https?)://[-A-Z0-9+&@\#/%?=~_|!:,.;]*[-A-Z0-9+&@\#/%=~_|]#i',
                create_function(
                '$matches',
                'if(strlen($matches[0])>24) { return "一二三四五六七八九十一二";} else { return str_repeat("a", strlen($matches[0])); }'
                ),
                $str
        );

        preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/", $str, $match);
        foreach ($match[0] as $val) {
            $i = ord($val) > 127 ? $i+2 : $i+1;
        }
        return ceil($i/2);
    }


} 
?>