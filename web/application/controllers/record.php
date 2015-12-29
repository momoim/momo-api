<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * 广播模块
 * @author Administrator
 *
 */
class Record_Controller extends Controller {
	
	var $at2mo = array();
	
	private $thumbUrl;
	private $xhprof =null;
	private $group_id=0;

    public function __construct() {
		//require_once Kohana::find_file('hooks','xhprof');
		//$this->xhprof = new Xhprof;
        parent::__construct();
        $this->uid = $this->getUid();
        $this->group = Group_Model::instance();
        $this->feed = Feed_Model::instance();
    }

    /**
     * 取得广播
     */
    public function onget() {
        $this->setResponseCode(403);
        $this->setResponseMsg('未开放接口');
    }

    /**
     * 创建广播
     * @return <array>
     */
    public function create() {
    	//$this->xhprof->start();
        $post = $this->get_data();

        $content = trim($post['text']);
        $appid = $this->get_source();
        $sync = isset($post['sync'])?$post['sync']:0;
        $retweet_id = isset($post['retweet_id'])?$post['retweet_id']:'';
        $images = !empty($post['accessery']['image'])?$post['accessery']['image']:array();
        $file = !empty($post['accessery']['file'])?$post['accessery']['file']:array();
        $location = !empty($post['location'])?$post['location']:array();
        $group_type = isset($post['group_type'])?$post['group_type']:0;
        $group_id = isset($post['group_id'])?$post['group_id']:0;
        $group_type = $group_id>0?1:0;
        $this->group_id=$group_id;
        $long_content='';
        $str_len = str::strLen($content);
        
        //初始化容器
        
        $this->customImgArray = array();
        $this->customUrl = array();
        $this->at2id = array();
        $this->thumbUrl = Kohana::config('album.recordThumb');
        
        $typeid = 4;
        
    	if($group_id) {
			$grade = $this->group->getMemberGrade($group_id, $this->uid);
			if($grade < 1)
		    	$this->send_response(400, NULL, '400:你不是该群成员，无权限发广播');
		}
        
        if(empty($retweet_id) && empty($content) && empty($file)) {
            $this->send_response(400, NULL, '400:广播内容不能为空');
        }
        
        if(!empty($images) && !is_array($images)) {
            $this->send_response(400, NULL, '400:图片参数格式不正确');
        }
        
        if ($str_len>10000) {
            $this->send_response(400, NULL, '400:字数超出1万个字');
        }
        
        if(empty($appid)) {
            $appid = 0;
        }
        
        $cc = array();
        if($sync) {
            $weibo_result = Bind_Model::instance()->oauth2_check($this->uid,'weibo',0);
            if(!$weibo_result) {
            	$this->send_response(400, NULL, '400111:未绑定微博帐号');
            }elseif($weibo_result['expires_in']+$weibo_result['updated']<time()) {
            	$this->send_response(400, NULL, '400112:微博绑定已过期');
            }else {
            	$cc = array("sina"=>1);
            }
        }
        
        if($retweet_id) {
        	$content = empty($content)?'RT ':$content.' // ';
        	$retweet_feed = $this->feed->findFeed($retweet_id);
            if($retweet_feed) {
            	$at_count = 0;
            	if(count($retweet_feed['at']) > 0) {
            		$this->at2id =$retweet_feed['at'];
            		$at_count = count($retweet_feed['at']);
            	}
            	$this->at2id[$at_count] = array('id'=>$retweet_feed['owner_uid'],'name'=>$retweet_feed['owner_name']);
            	
            	$content = $content.'[@'.$at_count.'] '.str::unhtmlspecialchars($retweet_feed['text']);
            	$str_len = str::strLen($content);
            	if($retweet_feed['long_text']) {
            		$long_content = $content.$retweet_feed['long_text'];
            	}
            } else {
            	$this->send_response(404, NULL, '404:转发动态不存在');
            }
        }

        $short_content = '';
        //$content = html::specialchars($content);
        $thumbUrl = Kohana::config('album.recordThumb');

        //匹配超链接
        $content = preg_replace_callback('#\b(https?)://[-A-Z0-9+&\#/%?=~_|!:,.;]*[-A-Z0-9+&\#/%=~_|]#i', array($this,'bulid_hyperlinks'), $content);
        //匹配@用户名(用户ID)
        $content = preg_replace_callback('/@([^@]+?)\(([1-9][0-9]*)\)/', array($this,'bulid_user_hyperlinks'), $content);
        $content = trim($content);
        
        if($str_len>140) {
            $long_content = empty($long_content)?$content:$long_content;
            $short_content = str::cnSubstr($content, 0,140);
        }
        $short_content=$short_content==''?$content:$short_content;
    
        if($this->check_duplicate($long_content?$long_content:$content, $this->uid, $images, $file)) {
            $this->send_response(400, NULL, '400:不允许提交重复内容');
        }
    
        if(!empty($location) && isset($location['longitude']) && isset($location['latitude'])) {
        	if(empty($location['address'])) {
        		$location['address'] = lbs::get_address_by_location($location['longitude'],$location['latitude']);
        	}
        	Im_Model::instance()->add_map_history($location['address'],$location['latitude'],$location['longitude'],$location['is_correct']);
        }
        
        $accessery = array();
        if(is_array($images) && count($images) > 0) {
        	$typeid = 3;
            foreach($images as $k => $v) {
                if (empty($v['id'])) continue;
                
                $accessery[$k]['type'] = 'pic';
                $accessery[$k]['id'] = (int)$v['id'];
                $accessery[$k]['title'] = '';
                $accessery[$k]['url'] = '';
                $accessery[$k]['status_id'] = 0;
                $accessery[$k]['meta'] = array();
            }
        }elseif(is_array($file) && count($file) > 0) {
        	$typeid = 9;
            foreach($file as $k => $v) {
                if (empty($v['id'])) continue;
                
                $accessery[$k]['type'] = 'file';
                $accessery[$k]['id'] = (int)$v['id'];
                $accessery[$k]['title'] = $v['title'] ? $v['title'] : '';
                $accessery[$k]['url'] = '';
                $accessery[$k]['status_id'] = 0;
                $accessery[$k]['meta'] = $v['meta'] ? $v['meta'] : array();
            }
        }elseif(is_array($this->customImgArray) && count($this->customImgArray) >0) {
        	$typeid = 3;
            foreach($this->customImgArray as $k => $v) {
                $accessery[$k]['type'] = 'pic';
                $accessery[$k]['id'] = 0;
                $accessery[$k]['title'] = '';
                
                $size = @getimagesize($v['src']);
                $meta=array();
                if($size) {
                    $meta['width']=$size[0];
                    $meta['height']=$size[1];
                }

                $accessery[$k]['url'] = $v['src'];
                $accessery[$k]['status_id'] = 0;
                $accessery[$k]['meta'] = $meta;
            }
        }elseif(!empty($retweet_id) && !empty($retweet_feed)) {
            //$row = $this->feed->findFeed($retweet_id);
            $accessery = $retweet_feed['accessory'];
        	$typeid = $retweet_feed['typeid'];
        }
            
        if($typeid == 3 && empty($content)) {
            $content = '分享图片';
        }
        
        if(!empty($file) && is_array($file) && empty($content)) {
            $content = '分享文件';
        }
        $application = array();
        $source = $this->get_source();

        $feedid = $this->feed->addFeed($this->uid,$typeid,$short_content,$source,$application,$this->at2id,$accessery,$cc,$group_type,$group_id,$retweet_id,$location,$long_content,$this->customUrl);

        //广播中提到的好友
        $aboutme_id = '';
        $sended_uid =array(0=>$this->uid);
        
        //更新群组时间
        if($group_type==1 && $group_id)
            Tab_Model::instance()->lastModify($this->uid,1,$group_id);
        
        //增加群组关于我的
        if($group_type==1) {
        	$group_user = $this->group->getGroupAllMember($group_id);
        	foreach ($group_user as $v) {
        		$this->at2id[]=array('id'=>$v['uid'],'name'=>$v['realname']);
        	}
        }
        foreach($this->at2id as $sender) {
            if (in_array($sender['id'],$sended_uid)|| !$sender['id'] || $sender['group_id']) {
                continue;
            }
            $aboutme_id = $this->feed->addAboutme($sender['id'],$this->uid,$typeid,0,'',$this->at2id,$feedid,5);
            $sended_uid[] = $sender['id'];
        }
        $this->feed->mo_sms('feed',$feedid,'',$this->at2mo,$aboutme_id,'',true);
        
        //同步到新浪微薄
        if ($sync && $weibo_result) {
                $message = array(
                        'kind' => 'momoweibo',
                        'data' => array('source_id'=>$feedid, 'images' => $imgids,'site'=>'weibo','name' => $weibo_result['name'],'access_token' => $weibo_result['access_token'], 'expires_in' => $weibo_result['expires_in'])
                );

                $this->feed->mq_send(json_encode($message), "queue_momoweibo", "amq.direct");
        }
        //$run_id = $this->xhprof->stop();
        $result = array('statuses_id'=>$feedid);
        $this->send_response(200,$result);
    }

