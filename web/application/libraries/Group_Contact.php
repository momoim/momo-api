<?php
defined('SYSPATH') or die('No direct access allowed.');

/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * 群联系人信息库文件
 */
/**
 * 群联系人类
 */
class Group_Contact
{
    /**
     * 联系人ID
     * @var int
     */
    protected $id = 0;
    /**
     * 群ID
     * @var int
     */
    protected $group_id = 0;
    /**
     * 用户ID
     * @var int
     */
    protected $user_id = 0;
    /**
     * 全名
     * @var string
     */
    protected $formatted_name = '';
    /**
     * 姓
     * @var string
     */
    protected $family_name = '';
    /**
     * 名
     * @var string
     */
    protected $given_name = '';
    /**
     * 中间名
     * @var string
     */
    protected $middle_name = '';
    /**
     * 前缀
     * @var string
     */
    protected $prefix = '';
    /**
     * 后缀
     * @var string
     */
    protected $suffix = '';
    /**
     * 昵称
     * @var string
     */
    protected $nickname = '';
    /**
     * 拼音
     * @var string
     */
    protected $phonetic = '';
    /**
     * 简拼
     * @var string
     */
    protected $sort = '';
    /**
     * 生日
     * @var string
     */
    protected $birthday = '';
    /**
     * 头像
     * @var string
     */
    protected $avatar = '';
    /**
     * 公司
     * @var string
     */
    protected $organization = '';
    /**
     * 部门
     * @var string
     */
    protected $department = '';
    /**
     * 职位
     * @var string
     */
    protected $title = '';
    /**
     * 备注
     * @var string
     */
    protected $note = '';
    /**
     * 好友ID
     * @var int
     */
    protected $momo_user_id = 0;
    /**
     * 是否在删除
     * @var int
     */
    protected $deleted = 0;
    /**
     * 创建时间戳
     * @var string
     */
    protected $created_at = 0;
    /**
     * 修改时间戳
     * @var string
     */
    protected $modified_at = 0;
    /**
     * 电话
     * @var array
     */
    protected $tels = array();
    /**
     * 邮箱
     * @var array
     */
    protected $emails = array();
    /**
     * 地址
     * @var array
     */
    protected $addresses = array();
    /**
     * 网址
     * @var array
     */
    protected $urls = array();
    /**
     * 即时通讯
     * @var array
     */
    protected $ims = array();
    /**
     * 纪念日
     * @var array
     */
    protected $events = array();
    /**
     * 关系
     * @var array
     */
    protected $relations = array();
    /**
     * 变量数组
     * @var array
     */
    protected static $keys = array();

    public function __construct ()
    {}

    /**
     * 设置群联系人ID
     * @param int $id 群联系人ID
     * @return Group_Contact $this
     */
    public function set_id ($id)
    {
        if (empty($this->id)) {
            $this->id = $id;
        }
        return $this;
    }

    /**
     * 设置或获取属性值
     * @param string $name 方法名
     * @param array $args 参数
     * @throws Group_Contact_Input_Exception
     * @return mixed
     */
    public function __call ($name, $args)
    {
        $prefix = substr($name, 0, 4);
        $prop = substr($name, 4);
        if (($prefix == 'get_' || $prefix = 'set_') &&
         $attribute = $this->validate_attribute($prop)) {
            if ('get_' == $prefix) {
                return $this->$attribute;
            } else {
                $this->$attribute = $args[0];
                return $this;
            }
        } else {
            throw new Group_Contact_Input_Exception(
            'Call to undefined method Group_Contact::' . $name . '()');
        }
    }

    /**
     * 验证属性是否存在
     * @param string $name 属性名
     * @return 返回属性名
     */
    protected function validate_attribute ($name)
    {
        if (empty(self::$keys)) {
            self::$keys = array_keys(get_class_vars(get_class($this)));
        }
        if (in_array($name, self::$keys)) {
            return $name;
        }
    }

    /**
     * 把联系人对象转为联系人数组
     * @return array
     */
    public function to_array ()
    {
        $result = array();
        foreach (simplexml_load_file(APPPATH . 'config/group_contact.xml') as $field) {
            $accessor = (string) $field->accessor;
            if ($accessor) {
                if (substr($accessor, - 2) == 'id') {
                    $result[str_replace('get_', '', $accessor)] = (int) call_user_func(
                    array($this, $accessor));
                } elseif (substr($accessor, - 2) == 'at' and
                 $accessor != 'get_created_at') {
                    $value = call_user_func(array($this, $accessor));
                    $result[str_replace('get_', '', $accessor)] = $value ? $value : '';
                } elseif ($accessor == 'get_tels') {
                    $tels = call_user_func(array($this, $accessor));
                    if (! empty($tels)) {
                        foreach ($tels as $key => $tel) {
                            $tels[$key]['pref'] = (bool) $tel['pref'];
                        }
                    }
                    $result[str_replace('get_', '', $accessor)] = $tels;
                } else {
                    $result[str_replace('get_', '', $accessor)] = call_user_func(
                    array($this, $accessor));
                }
            }
        }
        return $result;
    }

