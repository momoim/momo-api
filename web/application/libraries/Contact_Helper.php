<?php
defined('SYSPATH') or die('No direct access allowed.');
/*
 * [MOMO API] (C)1999-2012 ND Inc.
 * 联系人信息库文件
 */
/**
 * 联系人类
 */
class Contact_Helper {

	public static $family_names;

	/**
	 * 姓名转全名
	 * @param string $family_name 姓
	 * @param string $given_name 名
	 * @param string $prefix 前缀
	 * @param string $middle_name 中间明
	 * @param string $suffix 后缀
	 * @return string 全名
	 */
	public static function name_to_formatted_name($family_name, $given_name, $prefix = '', $middle_name = '',
	                                              $suffix = '')
	{
		$chinese_name = implode('',
			array_filter(array(
			                  $family_name, $middle_name, $given_name, $suffix, $prefix
			             )));
		if (mb_check_encoding($chinese_name, 'ASCII') === FALSE)
		{
			return $chinese_name;
		}
		else
		{
			return implode(' ',
				array_filter(array(
				                  $prefix, $given_name, $middle_name, $family_name, $suffix
				             )));
		}
	}

	/**
	 * 全名转姓名
	 * @param string $formatted_name 全名
	 * @return array 姓名
	 */
	public static function formatted_name_to_name($formatted_name)
	{
		$data = array(
			'given_name' => '',
			'family_name' => '',
			'prefix' => '',
			'suffix' => '',
			'middle_name' => ''
		);
		if (! empty($formatted_name))
		{
			if (mb_check_encoding($formatted_name, 'ASCII') === FALSE)
			{
				preg_match_all(
					"/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/",
					$formatted_name, $match);
				if (empty(self::$family_names))
				{
					self::$family_names = Kohana::config_load('bjx');
				}
				if (in_array($match[0][0].$match[0][1], self::$family_names)
					&& mb_strlen($formatted_name, "utf-8") > 2
				)
				{
					$tmp = array(
						array_shift($match[0]).array_shift($match[0]),
						join("", $match[0])
					);
				}
				elseif (in_array($match[0][0], self::$family_names))
				{
					$tmp = array(
						array_shift($match[0]), join("", $match[0])
					);
				}
				if (! empty($tmp))
				{
					$data['family_name'] = $tmp[0];
					$data['given_name'] = $tmp[1];
				}
				else
				{
					$data['given_name'] = $formatted_name;
				}
			}
			else
			{
				$prefixes = array(
					'1st Lt',
					'Adm',
					'Atty',
					'Brother',
					'Capt',
					'Chief',
					'Cmdr',
					'Col',
					'Dean',
					'Dr',
					'Elder',
					'Father',
					'Gen',
					'Gov',
					'Hon',
					'Lt Col',
					'Maj',
					'MSgt',
					'Mr',
					'Mrs',
					'Ms',
					'Prince',
					'Prof',
					'Rabbi',
					'Rev',
					'Sister'
				);

				$suffixes = array(
					'II',
					'III',
					'IV',
					'CPA',
					'DDS',
					'Esq',
					'JD',
					'Jr',
					'LLD',
					'MD',
					'PhD',
					'Ret',
					'RN',
					'Sr',
					'DO'
				);
				if (preg_match("/^".implode('|', $prefixes)."/", $formatted_name, $match))
				{
					$data['prefix'] = $match[0];
					$formatted_name = str_replace($data['prefix'], '', $formatted_name);
				}

				if (preg_match("/".implode('|', $suffixes)."$/", $formatted_name, $match))
				{
					$data['suffix'] = $match[0];
					$formatted_name = str_replace($data['suffix'], '', $formatted_name);
				}

				$names = explode(' ', $formatted_name);
				$count = count($names);
				switch ($count)
				{
					case 3:
						$data['given_name'] = $names[0];
						$data['middle_name'] = $names[1];
						$data['family_name'] = $names[2];
						break;
					case 2:
						$data['given_name'] = $names[0];
						$data['family_name'] = $names[1];
						break;
					case 1:
						$data['given_name'] = $names[0];
						break;
					default:
						$data['family_name'] = $names[$count - 1];
						unset($names[$count - 1]);
						$data['given_name'] = implode(' ', $names);
						break;
				}
			}
		}
		return $data;
	}

