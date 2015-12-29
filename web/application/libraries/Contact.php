<?php
defined('SYSPATH') or die('No direct access allowed.');
/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * 联系人信息库文件
 */
/**
 * 联系人实体类
 */
class Contact {

	/**
	 * @var array 数据
	 */
	protected $_values = array();
	/**
	 * 属性数组
	 * @var array
	 */
	public static $allowed_fields = array(
		'id',
		'user_id',
		'formatted_name',
		'family_name',
		'given_name',
		'middle_name',
		'prefix',
		'suffix',
		'nickname',
		'phonetic',
		'sort',
		'birthday',
		'avatar',
		'organization',
		'department',
		'title',
		'note',
		'category',
		'created_at',
		'modified_at',
		'source',
		'tels',
		'emails',
		'addresses',
		'urls',
		'ims',
		'events',
		'relations',
		'customs'
	);

	public static $list_fields = array(
		'cid',
		'modified',
		'formatted_name',
		'sort',
		'phonetic',
		'source',
		'avatar'
	);

	public static $map_fields = array(
		'cid'      => 'id',
		'uid'      => 'user_id',
		'created'  => 'created_at',
		'modified' => 'modified_at'
	);

	public static $allow_cols = array(
		'tels'      => array('type', 'value', 'pref', 'city', 'search'),
		'addresses' => array('type', 'country', 'postal', 'region', 'city', 'street'),
		'ims'       => array('protocol', 'type', 'value'),
		'emails'    => array('type', 'value'),
		'urls'      => array('type', 'value'),
		'events'    => array('type', 'value'),
		'relations' => array('type', 'value'),
		'customs'   => array('type', 'value')
	);

	/**
	 * 头像是否存在
	 */
	private $_is_avatar_exist = TRUE;

	public function set_avatar_exist($is_exist = TRUE)
	{
		$this->_is_avatar_exist = $is_exist;
	}

	public function get_avatar_exist()
	{
		return $this->_is_avatar_exist;
	}

	/**
	 * 构造方法
	 * @param array $data 联系人数据
	 */
	public function __construct($data = array())
	{
		$map_fields_key = array_keys(self::$map_fields);
		foreach ($data as $name => $value)
		{
			if (in_array($name, $map_fields_key))
			{
				$name = self::$map_fields[$name];
			}
			call_user_func(array($this, 'set_' . $name), $value);
		}
		//设置默认值
		$this->_values += array(
			'id'             => 0,
			'user_id'        => 0,
			'formatted_name' => '',
			'family_name'    => '',
			'given_name'     => '',
			'middle_name'    => '',
			'prefix'         => '',
			'suffix'         => '',
			'nickname'       => '',
			'phonetic'       => '',
			'sort'           => '',
			'birthday'       => '',
			'avatar'         => '',
			'organization'   => '',
			'department'     => '',
			'title'          => '',
			'note'           => '',
			'category'       => '',
			'created_at'     => 0,
			'modified_at'    => 0,
			'source'         => 0,
			'tels'           => array(),
			'emails'         => array(),
			'addresses'      => array(),
			'urls'           => array(),
			'ims'            => array(),
			'events'         => array(),
			'relations'      => array(),
			'customs'        => array()
		);
		unset($map_fields_key);
	}

	/**
	 * 获取联系人列表主要字段名
	 * @static
	 * @param bool $is_map 是否需要映射
	 * @param bool $is_recycled 是否在回收站
	 * @return array
	 */
	public static function get_list_fields($is_map = FALSE, $is_recycled = FALSE)
	{
		$result = self::$list_fields;
		if ($is_map === FALSE)
		{
			foreach ($result as $key => $val)
			{
				if (isset(self::$map_fields[$val]))
				{
					$result[$key] = self::$map_fields[$val];
				}
			}
		}
		// 针对不同情况增加字段
		if ($is_recycled)
		{
			if ($is_map)
			{
				$result = array_merge($result, array('operation', 'recycled_id'));
			}
			else
			{
				$result = array_merge($result, array('operation', 'tels'));
			}
		}
		else
		{
			$result = array_merge($result, array('category'));
		}
		return $result;
	}

	/**
	 * 设置电话
	 * @param array $value 值
	 * @return Contact
	 */
	public function set_tels($value)
	{
		foreach ($value as $key => $val)
		{
			$value[$key]['pref'] = (bool) $val['pref'];
		}
		$this->_values['tels'] = $value;
		return $this;
	}

