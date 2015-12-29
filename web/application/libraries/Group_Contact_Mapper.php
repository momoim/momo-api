<?php
defined('SYSPATH') or die('No direct access allowed.');

/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * 群组联系人数据映射库文件
 */
/**
 * 群组联系人数据映射类
 */
class Group_Contact_Mapper
{
    /**
     * 数据映射
     * @var array
     */
    protected $map = array();
    /**
     * 数据库连接
     * @var DataBase
     */
    protected $db;
    /**
     * 实例
     * @var Group_Contact_Mapper
     */
    protected static $instance;
    
    /**
     * 单例模式
     * @param DataBase $db 数据库连接
     */
    public static function &instance ($db)
    {
        if (! isset(Group_Contact_Mapper::$instance)) {
            // Create a new instance
            Group_Contact_Mapper::$instance = new Group_Contact_Mapper($db);
        }
        return Group_Contact_Mapper::$instance;
    }
    
    /**
     * 构造函数
     * @param DataBase $db 数据库连接
     */
    public function __construct ($db)
    {
        $this->db = $db;
        foreach (simplexml_load_file(APPPATH . 'config/group_contact.xml') as $field) {
            $this->map[(string) $field->name] = $field;
        }
    }
    
    /**
     * 根据群联系人查找群联系人
     * @param int $id 群联系人ID
     */
    public function find_by_id ($group_id, $id)
    {
        $group_contact = new Group_Contact();
        if($group_id) {
        	$where = "`gid` = '$group_id' AND `gcid` = '$id' AND deleted = 0";
        } else {
        	$where = "`gcid` = '$id' AND deleted = 0";
        }
        //查询联系人主表
        $query = $this->db->query(
        "SELECT * FROM `gcp_contacts` WHERE $where LIMIT 1");
        if (! $query->count()) {
            return FALSE;
        } else {
            $result = $query->result_array(FALSE);
            $row = $result[0];
            $row['avatar'] = $this->_get_avatar($id);
            //填充数据
            foreach ($this->map as $field) {
                //有多值，取变量的变量
                $name = (string) $field->name;
                if (in_array($name, 
                array('tels', 'emails', 'addresses', 'urls', 'ims', 'events', 
                'relations'))) {
                    $value = $this->_get_info_list($id, $name);
                } else {
                    $value = isset($row[$name]) ? $row[$name] : '';
                }
                $setprop = (string) $field->mutator;
                if ($setprop) {
                    call_user_func(array($group_contact, $setprop), $value);
                }
            }
            return $group_contact;
        }
    }
    
    /**
     * 根据群ID查找联系人
     * @param int $group_id 群ID
     */
    public function find_by_group_id ($group_id)
    {
        $binds = array('gid' => $group_id, 'deleted' => 0);
        $query = $this->db->getwhere('gcp_contacts', $binds);
        $result = array();
        if ($query->count()) {
            $rows = $query->result_array(FALSE);
            foreach ($rows as $row) {
                $result[$row['gcid']] = array('id' => $row['gcid'], 
                'modified_at' => $row['modified'] ? (int) $row['modified'] : 0, 
                //更多信息
                'formatted_name' => $row['formatted_name'], 
				'sort' => $row['sort'],
                'phonetic' => $row['phonetic'], 
                'momo_user_id' => (int) $row['uid'], 
                'deleted' => (bool) $row['deleted'], 
                'telephone' => $this->get_main_tel($row['gcid']));
            }
        }
        return $result;
    }

    /**
     * 获取主手机号
     * @param int $id 群联系人ID
     */
    public function get_main_tel ($id, $is_pref = FALSE)
    {
        $tels = $this->_get_info_list($id, 'tels');
        $main_tel = $main_type = $pref = '';
        if (! empty($tels)) {
            foreach ($tels as $key => $tel) {
                if ($key == 0) {
                    $main_type = $tel['type'];
                    $main_tel = $tel['value'];
                }
                if ($tel['pref']) {
                    $pref = TRUE;
                    $main_tel = $tel['value'];
                    break;
                } elseif ($main_type != 'cell' and $tel['type'] == 'cell') {
                    $main_tel = $tel['value'];
                    $main_type = $tel['type'];
                }
            }
        }
        if ($is_pref and ! $pref) {
            $main_tel = '';
        }
        return $main_tel;
    }

