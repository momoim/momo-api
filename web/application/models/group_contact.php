<?php
defined('SYSPATH') or die('No direct script access.');

/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * 联系人模型文件
 */
/**
 * 联系人模型
 */
class Group_Contact_Model extends Model
{
    /**
     * 联系人数据映射
     * @var Contact_Mapper
     */
    protected $group_contact_mapper;
    /**
     * 缓存
     * @var Cache
     */
    protected $cache;
    /**
     * 缓存前缀
     * @var string
     */
    protected $prefix = 'api_momo_group_contact_';
    /**
     * 实例
     * @var Group_Contact_Model
     */
    protected static $instance;

    /**
     * 单例模式
     * @return Group_Contact_Model
     */
    public static function &instance ()
    {
        if (! isset(self::$instance)) {
            // Create a new instance
            self::$instance = new Group_Contact_Model();
        }
        return self::$instance;
    }

    /**
     * 构造函数,
     * 为了避免循环实例化，请调用单例模式
     */
    public function __construct ()
    {
        parent::__construct();
        $this->cache = Cache::instance();
        $this->group_contact_mapper = Group_Contact_Mapper::instance($this->db);
    }

    /**
     * 从缓存获取联系人列表或联系人详情
     * @param int $group_id 群ID
     * @param int $id 联系人ID
     * @param string $callback id为NULL时，允许设置回调方法
     */
    public function get ($group_id, $id = NULL)
    {
//        if ($id !== NULL) {
//            $result = $this->cache->get($this->prefix . 'find_by_id_' . $id);
//        } else {
//            $result = $this->cache->get(
//            $this->prefix . 'get_list_' . $group_id);
//        }
        //@todo 禁用缓存数据，目前使用缓存会导致接口和网站数据不一致，待统一接口时开启。
        $result = NULL;
        if ($result === NULL) {
            if ($id !== NULL) {
                $result = call_user_func_array(array($this, 'find_by_id'), array($group_id, $id));
                $result = $result->to_array();
//                if ($result !== FALSE) {
//                    $result = $result->to_array();
//                }
//                $this->cache->set($this->prefix . 'find_by_id_' . $id, $result, 
//                NULL, 3600);
            } else {
                $result = call_user_func(array($this, 'get_list'), $group_id);
//                $this->cache->set($this->prefix . 'get_list_' . $group_id, 
//                $result, NULL, 3600);
            }
        }
//        if ($id !== NULL and $result !== FALSE and $group_id != $result['group_id']) {
//            $result = FALSE;
//        }
        return $result;
    }

    /**
     * 根据联系人ID获取联系人
     * @param int $id 联系人ID
     * @return Contact|FALSE
     */
    public function find_by_id ($group_id, $id)
    {
        return $this->group_contact_mapper->find_by_id($group_id, $id);
    }

    /**
     * 获取联系人列表
     * @param int $group_id 群ID
     * @return array
     */
    public function get_list ($group_id)
    {
        $ids = $this->group_contact_mapper->find_by_group_id($group_id);
        return $ids;
    }

    /**
     * 获取系统分组联系人数
     * @param int $group_id 群ID
     * @return array
     */
    public function get_count ($group_id)
    {
        $count = array();
        foreach (array('all', 'friend', 'favorited', 'none', 'recycled') as $type) {
            $count[$type . '_count'] = $this->group_contact_mapper->get_count(
            $group_id, $type);
        }
        return $count;
    }

    /**
     * 根据标签更新联系人
     * @param int $group_id 用户名
     * @param int $id 联系人ID
     * @param string $tag_name 标签名 可用字段recycle、favorite
     * @param string $tag_value 标签值
     * @return array 成功的联系人ID数组
     */
    public function update_by_tag ($group_id, $ids, $tag_name, $tag_value)
    {
        $list = $this->get($group_id);
        $contact_ids = array_keys($list);
        $need_update_ids = $used_ids = array();
        foreach ($ids as $id) {
            if (in_array($id, $contact_ids)) {
                if ($list[$id][$tag_name . 'd'] != $tag_value) {
                    $need_update_ids[] = $id;
                }
                $used_ids[] = $id;
            }
        }
        if (! empty($need_update_ids)) {
            $this->group_contact_mapper->update_by_tags($need_update_ids, $tag_name, 
            $tag_value);
            $this->clear_cache($group_id, $need_update_ids);
        }
        return $used_ids;
    }

