<?php
defined('SYSPATH') or die('No direct script access.');
/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * 归属地模型文件
 */
/**
 * 联系人模型
 */
class Location_Model extends Model {

	/**
	 * 实例
	 * @var Location_Model
	 */
	protected static $instance;

	/**
	 * 单例模式
	 * @return Location_Model
	 */
	public static function &instance()
	{
		if (! isset(self::$instance))
		{
			// Create a new instance
			self::$instance = new Location_Model();
		}
		return self::$instance;
	}

	/**
	 * 构造函数,
	 * 为了避免循环实例化，请调用单例模式
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * 查询手机号码归属地
	 * @param int  $tel 手机号码
	 * @param int  $type 是否值类型 0 不返回运营商、1 返回运营商、2 只返回城市
	 * @return string 归属地名
	 */
	public function get_tel_location($tel, $type = 1)
	{
		$data = international::check_mobile($tel, 86, FALSE);
		$country_code = ! empty($data['country_code']) ? $data['country_code'] : 86;
		$cell = isset($data['mobile']) ? $data['mobile'] : '';
		//中国归属地
		if ($country_code == 86)
		{
			$location = self::_get_location($cell, $type);
		}
		else
		{
			$location = self::_get_country($country_code, $cell);
		}
		return $location;
	}

	/**
	 * 获取中国电话号码归属地
	 * @param string $tel 电话
	 * @param int    $type 是否值类型 0 不返回运营商、1 返回运营商、2 只返回城市
	 * @return string 归属地名
	 */
	private function _get_location($tel, $type = 1)
	{
		preg_match(
			"/^(0[1-2])\d\-?\d{0,8}$|^(0[3-9])\d{2}\-?\d{0,8}$|^(13|15|14|18)\d{5,9}$/",
			$tel, $match);
		if (! empty($match))
		{
			if (! empty($match[1]))
			{
				$check_num = substr($tel, 0, 3);
			}
			elseif (! empty($match[2]))
			{
				$check_num = substr($tel, 0, 4);
			}
			elseif (! empty($match[3]))
			{
				$check_num = substr($tel, 0, 7);
			}
			else
			{
				$check_num = '';
			}
		}
		else
		{
			$check_num = '';
		}
		$length = strlen($check_num);
		if ($length == 3 or $length == 4)
		{
			$sql = sprintf(
				"SELECT cc.name,cpnt.name as `type` FROM contact_phone_number cpn, contact_city cc, ".
					"contact_phone_number_type cpnt WHERE cpn.number = ".
					"(SELECT max(number) from contact_phone_number where number<= %d) ".
					"AND cpn.number > %d - cpn.count AND cpn.contact_city_id = cc.id AND cpn.type_id = cpnt.id",
				$check_num, $check_num);
			$query = $this->db->query($sql);
			if ($query->count())
			{
				$result = $query->result_array(FALSE);
				if ($type)
				{
					$location = $result[0]['name'].$result[0]['type'];
				}
				else
				{
					$location = $result[0]['name'];
				}
			}
			else
			{
				$location = '';
			}
		}
		elseif ($length == 7)
		{
			$sql = sprintf("SELECT * FROM location WHERE number = %s LIMIT 1", $check_num);
			$query = $this->db->query($sql);
			if ($query->count())
			{
				$result = $query->result_array(FALSE);
				switch ((int) $type)
				{
					case 1:
						$location = str_replace(' ', '', $result[0]['area']).
							mb_substr($result[0]['type'], 2, 2);
						break;
					case 2:
						$area = explode(' ', $result[0]['area']);
						$location = count($area) == 1 ? $area[0] : $area[1];
						break;
					default:
						$location = str_replace(' ', '', $result[0]['area']);
						break;

				}
			}
			else
			{
				$location = '';
			}
		}
		else
		{
			$location = '';
		}
		return $location;
	}

	/**
	 * 获取国家名
	 * @param int    $country_code 国家码
	 * @param string $cell 号码
	 * @return string 归属地名
	 */
	private function _get_country($country_code, $cell)
	{
		$all_country = Kohana::lang('calling_code');
		$country = isset($all_country[$country_code]) ? $all_country[$country_code] : '';
		//北美洲号码计划区
		if (is_array($country))
		{
			$area_code = substr($cell, 0, 3);
			foreach ($country as $key => $val)
			{
				if (strpos($val, $area_code) !== FALSE)
				{
					$country = $key;
					break;
				}
			}
		}
		//北美洲号码计划区，但区号不在范围的值需要过滤
		return is_string($country) ? $country : '';
	}
}
