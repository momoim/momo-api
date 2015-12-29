<?php defined('SYSPATH') or die('No direct access allowed.');
//微博Oauth token 管理
class Syncweibo_Model extends Model {

    public function __construct($sid = null)
    {
        parent::__construct();
    }
    
    /**
     * 保存己授权的token
     * @param Array $field
     * @param String $type
     * @return Boolean
     */
    public function saveToken($field, $type = 'weibo.com')
    {
        $field = array_merge(array('user_id'=>0, 'oauth_token'=>'', 'oauth_token_secret'=>'', 'name'=>''), $field);
        $query = $this->db->query("INSERT IGNORE INTO `oauth_token` (`uid`, `usa_id`, `name`, `oauth_token`, `oauth_token_secret`, `site`) VALUES (" . $this->getUid() . ", '{$field['user_id']}', '{$field['name']}', '{$field['oauth_token']}', '{$field['oauth_token_secret']}', '$type')");
        
        return (count($query) > 0) ? $query->insert_id() : FALSE; 
    }

    /**
     * 更新己授权的token
     * @param Array $field
     * @param Int $uid
     * @param String $type
     * @return Boolean
     */
    public function updateToken($field, $uid, $type)
    {
        $field = array_merge(array('user_id'=>0, 'oauth_token'=>'', 'oauth_token_secret'=>'', 'name'=>''), $field);
        $query = $this->db->query("UPDATE `oauth_token` SET `usa_id`='{$field['user_id']}', `name`='{$field['name']}', `oauth_token`='{$field['oauth_token']}', `oauth_token_secret`='{$field['oauth_token_secret']}' WHERE `uid`=" . $this->getUid() . " AND `site`='$type'");
        
        return (count($query) > 0) ? $query->insert_id() : FALSE;
    }

    /**
     * 取得用户己授权的token
     * @param Int $uid
     * @param String $type
     * @return Array
     */
    public function getToken($uid, $type = null)
    {
        $where['uid'] = $uid;
        if ($type !== null) {
            $where['site'] = $type;
        }
        
        $query = $this->db->where($where)->get('oauth_token');
        if ($query->count()) {
            return $query->result_array(FALSE);
        } else {
            return array();
        }
    }
    
    /**
     * 取消授权
     * @param Int $uid
     * @param String $type
     * @return Boolean
     */
    public function destroyToken($uid, $type = null)
    {
        $where['uid'] = $uid;
        if ($type !== null) {
            $where['site'] = $type;
        }
        
        $query = $this->db->where($where)->delete('oauth_token');
        if ($query->count()) {
            return TRUE;
        } else {
            return FALSE;
        }        
    }
    
    /**
     * 检查微博的帐号是否在MOMO上授权了
     * @param unknown_type $token
     * @param unknown_type $token_secret
     * @param unknown_type $type
     */
    public function ckToken($token, $token_secret, $type)
    {
        return $this->db->count_records('oauth_token', array("oauth_token"=>$token, "oauth_token_secret"=>$token_secret, "site"=>$type));
    }
}