    /**
     * 从回收站删除联系人
     * @param int $group_id 用户名
     * @param int $ids 联系人ID
     * @return array 成功的联系人ID数组
     */
    public function delete ($group_id, $ids)
    {
        $list = $this->get($group_id);
        $contact_ids = array_keys($list);
        $need_update_ids = array();
        foreach ($ids as $id) {
            if (in_array($id, $contact_ids) and $list[$id]['recycled'] == 1) {
                $need_update_ids[] = (int) $id;
            }
        }
        if (! empty($need_update_ids)) {
            $this->group_contact_mapper->delete($need_update_ids);
            $this->clear_cache($group_id, $need_update_ids);
        }
        return $need_update_ids;
    }

    /**
     * 把联系人移出分组
     * @param int $group_id 群ID
     * @param int $contact_group_id 联系人分组ID
     */
    public function delete_contact_group ($group_id, $contact_group_id)
    {
        $ids = $this->group_contact_mapper->delete_contact_group($group_id, 
        $contact_group_id);
        $this->clear_cache($group_id, $ids);
    }

    /**
     * 新增联系人
     * @param Contact $contact 联系人对象
     * @param int $force 是否强制新增
     */
    public function add (Group_Contact $contact, $force = 0)
    {
        if ($force) {
            $this->group_contact_mapper->insert($contact);
            return TRUE;
        } else {
            //查重
            $new_contact = $this->_find_duplicate($contact);
            if($new_contact === FALSE) {
            	$this->group_contact_mapper->insert($contact);
                return SUCCESS;
            } else {
            	$new_contact->set_modified_at($contact->get_modified_at());
            	$status = $this->group_contact_mapper->update($contact->get_group_id(), $new_contact, 
                    'overwrite');
            	 return $status;
            }
            return FALSE;
        }
    }

    private function _find_duplicate (Group_Contact $contact)
    {
        /*1.联系人姓名相同，且有一个手机号码相同的，判断为同一个联系人，
        	自动进行合并处理，合并时需要判断好友关系、公司、部门、职位、生日、
       		昵称、头像这几个信息是否有冲突，如果没有冲突则可以合并两个联系人
        */
        $same_name_tel_tag = new Group_Contact();
        $same_name_tel_tag->set_group_id($contact->get_group_id())
            ->set_formatted_name(
        $this->name_to_formatted_name($contact->get_family_name(), 
        $contact->get_given_name()))
            ->set_tels($contact->get_tels());
        $result = $this->group_contact_mapper->find_by_tags($same_name_tel_tag);
        if (! empty($result)) {
            //冲突检测
            foreach ($result as $id) {
                $find_contact = $this->find_by_id($contact->get_group_id(), $id);
                if ($find_contact->merge($contact)) {
                    return $find_contact;
                }
            }
        }
        return FALSE;
        /*
        //2 两个联系人中有Email、IM、电话号码相同，其中一个没有姓名，在其他信息没有冲突情况下，可以合并 
        $same_email_tag = new Contact();
        $same_email_tag->set_user_id($contact->get_user_id())
            ->set_emails($contact->get_emails());
        $result = $this->contact_mapper->find_by_tags($same_email_tag);
        if (! empty($result)) {
            //冲突检测
            foreach ($result as $id) {
                $find_contact = $this->get($contact->get_user_id(), $id);
                if ($find_contact->merge($contact, $is_append)) {
                    return $find_contact;
                }
            }
        }
        
        //3 所有信息完全相同的联系人，可以合并
        $all_same_tag = new Contact();
        $all_same_tag->set_user_id($contact->get_user_id());
        $contact_array = $contact->to_array();
        foreach ($contact_array as $key => $value) {
            if (! in_array($key, array('tels', 'emails', 'ims')) and
             ! empty($value)) {
                call_user_func(array($all_same_tag, 'set_' . $key), $value);
            }
        }
        $result = $this->contact_mapper->find_by_tags($all_same_tag);
        if (! empty($result)) {
            //冲突检测
            foreach ($result as $id) {
                $find_contact = $this->get($contact->get_user_id(), $id);
                if ($contact_array == array_intersect_assoc(
                $find_contact->to_array(), $contact_array)) {
                    return $find_contact;
                }
            }
        }
        return FALSE;
        */
    }