	/**
	 * 过滤输入、创建联系人对象
	 * @param array $data 联系人信息
	 * @param int   $default_country_code 默认国家码
	 * @return Contact $contact
	 */
	public static function array_to_contact($data, $default_country_code = 86)
	{
		$result = array();
		$location_model = Location_Model::instance();
		//过滤只读或不支持字段
		unset($data['id'], $data['user_id'], $data['source'], $data['formatted_name']);
		foreach ($data as $type => $value)
		{
			/* @var int|string|array $value*/
			switch ($type)
			{
				//根据值和类型过滤
				case 'tels':
					if (! empty($value))
					{
						$values = $tmp = array();
						foreach ($value as $val)
						{
							$val['type'] = empty($val['type']) ? 'other' : $val['type'];
							$md5 = md5(strtolower($val['type']).$val['value']);
							if (! empty($val['value']) and ! in_array($md5, $tmp))
							{
								$tmp[] = $md5;
								$valid = international::check_mobile($val['value'],
									$default_country_code);
								$values[] = array(
									'value' => $val['value'],
									'type' => $val['type'],
									'city' => $location_model->get_tel_location(
										$val['value']),
									'pref' => ! empty($val['pref']) ? (int) $val['pref'] : 0,
									'search' => ! empty($valid) ? '+'.implode('', $valid) : ''
								);
							}
						}
						$result[$type] = $values;
					}
					break;
				case 'ims':
					if (! empty($value))
					{
						$values = $tmp = array();
						foreach ($value as $val)
						{
							$val['protocol'] = ! empty($val['protocol']) ? $val['protocol'] : '';
							$val['type'] = empty($val['type']) ? 'other' : $val['type'];
							$md5 = md5(strtolower($val['protocol']).strtolower($val['type']).$val['value']);
							if (! empty($val['protocol']) AND ! empty($val['value']) AND ! in_array($md5, $tmp)
							)
							{
								$tmp[] = $md5;
								$values[] = $val;
							}
						}
						$result[$type] = $values;
					}
					break;
				case 'addresses':
					if (! empty($value))
					{
						$values = $tmp = array();
						foreach ($value as $val)
						{
							$val += array(
								'country' => '',
								'region' => '',
								'city' => '',
								'street' => '',
								'postal' => '',
								'type' => 'other'
							);
							$md5 = md5(strtolower($val['type']).$val['country'].$val['region'].
								$val['city'].$val['street'].
								$val['postal']);
							if (($val['country'] or $val['region'] or
								$val['city'] or $val['street'] or $val['postal']) and
								! in_array($md5, $tmp)
							)
							{
								$tmp[] = $md5;
								$values[] = $val;

							}
						}
						$result[$type] = $values;
					}
					break;
				case 'events':
					if (! empty($value))
					{
						$values = $tmp = array();
						foreach ($value as $val)
						{
							$val['value'] = ! empty($val['value']) ? self::filter_birthday(
								$val['value']) : '';
							$val['type'] = empty($val['type']) ? 'other' : $val['type'];
							$md5 = md5(strtolower($val['type']).$val['value']);
							if (! empty($val['value']) and ! in_array($md5, $tmp))
							{
								$tmp[] = $md5;
								$values[] = $val;
							}
						}
						$result[$type] = $values;
					}
					break;
				case 'emails':
					if (! empty($value))
					{
						$values = $tmp = array();
						foreach ($value as $val)
						{
							//暂时不验证邮箱
							//							$val['value'] = ! empty($val['value']) ? self::filter_email(
							//								$val['value']) : '';
							$val['type'] = empty($val['type']) ? 'other' : $val['type'];
							$md5 = md5(strtolower($val['type']).$val['value']);
							if (! empty($val['value']) and ! in_array($md5, $tmp))
							{
								$tmp[] = $md5;
								$values[] = $val;
							}
						}
						$result[$type] = $values;
					}
					break;
				case 'urls':
				case 'relations':
				case 'customs':
					if (! empty($value))
					{
						$values = $tmp = array();
						foreach ($value as $val)
						{
							$val['type'] = empty($val['type']) ? 'other' : $val['type'];
							$md5 = md5(strtolower($val['type']).$val['value']);
							if (! empty($val['value']) and ! in_array($md5, $tmp))
							{
								$tmp[] = $md5;
								$values[] = $val;
							}
						}
						$result[$type] = $values;
					}
					break;
				case 'birthday':
					$birthday = ! empty($value) ? self::filter_birthday($value) : '';
					$result[$type] = $birthday;
					break;
				case 'avatar':
					if (
						strrpos($value, Kohana::config('contact.avatar')) !== FALSE
					)
					{
						$value = '';
					}
					$result[$type] = $value;
					break;
				case 'category':
					$result[$type] = ! empty($value) ? implode(',', array_unique(explode(',', $value))) : '';
					break;
				default:
					$result[$type] = ! empty($value) ? $value : '';
					break;
			}
		}
		$data['family_name'] = isset($data['family_name']) ? $data['family_name'] : '';
		$data['given_name'] = isset($data['given_name']) ? $data['given_name'] : '';
		$data['middle_name'] = isset($data['middle_name']) ? $data['middle_name'] : '';
		$data['prefix'] = isset($data['prefix']) ? $data['prefix'] : '';
		$data['suffix'] = isset($data['suffix']) ? $data['suffix'] : '';

		$formatted_name = self::name_to_formatted_name($data['family_name'],
			$data['given_name'],
			$data['prefix'], $data['middle_name'], $data['suffix']
		);
		//拼接后的全名为空，把昵称或公司设置到全名
		if (empty($formatted_name))
		{
			if (! empty($data['nickname']))
			{
				$fn = $data['nickname'];
			}
			elseif (! empty($data['organization']))
			{
				$fn = $data['organization'];
			}
		}
		else
		{
			$fn = $formatted_name;
		}
		if (! empty($fn))
		{
			require_once Kohana::find_file('vendor', 'pinyin/c2p');
			$res = get_name_phonetic($fn, $result['family_name']);
			if(mb_check_encoding(mb_substr($fn, 0, 1, 'UTF-8'), 'ASCII')) {
				$tmp = strtolower($fn);
				if((ord($tmp[0])  < 97 OR ord($tmp[0]) > 122)) {
					$sort = '#';
					//非中文、英文首字母，拼音置为空
					$res['phonetic'] = '';
				}
				else {
					$sort = substr($res['sort'], 0, 20);
				}
			} else {
				$sort = substr($res['sort'], 0, 20);
			}
			$result['formatted_name'] = $fn;
			$result['phonetic'] = $res['phonetic'];
			$result['sort'] = $sort;
		}
		else
		{
			$result['formatted_name'] = '';
			$result['phonetic'] = '';
			$result['sort'] = '#';
		}
		$contact = new Contact($result);
		
		$contact->set_avatar_exist(isset($data['avatar']));
		return $contact;
	}