	/**
	 * 设置联系人ID
	 * @param int $value 联系人ID
	 * @return Contact

	 */
	public function set_id($value)
	{
		$this->_values['id'] = (int) $value;
		return $this;
	}

	/**
	 * 设置用户ID
	 * @param string $value 用户ID
	 * @return Contact
	 */
	public function set_user_id($value)
	{
		$this->_values['user_id'] = (int) $value;
		return $this;
	}

	/**
	 * 设置数据来源
	 * @param string $value 数据来源
	 * @return Contact
	 */
	public function set_source($value)
	{
		$this->_values['source'] = $value;
		return $this;
	}

	/**
	 * 设置修改时间
	 * @param string $value 修改时间
	 * @return Contact
	 */
	public function set_modified_at($value)
	{
		$this->_values['modified_at'] = (int) $value;
		return $this;
	}

	/**
	 * 设置创建时间
	 * @param string $value 创建时间
	 * @return Contact
	 */
	public function set_created_at($value)
	{
		$this->_values['created_at'] = (int) $value;
		return $this;
	}

	/**
	 * 把联系人对象转为联系人简单数组
	 * @return array
	 */
	public function to_simple_array()
	{
		return array(
			'id'          => $this->_values['id'],
			'modified_at' => $this->_values['modified_at']
		);
	}

	/**
	 * 获取主要字段
	 * @static
	 * @return array
	 */
	public static function get_main_fields()
	{
		$result = array();
		$allow_cols_key = array_keys(self::$allow_cols);
		foreach (self::$allowed_fields as $value)
		{
			if (! in_array($value, $allow_cols_key))
			{
				if (in_array($value, self::$map_fields))
				{
					$value = array_search($value, self::$map_fields);
				}
				if ($value != 'category')
				{
					$result[] = $value;
				}
			}
		}
		return $result;
	}

	/**
	 * 获取主要信息
	 * @return array
	 */
	public function get_main_info()
	{
		$result = array();
		$allow_cols_key = array_keys(self::$allow_cols);
		foreach ($this->_values as $key => $value)
		{
			if (! in_array($key, $allow_cols_key))
			{
				if (in_array($key, self::$map_fields))
				{
					$key = array_search($key, self::$map_fields);
				}
				$result[$key] = $value;
			}
		}
		return $result;
	}

	/**
	 * 获取联系方式
	 * @return array
	 */
	public function get_more_info()
	{
		$result = array();
		$allow_cols_key = array_keys(self::$allow_cols);
		foreach ($this->_values as $key => $value)
		{
			if (in_array($key, $allow_cols_key))
			{
				$result[$key] = $value;
			}
		}
		return $result;
	}

