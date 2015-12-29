<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * 日记模块
 * 
 * 
 * @package None
 * @author Yufeng <ivwsai@gmail.com>
 * @copyright (c) 2010-2011 MOMO Team
 */

class Blog_Model extends Model {

    public static $instances = null;
    
    public function __construct($sid = null)
    {
        parent::__construct();
    }
    
    /**
     * 
     * @return Blog_Model
     */
    public static function & instance()
    {
        if ( !is_object(Blog_Model::$instances) ){
            // Create a new instance
            Blog_Model::$instances = new Blog_Model;
        }
        return Blog_Model::$instances;
    }
    
    
    /**
     * 取得某篇日记
     * 
     * @access public
     * @param int $id
     * @param mixed $field
     * @return array
     */
    public function get_oneblog($id, $field = FALSE)
    {
        if ($field != FALSE) {
            $field = $field===TRUE ? array('id', 'classid', 'uid', 'aid', 'subject', 'summary', 'privacy', 'dtype', 'allowshare', 'draft') : $field;
            $query = $this->db->select($field)->where(array('id' => $id))->get('diary');
        } else {
            $query = $this->db->select(array('id', 'classid', 'uid', 'aid', 'subject', 'privacy', 'dtype', 'password', 'addtime', 'allowshare', 'commentnums', 'draft', 'appoint', 'appoint_group'))->where(array('id' => $id))->get('diary');
        }
        
        $result = $query->result_array(FALSE);
        
        if (isset($result[0])) {
            return $result[0];
        } else {
            return FALSE;
        }
    }
    
    
    /**
     * 取得某篇日记 正文
     * 
     * @access public
     * @param mixed $id
     * @return void
     */
    public function get_blog_content($id)
    {
        $query = $this->db->select(array('content', 'quoturl'))->where(array('did' => $id))->get('diary_fields');
        
        $result = $query->result_array(FALSE);
        
        if (isset($result[0])) {
            return $result[0];
        } else {
            return FALSE;
        }
    }
    
    /**
     * 保存日记访问者
     * 
     * @access public
     * @param int $uid
     * @param int $did
     * @param int $time
     * @return void
     */
    public function diary_read($uid, $did, $time)
    {
        return $this->db->query("REPLACE INTO `diary_read` VALUES ($did, $uid, $time)");
    }
}


