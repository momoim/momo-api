<?php defined('SYSPATH') OR die('No direct access allowed.');
class Comment_Model extends Model
{
        public $uid;
        public $m;
		public $at2mo = array();
		private $group_id=0;

        public function __construct()
        {
            parent::__construct();
            $mg_instance = new MongoClient(Kohana::config('uap.mongodb'));
            $this->m = $mg_instance->selectDB(MONGO_DB_FEED);
            $this->comment = $this->m->selectCollection('comment_new');
            $this->comment_new = $this->m->selectCollection('comment_new');
        }

        public function setUid($uid) {
            $this->uid = $uid;
        }


        public function saveMessage($array)
        {
            return  $this->db->insertData('usermessage',$array);
        }

            //构造文本域中的超链接（回调函数）
        private function bulid_hyperlinks($matches) {
            $notlink = false;
            $matches[0] = str_replace("&amp;", "&", $matches[0]);

            if (stripos($matches[0], YOURLS_SITE) !== false) {
                $shortUrl = $matches[0];
            } else {
                $shortUrl = url::getShortUrl($matches[0]);

            }

            return $shortUrl;
        }

        //构建　@用户名(用户ID)的超链接（回调函数）
    private function bulid_user_hyperlinks(&$matches) {
    	$user = sns::getuser($matches[2]);
    	$realname = $user['realname'];

        if ($matches[1]==$realname) {
        	if($user['status']<2) {
            	$this->at2mo[] = array('id' => $matches[2], 'name' => $matches[1]);
        	}
            $this->at2id[] = array('id' => $matches[2], 'name' => $matches[1],'group_id'=>$this->group_id);
        }elseif(!empty($realname)) {
        	$this->at2id[] = array('id' => $matches[2], 'name' => $realname,'group_id'=>$this->group_id);
        }else{
        	$this->at2id[] = array('id' => $matches[2], 'name' => $matches[1],'group_id'=>$this->group_id);
        }

        return ' [@'.(count($this->at2id)-1).']';
    }