	/**
	 * 比较联系人是否相同
	 * @param Contact $contact
	 * @return bool
	 */
	public function compare(Contact $contact)
	{
		$curr = $this->to_array();
		$new = $contact->to_array();
		//忽略创建时间、修改时间、数据源
		unset($curr['created_at'], $curr['modified_at'],
		$curr['source'], $new['created_at'], $new['modified_at'], $new['source']);
		if ($curr == $new)
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * 比较增量模式联系人是否相同
	 * @param Contact $contact
	 * @return bool
	 */
	public function compare_special(Contact $contact)
	{
		$curr = $this->to_array();
		$new = $contact->to_array();
		//忽略创建时间、修改时间、数据源
		unset($curr['created_at'], $curr['modified_at'],
		$curr['source'], $new['created_at'], $new['modified_at'], $new['source']);
		foreach ($new as $key => $val)
		{
			if (! empty($val))
			{
				if (! in_array($key, array_keys(self::$allow_cols, TRUE)))
				{
					if ($curr[$key] != $val)
					{
						return FALSE;
					}
				}
				else
				{
					foreach ($val as $v)
					{
						if (! in_array($v, $curr[$key]))
						{
							return FALSE;
						}
					}
				}
			}
		}
		return TRUE;
	}

	/**
	 * 合并联系人
	 * @param Contact $contact 联系人对象
	 * @param bool    $is_append 是否新增
	 * @return bool 是否成功
	 */
	public function merge(Contact $contact, &$is_append)
	{
		$curr_array = $this->to_array();
		$new_array = $contact->to_array();
		$check_arr = array(
			'formatted_name',
			'organization',
			'department',
			'title',
			'birthday',
			'nickname',
			'avatar'
		);
		foreach ($check_arr as $key)
		{
			$curr = $curr_array[$key];
			$new = $new_array[$key];
			if ($key == 'avatar')
			{
				do
				{
					//其中一个头像为空或相同不冲突
					if (empty($curr) or empty($new) or
						$curr == $new
					)
					{
						break;
					}
					//其中一个是默认头像
					if (
						strrpos($new, Kohana::config('contact.avatar')) !== FALSE
						or
						strrpos($curr, Kohana::config('contact.avatar')) !== FALSE
					)
					{
						break;
					}
					list ($new_md5, $curr_md5) = Photo_Controller::getoriginmd5(
						array(
						     $new,
						     $curr
						));
					if ($curr_md5 != $new_md5)
					{
						return FALSE;
					}
				} while (FALSE);
			}
			elseif ($key == 'birthday')
			{
				/*
				//忽略年份是1900
				if (substr($curr, 0, 4) == 1900 or
					substr($new, 0, 4) == 1900
				)
				{
					$curr = substr($curr, 4);
					$new = substr($new, 4);
				}
				*/
				if (! empty($curr) AND ! empty($new) AND $curr != $new)
				{
					return FALSE;
				}
			}
			else
			{
				if (! empty($curr) AND ! empty($new) AND $curr != $new)
				{
					return FALSE;
				}
			}
		}
		foreach (self::$allowed_fields as $field)
		{
			$curr = $curr_array[$field];
			$new = $new_array[$field];
			$setter = 'set_' . $field;
			switch ($field)
			{
				case 'id':
				case 'source':
				case 'user_id': //不比较
					break;
				case 'tels':
					if (! empty($new))
					{
						$tmp = array();
						foreach ($curr as $val)
						{
							$md5 = ! empty($val['search']) ? md5(strtolower($val['type']) . $val['search'])
								: md5(strtolower($val['type']) . $val['value']);
							$tmp[] = $md5;
						}
						foreach ($new as $val)
						{
							$md5 = ! empty($val['search']) ? md5(strtolower($val['type']) . $val['search'])
								: md5(strtolower($val['type']) . $val['value']);
							if ($val['value'] AND ! in_array($md5, $tmp))
							{
								$is_append = TRUE;
								$curr[] = $val;
								$tmp[] = $md5;
							}
						}
						call_user_func(array(
						                    $this,
						                    $setter
						               ), $curr);

					}
					break;
				case 'emails':
				case 'urls':
				case 'ims':
				case 'events':
				case 'relations':
				case 'customs':
					if (! empty($new))
					{
						$tmp = array();
						foreach ($curr as $val)
						{
							$tmp[] = md5(strtolower($val['type']) . $val['value']);
						}
						foreach ($new as $val)
						{
							$md5 = md5(strtolower($val['type']) . $val['value']);
							if ($val['value'] AND ! in_array($md5, $tmp))
							{
								$is_append = TRUE;
								$curr[] = $val;
								$tmp[] = $md5;
							}
						}
						call_user_func(array(
						                    $this,
						                    $setter
						               ), $curr);

					}
					break;
				case 'ims':
					if (! empty($new))
					{
						$tmp = array();
						foreach ($curr as $val)
						{
							$tmp[] = md5(strtolower($val['protocol']) . strtolower($val['type']) . $val['value']);
						}
						foreach ($new as $val)
						{
							$md5 = md5(strtolower($val['protocol']) . strtolower($val['type']) . $val['value']);
							if ($val['protocol'] AND $val['value'] AND ! in_array($md5, $tmp))
							{
								$is_append = TRUE;
								$curr[] = $val;
								$tmp[] = $md5;
							}
						}
						call_user_func(array(
						                    $this,
						                    $setter
						               ), $curr);
					}
					break;
				case 'addresses':
					if (! empty($new))
					{
						$tmp = array();
						foreach ($curr as $val)
						{
							$tmp[] = md5(strtolower($val['type']) . $val['country'] . $val['region'] .
								$val['city'] . $val['street'] .
								$val['postal']);
						}
						foreach ($new as $val)
						{
							$md5 = md5(strtolower($val['type']) . $val['country'] . $val['region'] .
								$val['city'] . $val['street'] .
								$val['postal']);
							if (! in_array($md5, $tmp))
							{
								$is_append = TRUE;
								$curr[] = $val;
								$tmp[] = $md5;
							}
						}
						call_user_func(array(
						                    $this,
						                    $setter
						               ), $curr);
					}
					break;
				case 'avatar':
					if (empty($curr) or
						strrpos($curr, Kohana::config('contact.avatar')) !== FALSE
					)
					{
						if (! empty($new) AND $curr != $new)
						{
							$is_append = TRUE;
							call_user_func(
								array(
								     $this,
								     $setter
								), $new);
						}
					}
					break;
				case 'note':
					if ($curr == $new)
					{
						continue;
					}
					elseif (! empty($new) AND ! in_array($new,
						explode(' ', $curr))
					)
					{
						$is_append = TRUE;
						call_user_func(array(
						                    $this,
						                    $setter
						               ), $curr . ' ' . $new);
					}
					break;
				case 'category':
					$curr_tmp = array_unique(array_filter(explode(',', $curr)));
					$new_tmp = array_unique(array_filter(explode(',', $new)));
					if (! empty($new_tmp) AND array_diff($new_tmp, $curr_tmp))
					{
						$is_append = TRUE;
						call_user_func(array(
						                    $this,
						                    $setter
						               ), implode(',', array_unique(array_merge($curr_tmp, $new_tmp))));
					}
					break;

				case 'prefix':
				case 'suffix':
				case 'given_name':
				case 'middle_name':
				case 'family_name':
					$formatted_name = $curr_array['formatted_name'];
					if (empty($formatted_name))
					{
						$is_append = TRUE;
						call_user_func(array(
						                    $this,
						                    $setter
						               ), $new);
					}
					break;
				default:
					if (empty($curr) AND ! empty($new))
					{
						$is_append = TRUE;
						call_user_func(array(
						                    $this,
						                    $setter
						               ), $new);
					}
					break;
			}
		}
		return TRUE;
	}

	/**
	 * 设置字段的值
	 * @param string $name 字段
	 * @param string $value 值
	 * @throws Contact_Exception
	 * @return Contact
	 */
	public function set($name, $value)
	{
		if (! in_array($name, self::$allowed_fields))
		{
			//字段不存在直接返回
			return $this;
			//throw new Contact_Exception('The field '.$name.' is not allowed for this entity.');
		}
		$setter = 'set_' . $name;
		if (method_exists($this, $setter) && is_callable(array($this, $setter)))
		{
			$this->$setter($value);
		}
		else
		{
			$this->_values[$name] = $value;
		}
		return $this;
	}

	/**
	 * 获取字段值
	 * @param string $name 字段
	 * @throws Contact_Exception
	 * @return mixed
	 */
	public function get($name)
	{
		if (! in_array($name, self::$allowed_fields))
		{
			throw new Contact_Exception('The field ' . $name . ' is not allowed for this entity.');
		}
		$getter = 'get_' . $name;
		if (method_exists($this, $getter) && is_callable(array($this, $getter)))
		{
			return $this->$getter;
		}
		if (isset($this->_values[$name]))
		{
			return $this->_values[$name];
		}
		throw new Contact_Exception('The field ' . $name . ' is not set for this entity.');
	}

	/**
	 * 转换成数组
	 * @return array
	 */
	public function to_array()
	{
		return $this->_values;
	}

	/**
	 * 设置或获取属性
	 * @param string $name 方法名
	 * @param string $args 参数
	 * @throws Contact_Exception
	 * @return mixed
	 */
	public function __call($name, $args)
	{
		$prefix = substr($name, 0, 4);
		$prop = substr($name, 4);
		if (($prefix == 'get_' or $prefix = 'set_'))
		{
			if ('get_' == $prefix)
			{
				return $this->get($prop);
			}
			else
			{
				return $this->set($prop, $args[0]);
			}
		}
		throw new Contact_Exception('The function ' . $name . ' is not defined for this entity.');
	}

	/**
	 * 获取分组字段
	 * @static
	 * @return array
	 */
	public static function get_contact_categories_fields()
	{
		return array(
			'id',
			'uid',
			'category_name',
			'order_by'
		);
	}

	/**
	 * 获取分组字段
	 * @static
	 * @return array
	 */
	public static function get_contact_classes_fields()
	{
		return array(
			'category_id',
			'uid',
			'cid'
		);
	}

} // End Contact

/**
 * 设置实体异常
 */
class Contact_Exception extends Exception {

	protected $code = '400215';
} // End Contact_Exception