    /**
     * 修改联系人
     * @param Contact $contact
     * @param string $mode
     */
    public function edit ($gid, Contact $contact, $mode = 'default')
    {
        return $this->group_contact_mapper->update($gid, $contact, $mode);
    }

    /**
     * 查询手机号码归属地
     * @param int $tel 手机号码
     * @param bool $type 是否返回运营商
     * @return string 归属地名
     */
    function get_tel_location ($tel, $type = TRUE)
    {
        $tel = preg_replace(
        "/^\+86|^12593|^17951|^17911|^17910|^17909|^10131|^10193|^96531|^193/", 
        '', $tel);
        preg_match(
        "/^(0[1-2])\d{1}\-?\d{0,8}$|^(0[3-9])\d{2}\-?\d{0,8}$|^(13|15|14|18)\d{5,9}$/", 
        $tel, $match);
        if (! empty($match)) {
            if (! empty($match[1])) {
                $checkNum = substr($tel, 0, 3);
            } elseif (! empty($match[2])) {
                $checkNum = substr($tel, 0, 4);
            } elseif (! empty($match[3])) {
                $checkNum = substr($tel, 0, 7);
            } else {
                $checkNum = '';
            }
        } else {
            $checkNum = '';
        }
        if ($checkNum) {
            $sql = sprintf(
            "SELECT cc.name,cpnt.name as `type` FROM contact_phone_number cpn, contact_city cc, " .
             "contact_phone_number_type cpnt WHERE cpn.number = " .
             "(SELECT max(number) from contact_phone_number where number<= %d) " .
             "AND cpn.number > %d - cpn.count AND cpn.contact_city_id = cc.id AND cpn.type_id = cpnt.id", 
            $checkNum, $checkNum);
            $query = $this->db->query($sql);
            if ($query->count()) {
                $result = $query->result_array(FALSE);
                if ($type) {
                    $location = $result[0]['name'] . $result[0]['type'];
                } else {
                    $location = $result[0]['name'];
                }
            } else {
                $location = '';
            }
        } else {
            $location = '';
        }
        return $location;
    }

    /**
     * 检查分组名是否存在
     * @param int $group_id 群ID
     * @param int $contact_group_id 分组ID
     * @return bool
     */
    public function check_contact_group ($group_id, $contact_group_id)
    {
        return $this->contact_group_model->is_my_contact_group($group_id, 
        $contact_group_id);
    }

    /**
     * 清除缓存
     * @param int $group_id 群ID
     * @param int|array $id 分组ID
     */
    public function clear_cache ($group_id, $ids = array())
    {
        $ids = (array) $ids;
        if (! empty($ids)) {
            foreach ($ids as $id) {
                $this->cache->delete($this->prefix . 'find_by_id_' . $id);
            }
        }
        $this->cache->delete($this->prefix . 'get_list_' . $group_id);
    }

    /**
     * 姓名转全名
     * @param array $name 姓名
     * @return string 全名
     */
    public function name_to_formatted_name ($family_name, $given_name)
    {
        $chinese_name = implode('', 
        array_filter(array($family_name, $given_name)));
        if (mb_check_encoding($chinese_name, 'ASCII') === false) {
            return $chinese_name;
        } else {
            return implode(' ', array_filter(array($given_name, $family_name)));
        }
    }

    /**
     * 全名转姓名
     * @param string $formatted_name 全名
     * @return array 姓名
     */
    public function formatted_name_to_name ($formatted_name)
    {
        $data = array('given_name' => '', 'family_name' => '');
        if (! empty($formatted_name)) {
            if (mb_check_encoding($formatted_name, 'ASCII') === false) {
                $bjx_arr = Kohana::config_load('bjx');
                $tmp = array();
                if (strlen($formatted_name) >= 9) {
                    $bjx_arr = array_reverse($bjx_arr);
                }
                if (is_array($bjx_arr)) {
                    foreach ($bjx_arr as $bjx_item) {
                        if (preg_match_all('/^(' . $bjx_item . ')(.*)/is', 
                        $formatted_name, $matches)) {
                            $tmp = array($matches[1][0], $matches[2][0]);
                        }
                    }
                }
                if (! empty($tmp)) {
                    $data['family_name'] = $tmp[0];
                    $data['given_name'] = $tmp[1];
                } else {
                    $data['given_name'] = $formatted_name;
                }
            } else {
                $name_arr = explode(' ', $formatted_name);
                $count = count($name_arr);
                if ($count == 1) {
                    $data['given_name'] = $name_arr[0];
                } else {
                    $data['family_name'] = $name_arr[$count - 1];
                    unset($name_arr[$count - 1]);
                    $data['given_name'] = implode(' ', $name_arr);
                }
            }
        }
        return $data;
    }
    