	public function saveComment($status_id, $content, $feed_uid, $client_id=0, $reply_commentid=0,$uid=0,$group_member=array())
        {
            $this->at2id = array();
            $comment_at = array();
            $isall = 0;
            if(strpos($content, '@全部成员(0)') !== false){
                    $isall = 1;
            }
            $addfeed = new Feed_Model;
            $doc = $addfeed->findFeed($status_id);
            $this->group_id = (int) $doc['group_id'];
            $content = preg_replace_callback('#\b(https?)://[-A-Z0-9+&\#/%?=~_|!:,.;]*[-A-Z0-9+&\#/%=~_|]#i', array($this,'bulid_hyperlinks'), $content);
            $content = preg_replace_callback('/@([^@]+?)\(([0-9]*)\)/', array($this,'bulid_user_hyperlinks'), $content);
            $comment_at = $this->at2id;
            $time = time();
            $typeid = 4;
            $feed_uid=$doc['owner_uid'];

            $uid = $uid==0?$this->uid:$uid;

            $content_link = $addfeed->atLink($content, $this->at2id);
            $m = new MongoClient(Kohana::config('uap.mongodb'));

            $comment_id = md5($status_id.'_'.$feed_uid.'_'.microtime());
			$addTime = microtime(true)*10000;
            $col = $this->m->selectCollection ( 'im_user' );
            //右侧聊天窗口start
            $arr = $col->find ( array ('feedid' => $status_id ) );
            if (! empty ( $arr )) {
            		$source_name = api::get_source_name( $client_id );
                    $immsg = array (
                            "kind" => "im",
                            "data" => array (
                                    "id" => $comment_id,
                                    "statuses_id" => $status_id,
                                    "owner_uid" => $feed_uid,
                                    "user" => array('id'=>$uid, 'name'=>sns::getrealname ( $uid ), 'avatar'=>sns::getavatar ( $uid )),
                                    "datetime" => $addTime,
                                    "created_at" => sns::gettime($time),
                                    "source_name" => ($source_name == 'MOMO网站' ? '' : $source_name),
                                    "text" => $content_link
                            )
                    );

                    $uid_string = '';
                    foreach ( $arr as $v ) {
                    	if($v ['uid'] == $uid) {
                    		continue;
                    	}
                            if (strlen ( $uid_string ) > 200) {
                                    $this->mq_send (json_encode ( $immsg), substr ( $uid_string, 0, - 1 ) );
                                    $uid_string = '';
                            }
                            $uid_string .= $v ['uid'] . '.';
                    }
                    $this->mq_send ( json_encode ( $immsg ), substr ( $uid_string, 0, - 1 ) );
            }

            //右侧聊天窗口end
            $client_id = $client_id ? $client_id : 0;
            $realname = sns::getrealname($uid);
            $array		= array(
                    'id'			=> $comment_id,
                    'feedid'			=> $status_id,
                    'content'		=> $content,
                    'at'		=> $this->at2id,
                    'addtime'		=> $addTime,
                    'uid'			=> intval($uid),
                    'realname'			=> $realname,
                    'client_id' => intval($client_id),
                    'owner'			=> intval($feed_uid),
            );
            $this->comment->insert($array);
            if($doc) {

            }
            $typeid=$doc['typeid'];


            $sended_uid = array();
            //回复某人
            if($reply_commentid) {
                $reply_comment = $this->comment->findOne(array('id'=>$reply_commentid));
            	$is_reply = false;

                if($reply_comment['uid'] && count($this->at2id) > 0) {
                    foreach($this->at2id as $key => $var) {
                        if($var['id'] == $reply_comment['uid']) {
                             $is_reply = true;
                             $reply_key = key;
                             continue;
                        }
                    }
                    if($is_reply) {
                        $sended_uid[] = $reply_comment['uid'];
                        unset($comment_at[$reply_key]);
                        $addfeed->addAboutme($reply_comment['uid'],$this->uid, $typeid, $comment_id,$content,$this->at2id,$status_id,6,$reply_comment);
                    }
                }
            }
            //评论动态
            if($feed_uid != $this->uid && $reply_uid!=$feed_uid) {
                if(!in_array($feed_uid,$sended_uid)) {
                    $sended_uid[] = $feed_uid;
                    $addfeed->addAboutme($feed_uid,$this->uid,$typeid, $comment_id,$content,$this->at2id,$status_id,1);
                }
            }
            //群组评论动态
            if(count($group_member)>0) {
            	foreach ($group_member as $member) {
            		if(!in_array($member['uid'],$sended_uid) && $member['uid'] != $this->uid) {
            			$sended_uid[] = $member['uid'];
                    	$addfeed->addAboutme($member['uid'],$this->uid,$typeid, $comment_id,$content,$this->at2id,$status_id,1);
            		}
            	}
            }
            //动态中@某人
            if(!empty($comment_at) && count($comment_at) > 0) {
                foreach($comment_at as $key => $var) {
                    if($var['id'] != $feed_uid && !in_array($var['id'],$sended_uid) && !$var['group_id']) {
                        $sended_uid[] = $var['id'];
                        $addfeed->addAboutme($var['id'],$this->uid,$typeid, $comment_id,$content,$this->at2id,$status_id,3);
                    }
                }
            }

            $comment_list = array('id'=>$comment_id,'uid'=>intval($uid),'name'=>$realname,'created_at'=>intval($time),'text'=>$content,'at'=>$this->at2id,'source'=>$client_id,'im'=>0);
            $isbubble = $feed_uid==Kohana::config('uap.xiaomo')?false:true;
            $addfeed->addFeedComment($status_id,$comment_list,$isbubble);
            $addfeed->updateFeed ( $status_id );
            $addfeed->delHidden ( $status_id );

            $addfeed->mo_sms('comment',$status_id,$comment_id,$this->at2mo);

            $return = array("success"=>true,"data"=>array("id"=>$comment_id,"text"=>$content_link));


            return $return;
        }

		/**
		 * 删除评论
		 * @param integer $appid 	评论唯一性id
		 * @param integer $describe 评论类型
		 */
		public function deleteComment($appid,$describe)
		{
			return $this->db->deleteData('comment',"appid='$appid' and appdescribe='$describe'");
		}


