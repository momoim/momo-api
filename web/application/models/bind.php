<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * 绑定微博模块
 * 
 * @package None
 * @author Yufeng <ivwsai@gmail.com>
 * @copyright (c) 2010-2011 MOMO Team
 */

class Bind_Model extends Model {

    public static $instances = null;
    
    public function __construct($sid = null)
    {
        parent::__construct();
    }
    
    /**
     * 
     * @return Bind_Model
     */
    public static function & instance()
    {
        if ( !is_object(Bind_Model::$instances) ){
            // Create a new instance
            Bind_Model::$instances = new Bind_Model;
        }
        
        return Bind_Model::$instances;
    }
    
    /**
     * 保存己授权的token
     * @param Array $field
     * @param String $type
     * @return int
     */
    public function saveToken($field, $type = 'weibo.com')
    {
        $first_bind = true;
        $field = array_merge(array('user_id'=>0,'usa_id'=>0, 'oauth_token'=>'', 'oauth_token_secret'=>'', 'name'=>'', 'homepage'=>''), $field);
        
        //$query = $this->db->query("REPLACE INTO `oauth_token` (`uid`, `usa_id`, `name`, `oauth_token`, `oauth_token_secret`, `site`, `disable`) VALUES (" . $field['user_id'] . ", '{$field['usa_id']}', '{$field['name']}', '{$field['oauth_token']}', '{$field['oauth_token_secret']}', '$type', 'N')");
        if ($this->db->count_records("oauth_token", array("uid"=>$field['user_id'], "site"=>$type)) > 0) {
            $first_bind = false;
            
            $query = $this->db->query("UPDATE `oauth_token` SET `usa_id`='{$field['usa_id']}', `name`='{$field['name']}', `oauth_token`='{$field['oauth_token']}', `oauth_token_secret`='{$field['oauth_token_secret']}', `disable`='N' WHERE `uid`={$field['user_id']} AND `site`='$type'");
        } else {
            $query = $this->db->query("INSERT INTO `oauth_token` (`uid`, `usa_id`, `name`, `oauth_token`, `oauth_token_secret`, `site`, `disable`) VALUES ({$field['user_id']}, '{$field['usa_id']}', '{$field['name']}', '{$field['oauth_token']}', '{$field['oauth_token_secret']}', '$type', 'N')");
        }
        
        if (count($query) > 0) {
            
            //第一次绑定赠送短信
            if ($first_bind) {
                $sms_count = Kohana::config('uap.oauth');
                $sms_count = $sms_count[$type]['sms_present'];
                
                $sms_model = User_Model::instance();
                $sms_count_total = $sms_model->get_sms_count($field['user_id']);
				$sms_count_total = $sms_count_total + $sms_count;

                $content = '您刚绑定了微博，momo.im赠送'.$sms_count.'条全球免费MO短信给您，您现在的免费短信总数为'.$sms_count_total;
                
                $sms_model->present_sms($field['user_id'], $sms_count, $content,false);
            }
            
            //添加个人主页URL
            if ($this->db->count_records("personal_urls", array("uid"=>$field['user_id'], "type"=>$type)) > 0) {
                $status = $this->db->set(array("value"=>$field['homepage'], "show"=>1))->where(array("uid"=>$field['user_id'], "type"=>$type))->update("personal_urls");
            } else {
                $status = $this->db->insert("personal_urls", array("uid"=>$field['user_id'], "type"=>$type, "value"=>$field['homepage'], "show"=>1));
                //if (count($status) < 0) {
                //    return 0;
                //}
                
                //执行用户信息完善度信息更新
                $user_model = User_Model::instance();
                $user_info = $user_model->get_user_info($field['user_id']);
                $member_field = array ("completed" => $user_info['completed'] + 10 );
                $user_model->update_user_info ( $field['user_id'], $member_field );
            }
            
            return count($query);
        } else {
            return 0;
        }
    }
    