    /**
     * 获取群组的联系人数量
     * @param int $gid 群组ID
     * @return int
     */
    public function getContactNum($gid)
    {
    	return $this->db->getCount('gcp_contacts', "`gid` = '$gid' AND `deleted` = 0");
    }
    
	/**
     * 更新自己在所有群组的联系人头像
     * @param int $uid 用户ID
     */
    public function updateGroupContactAvatar($uid, $avatarUrl = '')
    {
		$groupModel = new Group_Model();
		$gidList = $groupModel->getUserAllGroupId($uid);
		if($gidList){
			$dateline = time();
			$groupInfo['modify_time'] = $dateline;
			//获取头像新地址
			if(!$avatarUrl){
				$avatarUrl = $this->getSpaceAvatar($uid);
			}
			if (!empty($avatarUrl) && $avatarUrl != Kohana::config('contact.avatar')) {
				foreach($gidList as $value){
					$gid = $value['gid'];
					$gcid = $this->getGroupCidbyGidAndUid($gid, $uid);
					if(!$gcid){
						continue;
					}
					$this->editGroupContactAvatar($gid, $gcid, $avatarUrl, 1);
					$this->_updateGroupContactModified($gid, $gcid, $dateline);
					//对应修改群组的修改时间，提供手机端接口同步使用
					$groupModel->modifyGroup($gid, $groupInfo);
				}
			}
		}
    }
    
	/**
     * 获取联系人头像
     * @param int $cid 联系人ID
     * @param int $fid 好友ID
     * @return string
     */
    public function getSpaceAvatar ($uid)
    {
        $photoModel = new Photo_Model();
        $avatarUrl = $photoModel->getLatestAvatar($uid);
        return empty($avatarUrl) ? Kohana::config(
        'contact.avatar') : $avatarUrl;
    }
    
    /**
     * 修改联系人头像
     * @param int $gid 群组ID
     * @param int $gcid 群组联系人ID
     * @param string $avatarUrl 头像地址
     * @param int $isSpaceAvatar 是否空间头像
     */
    public function editGroupContactAvatar($gid, $gcid, $avatarUrl, $isSpaceAvatar = 0)
    {
		if (!empty($avatarUrl) && $avatarUrl != Kohana::config('contact.avatar')) {
			$query = $this->db->query("SELECT * FROM `gcp_avatars` WHERE `gcid` = '$gcid' LIMIT 1");
			if ($query->count()) {
				$this->db->query(
					"UPDATE `gcp_avatars` SET avatar = '$avatarUrl', `space` = '$isSpaceAvatar' WHERE `gcid` = '$gcid'"
				);
			} else {
				$query = $this->db->query(
					"INSERT INTO `gcp_avatars` (`gid`, `gcid`, `avatar`, `space`)
					VALUES('$gid', '$gcid','$avatarUrl', '$isSpaceAvatar')"
				);
				if (!$query->count()) {
					throw new Contact_Exception('更新头像信息失败');
				}
			}
		}
    }
    
	/**
     * 更新群联系人修改时间
	 * @param int $gid 群ID
     * @param int $gcid 群联系人ID
	 * @param int $dateline 修改时间
     */
    private function _updateGroupContactModified ($gid, $gcid, $dateline)
    {
        $query = $this->db->query("UPDATE `gcp_contacts` SET `modified` = $dateline WHERE `gid` = $gid AND `gcid` = $gcid");
    }
    