		/**
		 * 正规的content
		 * @param unknown_type $content
		 */
		public function undoContent($content)
		{
			if (strlen($content)>0)
			{
				$trans = array("\r\n" => "", "\n" => "","\r"=>"","["=>"\[","]"=>"\]","{"=>"\{","}"=>"\}",'"'=>'\"',"\\"=>'');
				return strtr($content, $trans);
			}
			else
				return '';
		}

		/**
		 * 决定是否显示评论框
		 * @param int $vuid 登录者id
		 * @param int $uid 所有者id
		 */
		public function retrunAllow($vuid, $uid)
		{
			if ($vuid==$uid) return 1;

		    $right	= User_Model::instance()->getRights($uid,'allowcomment');
			if ($right==0) {    //任何人可以访问
				return 1;
			} elseif($right ==1) {    //仅好友
				$return	= Friend_Model::instance()->getCheckIsFriend($vuid,$uid);
				if ($return) {    //如果是好友返回1
					return 1;
				} else {    //如果不是好友返回2
					return 2;
				}
			} else {
				return 0;
			}
		}

		/**
		 * 取得某条留言内容
		 * @param integer $pid
		 */
		public function getOnlyTitle($pid)
		{
			return $this->db->getOne('comment','content',"id='$pid'");
		}

		public function getCommentById($id)
		{
			//return $this->db->getRow('comment','content, uid',"id='$id'");
			return $this->comment->findOne(array('id' => $id));
		}

		/**
		 * 取得某条留言标题
		 * @param integer $appid 留言唯一标识
		 *
		 */
		public function getOnlySubject($sign=1,$appid)
		{
			switch($sign)
			{
				case 1:
					$subject	= $this->db->getOne('diary','subject',"id='$appid'");
					break;
				case 2:
					$subject	= $this->db->getOne('vote','subject',"id='$appid'");
					break;
				case 3:
					$subject	= $this->db->getOne('userrecord','content',"id='$appid'");
					break;
				case 4:
					$album		= new Album_Model;
					$array		= $album->getAlbumInfoByAid($appid);
					$subject	= $array['data']['album_name'];
					break;
				case 5:
					$photo		= new Photo_Model;
					$array		= $photo->getPhotoInfoByPid($appid);
					$subject	= $array['data']['pic_title'];
					break;
				case 6:
					$album		= new Group_Model;
					$array		= $album->getAlbumInfoByAid($appid);
					$subject	= $array['data']['album_name'];
					break;
				case 7:
					$photo		= new Group_Model;
					$array		= $photo->getPhotoInfoByPid($appid);
					$subject	= $array['data']['pic_title'];
					break;
				case 8:
					$album		= new Group_Model;
					$array		= $album->getAlbumInfoByAid($appid);
					$subject	= $array['data']['group_id'];
					break;

				default:
					break;
			}
			return $subject;

		}

		/**
		 * 该方法已失效。
		 * @param unknown_type $id
		 * @param unknown_type $uid
		 */
		public function removeComment($id,$uid='')//日记单独的删除方法
		{
			 $return	= '';
			 $appid		= $this->db->getOne('comment','appid',"id=$id");//得到帖子id
			 $realUid	= $this->db->getOne('diary','uid',"id=$appid");//能过帖子id得到发布者id
			 if ($uid == $realUid)//只有发布者才能删除评论
			 	$return 	= $this->db->deleteData('comment',array('id'=>$id));
			 if ($return)
			 {
			 	$this->db->deleteData('comment',array('pid'=>$id));//也删除回复
			 }
			 return $return;
		}

		/**
		 * 删除评论
		 * @param integer $id //评论id
		 * @param string $describe
		 */
		public function delComment($id,$uid)//公用的评论删除方法
		{
			$this->comment->remove(array('id' => $id, 'uid' => intval($uid)), true);
			$this->comment->remove(array('id' => $id, 'owner' => intval($uid)), true);
			return 1;
		}

		public function delMessage($id,$uid)//留言删除方法
		{
			if ($uid>0 && $id>0)
			{
				$return		= $this->db->query("delete from usermessage where id='$id' and (uid='$uid' or owner='$uid')");
				if ($return)
				{
					$this->db->deleteData('comment',array('appid'=>$id));//也删除回复
				}
				return $return;
			}
			else
				return '';
		}