	/**
	 * 过滤生日，把生日转化成yyyy-mm-dd格式
	 * @param string $birthday 生日
	 * @return string
	 */
	public static function filter_birthday($birthday)
	{
		$result = '';
		if (! empty($birthday))
		{
			$timestamp = 0;
			if (is_numeric($birthday))
			{
				//时间戳格式日期
				if (strlen($birthday) > 10)
				{
					$timestamp = $birthday / 1000;
				}
				else
				{
					$timestamp = $birthday;
				}
			} elseif(trim($birthday)) {
				$timestamp = strtotime($birthday);
			}
			if ($timestamp)
			{
				$result = @date('Y-m-d', $timestamp);
			}
		}
		return $result ? $result : '';
	}

	/**
	 * 过滤非标准的邮箱
	 * @param string $email 邮箱
	 * @return string
	 */
	public static function filter_email($email)
	{
		//		if (! empty($email) and ! preg_match(
		//		'/^[-_a-z0-9\'+*$^&%=~!?{}]++(?:\.[-_a-z0-9\'+*$^&%=~!?{}]+)*+@(?:(?![-.])[-a-z0-9.]+(?<![-.])\.[a-z]{2,6}|\d{1,3}(?:\.\d{1,3}){3})(?::\d++)?$/iD', 
		//		(string) $email)) {
		//			$email = '';
		//		}
		return $email;
	}
} // End Contact_Helper