    /**
     * 根据群组ID和用户ID获取群组联系人ID
     * @param int $gid 群组ID
     * @param int $uid 用户ID
     * @return int $gcid
     */
    public function getGroupCidbyGidAndUid($gid, $uid)
    {
        $query = $this->db->query("SELECT `gcid` FROM `gcp_contacts` WHERE `gid` = '$gid' AND `uid` = '$uid' LIMIT 1");
        if ($query->count()) {
            $result = $query->result_array(FALSE);
            return $result[0]['gcid'];
        } else {
            return 0;
        }
    }
    
	//用户修改了个人名片，自动完全覆盖对应所有加入的群联系人名片。
	public function updateGroupMemberContact($user_id){
		$groupModel = new Group_Model();
		$gidList = $groupModel->getUserGroupIdList($user_id, Kohana::config('group.type.private'));
		$contactModel = Contact_Model::instance();
		$contact = $contactModel->get_user_info($user_id);
		$dateline = time();
		foreach($gidList as $value){
			$gid = $value['gid'];
			$id = $this->getGroupCidbyGidAndUid($gid, $user_id);
			if($id) {
                $contact->set_id($id)
                    ->set_user_id($user_id)
                    ->set_modified_at($dateline);
                $result = $this->edit($gid, $contact, 'overwrite');
                $groupInfo['modify_time'] = $dateline;
				$groupModel->modifyGroup($gid, $groupInfo);
				unset($groupInfo);
			}

		}

	}

	public function addGroupContactByUserCard($group_id, $user_id, $dateline) {
		$contactModel = Contact_Model::instance();
		$contact = $this->get_user_info($user_id);
		$contact->set_user_id($user_id);
		$contact->set_momo_user_id($user_id);
		$contact->set_modified_at($dateline);
		$contact->set_group_id($group_id);
		$this->add($contact);
	}
	
	/**
     * 根据用户权限获取好友信息
     * @param int $friend_user_id
     * @return Contact
     */
    public function get_user_info ($user_id)
    {
        $user_model = User_Model::instance();
        $result = $user_model->get_user_info($user_id);
        if ($result !== FALSE) {
            $data = $user_model->profile_assembly($result);
            //根据权限获取好友信息
            $array = array();
            foreach ($data as $key => $value) {
                if (in_array($key, 
                array('family_name', 'given_name', 'nickname', 'department', 
                'title'))) {
                    $array[$key] = $value;
                } elseif (in_array($key, 
                array('tels', 'emails', 'ims', 'addresses', 'urls'))) {
                    if (! empty($value)) {
                        foreach ($value as $val) {
                            if (! empty($val['is_master'])) {
                                $array[$key][] = array('type' => $val['type'], 
                                'value' => $val['value'], 
                                'pref' => $val['is_master']);
                            } elseif ($val['is_public']) {
                                $array[$key][] = $val;
                            }
                        }
                    }
                } elseif ($key == 'company') {
                    $array['organization'] = $value;
                }
            }
            if (! empty($data['is_hide_year'])) {
                $data['birthyear'] = 1900;
            }
            if (! empty($data['birthmonth']) && ! empty($data['birthday']) &&
             ! empty($data['birthyear'])) {
                if (strlen($data['birthday']) == 1) {
                    $data['birthday'] = '0' . $data['birthday'];
                }
                if (strlen($data['birthmonth']) == 1) {
                    $data['birthmonth'] = '0' . $data['birthmonth'];
                }
                if (checkdate($data['birthmonth'], $data['birthday'], 
                $data['birthyear'])) {
                    $array['birthday'] = $data['birthyear'] . '-' .
                     $data['birthmonth'] . '-' . $data['birthday'];
                } else {
                    $array['birthday'] = '';
                }
            } else {
                $array['birthday'] = '';
            }
            //获取好友头像
            $array['avatar'] = sns::getavatar($user_id, 130);
            return $this->array_to_Group_contact($array);
        }
        return FALSE;
    }
    