    /**
     * 把联系人对象转为联系人简单数组
     * @return array
     */
    public function to_simple_array ()
    {
        $result = array();
        foreach (array('get_id', 'get_modified_at') as $accessor) {
            if (substr($accessor, - 2) == 'id') {
                $result[str_replace('get_', '', $accessor)] = (int) call_user_func(
                array($this, $accessor));
            } elseif (substr($accessor, - 2) == 'at') {
                $value = call_user_func(array($this, $accessor));
                $result[str_replace('get_', '', $accessor)] = $value ? $value : '';
            }
        }
        return $result;
    }

    /**
     * 合并联系人对象
     * @todo 未完成
     * @param Group_Contact $contact
     */
    public function merge (Group_Contact $contact)
    {
    	$check_arr = array('get_user_id', 'get_organization',
        'get_department', 'get_title', 'get_birthday', 'get_nickname', 
        'get_avatar', 'get_formatted_name');
    	foreach ($check_arr as $accessor) {
    		$curr = call_user_func(array($this, $accessor));
    		$new = call_user_func(array($contact, $accessor));
    		if ($accessor == 'get_avatar') {
    			if (! empty($new) and $new != $curr and ($new !=
    			Kohana::config('contact.avatar') or $curr !=
    			Kohana::config('contact.avatar'))) {
    				return FALSE;
    			}
    		} elseif ($accessor == 'get_user_id') {
    			if ($curr > 0 and $new > 0 and $curr != $new) {
    				return FALSE;
    			}
    		}  else {
    			if (! empty($curr) and ! empty($new) and $curr != $new) {
    				return FALSE;
    			}
    		}
    	}
    	foreach (simplexml_load_file(APPPATH . 'config/group_contact.xml') as $field) {
    		$accessor = (string) $field->accessor;
    		$name = (string) $field->name;
    		$curr = call_user_func(array($this, $accessor));
    		$mutator = (string) $field->mutator;
    		if (in_array($name,
    		array('emails', 'urls', 'ims', 'addresses', 'events', 'relations'))) {
    			$new = call_user_func(array($contact, $accessor));
    			if (! empty($new)) {
    				foreach ($new as $val) {
    					if (! in_array($val, $curr)) {
    						$curr[] = $val;
    					}
    				}
    				call_user_func(array($this, $mutator), $curr);
    			}
    		} elseif ($name == 'tels') {
    			$new = call_user_func(array($contact, $accessor));
    			$tels = array();
    			foreach ($curr as $val) {
    				$tels[] = array('type' => $val['type'],
                    'value' => $val['value']);
    			}
    			if (! empty($new)) {
    				foreach ($new as $val) {
    					$tmp = array('type' => $val['type'],
	                            'value' => $val['value']);
    					if (! in_array($tmp, $tels)) {
    						$curr[] = $val;
    						$tels[] = $tmp;
    					}
    				}
    				call_user_func(array($this, $mutator), $curr);
    			}

    		} elseif ($name == 'avatar' and
    		(empty($curr) or $curr == Kohana::config('contact.avatar'))) {
    			$new = call_user_func(array($contact, $accessor));
    			if (! empty($new) and $curr != $new) {
    				call_user_func(array($this, $mutator), $new);
    			}

    		} elseif ($name == 'note') {
    			$new = call_user_func(array($contact, $accessor));
    			if (!empty($new) and !in_array($new, explode(' ', $curr))) {
    				call_user_func(array($this, $mutator), $curr . ' ' . $new);
    			}

    		} elseif (in_array($name,
    		array('prefix', 'suffix', 'given_name', 'middle_name',
            'family_name'))) {
    		$new = call_user_func(array($contact, $accessor));
    		$formatted_name = call_user_func(
    		array($this, 'get_formatted_name'));
    		if (empty($formatted_name)) {
    			call_user_func(array($this, $mutator), $new);
    		}
            } else {
            	$new = call_user_func(array($contact, $accessor));
            	if (empty($curr) and ! empty($new)) {
            		call_user_func(array($this, $mutator), $new);
            	}
            }
    	}
        return TRUE;
    }
}

/**
 * 设置分组联系人输入异常
 */
class Group_Contact_Input_Exception extends Exception
{
    protected $code = '400315';
} // End Kohana Database Exception