		/**
		 * 取得子评论
		 * @param integer $pid 父评论id
		 * @param integer $perPage 每页评论数
		 * @param integer $uid 访问者
		 * @param integer $appid 唯一标识
		 * @param string $appdescribe 评论描述
		 * @param string $type 评论类别
		 *
		 */
		public function getChildComment($pid,$perPage,$offset=0,$uid='',$appid='',$appdescribe='',$type='')//取得子评论
		{
			if ($pid>0)//如果是回复
			{
				return $this->db->fetchData('comment','',array('pid'=>$pid),array('id'=>'desc'),$perPage,$offset);
			}
			else
			{
				$wh		= array('appid'=>$appid,'appdescribe'=>$appdescribe);
				if ($type=='me')
					$wh		= array('uid'=>$uid,'appid'=>$appid,'appdescribe'=>$appdescribe);
				elseif ($type =='other')
					$wh		= array('owner'=>$uid,'appid'=>$appid,'appdescribe'=>$appdescribe);
				return $this->db->fetchData('comment','',$wh,array('id'=>'desc'),$perPage,$offset);
			}
		}

		/**
		 * 取得评论
		 * @param integer $objid 帖子id
		 * @param string $typeid 评论类型
		 * @param integer $limit 数据偏移量
		 * @param integer $start 数据起始数
		 * @param integer $order 排序类型
		 */
		public function fetchComment($objid=0,$typeid,$limit,$start,$order='DESC')//取回评论
		{
			$col = $this->comment->find(array('objid'=>$objid,'typeid'=>intval($typeid)))->sort(array('addtime'=>-1))->skip(intval($start))->limit(intval($limit));
			$arr = iterator_to_array($col);

			return $arr;
		}

                public function findComment($comment_id) {
                    $val = $this->comment->findOne(array('id' => $comment_id));
                    return $val;
                }

		public function fetchImComment($objid,$typeid,$limit,$id)//取回评论
		{	//echo $appid.'_'.$appdescibe;
			$arr = $id ? array('objid'=>$objid, 'typeid'=>intval($typeid), 'id' => array('$lt' => intval($id))) : array('objid'=>$objid, 'typeid'=>intval($typeid));
			$col = $this->comment->find($arr)->sort(array('addtime'=>-1))->limit(intval($limit));
			$arr = iterator_to_array($col);

			return $arr;
		}

		/**
		 * 取得回复
		 * @param integer $pid 父评论id
		 */
		public function fetchReply($pid, $limit=NULL, $order='ASC')//取得回复
		{
			$pid	= intval($pid);
			if ($pid>0)
				return $this->db->fetchData('comment','*',array('pid'=>$pid),array('id'=>$order),$limit);
			else
				return '';
		}


		/**
		 * 某帖的评论总数
		 * @param integer $appid 帖子id
		 * @param string $appdescribe评论类型
		 * @param integer $pid 父评论id
		 */

		public function getCommentCount($appid,$appdescribe,$pid='')//取得某帖的评论总数
		{
			$coll = $this->m->selectCollection('comment');
			$arr = array('appid' => $appid, 'appdescribe' => $appdescribe);
			$cur = $coll->find($arr);

			return $cur->count();
		}

		/**
		 * 取得某帖的评论总数
		 * @param unknown_type $appid
		 * @param unknown_type $appdescribe
		 */
		public function getOtherCount($appid,$appdescribe)
		{
			return $this->db->getCount('comment',"appid='$appid' and appdescribe='$appdescribe'");
		}

		/**
		 * 保存到评论临时表
		 * @param integer $appid //唯一标识符
		 * @param string $appdescribe //评论描述
		 * @param integer $uid //用户id
		 * @param integer $owner //所有者id
		 */
		public function insertToLast($appid,$appdescribe,$uid,$owner)
		{
			return null;
			/*
			$id		= $this->db->getOne('comment_last','id',"appid='$appid' and owner='$owner' and appdescribe='$appdescribe'");
			$addtime	= time();
			if ($id>0) {
				$this->db->updateData('comment_last',array('addtime'=>$addtime),"id='$id'");

			} else {
				$array		= array(
					'appid'		=> $appid,
					'appdescribe'	=> $appdescribe,
					'uid'		=> $uid,
					'owner'		=> $owner,
					'addtime'	=> $addtime
				);
				$this->db->insertData('comment_last',$array);
			}
			*/
		}

