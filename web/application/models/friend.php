<?php
defined('SYSPATH') or die('No direct access allowed.');

/*
* [UAP Portal] (C)1999-2010 ND Inc.
* 好友模型文件
*/

class Friend_Model extends Model
{
    public static $instances = NULL;
    public $cache_pre;
    public $cache;

    public function __construct($sid = NULL)
    {
        parent::__construct();
        $this->cache = Cache::instance('contact');
        $this->cache_pre = CACHE_PRE . 'user_link_';
    }

    /**
     * 单例
     * @return Friend_Model
     */
    public static function &instance()
    {
        if (!is_object(Friend_Model::$instances)) {
            // Create a new instance
            Friend_Model::$instances = new Friend_Model();
        }
        return Friend_Model::$instances;
    }

    /**
     * 按姓名取得用户数(搜索用)
     * @param string $name
     * @return Integer
     */
    public function get_user_counts_byname($name)
    {
        return $this->db->where(array("realname" => $name))->count_records(
            'membersinfo');
    }

    /**
     * 按姓名取得用户一些基本信息(搜索用)
     * @param string $name
     * @param Integer $offset
     * @param Integer $pos
     */
    public function get_users_byname($name, $offset = 0, $pos = 20)
    {
        $query = $this->db->select('uid', 'realname', 'sex', 'sign',
            'birthyear', 'astro', 'resideprovince', 'residecity', 'company',
            'college')
            ->where(array("realname" => $name))
            ->get('membersinfo', $pos, $offset);
        return $query->result_array(FALSE);
    }

    /**
     * 邀请是否失效
     * @param string $invite_code
     * @return mixed
     */
    public function invite_isfailure($invite_code)
    {
        $query = $this->db->where(
            array("invite_code" => $invite_code,
                "status" => 0
            ))->get("invitation");
        if (!$query->count()) {
            return FALSE;
        }
        $result = $query->result_array(FALSE);
        //更新邀请状态
        $this->db->set(array("status" => 1))
            ->where(array("invite_code" => $invite_code))
            ->update("invitation");
        return $result[0];
    }

    /**
     * 获取用户关联
     * @param int $user_id 用户ID
     * @param string $friend_user_id 关联用户ID
     * @return bool
     */
    public function get_link($user_id, $friend_user_id)
    {
        $result = $this->db->getwhere('friends', array(
            'uid' => $user_id,
            'fid' => $friend_user_id,
        ));

        if ($result->count()) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * 获取双向关联用户ID
     * @param int $user_id 用户ID
     * @param bool $xiaomo 是否返回小秘（默认 TRUE）
     * @return array
     */
    public function getAllFriendIDs($user_id, $xiaomo = TRUE)
    {
        $ids = $this->get_user_link_cache($user_id);
//		$ids2   = $this->get_friend_link_cache($user_id);
//		$data   = array_intersect($ids, $ids2);
        $data = $ids;
        $xm_uid = Kohana::config('uap.xiaomo');
        if ($xiaomo === TRUE && $user_id != $xm_uid) {
            $data[] = $xm_uid;
        }
        return $data;
    }

    /**
     * 判断用户是否双向关联
     * @param int $user_id 用户ID
     * @param int $friend_user_id 关联用户ID
     * @return bool
     */
    public function check_isfriend($user_id, $friend_user_id)
    {
        if ($this->get_link($user_id, $friend_user_id) and
            $this->get_link($friend_user_id, $user_id)
        ) {
            return TRUE;
        }
        if ($user_id == Kohana::config('uap.xiaomo')
            || $friend_user_id == Kohana::config('uap.xiaomo')
        ) {
            return TRUE;
        }
        return FALSE;
    }

    public function check_iscontact($user_id, $friend_user_id)
    {
        return Contact_Model::instance()->is_contact($user_id, $friend_user_id);
    }

    /**
     * 获取双向关联用户个数
     * @param int $user_id 用户ID
     * @return int
     */
    public function getFriendTotal($user_id)
    {
        return count($this->getAllFriendIDs($user_id));
    }

    /**
     * 获取用户关联的人用户ID
     * @param int $user_id 用户ID
     * @return array
     */
    public function get_user_link_cache($user_id)
    {
        $query = $this->db->select('fid')
            ->where(array('uid' => $user_id))
            ->get('friends');
        $result = $query->result_array(FALSE);
        $fids = array();
        if ($result) {
            foreach ($result as $res) {
                $fids[] = (int)$res['fid'];
            }
        }
        return $fids;
    }

    public function add_friend($uid, $fid)
    {
        $this->db->insert('friends', array(
            'uid' => $uid,
            'fid' => $fid,
            'dateline' => time()
        ));
        $this->db->insert('friends', array(
            'fid' => $uid,
            'uid' => $fid,
            'dateline' => time()
        ));
        return true;
    }

    public function delete_friend($uid, $fid)
    {
        $this->db->delete('friends', array(
            'uid' => $uid,
            'fid' => $fid,
        ));
        $this->db->delete('friends', array(
            'fid' => $uid,
            'uid' => $fid,
        ));
        return true;
    }

    /**
     * 清除用户关联缓存
     * @param int $user_id 用户ID
     * @return bool
     */
    public function del_user_link_cache($user_id)
    {
        $key = $this->cache_pre . $user_id;
        $this->cache->delete($key);
        return TRUE;
    }

    /**
     * 根据手机号码获取用户联系人的姓名
     * @param $user_id 用户ID
     * @param $mobile 手机号码
     * @param string $zone_code 国家码
     * @return array
     */
    public function get_contact_formatted_name($user_id, $mobile, $zone_code = '86')
    {
        $result = array();
        if ($mobile) {
            $zone_code = $zone_code ? $zone_code : '86';
            $search = '+' . $zone_code . $mobile;
            $ids = Contact_Mapper::instance()->get_id_by_tel($user_id, $search);
            $contact_model = Contact_Model::instance();
            foreach ($ids as $id) {
                $contact = $contact_model->get($user_id, $id);
                $result[] = $contact->get_formatted_name();
            }
        }
        return $result;
    }
}