    /**
     * 构造文本域中的超链接（回调函数）
     * @param <string> $matches
     * @return <string>
     */
    private function bulid_hyperlinks(&$matches) {
        $matchUrl = str_replace('&amp;', '&', $matches[0]);
        $tmp      = preg_split ("/(&#039|&quot)/", $matchUrl);
        $debris   = isset($tmp[1]) ? substr($matchUrl, strlen($tmp[0])) : "";
        $matchUrl = $tmp[0];
        unset($tmp);
        
        if (stripos($matchUrl, YOURLS_SITE) !== false) {
            $shortUrl = $matchUrl;
        } else {
            if (stripos($matchUrl, $this->thumbUrl . 'photo/') !== false) {
//                $shortUrl = $matchUrl;
//                
//                preg_match_all('/photo\/([\d]+)_[a-z0-9\-]*?/is',$shortUrl,$matches);
//                $pic_id = (int) $matches[1][0];
//                if ($pic_id>0) {
//                    $result = $this->photo->wpThumb($pic_id, true);
//
//                    if ($result[0]['width'] > $result[0]['height']) {
//                        $wh_size = $result[0]['width']>130 ? 'width="130"' : '';
//                    } else {
//                        $wh_size = $result[0]['height']>130 ? 'height="130"' : '';
//                    }
//                    
//                    $this->customImgArray[] = array('src' => $result[1]['url'], 'width'=>$result[0]['width'], 'height'=>$result[0]['height'], 'size' => $wh_size, 'rel' => $result[4]['url']);
//                }

                $shortUrl = "";
                $this->customImgArray[] = array('src' => $matchUrl);
            } else {
                $shortUrl = url::getShortUrl($matchUrl);
            }
        }
        $this->customUrl[] = $matchUrl;

        return $shortUrl;
    }

    /**
     * 构建　@用户名(用户ID)的超链接（回调函数）
     * @param <type> $matches
     * @return <type>
     */
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

    /**
     * 字符截取
     * @param <type> $str
     * @return <type>
     */
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

    /**
     * 重复内容检查
     * @param <string> $content
     * @param <int> $uid
     * @param <string> $images
     * @return <boolean>
     */
    public function check_duplicate($content,$uid,$images,$file) {
        if (!empty($images) || !empty($file)) {
            return false;
        }
        
        $content_md5 = md5($uid.'_'.$content);
        
        $count = Cache::instance()->get($content_md5);
        if(!$count) {
            Cache::instance()->set($content_md5, 1, null,3600);
            return false;
        }
        return true;
    }
}
?>