    /**
     * 根据标签查找联系人
     * @param Group_Contact $group_contact
     * @return array
     */
    public function find_by_tags (Group_Contact $group_contact)
    {
        $keys = array();
        $others = array();
        //组织SQL
        foreach ($this->map as $field) {
            $name = (string) $field->name;
            $getprop = (string) $field->accessor;
            if ($getprop) {
                $value = call_user_func(array($group_contact, $getprop));
                if ($value) {
                    if (in_array($name, 
                    array('tels', 'emails', 'addresses', 'ims', 'urls', 
                    'relations', 'events'))) {
                        if (in_array($name, array('tels', 'emails', 'ims'))) {
                            $others[$name] = $value;
                        }
                    } else {
                        $keys['gcp_contacts.' . $name] = $value;
                    }
                }
            }
        }
        if (! array_key_exists('gcp_contacts.deleted', $keys)) {
            $keys['gcp_contacts.deleted'] = 0;
        }
        if (! empty($others)) {
            $this->db->select('gcp_contacts.*')
                ->from('gcp_contacts')
                ->where($keys);
            $type = key($others);
            $other = $others[$type];
            $tmp = array();
            foreach ($other as $val) {
                $tmp[] = $val['value'];
            }
            $this->db->join('gcp_' . $type, 
            'gcp_contacts.gcid = gcp_' . $type . '.gcid', '', 'LEFT')->in(
            'gcp_' . $type . '.value', $tmp);
            $query = $this->db->get();
        } else {
            $query = $this->db->getwhere('gcp_contacts', $keys);
        }
        $result = array();
        if ($query->count()) {
            $rows = $query->result_array(FALSE);
            foreach ($rows as $row) {
                $result[] = $row['gcid'];
            }
        }
        return $result;
    }
    
    /**
     * 根据群联系人对象创建群联系人
     * @param Group_Contact $group_contact 群联系人对象
     * @throws Exception
     */
    public function insert ($group_contact)
    {
        $query = $this->db->query(
        "INSERT INTO `gcp_contacts` (`gid`,`uid`, `formatted_name`," .
         " `phonetic`, `given_name`, `middle_name`, `family_name`," .
         " `prefix`, `suffix`, `organization`, `department`, `note`," .
         " `birthday`, `title`, `nickname`, `sort`," .
         "`created`, `modified`, `fid`) VALUES (? , ?, " .
         " ? , ?, ? , ? , ? , ? , ? , ? , ?, ? , ? , ? , ? , ? , ? , ? , ? )", 
        array( $group_contact->get_group_id(),
        $group_contact->get_user_id(), $group_contact->get_formatted_name(), 
        $group_contact->get_phonetic(), $group_contact->get_given_name(), 
        $group_contact->get_middle_name(), $group_contact->get_family_name(), 
        $group_contact->get_prefix(), $group_contact->get_suffix(), 
        $group_contact->get_organization(), $group_contact->get_department(), 
        $group_contact->get_note(), $group_contact->get_birthday(), $group_contact->get_title(), 
        $group_contact->get_nickname(), $group_contact->get_sort(), $group_contact->get_modified_at(), $group_contact->get_modified_at(),$group_contact->get_momo_user_id()
        ));
        $id = $query->insert_id();
        if ($id) {
            $sqls = array_merge(
            $this->_edit_avatar($group_contact->get_group_id(), $id, 
            $group_contact->get_avatar()));
            foreach (array('emails', 'tels', 'addresses', 'ims', 'urls', 
            'events', 'relations') as $type) {
                $sqls = array_merge($sqls, 
                $this->_add_info($group_contact->get_group_id(), $id, $type, 
                call_user_func(array($group_contact, 'get_' . $type))));
            }
            foreach ($sqls as $sql) {
                $this->db->query($sql);
            }
            $inserted = $this->find_by_id($group_contact->get_group_id(), $id);
            // clean up database related fields in parameter instance
            if (method_exists($inserted, 'set_id')) {
                $group_contact->set_id($inserted->get_id());
                $group_contact->set_created_at($inserted->get_created_at());
                $group_contact->set_modified_at($inserted->get_modified_at());
            }
        } else {
            throw new Exception('DB Error: Add Group_Contact Fail');
        }
    }