    /**
     * 取消授权
     * @param Int $user_id
     * @param String $type
     * @return Boolean
     */
    public function destroyToken($user_id, $type = null)
    {
        $where['uid'] = $user_id;
        if ($type !== null) {
            $where['site'] = $type;
        }
        
        $query = $this->db->set(array("oauth_token"=>"","oauth_token_secret"=>"", "disable"=>"Y"))->where($where)->update('oauth_token');
        
        if ($query->count()) {
            
            //@todo 查找url匹配再删除
            $status = $this->db->delete("personal_urls", array("uid"=>$user_id, "type"=>$type));
            
            if (count($status) < 0) {
                return FALSE;
            }
            //执行用户信息完善度信息更新
            $user_model = User_Model::instance();
            $user_info = $user_model->get_user_info($user_id);
            $member_field = array ("completed" => $user_info['completed'] - 10 );
            $user_model->update_user_info ( $user_id, $member_field );
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    /**
     * 检查微博的帐号是否在MOMO上授权了
     * @param String $token
     * @param String $token_secret
     * @param String $type
     */
    public function ckToken($token, $token_secret, $type)
    {
        return $this->db->count_records('oauth_token', array("oauth_token"=>$token, "oauth_token_secret"=>$token_secret, "site"=>$type, "disable"=>"N"));
    }
    
    /**
     * 检查微博帐号是否在ＭＯＭＯ上授权了
     * @param int $user_id
     * @param string $type
     */
    public function ckBinding($user_id, $type)
    {
        return $this->db->count_records('oauth_token', array("uid"=>$user_id, "site"=>$type, "disable"=>"N"));
    }
    
    /**
     * 
     * @param $user_id
     * @param $access_token
     * @param $expires_in
     * @param $site
     * @return unknown_type
     */
    public function oauth2_create($user_id,$access_token,$expires_in,$site,$appid,$wb_uid='',$wb_name='') {
    	$setters = array(
			'uid'=>$user_id,
	        'usa_id'=>$wb_uid,
	        'name'=>$wb_name,
			'access_token'=>$access_token,
			'expires_in'=>$expires_in,
			'site'=>$site,
			'created'=>time(),
    	    'updated'=>time(),
    	    'appid'=>$appid
		);
		return $this->db->insertData('oauth2_token', $setters);
    }
    
    /**
     * 
     * @param $user_id
     * @param $access_token
     * @param $expires_in
     * @param $site
     * @return unknown_type
     */
    public function oauth2_update($user_id,$access_token,$expires_in,$site,$appid,$wb_uid='',$wb_name='') {
    	$setters = array(
			'access_token'=>$access_token,
    	    'usa_id'=>$wb_uid,
    	    'name'=>$wb_name,
			'expires_in'=>$expires_in,
			'updated'=>time()
		);
    	return $this->db->updateData('oauth2_token', $setters, "appid={$appid} AND site='{$site}' AND uid='{$user_id}'");
    }
    
    /**
     * 
     * @param $user_id
     * @param $site
     * @return unknown_type
     */
	public function oauth2_check($user_id,$site,$appid) {
    	$query = $this->db->fetchData('oauth2_token', '*', array('uid'=>$user_id,'site'=>$site,'appid'=>$appid));
		$result = $query->result_array(FALSE);
		if($result)
			return $result[0];
		return false;
    }
    
    public function oauth2_destroy($user_id,$site,$appid){
        return $this->db->delete('oauth2_token', array('uid'=>$user_id,'site'=>$site,'appid'=>$appid));
    }
    
    public function weibo_settings($appid){
        //@todo 对应关系需记录在数据库
        $oauth = Kohana::config('uap.oauth');
        
        $settings = array(
                0=>array(
                        'uid'=>'2255055220',
                        'key'=>$oauth['weibo.com']['WB_AKEY'],
                        'secret'=>$oauth['weibo.com']['WB_SKEY'],
                ),
                29=>array(
                        'uid'=>'3180775994',
                        'key'=>'617425207',    
                        'secret'=>'e7f799e2b62e4d066ce0ddc55242e920',
                )
        );
        
        return $settings[$appid];
    }
    
    public function weibo_userinfo($access_token,$follow=FALSE,$appid=0){
        require_once Kohana::find_file('vendor', 'weibo/saetv2.ex.class');
        $site_setting = $this->weibo_settings($appid);
        $c = new SaeTClientV2($site_setting['key'], $site_setting['secret'], $access_token);
        
        $data = array();
        if($c) {
            $uid_get = $c->get_uid();
            $uid = $uid_get['uid'];
            $user_info = $c->show_user_by_id($uid);//根据ID获取用户等基本信息
            
            //关注官方微博
            if($follow) {
                $c->follow_by_id($site_setting['uid']);
            }
            
            $data['uid']=$uid;
            $data['name']=$user_info['screen_name'];
            $data['avatar']=$user_info['avatar_large'];
        }
        
        return $data;
    }
    
    public function weibo_share($share_info,$appid=0){
        //@todo 91来电秀分离
        if($appid != 29){
            $appid = 0;
        }
        
        $token_info = $this->oauth2_check($this->uid,'weibo',$appid);
        
        if(!$token_info){
            return array('code'=>401);
        }
        
        if($token_info['updated']+$token_info['expires_in'] < time()){
            return array('code'=>403);
        }
        
        require_once Kohana::find_file('vendor', 'weibo/saetv2.ex.class');
        $site_setting = $this->weibo_settings($appid);
        $c = new SaeTClientV2($site_setting['key'], $site_setting['secret'], $token_info['access_token']);
        //$c->set_debug(true);
        
        $status = $share_info['text'];
        if($share_info['video']){
            $r = $c->upload($status,$share_info['video']['url'].'?filetype=gif');
        }elseif($share_info['images']){
            $r = $c->upload($status,$share_info['images'][0]);
        }else{
            $r = $c->update($status);
        }
        
        if($r['id']){
            return array('code'=>200);
        }elseif(in_array(intval($r['error_code']), array(21315, 21327, 21319))){
            return array('code'=>403);
        }else{
            return array('code'=>400,'data'=>$r);
        }
    }
    
    public function qq_settings($appid){
        //@todo 对应关系需记录在数据库
        $oauth = Kohana::config('uap.oauth');
    
        $settings = array(
                0=>array(),
                29=>array(
                        'name'=>'91来电秀',
                        'home'=>'http://show.91.com',
                        'uid'=>'momo_show_91',
                        'key'=>'100386805',
                        'secret'=>'474ea4625962837f48a96db3e3e7a327',
                )
        );
    
        return $settings[$appid];
    }
    
    public function qq_userinfo($access_token,$follow=FALSE,$appid=0){
        require_once Kohana::find_file('vendor', 'qq/qqConnectAPI');
        $site_setting = $this->qq_settings($appid);
        
        $c = new QC($access_token, '', array('appid'=>$site_setting['key'], 'appkey'=>$site_setting['secret']));
    
        $data = array();
        if($c) {
            $uid = $c->get_uid();
            $ret = $c->get_info();//获取用户等基本信息
            $user_info = $ret['data'];
            $avatar = '';
            if ($user_info['head']){
                $avatar = $user_info['head']+'/100';
            }

            //关注官方微博
            if($follow) {
               $c->add_idol(array('name'=>$site_setting['uid']));
            }
    
            $data['uid']=$uid;
            $data['name']=$user_info['name'];
            $data['avatar']=$avatar;
        }
    
        return $data;
    }
    
    public function qq_share($share_info,$appid=0){
        //@todo 91来电秀分离
        if($appid != 29){
            $appid = 0;
        }
    
        $token_info = $this->oauth2_check($this->uid,'qq',$appid);
    
        if(!$token_info){
            return array('code'=>401);
        }
    
        if($token_info['updated']+$token_info['expires_in'] < time()){
            return array('code'=>403);
        }
    
        require_once Kohana::find_file('vendor', 'qq/qqConnectAPI');
        $site_setting = $this->qq_settings($appid);
        
        $c = new QC($token_info['access_token'], '', array('appid'=>$site_setting['key'], 'appkey'=>$site_setting['secret']));
    
        $status = array();
        $status['title'] = $share_info['title'];
        $status['url'] = $share_info['url'];
        $status['site'] = $site_setting['name'];
        $status['fromurl'] = $site_setting['home'];
        if($share_info['text']){
            $status['comment'] = $share_info['text'];
        }
        if($share_info['summary']){
            $status['summary'] = $share_info['summary'];
        }
        if($share_info['images']){
            $status['images'] = implode('|', $share_info['images']);
        }
        if($share_info['video']){
            //$status['images'] = $share_info['video']['snap_url'];
            $status['images'] = $share_info['video']['url'].'?filetype=gif';
        }
        
        $r = $c->add_share($status);
    
        if($r['ret'] == 0){
            return array('code'=>200);
        }elseif(in_array(intval($r['ret']), array(100014, 100015, 100030))){
            return array('code'=>403);
        }else{
            return array('code'=>400,'data'=>$r);
        }
    }
}