		/**
		 * 得到全部评论总数
		 * @param integer $uid 用户id
		 * @param string $type 评论的类型
		 */
		public function getOtherCommentCount($uid,$type)
		{
			$wh		= " and appdescribe!='index_leave'";
			if ($type=='all')
			{
				$rs		= $this->db->query("select id from comment where (uid='$uid' or owner='$uid') and uid!=owner $wh group by appid,appdescribe");
			}
			elseif($type=='me')
			{
				$rs		= $this->db->query("select id from comment where uid='$uid' and owner!='$uid' $wh group by appid,appdescribe ");
			}
			elseif ($type=='other')
			{
				$rs		= $this->db->query("select id from comment where owner='$uid' and uid!='$uid' $wh group by appid,appdescribe");
			}
			return $rs->count();
		}

		/**
		 * 得到全部评论
		 * @param integer $uid 用户id
		 * @param string $type 留言的类型
		 * @param integer $perPage 每页条数
		 * @param integer $offset 偏移量
		 */
		public function getOtherComment($uid,$type,$perPage,$offset=0) //得到全部评论
		{
			$wh		= " and appdescribe!='index_leave'";
			if ($type=='all')
			{
				return $this->db->query("select * from comment where (uid='$uid' or owner='$uid') and uid!=owner $wh group by appid,appdescribe order by addtime desc limit $offset,$perPage");
			}
			elseif($type=='me')
			{
				return $this->db->query("select * from comment where uid='$uid' and owner!='$uid' $wh group by appid,appdescribe order by addtime desc limit $offset,$perPage");
			}
			elseif ($type=='other')
			{
				return $this->db->query("select * from comment where owner='$uid' and uid!='$uid' $wh group by appid,appdescribe order by addtime desc limit $offset,$perPage");
			}
		}

		/**
		 * 得到全部留言总数
		 * @param integer $uid 用户id
		 * @param string $type 留言的类型
		 * @param string $filter 评论类型
		 */
		public function getUserCommentCount($uid,$type,$filter='')
		{
			$wh		= " and appdescribe='index_leave'";
			if ($type=='all')
			{
				return $this->db->getCount('comment',"(uid='$uid' or owner='$uid') and uid!=owner and pid=0 $wh");
			}
			elseif($type=='me')
			{
				return $this->db->getCount('comment',"uid='$uid' and pid=0 $wh and owner!='$uid'");
			}
			elseif ($type=='other')
			{
				return $this->db->getCount('comment',"owner='$uid' and pid=0 $wh and uid!='$uid' ");
			}
		}

		public function getUserMessageCount($uid,$type,$filter='')
		{
			if ($type=='all')
			{
				return $this->db->getCount('usermessage',"(uid='$uid' or owner='$uid') and uid!=owner");
			}
			elseif($type=='me')
			{
				return $this->db->getCount('usermessage',"uid='$uid' and owner!='$uid'");
			}
			elseif ($type=='other')
			{
				return $this->db->getCount('usermessage',"owner='$uid' and uid!='$uid' ");
			}
		}

		/**
		 * 更新父评论时间
		 */
		public function updateParent($id,$time)
		{
			$this->db->updatedata('comment',array('updatetime'=>$time),array('id'=>$id));
		}

		/**
		 * 取得全部留言
		 * @param integer $uid 用户id
		 * @param string $type 留言的类型
		 * @param string $filter 评论类型（由于现与评论分开了，此参数现在无实际意义）
		 * @param integer $perPage 每页条数
		 * @param integer $offset 偏移量
		 */
		public function getUserMessage($uid,$type,$filter,$perPage,$offset=0)
		{
			//$wh		= " and appdescribe ='index_leave'";
			if ($type=='all')
			{
				return $this->db->query("select * from usermessage where (uid='$uid' or owner='$uid') and uid!=owner order by addtime desc limit $offset,$perPage");
			}
			elseif($type=='me')
			{
				return $this->db->query("select * from usermessage where uid='$uid' and owner!='$uid' order by addtime desc limit $offset,$perPage");
			}
			elseif ($type=='other')
			{
				return $this->db->query("select * from usermessage where owner='$uid' and uid!='$uid' order by addtime desc limit $offset,$perPage");
			}
		}