    /**
     * 修改联系人信息
     * @todo 未完成
     * @param Group_Contact $group_contact 联系人信息
     */
    public function update ($group_id, $contact, $mode = 'default')
    {
    	$id = $contact->get_id();
        switch ($mode) {
            case 'overwrite':
                $setters = array();
                $setters['gid'] = $group_id;
                foreach ($this->map as $field) {
                    $name = (string) $field->name;
                    $getprop = (string) $field->accessor;
                    if (in_array($name, 
                    array('uid', 'fid', 'formatted_name', 'phonetic', 'given_name', 
                    'middle_name', 'family_name', 'prefix', 'suffix', 
                    'organization', 'department', 'note', 'birthday', 'title', 
                    'nickname', 'sort', 'modified'))) {
                        $setters[$name] = call_user_func(
                        array($contact, $getprop));
                    }
                }
                $sqls = $this->_edit_avatar($group_id, $id, $contact->get_avatar());
                foreach (array('emails', 'tels', 'addresses', 'ims', 'urls', 
                'events', 'relations') as $type) {
	                $sqls = array_merge($sqls, 
	                $this->_edit_info($group_id, $id, $type, 
	                call_user_func(array($contact, 'get_' . $type))));
                }
                //更新数据库
                $query = $this->db->update('gcp_contacts', $setters, 
                array('gcid' => $id));
                foreach ($sqls as $sql) {
                    $this->db->query($sql);
                }
                $contact->set_modified_at($setters['modified']);
                return SUCCESS;
                break;
        }
        return FAIL;
    }
    
