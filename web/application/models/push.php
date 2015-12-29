<?php
defined('SYSPATH') or die('No direct script access.');

/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * 苹果消息推送模型文件
 */
/**
 * 苹果消息推送模型器
 */
class Push_Model extends Model
{
    /**
     * 实例
     * @var Push_Model
     */
    protected static $instance;

    /**
     * 单例模式
     * @return Push_Model
     */
    public static function &instance ()
    {
        if (! isset(self::$instance)) {
            // Create a new instance
            self::$instance = new Push_Model();
        }
        return self::$instance;
    }

    /**
     * 构造函数,
     * 为了避免循环实例化，请尽量调用单例模式
     */
    public function __construct ()
    {
        parent::__construct();
    }

    /**
     * 增加苹果消息推送,返回分组ID
     * @param int $user_id 用户ID
     * @param string $device_id 设备ID
     * @return bool
     */
    public function add ($user_id, $device_id)
    {
        $escape_name = $this->db->escape($device_id);
        //删除设备ID和用户ID关联
        $query = $this->db->query(
        "DELETE FROM `apns_device` WHERE app_id=0 AND (`uid` = $user_id OR device_id = $escape_name)");
        //重新建立设备ID和用户ID关联
        $query = $this->db->query(
        "INSERT INTO `apns_device` (`uid`, `device_id`) VALUES ('$user_id', $escape_name)");
        if ($query->count()) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    public function delete($user_id, $device_id)
    {
        $escape_name = $this->db->escape($device_id);
        //删除设备ID和用户ID关联
        $query = $this->db->query(
        "DELETE FROM `apns_device` WHERE app_id=0 AND (`uid` = $user_id OR device_id = $escape_name)");
        if ($query->count()) {
            return TRUE;
        }else{
            return FALSE;
        }
    }
}