		/**
		 * 取得我的回复及评论
		 * @param integer $uid 用户id
		 * @param integer $appid 帖子id
		 * @param string $appdescribe 评论类型
		 * @param integer $start 起始数
		 * @param integer $end 结束数
		 */
		public function getMyComment($uid,$appid,$appdescribe,$start,$end)//我的回复及评论
		{
			$start		= $start ? $start :0;
			$end		= $end ?$end :100;
			return $this->db->fetchData('comment','',array('uid'=>$uid,'appdescribe'=>$appdescribe,'pid'=>0),array('id'=>'asc'),$start,$end);
		}

		/**
		 * 别人对我的回复和评论
		 * @param integer $uid
		 * @param integer $appid
		 * @param string $appdescibe
		 * @param integer $start
		 * @param integer $end
		 */
		public function getCommentByUid($uid,$appid,$appdescibe,$start,$end)
		{
			$start		= $start ? $start :0;
			$end		= $end ?$end :100;
			return $this->db->fetchData('comment','',array('owner'=>$uid,'appdescribe'=>$appdescibe,'pid'=>0),array('id'=>'asc'),$start,$end);
		}

		/**
		 * 获取评论js
		 * @param string $title 评论的题头，目前有留言和评论两个，默认为评论
		 * @param integer $vuid 登录者id
		 * @param integer $uid 被访问者id
		 * @param integer $appid 评论的唯一标识，即文章id,图片id等
		 * @param string $appdescribe 评论类型描述，目前有blog,vote,album,photo,record,index等。
		 * @param string feed 模板名称，目前有vote_add,diary_add等。
		 */
		public function getCommentJs($title='评论',$vuid,$uid,$appid,$appdescribe)//得到评论js
		{
			$allow			= $this->retrunAllow($vuid,$uid);
            $showInput  	= 'false';         //是否显示输入
            $showInputMsg	= '用户关闭评论';
			$feedModel   = new Feed_Model;
			if ($allow ==1)
			{
				$showInput		= 'true';
				$showInputMsg	= '';
			}
			elseif ($allow ==2)
			{
				$showInput		= 'false';
				$showInputMsg	= '仅好友可以评论';
			}

			$praise  = new Praise_Model;

		    $str .='
		    	<script src="'.url::js_url().'lib/ui.pagination/jquery.pagination.js" type="text/javascript"></script>
				<div>
					<span class="span-comment-opt"></span>
				</div>
				<div class="tip-1 feed">
					<textarea class="hiddenJSON hide">
					{
						"uid": '.$uid.'
						,"commentCount":"0"             
						,"success":true
						,"msg":""
						,"typeid":"'.$feedModel->getFeedTplId($appdescribe).'"
						,"objid":'.$appid.'
						,"pid":0
						,"commentEnabled":'.$showInput.'
						,"likeCount":'.$praise->getPraiseCount($appdescribe,$appid).'
						,"likeList":'.json_encode($praise->getPraiseUser($appdescribe,$appid,null,null,$uid)).'
					}
					</textarea>
				</div>

				<script type="text/javascript">
		        $(document).ready(function() {
                    $("#div_comment .hiddenJSON").initComment({
                            title: "'.$title.'",
                            pageSize: 12, 
                            showPrivateChk: true,   //显示悄悄话的checkbox
                            showHeader: true,       //显示头部
                            uid: '.$uid.',                //被访问者
                            vuid: '.$vuid.',               //当前登录者
                            showInput:'.$showInput.',
                            showInputMsg:"'.$showInputMsg.'",
                            typeid: "'.$feedModel->getFeedTplId($appdescribe).'",    //diary,index,vote等
                            objid: '.$appid.',               //当前应用id 如日志id,投票id,首页留言appid=uid
                            inputPosition: "top",
                            inputExpand: true,
                            showPager: true
                    });
		        });
		    </script>';
		    return $str;
		}

	}