    /**
     * 过滤输入、创建群联系人对象
     * @param array $data 联系人信息
     * @return Group_Contact $contact
     */
    public function array_to_Group_contact ($data)
    {
        $contact = new Group_Contact();
        $location_model = Location_Model::instance();
        $bjx_arr = Kohana::config_load('bjx');
        foreach ($data as $type => $value) {
            switch ($type) {
                case 'tels':
                    if (! empty($value)) {
                        $values = $tmp = array();
                        foreach ($value as $val) {
                            if (! in_array(trim($val['value']), $tmp)) {
                                $tmp[] = trim($val['value']);
                                $values[] = array(
                                'value' => trim($val['value']), 
                                'type' => $val['type'], 
                                'city' => $location_model->get_tel_location(
                                trim($val['value'])), 
                                'pref' => ! empty($val['pref']) ? (int) $val['pref'] : 0);
                            }
                        }
                        call_user_func(array($contact, 'set_' . $type), $values);
                    }
                    break;
                case 'ims':
                    if (! empty($value)) {
                        $values = $tmp = $protocols = array();
                        foreach ($value as $val) {
                            $val['protocol'] = strtolower($val['protocol']);
                            $keys = array_keys($tmp, trim($val['value']));
                            $key = isset($keys[0]) ? $keys[0] : - 1;
                            if ($key < 0 or $protocols[$key] != $val['protocol']) {
                                $tmp[] = trim($val['value']);
                                $protocols[] = $val['protocol'];
                                $values[] = array(
                                'value' => trim($val['value']), 
                                'protocol' => $val['protocol'], 
                                'type' => $val['type']);
                            }
                        }
                        call_user_func(array($contact, 'set_' . $type), $values);
                    }
                    break;
                case 'addresses':
                    if (! empty($value)) {
                        $values = $tmp = array();
                        $t = '';
                        foreach ($value as $val) {
                            $t = trim($val['country']) . '|' .
                             trim($val['region']) . '|' . trim($val['city']) .
                             '|' . trim($val['street']) . '|' .
                             trim($val['postal']);
                            if (! in_array($t, $tmp)) {
                                $values[] = array(
                                'country' => trim($val['country']), 
                                'region' => trim($val['region']), 
                                'city' => trim($val['city']), 
                                'street' => trim($val['street']), 
                                'postal' => trim($val['postal']), 
                                'type' => $val['type']);
                                $tmp[] = $t;
                            }
                        }
                        call_user_func(array($contact, 'set_' . $type), $values);
                    }
                    break;
                case 'emails':
                case 'urls':
                case 'events':
                case 'relations':
                    if (! empty($value)) {
                        $values = $tmp = array();
                        foreach ($value as $val) {
                            if (! in_array(trim($val['value']), $tmp)) {
                                $tmp[] = trim($val['value']);
                                $values[] = array(
                                'value' => trim($val['value']), 
                                'type' => $val['type']);
                            }
                        }
                        call_user_func(array($contact, 'set_' . $type), $values);
                    }
                    break;
                case 'birthday':
                	$contactModel = Contact_Model::instance();
                    call_user_func(array($contact, 'set_' . $type), 
                    ! empty($value) ? $contactModel->_filter_birthday($value) : '');
                    break;
                case 'id':
                    break;
                default:
                    call_user_func(array($contact, 'set_' . $type), 
                    ! empty($value) ? $value : '');
                    break;
            }
        }
        $formatted_name = $this->name_to_formatted_name($data['family_name'], 
        $data['given_name']);
        //拼接后的全名为空，并且输入的全名不是空的，把全名拆分设置
        if (empty($formatted_name) and ! empty($data['formatted_name'])) {
            $name = $this->formatted_name_to_name($data['formatted_name']);
            $contact->set_given_name($name['given_name']);
            $contact->set_family_name($name['family_name']);
        } else {
            $fn = $formatted_name;
        }
        if (! empty($fn)) {
            require_once Kohana::find_file('vendor', 'pinyin/c2p');
            $phonetic = getPinYin($fn, false, ' ');
            $tmp = explode(' ', $phonetic);
            $sort = '';
            if (is_array($tmp)) {
                foreach ($tmp as $t) {
                    $sort .= isset($t[0]) ? $t[0] : '';
                }
            }
            $t = ord($sort[0]);
            if (empty($sort) or $t < 97 or $t > 122) {
                $sort = '#';
            }
            $sort = substr($sort, 0, 20);
            $contact->set_formatted_name($fn);
            $contact->set_phonetic(implode('', $tmp));
            $contact->set_sort($sort);
        } else {
            $contact->set_formatted_name('');
            $contact->set_phonetic('');
            $contact->set_sort('#');
        }
        return $contact;
    }
	
}