    /**
     * 根据标签更新联系人
     * @param int $ids 群联系人ID
     * @param string $tag_name 标签名(例如deleted)
     * @param string $tag_value 标签值
     */
    public function update_by_tags ($ids, $tag_name, $tag_value)
    {
        $this->db->escape($tag_value);
        $query = $this->db->query(
        "UPDATE `gcp_contacts` SET {$tag_name} = {$tag_value}, modified = '" . time() .
         "' WHERE `gcid` IN (" . implode(',', $ids) . ")");
        if ($query) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    /**
     * 删除群联系人
     * @param array $ids 群联系人ID
     */
    public function delete ($ids)
    {
        foreach (array('gcp_contacts', 'gcp_avatars', 
        'gcp_tels', 'gcp_emails', 'gcp_urls', 'gcp_addresses', 
        'gcp_ims', 'gcp_events', 'gcp_relations') as $table) {
            $this->db->query(
            "DELETE FROM {$table} WHERE gcid IN (" . implode(',', $ids) . ")");
        }
    }

    /**
     * 获取联系人头像
     * @param int $id 联系人ID
     * @return string
     */
    private function _get_avatar ($id)
    {
        $query = $this->db->query(
        "SELECT avatar FROM `gcp_avatars` WHERE `gcid` = '$id' LIMIT 1");
        if ($query->count()) {
            $result = $query->result_array(FALSE);
            return $result[0]['avatar'];
        } else {
            return Kohana::config('contact.avatar');
        }
    }

    /**
     * 修改联系人头像
     * @param int $group_id 用户ID
     * @param int $id 联系人ID
     * @param string $avatar 头像地址
     * @param int $space 是否空间头像
     */
    private function _edit_avatar ($group_id, $id, $avatar)
    {
        $sql = array();
        if (! empty($avatar) && $avatar != Kohana::config('contact.avatar')) {
            $query = $this->db->query(
            "SELECT * FROM `gcp_avatars` WHERE `gcid` = '$id' LIMIT 1");
            $dateline = time();
            if ($query->count()) {
                $sql[] = "UPDATE `gcp_avatars` SET avatar = '$avatar', `space` = '0',
                `dateline` = $dateline WHERE `gcid` = '$id';";
            } else {
                $sql[] = "INSERT INTO `gcp_avatars` (`gid`, `gcid`, `avatar`, `space`, `dateline`)
                VALUES('$group_id', '$id', '$avatar', '0', $dateline);";
            }
        }
        return $sql;
    }

    /**
     * 更新联系人修改时间
     * @param array $ids 联系人ID数组
     */
    private function _update_modified ($ids)
    {
        $ids = (array) $ids;
        if (! empty($ids)) {
            $query = $this->db->query(
            "UPDATE `gcp_contacts` SET `modified` = " . time() . " WHERE `gcid` IN (" .
             implode(',', $ids) . ")");
        }
    }

    /**
     * 添加电话、邮箱、网址、纪念日、关系、地址等信息
     * @param int $group_id 用户ID
     * @param int $id 联系人分组ID
     * @param int $type 类型
     * @param array $emails 邮箱信息
     * @return array
     */
    private function _add_info ($group_id, $id, $type = 'emails', $values, $pref_tel = '')
    {
        $sql = array();
        switch ($type) {
            case 'tels':
                if (! empty($values)) {
                    foreach ($values as $tel) {
                        if (! empty($tel['value'])) {
                        	if ($pref_tel and ($tel['value'] == $pref_tel or $tel['pref'] == 1)) {
                                continue;
                            }
                            if ($tel['pref'] == 1) {
                                $pref_tel = $tel['value'];
                            }
                            $tel['type'] = $this->db->escape($tel['type']);
                            $tel['value'] = $this->db->escape($tel['value']);
                            $tel['city'] = $this->db->escape($tel['city']);
                            $sql[] = "INSERT INTO `gcp_tels` (`gid`, `gcid`, `type`, `value`," .
                             " `pref`, `city`) VALUES ('$group_id', '$id', {$tel['type']} ,{$tel['value']}," .
                             " {$tel['pref']}, {$tel['city']});";
                        }
                    }
                }
                break;
            case 'addresses':
                if (! empty($values)) {
                    foreach ($values as $address) {
                        if (! empty($address['country']) ||
                         ! empty($address['region']) || ! empty(
                        $address['city']) || ! empty($address['street']) ||
                         ! empty($address['postal'])) {
                            $address['country'] = $this->db->escape(
                            $address['country']);
                            $address['region'] = $this->db->escape(
                            $address['region']);
                            $address['city'] = $this->db->escape(
                            $address['city']);
                            $address['street'] = $this->db->escape(
                            $address['street']);
                            $address['postal'] = $this->db->escape(
                            $address['postal']);
                            $address['type'] = $this->db->escape(
                            $address['type']);
                            $sql[] = "INSERT INTO `gcp_addresses` (`gid`, `gcid`, `type`, `country`," .
                             " `postal`, `region`, `city`, `street`) VALUES ('$group_id', '$id', " .
                             $address['type'] . "," . $address['country'] . ", " .
                             $address['postal'] . ", " . $address['region'] .
                             ", " . $address['city'] . ", " . $address['street'] .
                             ");";
                        }
                    }
                }
                break;
            case 'ims':
                if (! empty($values)) {
                    foreach ($values as $im) {
                        if (! empty($im)) {
                            if (in_array(strtolower($im['protocol']), 
                            Kohana::config('contact.protocol'), TRUE)) {
                                $im['value'] = $this->db->escape($im['value']);
                                $im['type'] = $this->db->escape($im['type']);
                                $im['protocol'] = $this->db->escape($im['protocol']);
                                $sql[] = "INSERT INTO `gcp_ims` (`gid`, `gcid`, `protocol`, `type`, `value`)
                            VALUES ('$group_id', '$id', {$im['protocol']}, {$im['type']}, {$im['value']});";
                            }
                        }
                    }
                }
                break;
            default:
                if (! empty($values)) {
                    foreach ($values as $value) {
                        if (! empty($value['value'])) {
                            $value['type'] = $this->db->escape($value['type']);
                            $value['value'] = $this->db->escape($value['value']);
                            $sql[] = "INSERT INTO `gcp_{$type}` (`gid`, `gcid`, `type`, `value`)
                        VALUES ('$group_id', '$id', {$value['type']}, {$value['value']});";
                        }
                    }
                }
                break;
        }
        return $sql;
    }

    /**
     * 获取联系信息列表
     * @param int $id 联系人ID
     * @return array
     */
    private function _get_info_list ($id, $type = 'emails')
    {
        switch ($type) {
            case 'tels':
                $row = '`type`, `value`, `pref`, `city`';
                break;
            case 'addresses':
                $row = '`type`, `country`, `postal`, `region`, `city`, `street`';
                break;
            case 'ims':
                $row = '`protocol`, `type`, `value`';
                break;
            default:
                $row = '`type`, `value`';
                break;
        }
        $query = $this->db->query(
        "SELECT {$row} FROM `gcp_{$type}` WHERE `gcid` = '$id' ORDER BY `id` ASC");
        return $query->result_array(FALSE);
    }
    
    /**
     * 修改电话、邮箱、网址、纪念日、关系、即时通讯、关系信息
     * @param int $user_id 用户ID
     * @param int $id 联系人分组ID
     * @param int $type 类型
     * @param array $emails 邮箱信息
     * @return array
     */
    private function _edit_info ($group_id, $id, $type = 'emails', $values = array())
    {
        $sql = array();
        $pref_tel = '';
        switch ($type) {
            case 'tels':
                $sql[] = "DELETE FROM `gcp_{$type}` WHERE `gid` = '$group_id' AND `gcid`
                 = '$id' AND pref != 1;";
                $pref_tel = $this->get_main_tel($id, TRUE);
                break;
            default:
                $sql[] = "DELETE FROM `gcp_{$type}` WHERE `gid` = '$group_id' AND `gcid`
                 = '$id';";
                break;
        }
        $sql = array_merge($sql, 
        $this->_add_info($group_id, $id, $type, $values, $pref_tel));
        return $sql;
    }
}
