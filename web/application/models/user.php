<?php
defined('SYSPATH') or die('No direct script access.');
/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * 认证模型文件
 */

/**
 * 认证模型
 */
class User_Model extends Model implements FS_Gateway_Core
{

    /**
     * 实例
     * @var User_Model
     */
    protected static $instance;

    // Database object
    protected $redis = 'default';

    /**
     * 单例模式
     * @return User_Model
     */
    public static function &instance()
    {
        if (!isset(self::$instance)) {
            // Create a new instance
            self::$instance = new User_Model();
        }
        return self::$instance;
    }

    /**
     * 构造函数,
     * 为了避免循环实例化，请调用单例模式
     */
    public function __construct()
    {
        if (!is_object($this->redis)) {
            // Load the default database
            $this->redis = Redis_Core::instance($this->redis);
        }
        parent::__construct();
    }

    public function create_verify_code()
    {
        return rand(100000, 999999);
    }

    /**
     * @param $zone
     * @param string $number
     * @return string
     */
    public function get_full_mobile($zone, $number = '')
    {
        if ($number) {
            return $zone . $number;
        }
        return $zone;
    }

    public function verify_code_key($full_mobile)
    {
        return 'verify_code_' . $full_mobile;
    }

    public function access_token_key($token)
    {
        return 'access_token_' . $token;
    }

    public function refresh_token_key($token)
    {
        return 'refresh_token_' . $token;
    }

    public function set_verify_code($full_mobile, $code)
    {
        $key = $this->verify_code_key($full_mobile);
        $count = $this->redis->hGet($key, 'count');
        $count = $count ? (int)$count : 0;

        $this->redis->multi(Redis::PIPELINE);
        if ($count > 10) {
            $this->redis->hSet($key, 'count', 1);
        } else {
            $this->redis->hIncrBy($key, 'count', 1);
        }
        $m = array(
            'last_timestamp' => time(),
            'count' => $count,
            'code' => $code
        );
        $this->redis->hMset($key, $m);
        $this->redis->exec();
    }

    public function get_verify_code($full_mobile)
    {
        $key = $this->verify_code_key($full_mobile);
        $result = $this->redis->hMGet($key, array('last_timestamp', 'code', 'count'));
        $result['last_timestamp'] = $result['last_timestamp'] ? (int)$result['last_timestamp'] : 0;
        $result['count'] = $result['count'] ? (int)$result['count'] : 0;
        return $result;
    }

    public function check_verify_code($full_mobile, $code)
    {
        $result = $this->get_verify_code($full_mobile);
        if ($code == $result['code']) {
            return true;
        }
        return false;
    }

    public function save_token($token)
    {
        $access_token_key = $this->access_token_key($token['access_token']);
        $this->redis->hMset($access_token_key, $token);
        $this->redis->expire($access_token_key, $token['expires_in']);
        $this->redis->hMset($this->refresh_token_key($token['refresh_token']), $token);
    }

    public function get_user_by_mobile($zone_code, $mobile)
    {
        $query = $this->db->query("SELECT *, uid as id FROM members WHERE zone_code = ? AND mobile = ?", $zone_code, $mobile);
        if ($query->count()) {
            $result = $query->result_array(FALSE);
            return $result[0];
        }
        return array();
    }

    public function get_user_by_id($id)
    {
        $query = $this->db->query("SELECT *, uid as id FROM members WHERE uid = ?", $id);
        if ($query->count()) {
            $result = $query->result_array(FALSE);
            return $result[0];
        }
        return array();
    }

    public function create_user($zone_code, $mobile, $password = '', $regip = '127.0.0.1')
    {
        $user = $this->get_user_by_mobile($zone_code, $mobile);
        if (!$user) {
            $query = $this->db->query("INSERT INTO members (zone_code, mobile, password, regip, regdate) VALUES (?, ?, ?, ?, ?)",
                $zone_code, $mobile, $password ? password_hash($password, PASSWORD_DEFAULT) : '', $regip, time());
            $id = $query->insert_id();
            return $id;
        }
        return 0;
    }

    public function update_user($user_id, $password, $username)
    {
        return $this->db->update('members',
            array(
                'password' => $password ? password_hash($password, PASSWORD_DEFAULT) : '',
                'username' => $username
            ),
            array('uid' => $user_id)
        );
    }

    public function create_token($expires_in, $refresh_token = false)
    {
        $token = array(
            'access_token' => $this->random_token_generator(),
            'expires_in' => $expires_in,
            'token_type' => 'Bearer'
        );
        if ($refresh_token) {
            $token['refresh_token'] = $this->random_token_generator();
        }
        return $token;
    }

    public function random_token_generator($length = 30)
    {
        static $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $rand = '';
        $chars_length = strlen($chars);
        for ($i = 0; $i < $length; $i++) {
            $rand .= $chars[rand(0, $chars_length)];
        }
        return $rand;
    }

    public function get_access_token($access_token)
    {
        // 从redis获取token信息
        return $this->redis->hGetAll($this->access_token_key($access_token));
    }

    public function get_refresh_token($refresh_token)
    {
        // 从redis获取token信息
        return $this->redis->hGetAll($this->refresh_token_key($refresh_token));
    }

    public function send_message($zone, $number, $code)
    {
        if ($zone == 86) {
            require_once Kohana::find_file('vendor', 'tui');
            $tui = new Tui();
            return $tui->send($number, sprintf("尊敬的用户,您的注册验证码是%s,感谢您使用%s！", $code, $this->_get_app_name()));
        } else {
            return false;
        }
    }

    private function _get_app_name()
    {
        return "momo";
    }

    public function get_user_info($id)
    {
        return $this->get_user_by_id($id);
    }

}
