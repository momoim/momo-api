<?php
defined('SYSPATH') or die('No direct access allowed.');
/*
 * [MOMO API] (C)1999-2011 ND Inc. 
 * 国际手机号码验证辅助文件
*/
/**
 * international辅助类
 */
class international {

	/**
	 * 验证手机号码是否合法，返回国家码、手机号码
	 * @param string $mobile 手机号码
	 * @param string $default_country_code 默认国家码
	 * @param bool   $is_check_valid 是否校验
	 * @return array
	 */
	public static function check_mobile($mobile, $default_country_code = '86',
	                                    $is_check_valid = TRUE)
	{
		$country_code = strval($default_country_code);
		// 除了中国增加验证外，其他手机号码只验证标准格式
		$all_country_code = array_keys(Kohana::lang('calling_code'));

		if (preg_match("/^(\+86|^0086|^086|^\(86\)|^86)/", $mobile, $matches))
		{
			//替换国家码
			$mobile = preg_replace("/^(\+86|^0086|^086|^\(86\)|^86)/", '', $mobile);
			$country_code = '86';
		}
		elseif (preg_match("/^\+([1-9][0-9]{0,2})([\d\s(-])/", $mobile,
			$matches)
		)
		{
			if (isset($matches[2]) and ! is_numeric($matches[2]))
			{
				$country_code = $matches[1];
			}
			elseif (isset($matches[1]))
			{
				for ($i = 3; $i >= 1; $i --)
				{
					$country_code = substr($matches[1], 0, $i);
					if (in_array($country_code, $all_country_code))
					{
						break;
					}
				}
			}
		}
		// 去掉前缀和中间的-或空格
		$tel = str_replace(
			array(
			     '+'.$country_code, '-', ' ', '(', ')'
			), '', $mobile);
		if($country_code == '86' AND strlen($tel) > 11) {
			//替换IP拨号
			$tel       = preg_replace(
				"/^12593|^17951|^17911|^17910|^17909|^10131|^10193|^96531|^193|^12520|^11808|^17950/", '',
				$tel);
		}

		if ($is_check_valid)
		{
			$status = self::check_is_valid($country_code, $tel);
		}
		else
		{
			$status = 1;
		}
		if ($status)
		{
			return array(
				'country_code' => $country_code,
				'mobile'       => $tel
			);
		}
		return array();
	}

	/**
	 * 验证手机号码
	 * @author huangby
	 * @param int    $country_code 国家码
	 * @param string $tel 手机号码
	 * @return int
	 */
	public static function check_is_valid($country_code, $tel)
	{
		$mark         = 0;
		$country_code = strval($country_code);
		// 验证中国大陆手机号码合法性
		switch (true)
		{
			//  canada   加拿大       美国
			case $country_code === '1':
				//加拿大/美国     手机号码合法性验证（从维基百科说美国和加拿大号码格式一样而且国际区号也一样，不同的就是前面三位的地区区号
				//第一行是加拿大的区号，后面是美国的区号，我只做了区号的判断后面七位数字第一个不能是0，1，其他0-9都可以。号码格式：1-NPA-NXX-XXXX,)
				$array = array(
					//加拿大
					204, 226, 236, 249, 250, 289, 306, 343, 365, 403,
					416, 418, 431, 437, 438, 450, 506, 514, 519, 579, 581, 587,
					604, 613, 639, 647, 672, 705, 709, 778, 780, 807, 819, 825,
					867, 873, 902, 902, 905,
					//美国
					201, 202, 203, 205, 206, 207, 208, 209,
					210, 212, 213, 214, 215, 216, 217, 218, 219, 224, 225, 227,
					228, 229, 231, 234, 239, 240, 248, 250, 251, 252, 253, 254,
					256, 260, 262, 267, 269, 270, 272, 274, 276, 281, 283, 301,
					302, 303, 304, 305, 307, 308, 309, 310, 312, 313, 314, 315,
					316, 317, 318, 319, 320, 321, 323, 325, 327, 330, 331, 334,
					336, 337, 339, 341, 347, 351, 352, 360, 361, 364, 369, 380,
					385, 386, 401, 402, 404, 405, 406, 407, 408, 409, 410, 412,
					413, 414, 415, 417, 419, 423, 424, 425, 430, 432, 434, 435,
					440, 442, 443, 445, 447, 458, 464, 469, 470, 475, 478, 479,
					480, 484, 501, 502, 503, 504, 505, 507, 508, 509, 510, 512,
					513, 515, 516, 517, 518, 520, 530, 531, 534, 539, 540, 541,
					551, 557, 559, 561, 562, 563, 564, 567, 570, 571, 573, 574,
					575, 580, 582, 585, 586, 601, 602, 603, 605, 606, 607, 608,
					609, 610, 612, 614, 615, 616, 617, 618, 619, 620, 623, 626,
					627, 628, 630, 631, 636, 641, 646, 650, 651, 657, 659, 660,
					661, 662, 667, 669, 678, 679, 681, 682, 689, 701, 702, 703,
					704, 706, 707, 708, 712, 713, 714, 715, 716, 717, 718, 719,
					720, 724, 727, 730, 731, 732, 734, 737, 740, 747, 754, 757,
					760, 762, 763, 764, 765, 769, 770, 772, 773, 774, 775, 779,
					781, 785, 786, 801, 802, 803, 804, 805, 806, 808, 810, 812,
					813, 814, 815, 816, 817, 818, 828, 830, 831, 832, 835, 843,
					845, 847, 848, 850, 856, 857, 858, 859, 860, 862, 863, 864,
					865, 870, 872, 878, 901, 903, 904, 906, 907, 908, 909, 910,
					912, 913, 914, 915, 916, 917, 918, 919, 920, 925, 928, 929,
					931, 935, 936, 937, 938, 940, 941, 947, 949, 951, 952, 954,
					956, 959, 970, 971, 972, 973, 975, 978, 979, 980, 984, 985,
					989
				);
				foreach ($array as $canada)
				{
					if (preg_match('/^'.$canada.'[2-9][0-9]{6}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				$area_code = strval(substr($tel, 0, 3));
				$local_num = substr($tel, 3);
				switch (TRUE)
				{
					//巴哈马
					case $area_code === '242':
						//后面跟7位,不确定，北美计划，参考维基百科
						if (preg_match('/^[0-9]{7}$/',
							$local_num)
						)
						{
							$mark = 1;
							return $mark;
						}
						return $mark;
					//巴巴多斯
					case $area_code === '246':
						//以$array数组开头的后面跟4位，不确定，北美计划，参考维基百科
						$array = array(
							//Digicel
							260, 261, 262, 263, 264, 265,
							266, 267, 268, 269, 820, 821, 822, 823, 824, 825,
							826, 827, 828, 829,
							//Landline, Internet, Mobile, Entertainment (LIME)
							230, 231, 232, 233, 234,
							235, 236, 237, 238, 239, 240, 241, 242, 243, 244,
							245, 246, 247, 248, 249, 250, 251, 252, 253, 254,
							//Sunbeach
							450, 451, 452, 453, 454,
							455, 456, 457, 458, 459
						);
						foreach ($array as $t)
						{
							if (preg_match('/^'.$t.'[0-9]{4}$/', $local_num))
							{
								$mark = 1;
								return $mark;
							}
						}
						return $mark;
					//安圭拉岛 
					case $area_code === '264':
						//后面跟7位，不确定，北美计划，参考维基百科
						if (preg_match('/^[0-9]{7}$/',
							$local_num)
						)
						{
							$mark = 1;
							return $mark;
						}
						return $mark;
					//安提瓜和巴布达
					case $area_code === '268':
						//后面跟7位，不确定，北美计划，参考维基百科
						if (preg_match('/^[0-9]{7}$/',
							$local_num)
						)
						{
							$mark = 1;
							return $mark;
						}
						return $mark;
					//英属维京群岛
					case $area_code === '284':
						//后面跟7位，不确定，北美计划，参考维基百科
						if (preg_match('/^[0-9]{7}$/',
							$local_num)
						)
						{
							$mark = 1;
							return $mark;
						}
						return $mark;
					//美属维尔京群岛
					case $area_code === '340':
						//后面跟7位，不确定，北美计划，参考维基百科
						if (preg_match('/^[0-9]{7}$/',
							$local_num)
						)
						{
							$mark = 1;
							return $mark;
						}
						return $mark;
					//开曼群岛
					case $area_code === '345':
						//后面跟7位，不确定，北美计划，参考维基百科
						if (preg_match('/^[0-9]{7}$/',
							$local_num)
						)
						{
							$mark = 1;
							return $mark;
						}
						return $mark;
					//百慕大
					case $area_code === '441':
						//后面跟7位，不确定，北美计划，参考维基百科
						if (preg_match('/^[0-9]{7}$/',
							$local_num)
						)
						{
							$mark = 1;
							return $mark;
						}
						return $mark;
					//格林纳达
					case $area_code === '473':
						//后面跟7位，不确定，北美计划，参考维基百科
						if (preg_match('/^[0-9]{7}$/',
							$local_num)
						)
						{
							$mark = 1;
							return $mark;
						}
						return $mark;
					//特克斯和凯科斯群岛
					case $area_code === '649':
						//后面跟7位，不确定，北美计划，参考维基百科
						if (preg_match('/^[0-9]{7}$/',
							$local_num)
						)
						{
							$mark = 1;
							return $mark;
						}
						return $mark;
					//蒙特塞拉特
					case $area_code === '664':
						//以$array数组开头的后面跟4位，不确定，北美计划，参考维基百科
						$array = array(
							236, 349, 410, 411, 412, 413, 415, 491, 492,
							493, 494, 495, 496, 664, 724
						);
						foreach ($array as $Montserrat)
						{
							if (preg_match('/^'.$Montserrat.'[0-9]{4}$/',
								$local_num)
							)
							{
								$mark = 1;
								return $mark;
							}
						}
						return $mark;
					//北马里亚纳群岛
					case $area_code === '670':
						//后面跟7位，不确定，北美计划，参考维基百科
						if (preg_match('/^[0-9]{7}$/',
							$local_num)
						)
						{
							$mark = 1;
							return $mark;
						}
						return $mark;
					//关岛
					case $area_code === '671':
						//后面跟7位，不确定，北美计划，参考维基百科
						if (preg_match('/^[0-9]{7}$/',
							$local_num)
						)
						{
							$mark = 1;
							return $mark;
						}
						return $mark;
					//圣卢西亚
					case $area_code === '758':
						//后面跟7位，不确定，北美计划，参考维基百科
						if (preg_match('/^[0-9]{7}$/',
							$local_num)
						)
						{
							$mark = 1;
							return $mark;
						}
						return $mark;
					//多米尼克
					case $area_code === '767':
						//后面跟7位，不确定，北美计划，参考维基百科
						if (preg_match('/^[0-9]{7}$/',
							$local_num)
						)
						{
							$mark = 1;
							return $mark;
						}
						return $mark;
					//圣文森特和格林纳丁斯
					case $area_code === '784':
						//后面跟7位，不确定，北美计划，参考维基百科
						if (preg_match('/^[0-9]{7}$/',
							$local_num)
						)
						{
							$mark = 1;
							return $mark;
						}
						return $mark;
					//波多黎各
					case in_array($area_code,
						array(
						     '787', '939'
						), TRUE):
						//后面跟7位，不确定，北美计划，参考维基百科
						if (preg_match('/^[0-9]{7}$/',
							$local_num)
						)
						{
							$mark = 1;
							return $mark;
						}
						return $mark;
					//多米尼加共和国
					case in_array($area_code,
						array(
						     '809', '829', '849'
						), TRUE):
						//后面跟7位，不确定，北美计划，参考维基百科
						if (preg_match('/^[0-9]{7}$/',
							$local_num)
						)
						{
							$mark = 1;
							return $mark;
						}
						return $mark;
					//特立尼达和多巴哥
					case $area_code === '868':
						//后面跟7位，不确定，北美计划，参考维基百科
						if (preg_match('/^[0-9]{7}$/',
							$local_num)
						)
						{
							$mark = 1;
							return $mark;
						}
						return $mark;
					//圣基茨和尼维斯
					case $area_code === '869':
						//后面跟7位，不确定，北美计划，参考维基百科
						if (preg_match('/^[0-9]{7}$/',
							$local_num)
						)
						{
							$mark = 1;
							return $mark;
						}
						return $mark;
					//牙买加
					case $area_code === '876':
						//后面跟7位，不确定，北美计划，参考维基百科
						if (preg_match('/^[0-9]{7}$/',
							$local_num)
						)
						{
							$mark = 1;
							return $mark;
						}
						return $mark;
				}
				return $mark;
			//Egypt    埃及
			case $country_code === '20':
				//埃及手机号码合法性验证（以数组$array开头的跟7位，总共9-10位，参考维基百科）
				$array = array(
					12, 17, 18, 150, 122, 127, 128, 120, 10, 16, 19, 151,
					100, 114, 112, 106, 109, 101, 11, 14, 152, 111
				);
				foreach ($array as $Egypt)
				{
					if (preg_match('/^'.$Egypt.'[0-9]{7}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				return $mark;
			//Morocco  摩洛哥
			case $country_code === '212':
				//摩洛哥 手机号码验证（以66,61,67,64,65开头的跟7位）
				if (preg_match(
					'/^6[4-7][0-9]{7}$|^61[0-9]{7}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//阿尔及利亚
			case $country_code === '213':
				//6开头跟8位，总共9位，参考维基百科
				if (preg_match('/^6[0-9]{8}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//突尼斯
			case $country_code === '216':
				//6开头跟8位，总共9位，参考维基百科
				if (preg_match('/^6[0-9]{8}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//利比亚
			case $country_code === '218':
				//91，92开头跟6位，总共8位，参考维基百科
				if (preg_match('/^91[0-9]{6}$|^92[0-9]{6}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//冈比亚
			case $country_code === '220':
				//3,6,7,9开头跟6位，总共7位，参考维基百科
				if (preg_match(
					'/^3[0-9]{6}$|^6[0-9]{6}$|^7[0-9]{6}$|^9[0-9]{6}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//塞内加尔
			case $country_code === '221':
				//70,76,77开头跟7位，总共9位，参考维基百科
				if (preg_match(
					'/^70[0-9]{7}$|^76[0-9]{7}$|^77[0-9]{7}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//马里
			case $country_code === '223':
				//65，66,69,70,76，开头的跟6位，730-734,740-741,744-747,750-754,770-774,781-789,790-794开头 的跟5位。参考维基百科
				if (preg_match(
					'/^65[0-9]{6}$|^66[0-9]{6}$|^69[0-9]{6}$|^70[0-9]{6}$|^73[0-4][0-9]{5}$|^74[0-1][0-9]{5}$|^74[4-7][0-9]{5}$|^75[0-4][0-9]{5}$|^76[0-9]{6}$|^77[0-4][0-9]{5}$|^78[1-9][0-9]{5}$|^79[0-4][0-9]{5}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//几内亚  Guinea
			//以数组$array内容开头的跟4位,以62,68,64,65,67,55开头的跟6位，参考维基百科
			case $country_code === '224':
				$array = array(
					6020, 6021, 6022, 6023, 6023, 6024, 6025, 6026, 6027,
					6028, 6029, 6033, 6034, 6035, 6036, 6037, 6052, 6054, 6055,
					6057, 6058, 6059, 6310, 6335, 6340
				);
				foreach ($array as $Guinea)
				{
					if (preg_match('/^'.$Guinea.'[0-9]{4}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				if (preg_match(
					'/^62[0-9]{6}$|^68[0-9]{6}$|^64[0-9]{6}$|^65[0-9]{6}$|^67[0-9]{6}$|^55[0-9]{6}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//科特迪瓦  
			//以01-09开头的跟6位,以44-49开头的跟6位，以50，60，66,67开头的跟6位，参考维基百科
			case $country_code === '225':
				if (preg_match(
					'/^0[1-9][0-9]{6}$|^4[4-9][0-9]{6}$|^50[0-9]{6}$|^60[0-9]{6}$|^66[0-9]{6}$|^67[0-9]{6}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//布基纳法索  
			//以7开头的跟7位，总共八位，（其实更严格的要分成非常多段，这里简写成7开头的）参考维基百科
			case $country_code === '226':
				if (preg_match('/^7[0-9]{7}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//尼日尔 Niger
			//以下面$array数组开头的跟4位，总共八位，参考维基百科
			case $country_code === '227':
				$array = array(
					9605, 9606, 9607, 9608, 9609, 9610, 9611, 9612, 9613,
					9614, 9615, 9616, 9617, 9618, 9619, 9620, 9621, 9622, 9623,
					9624, 9625, 9626, 9627, 9628, 9629, 9640, 9642, 9643, 9646,
					9647, 9648, 9649, 9650, 9652, 9653, 9655, 9656, 9657, 9658,
					9659, 9666, 9667, 9687, 9688, 9689, 9696, 9697, 9698, 9699,
					9321, 9322, 9323, 9380, 9381, 9382, 9383, 9390, 9391, 9392,
					9393, 9424, 9425, 9428, 9484, 9485, 9494, 9495, 9462, 9463
				);
				foreach ($array as $Niger)
				{
					if (preg_match('/^'.$Niger.'[0-9]{4}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				return $mark;
			//多哥 Togo
			//以9开头的跟7位，总共8位，参考维基百科
			case $country_code === '228':
				if (preg_match('/^9[0-9]{7}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//贝宁 Benin
			//以$array数组开头的跟6位，以0开头的跟7位，以1129开头的跟4位。总共8位，参考维基百科
			case $country_code === '229':
				$array = array(
					40, 42, 44, 60, 64, 68, 69, 87, 89, 90, 91, 92, 93, 95,
					96, 97, 98
				);
				foreach ($array as $Benin)
				{
					if (preg_match('/^'.$Benin.'[0-9]{6}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				if (preg_match('/^0[0-9]{7}$|^1129[0-9]{4}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//毛里求斯
			//以7开头的跟6位，以87开头的跟5位，以9开头的跟6位。总共7位。参考维基百科
			case $country_code === '230':
				if (preg_match('/^7[0-9]{6}$|^87[0-9]{5}$|^9[0-9]{6}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//利比里亚 
			//以46开头的跟5位，47开头的跟5位，以5开头的跟6位，以64开头的跟5位。以65开头的跟5位。以7开头的跟7位。总共7-8位，参考维基百科
			case $country_code === '231':
				if (preg_match(
					'/^46[0-9]{5}$|^47[0-9]{5}$|^5[0-9]{6}$|^64[0-9]{5}$|^65[0-9]{5}$|^7[0-9]{7}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//塞拉利昂 Sierra Leone
			case $country_code === '232':
				//以数组$array开头的跟6位（且第一位不能为0），参考维基百科
				$array = array(
					76, 78, 30, 33, 77, 88, 44, 56, 55, 25
				);
				foreach ($array as $Sierra)
				{
					if (preg_match('/^'.$Sierra.'[1-9][0-9]{5}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				return $mark;
			//加纳
			case $country_code === '233':
				//以数组$array开头的跟7位，参考维基百科
				$array = array(
					23, 24, 54, 27, 57, 28, 20, 26
				);
				foreach ($array as $Ghana)
				{
					if (preg_match('/^'.$Ghana.'[0-9]{7}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				return $mark;
			//尼日利亚
			case $country_code === '234':
				//以$array数组开头的跟6位，以$arrayt数组开头的跟7位	
				$array  = array(
					7021, 7022, 7023, 7024, 7025, 7026, 7027, 7028, 7029,
					8190, 8191
				);
				$arrayt = array(
					704, 804, 703, 706, 803, 806, 705, 805, 807, 708, 802,
					808, 812, 709, 809, 700, 800
				);
				foreach ($array as $Nigeria)
				{
					if (preg_match('/^'.$Nigeria.'[0-9]{6}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				foreach ($arrayt as $Nigeria)
				{
					if (preg_match('/^'.$Nigeria.'[0-9]{7}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				return $mark;
			//乍得
			//630-634,660-669,950-954,990-999开头的跟5位。总共8位。参考维基百科
			case $country_code === '235':
				if (preg_match(
					'/^63[0-4][0-9]{5}$|^66[0-9]{6}$|^95[0-4][0-9]{5}$|^99[0-9]{6}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//中非共和国
			//70,75,77,72开头的跟6位。总共8位。参考维基百科
			case $country_code === '236':
				if (preg_match(
					'/^70[0-9]{6}$|^75[0-9]{6}$|^77[0-9]{6}$|^72[0-9]{6}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//喀麦隆  Cameroon
			//以数组$array开头的跟5位。以数组$arrayt开头的跟6位。总共8位。参考维基百科
			case $country_code === '237':
				$array  = array(
					745, 746, 747, 748, 749, 940, 941, 942, 943, 944
				);
				$arrayt = array(
					22, 33, 75, 77, 88, 96, 99
				);
				foreach ($array as $Cameroon)
				{
					if (preg_match('/^'.$Cameroon.'[0-9]{5}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				foreach ($arrayt as $Cameroon)
				{
					if (preg_match('/^'.$Cameroon.'[0-9]{6}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				return $mark;
			// 圣多美和普林西比
			//98,99开头的跟5位。总共7位。参考维基百科
			case $country_code === '239':
				if (preg_match('/^98[0-9]{5}$|^99[0-9]{5}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			// 加蓬
			//5,6,7开头的跟6位。总共7位。参考维基百科
			case $country_code === '241':
				if (preg_match('/^5[0-9]{6}$|^6[0-9]{6}$|^7[0-9]{6}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			// 刚果共和国（布）
			//01,04,05,06开头的跟7位。或者1,4,5,6开头的跟8位，总共9位。参考维基百科
			case $country_code === '242':
				if (preg_match(
					'/^0[4-6][0-9]{7}$|^01[0-9]{7}$|^1[0-9]{8}$|^[4-6][0-9]{8}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//刚果民主共和国(刚果（金）)。
			case $country_code === '243':
				// 从国外打过去的格式是 +243 yy xxx xx xx ，参考维基百科
				$array = array(
					80, 81, 82, 84, 85, 88, 89, 97, 98, 99
				);
				foreach ($array as $congo)
				{
					if (preg_match('/^'.$congo.'[0-9]{7}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				return $mark;
			//安哥拉
			//91,93开头的跟7位。或者923开头的跟6位，总共9位。参考维基百科
			case $country_code === '244':
				if (preg_match('/^91[0-9]{7}$|^93[0-9]{7}$|^923[0-9]{6}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//阿森松岛
			//总共4位。参考维基百科
			case $country_code === '247':
				if (preg_match('/^[0-9]{4}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//塞舌尔
			//25开头的跟5位。或者27开头的跟5位，总共7位。参考维基百科
			case $country_code === '248':
				if (preg_match('/^25[0-9]{5}$|^27[0-9]{5}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//苏丹
			//以9开头的跟8位，总共9位。参考维基百科
			case $country_code === '249':
				if (preg_match('/^9[0-9]{8}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			// 卢旺达
			//以72,75,78开头的跟7位，总共9位。参考维基百科
			case $country_code === '250':
				if (preg_match('/^72[0-9]{7}$|^75[0-9]{7}$|^78[0-9]{7}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			// 埃塞俄比亚
			//以91开头的跟7位，总共9位。参考维基百科
			case $country_code === '251':
				if (preg_match('/^91[0-9]{7}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			// 索马里
			//以91,90开头的跟6位，总共8位。参考维基百科
			case $country_code === '252':
				if (preg_match('/^91[0-9]{6}$|^90[0-9]{6}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			// 吉布提
			//以6,7,8开头的跟5位，总共6位。参考维基百科
			case $country_code === '253':
				if (preg_match('/^6[0-9]{5}$|^7[0-9]{5}$|^8[0-9]{5}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			// 肯尼亚
			//以7开头的跟8位，总共9位。参考维基百科
			case $country_code === '254':
				if (preg_match('/^7[0-9]{8}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			// 坦桑尼亚
			//以6,7开头的跟8位，总共9位。参考维基百科
			case $country_code === '255':
				if (preg_match('/^7[0-9]{8}$|^6[0-9]{8}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			// 乌干达
			//以7开头的跟8位，总共9位。参考维基百科
			case $country_code === '256':
				if (preg_match('/^7[0-9]{8}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			// 布隆迪
			//以7开头的跟7位，以29开头的跟6位。总共8位。参考维基百科
			case $country_code === '257':
				if (preg_match('/^7[0-9]{7}$|^29[0-9]{6}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//莫桑比克
			//以8开头的跟8位,总共9位。参考维基百科
			case $country_code === '258':
				if (preg_match('/^8[0-9]{8}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//赞比亚
			//以95,96,97开头的跟7位,总共9位。参考维基百科
			case $country_code === '260':
				if (preg_match('/^95[0-9]{7}$|^96[0-9]{7}$|^97[0-9]{7}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//马达加斯加
			//以30,32,33,34开头的跟7位,总共9位。参考维基百科
			case $country_code === '261':
				if (preg_match(
					'/^30[0-9]{7}$|^32[0-9]{7}$|^33[0-9]{7}$|^34[0-9]{7}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			// 留尼汪
			//以692开头的跟6位,总共9位。参考维基百科
			case $country_code === '262':
				if (preg_match('/^692[0-9]{6}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			// 津巴布韦
			//总共11位。，不确定。参考维基百科
			case $country_code === '263':
				if (preg_match('/^[0-9]{11}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			// 纳米比亚
			//60,81,82，85开头的跟6位。参考维基百科
			case $country_code === '264':
				if (preg_match(
					'/^60[0-9]{6}$|^81[0-9]{6}$|^85[0-9]{6}$|^82[0-9]{6}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//  马拉维
			//总共9位，不确定。参考维基百科
			case $country_code === '265':
				if (preg_match('/^[0-9]{9}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//  莱索托
			//5或者6开头的跟7位，总共8位。参考维基百科
			case $country_code === '266':
				if (preg_match('/^5[0-9]{7}$|^6[0-9]{7}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//博茨瓦纳
			case $country_code === '267':
				//(以71-76开头的八位，参考维基百科)
				if (preg_match('/^7[1-6][0-9]{7}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			// 斯威士兰
			case $country_code === '268':
				//(以78开头的跟6位，总共8位，参考维基百科)
				if (preg_match('/^78[0-9]{6}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//南非
			case $country_code === '27':
				//以0开头的10位，以其他数字开头的9位，（因为国外拨打省略0），参考维基百科
				if (preg_match('/^0[0-9]{9}$|^[1-9][0-9]{8}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//希腊
			case $country_code === '30':
				//(以69开头的十位。参考维基百科)
				if (preg_match('/^69[0-9]{8}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//Netherlands     荷兰
			case $country_code === '31':
				//荷兰 手机号码验证（以6开头的九位。 或者以06开头的10位，本处参考谷歌，维基百科也一样）
				if (preg_match('/^6[0-9]{8}$|^06[0-9]{8}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//比利时  Belgium
			case $country_code === '32':
				//(以47.48.49开头的跟7位,总共9位。参考维基百科）
				if (preg_match(
					'/^47[0-9]{7}$|^48[0-9]{7}$|^49[0-9]{7}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//法国    France
			case $country_code === '33':
				//法国号码合法性验证（根据维基百科是说国外拨打号码是以1-9开头的九位数字（国外拨打省略了一个0，要不然是十位），但
				//是不知道发短信要不要省略（根据网友提供是要去掉0），此处去掉0）
				if (preg_match('/^[1-9][0-9]{8}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//Spain      西班牙
			case $country_code === '34':
				//西班牙 手机号码验证（以6开头的9位数，以7开头的除了70的九位数。参考维基百科）
				if (preg_match('/^6[0-9]{8}$|^7[1-9][0-9]{7}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//直布罗陀
			case $country_code === '350':
				//(8位，参考维基百科)
				if (preg_match('/^[0-9]{8}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//Portugal    葡萄牙
			case $country_code === '351':
				//葡萄牙手机号码验证（以9开头的9位。本处参考维基百科）
				if (preg_match('/^9[0-9]{8}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//卢森堡
			case $country_code === '352':
				//(有两种一个是6X1（621,661,691）再跟六位数字，一个是6021跟八位数字,参考维基百科)
				if (preg_match(
					'/^621[0-9]{6}$|^661[0-9]{6}$|^691[0-9]{6}$|^6021[0-9]{8}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//爱尔兰     Ireland
			case $country_code === '353':
				//爱尔兰手机号码验证（参考维基百科，以82.83.85.86.87.88.89开头的9位手机号码）
				if (preg_match(
					'/^8[2-3][0-9]{7}$|^8[5-9][0-9]{7}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//冰岛
			case $country_code === '354':
				//(6,7,8开头的跟6位,参考维基百科)
				if (preg_match(
					'/^6[0-9]{6}$|^7[0-9]{6}$|^8[0-9]{6}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//阿尔巴尼亚
			case $country_code === '355':
				//(66,67,68,69开头的跟6位,参考维基百科)
				if (preg_match(
					'/^66[0-9]{6}$|^67[0-9]{6}$|^68[0-9]{6}$|^69[0-9]{6}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//马耳他
			case $country_code === '356':
				//(77,79,98,99开头的跟6位,参考维基百科)
				if (preg_match(
					'/^77[0-9]{6}$|^79[0-9]{6}$|^98[0-9]{6}$|^99[0-9]{6}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//塞浦路斯
			case $country_code === '357':
				//(97,99,96,95开头的跟6位,参考维基百科)
				if (preg_match(
					'/^97[0-9]{6}$|^99[0-9]{6}$|^96[0-9]{6}$|^95[0-9]{6}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//Finland     芬兰
			case $country_code === '358':
				//芬兰手机号码合法性验证（格式有4x+7位数字 or 457+7位数字 or 50 +7位数字，不确定）
				if (preg_match(
					'/^4[0-9]{8}$|^457[0-9]{7}$|^50[0-9]{7}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//保加利亚
			case $country_code === '359':
				//保加利亚手机号码合法性验证（维基百科，以87，88，89，98或者99开头的九位）
				if (preg_match(
					'/^88[0-9]{7}$|^89[0-9]{7}$|^87[0-9]{7}$|^98[0-9]{7}$|^99[0-9]{7}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			// 匈牙利   Hungary
			case $country_code === '36':
				//匈牙利号码合法性验证（参考维基百科，20，30,70，开头的跟6位，总共8位。不确定。）
				if (preg_match(
					'/^20[0-9]{6}$|^30[0-9]{6}$|^70[0-9]{6}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//立陶宛
			case $country_code === '370':
				//(6开头的跟7位,总共8位。参考维基百科)
				if (preg_match('/^6[0-9]{7}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//拉脱维亚
			case $country_code === '371':
				//(2开头的跟7位,参考维基百科)
				if (preg_match('/^2[0-9]{7}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//爱沙尼亚
			case $country_code === '372':
				//(5开头的跟6位或者7位,以81或者82开头的跟6位，参考维基百科)
				if (preg_match(
					'/^5[0-9]{6,7}$|^81[0-9]{6}$|^82[0-9]{6}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//摩尔多瓦
			case $country_code === '373':
				//(6或者7开头的跟7位,（8开头的好像是免费电话，9开头的是长期储备的，以便将来需要），参考维基百科)
				if (preg_match('/^6[0-9]{7}$|^7[0-9]{7}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//亚美尼亚
			case $country_code === '374':
				//(9开头的跟10位,以55开头的跟9位，以77开头的跟9位（是否11位不能确定，维基百科没有说是几位，只提供开头的前缀，11位是百度出来的），参考维基百科)
				if (preg_match(
					'/^9[0-9]{10}$|^77[0-9]{9}$|^55[0-9]{9}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//白俄罗斯
			case $country_code === '375':
				//(25,29,33或者44开头的跟7位，参考维基百科)
				if (preg_match(
					'/^25[0-9]{7}$|^29[0-9]{7}$|^33[0-9]{7}$|^44[0-9]{7}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//安道尔
			case $country_code === '376':
				//(3,4或者6开头的跟5位，参考维基百科)
				if (preg_match(
					'/^3[0-9]{5}$|^4[0-9]{5}$|^6[0-9]{5}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//摩纳哥
			case $country_code === '377':
				//(4开头的跟7位，总共八位。参考维基百科)
				if (preg_match('/^4[0-9]{7}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//圣马力诺
			case $country_code === '378':
				//(0549开头的跟6位，总共十位。参考维基百科)
				if (preg_match('/^0549[0-9]{6}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//乌克兰
			case $country_code === '380':
				//(总共九位。具体的不确定，参考维基百科)
				if (preg_match('/^[0-9]{9}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//塞尔维亚共和国(南斯拉夫解体后)
			case $country_code === '381':
				//以数组$array的值为开头的九位。其中44,45，43，49，是科索沃的。参考维基百科
				$array = array(
					60, 61, 62, 63, 64, 65, 66, 68, 69, 43, 44, 45, 49
				);
				foreach ($array as $Serbia)
				{
					if (preg_match('/^'.$Serbia.'[0-9]{7}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				return $mark;
			//黑山共和国(南斯拉夫解体后)
			case $country_code === '382':
				//(以数组$array的值为开头的8位。参考维基百科)
				$array = array(
					63, 67, 68, 69
				);
				foreach ($array as $Montenegro)
				{
					if (preg_match('/^'.$Montenegro.'[0-9]{6}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				return $mark;
			//     Croatia 克罗地亚    
			case $country_code === '385':
				//克罗地亚 手机号码合法性验证（以91,92,95,97,98,99开头的后面跟七位数字。参考维基百科）
				$array = array(
					91, 92, 95, 97, 98, 99
				);
				foreach ($array as $Croatia)
				{
					if (preg_match('/^'.$Croatia.'[0-9]{7}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				return $mark;
			//斯洛文尼亚
			case $country_code === '386':
				//(以数组$array的值为开头的九位。参考维基百科)
				$array = array(
					30, 31, 40, 41, 51, 64, 70, 71
				);
				foreach ($array as $Slovenia)
				{
					if (preg_match('/^'.$Slovenia.'[0-9]{7}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				return $mark;
			//  intaly  意大利
			case $country_code === '39':
				//意大利手机号码验证（以3开头的跟8-10位，总共9-11位，参考维基百科）
				if (preg_match('/^3[0-9]{8,10}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//Romania      罗马尼亚
			case $country_code === '40':
				//罗马尼亚手机号码验证（以07开头的10位，或者以7开头的9位。。参考维基百科）
				if (preg_match('/^7[0-9]{8}$|^07[0-9]{8}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//瑞士    Switzerland
			case $country_code === '41':
				$array  = array(
					21, 22, 24, 26, 27, 31, 32, 33, 34, 41, 43, 44, 51, 52,
					55, 56, 58, 61, 62, 71, 74, 76, 77, 78, 79, 81, 91
				);
				$arrayt = array(
					800, 840, 842, 844, 848, 860, 869, 878, 900, 901, 906
				);
				//瑞士手机号码验证（以数组$array,$arrayt的值开头的9位数字）
				foreach ($array as $Switzerland)
				{
					if (preg_match('/^'.$Switzerland.'[0-9]{7}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				foreach ($arrayt as $Switzerland)
				{
					if (preg_match('/^'.$Switzerland.'[0-9]{6}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				return $mark;
			//捷克共和国
			case $country_code === '420':
				//(总共是九位。以72,73,77,79,91开头，或者60[1-8]开头参考维基百科)
				$array = array(
					72, 73, 77, 79, 91
				);
				foreach ($array as $CzechRepublic)
				{
					if (preg_match('/^'.$CzechRepublic.'[0-9]{7}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				if (preg_match('/^60[1-8][0-9]{6}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//斯洛伐克
			case $country_code === '421':
				//(以9开头的跟8位，总共是9位 ，参考维基百科)
				if (preg_match('/^9[0-9]{8}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//列支敦士登  Liechtenstein 
			case $country_code === '423':
				//列支敦士登 手机号码验证（谷歌的规则测不出来，网上没有资料，只好6-13位）
				if (preg_match('/^[0-9]{6,13}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//奥地利号码验证   Austria
			case $country_code === '43':
				//**************前端注意                     //奥地利验证手机号码验证(以数组$array,$arrayt开头的7-13位，参考维基百科也是)
				$array  = array(
					650, 651, 652, 653, 655, 657, 659, 660, 661, 663, 664,
					665, 666, 667, 668, 669
				);
				$arrayt = array(
					67, 68, 69
				);
				foreach ($array as $Austria)
				{
					if (preg_match('/^'.$Austria.'[0-9]{4,10}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				foreach ($arrayt as $Austriat)
				{
					if (preg_match('/^'.$Austriat.'[0-9]{5,11}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				return $mark;
			//英国    U.K.
			case $country_code === '44':
				$array = array(
					'070', '074', '075', '07624', '077', '078', '079', 70,
					74, 75, 7624, 77, 78, 79
				);
				//英国手机号码验证（07是英国移动的开头号，0后面十位。包括070,074,075,07624,077,078，079（去掉0也可以），其他的不是手机号,此处参考维基百科）
				foreach ($array as $UK)
				{
					if (preg_match('/^'.$UK.'[0-9]{8}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				return $mark;
			//Denmark   丹麦
			case $country_code === '45':
				//以$array数组为开头的八位，参考维基百科
				$array = array(
					20, 31, 40, 42, 50, 53, 60, 61, 71, 81
				);
				foreach ($array as $Denmark)
				{
					if (preg_match('/^'.$Denmark.'[0-9]{6}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				return $mark;
			//瑞典    Sweden 
			case $country_code === '46':
				//瑞典  手机号码验证（参考谷歌手机，以0开头9-11位，其他数字开头8-10位，本处参考谷歌（维基百科也没有说清楚））
				if (preg_match(
					'/^0[0-9]{8,10}$|^[1-9][0-9]{7,9}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//Norway      挪威
			case $country_code === '47':
				//挪威手机号码验证（以4或者9开头的八位  ,或者58开头的12位，59开头的8位  本处参考维基百科）
				if (preg_match(
					'/^4[0-9]{7}$|^9[0-9]{7}$|^58[0-9]{10}$|^59[0-9]{6}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//Poland     波兰
			case $country_code === '48':
				//波兰手机号码验证（5,6,7，8开头的九位数，本处参考维基百科）
				if (preg_match(
					'/^5[0-9]{8}$|^6[0-9]{8}$|^7[0-9]{8}$|^8[0-9]{8}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//德国  Germany
			case $country_code === '49':
				//德国手机号码合法性验证（移动的号码是015，016.017，016和017大部分是十位，除了0176和01609是11位，015开头都是11位（这里的位数都是除了0以外的位数）参考维基百科）
				if (preg_match(
					'/^015[0-9]{9}$|^15[0-9]{9}$|^016[1-9][0-8][0-9]{6}$|^16[1-9][0-8][0-9]{6}$|^0176[0-9]{8}$|^176[0-9]{8}$|^1609[0-9]{7}$|^01609[0-9]{7}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					if (preg_match(
						'/^017[0-5][0-9]{7}$|^017[7-9][0-9]{7}$|^(17[0-5]|17[7-9])[0-9]{7}$/',
						$tel)
					)
					{
						$mark = 1;
						return $mark;
					}
					else
					{
						return $mark;
					}
				}
			//伯利兹
			case $country_code === '501':
				//(以 $array数组开头的跟四位，参考维基百科)
				$array = array(
					622, 623, 624, 625
				);
				foreach ($array as $Belize)
				{
					if (preg_match('/^'.$Belize.'[0-9]{4}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				return $mark;
			//危地马拉 Guatemala
			case $country_code === '502':
				//(以 3,4,5开头的跟7位，参考维基百科)
				$array = array(
					3, 4, 5
				);
				foreach ($array as $Guatemala)
				{
					if (preg_match('/^'.$Guatemala.'[0-9]{7}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				return $mark;
			//萨尔瓦多   El Salvador 
			case $country_code === '503':
				//(以 7开头的跟7位，或者7,8,9开头的跟6位。参考维基百科)
				if (preg_match('/^7[0-9]{7}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					if (preg_match('/^7[0-9]{6}$|^8[0-9]{6}$|^9[0-9]{6}$/',
						$tel)
					)
					{
						$mark = 1;
						return $mark;
					}
				}
				return $mark;
			//洪都拉斯    Honduras 
			case $country_code === '504':
				//(以 3,7,8,9开头的跟7位。参考维基百科)
				if (preg_match(
					'/^7[0-9]{7}$|^8[0-9]{7}$|^9[0-9]{7}$|^3[0-9]{7}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//尼加拉瓜    Nicaragua 
			case $country_code === '505':
				//(以 8开头的跟7位。参考维基百科)
				if (preg_match('/^8[0-9]{7}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//哥斯达黎加   Costa Rica 
			case $country_code === '506':
				//(以 83,88,89开头的跟6位。参考维基百科)
				if (preg_match(
					'/^83[0-9]{6}$|^88[0-9]{6}$|^89[0-9]{6}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//巴拿马  Panama  
			case $country_code === '507':
				//(以 6开头的跟7位。参考维基百科)
				if (preg_match('/^6[0-9]{7}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			// 海地
			case $country_code === '509':
				//(以 32-39开头的跟6位或者以4开头的跟7位。参考维基百科)
				if (preg_match('/^3[2-9][0-9]{6}$|^4[0-9]{7}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//Peru     秘鲁
			case $country_code === '51':
				//秘鲁手机号码验证（以9开头的跟8位，总共九位。参考维基百科）
				if (preg_match('/^9[0-9]{8}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//Mexico   墨西哥
			case $country_code === '52':
				//墨西哥 手机号码验证（以1开头跟10位。总共11位，1后面跟的是地区码，此处参考维基百科）
				if (preg_match('/^1[0-9]{10}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//古巴  Cuba
			case $country_code === '53':
				//(以 5开头的跟7位。总共8位，参考维基百科)
				if (preg_match('/^5[0-9]{7}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//阿根廷号码验证
			case $country_code === '54':
				//阿根廷手机号码验证（阿根廷的手机号码是区号加号码，但是号码总数为10位，如果国外拨打需加9，但是发短信又
				//不需要加9，但是维基百科说虽然大部分不需要加9，但是还需要测试，所以我这里加9或者不加9都可以
				//谷歌是只要第一个是9并且是11位就过或者是09开头的12位，此处参考维基百科）
				if (preg_match('/^9[0-9]{10}$|[0-9]{10}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//  Brasil 	巴西
			case $country_code === '55':
				//巴西手机号码合法性验证（以6,7,8,9开头的跟9位，总共10位，参考维基百科）
				if (preg_match('/^[6-9][0-9]{9}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//  chile   智利
			case $country_code === '56':
				//智利手机号码合法性验证（以5-9开头的跟7位，总共八位，参考维基百科。）
				if (preg_match('/^[5-9][0-9]{7}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//哥伦比亚  Colombia
			case $country_code === '57':
				//手机号码合法性验证（以数组$array的值开头的跟7位，总共10位，参考维基百科。）
				$array = array(
					300, 301, 302, 304, 310, 311, 312, 313, 314, 320, 321,
					315, 316, 317, 318
				);
				foreach ($array as $Colombia)
				{
					if (preg_match('/^'.$Colombia.'[0-9]{7}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				return $mark;
			//委内瑞拉  Venezuela
			//以 $array数组开头的跟7位。，参考维基百科
			case $country_code === '58':
				$array = array(
					412, 414, 424, 415, 416, 426, 417, 418
				);
				foreach ($array as $Venezuela)
				{
					if (preg_match('/^'.$Venezuela.'[0-9]{7}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				return $mark;
			//玻利维亚
			case $country_code === '591':
				//以6,7开头跟7位，总共8位，参考维基百科
				if (preg_match('/^[6-7][0-9]{7}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//圭亚那
			case $country_code === '592':
				//以609 – 629，638 – 639，640 – 658开头跟4位，总共7位，参考维基百科（不确定）
				if (preg_match(
					'/^609[0-9]{4}$|^6[1-2][0-9][0-9]{4}$|^638[0-9]{4}$|^639[0-9]{4}$|^64[0-9][0-9]{4}$|^65[0-8][0-9]{4}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//厄瓜多尔
			case $country_code === '593':
				//以8,9开头跟7位，总共8位，参考维基百科
				if (preg_match('/^[8-9][0-9]{7}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//法属圭亚那
			case $country_code === '594':
				//以694开头跟6位，总共9位，参考维基百科
				if (preg_match('/^694[0-9]{6}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//巴拉圭  Paraguay
			//以数组$array数值开头的跟6位。总共9位。参考维基百科
			case $country_code === '595':
				$array = array(
					991, 992, 993, 995, 971, 972, 973, 975, 976, 981, 982,
					983, 984, 985, 961, 963
				);
				foreach ($array as $Paraguay)
				{
					if (preg_match('/^'.$Paraguay.'[0-9]{6}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				return $mark;
			// 马提尼克
			case $country_code === '596':
				//以696开头跟6位，总共9位，参考维基百科
				if (preg_match('/^696[0-9]{6}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//苏里南
			case $country_code === '597':
				//以71,72,75,81-89开头跟5位，总共7位，参考维基百科
				if (preg_match(
					'/^71[0-9]{5}$|^72[0-9]{5}$|^75[0-9]{5}$|^8[1-9][0-9]{5}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//乌拉圭
			case $country_code === '598':
				//以09或者9开头跟7位(第一个不能为0)，参考维基百科（它只说需加09，但是按照以往的经验，海外拨打省略前面的0）
				if (preg_match(
					'/^09[1-9][0-9]{6}$|^9[1-9][0-9]{6}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//荷属安的列斯
			case $country_code === '599':
				//以1或者6开头跟6位，总共7位，参考维基百科
				if (preg_match('/^1[0-9]{6}$|^6[0-9]{6}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//Malaysia    马来西亚
			case $country_code === '60':
				//马来西亚 手机号码验证（马来西亚以1开头的8位或者9位，（以8开头的8到9位，这个不确定）此处参考维基百科）
				if (preg_match('/^1[0-9]{7,8}$|^8[0-9]{7,8}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//澳大利亚号码验证   Australia
			case $country_code === '61':
				//**************前端注意                     //澳大利亚手机号码验证（维基百科说必须以04开头的10位。谷歌以4开头的9位也可以。此处参考谷歌）
				if (preg_match('/^04[0-9]{8}$|4[0-9]{8}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//印度尼西亚手机号码验证Indonesia
			case $country_code === '62':
				//印度尼西亚手机号码验证（以$array数组开头的跟八位，以$arrayt数组开头的跟七位，以$arraytt数组开头的跟六位）
				$array   = array(
					814, 815, 858, 856, 857, 812, 813, 821, 822, 852, 853,
					818, 819, 859, 874, 876, 877, 878, 879, 896, 838, 832, 827,
					888, 828
				);
				$arrayt  = array(
					881, 814, 815, 816, 855, 856, 811, 812, 817, 819, 818,
					899, 898, 897, 838, 832, 831
				);
				$arraytt = array(
					882, 816, 811, 817, 819, 818, 832
				);
				foreach ($array as $Indonesia)
				{
					if (preg_match('/^'.$Indonesia.'[0-9]{8}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				foreach ($arrayt as $Indonesia)
				{
					if (preg_match('/^'.$Indonesia.'[0-9]{7}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				foreach ($arraytt as $Indonesia)
				{
					if (preg_match('/^'.$Indonesia.'[0-9]{6}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				return $mark;
			//Philippines     菲律宾
			case $country_code === '63':
				$array = array(
					905, 906, 915, 916, 917, 926, 927, 935, 936, 937, 994,
					973, 979, 938, 907, 908, 909, 910, 912, 918, 919, 920, 921,
					928, 929, 930, 938, 939, 946, 947, 948, 949, 989, 999, 922,
					923, 932, 933, 934, 942, 943
				);
				//菲律宾手机号码验证（以数组$array的值开头的十位号码，参考维基百科）
				foreach ($array as $Philippines)
				{
					if (preg_match('/^'.$Philippines.'[0-9]{7}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				return $mark;
			//New Zealand       新西兰
			case $country_code === '64':
				//新西兰手机号码验证（以2开头的跟7到9位，总共8-10位，（不确定是否可以省略前面的0，此处不省略。）参考维基百科）
				if (preg_match('/^2[0-9]{7,9}$|^02[0-9]{7,9}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//Singapore     新加坡
			case $country_code === '65':
				//新加坡 手机号码验证（参考维基百科8,9开头的8位数）
				if (preg_match('/^8[0-9]{7}$|^9[0-9]{7}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//泰国
			case $country_code === '66':
				//以8开头的9位，泰国国内拨打要多个0，但是省外拨打就没了，参考维基百科
				if (preg_match('/^8[0-9]{8}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//文莱
			case $country_code === '673':
				//以7或者8开头跟6位，总共7位，参考维基百科
				if (preg_match('/^7[0-9]{6}$|^8[0-9]{6}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//瑙鲁 Nauru
			case $country_code === '674':
				//以$array数组开头的跟四位，总共七位，参考维基百科
				$array = array(
					555, 556, 557, 558, 559
				);
				foreach ($array as $Nauru)
				{
					if (preg_match('/^'.$Nauru.'[0-9]{4}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				return $mark;
			//巴布亚新几内亚
			case $country_code === '675':
				//以71,72,76开头的跟六位，以731-739开头的跟五位，总共都是8位，参考维基百科
				if (preg_match(
					'/^71[0-9]{6}$|72[0-9]{6}$|73[1-9][0-9]{5}$|76[0-9]{6}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//汤加
			case $country_code === '676':
				//以87,88,89,15-19开头的跟3位,总共是5位，参考维基百科
				if (preg_match(
					'/^8[7-9][0-9]{3}$|1[5-9][0-9]{3}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//所罗门群岛
			case $country_code === '677':
				//以74,75开头的跟5位,总共是7位，参考维基百科
				if (preg_match('/^74[0-9]{5}$|75[0-9]{5}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//瓦努阿图
			case $country_code === '678':
				//以53-56,59,70-71,73-77开头的跟5位,572-575开头的跟4位，总共是7位，参考维基百科
				if (preg_match(
					'/^5[3-6][0-9]{5}$|59[0-9]{5}$|7[0-1][0-9]{5}$|7[3-7][0-9]{5}$|57[2-5][0-9]{4}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//斐济
			case $country_code === '679':
				//以7开头的跟6位,以92开头的跟5位，以99开头的跟5位，总共是7位，参考维基百科
				if (preg_match(
					'/^7[0-9]{6}$|92[0-9]{5}$|99[0-9]{5}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//帕劳
			case $country_code === '680':
				//以775,779，620,630,640,660,680,690开头的跟4位，参考维基百科
				if (preg_match(
					'/^775[0-9]{4}$|779[0-9]{4}$|620[0-9]{4}$|620[0-9]{4}$|640[0-9]{4}$|660[0-9]{4}$|680[0-9]{4}$|690[0-9]{4}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//库克群岛
			case $country_code === '682':
				//以5,7开头的跟4位，总共都是5位参考维基百科
				if (preg_match('/^5[0-9]{4}$|7[0-9]{4}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//美属萨摩亚(东萨摩亚)
			case $country_code === '684':
				//7位，极度不确定，参考维基百科
				if (preg_match('/^[0-9]{7}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			// 萨摩亚
			case $country_code === '685':
				//以72,75,76,77开头的跟5位,总共是7位，参考维基百科
				if (preg_match(
					'/^72[0-9]{5}$|^7[5-7][0-9]{5}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			// 基里巴斯，吉尔伯特群岛
			case $country_code === '686':
				//以6或9开头的跟4位,总共是5位，参考维基百科
				if (preg_match('/^6[0-9]{4}$|^9[0-9]{4}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			// 法属波利尼西亚
			case $country_code === '689':
				//以2或7开头的跟5位,30-35开头的跟4位，3917，4113，4114,4116,4117开头的跟2位，总共是6位，参考维基百科
				if (preg_match(
					'/^2[0-9]{5}$|^7[0-9]{5}$|^3[0-5][0-9]{4}$|^3917[0-9]{2}$|^4113[0-9]{2}$|^4114[0-9]{2}$|^4116[0-9]{2}$|^4117[0-9]{2}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//Russia       俄罗斯
			case $country_code === '7':
				//俄罗斯手机号码验证（以9开头的10位。或者以8开头的11位）本处参考维基百科）
				if (preg_match('/^9[0-9]{9}$|^8[0-9]{10}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//  japan  日本
			case $country_code === '81':
				//日本手机号码验证（谷歌没有参考。根据维基百科是9位或者十位，然后更具体的就是区域号什么的。但是上网搜一般都说是080,090,070开头的十一位
				//发短信不知道要不要去掉前面的0.此处参考网上的）
				if (preg_match(
					'/^080[0-9]{8}$|090[0-9]{8}$|070[0-9]{8}$|80[0-9]{8}$|90[0-9]{8}$|70[0-9]{8}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//韩国
			case $country_code === '82':
				//维基百科01开头的十一位，因为海外拨打省略一个0，所以就是1开头的十位
				if (preg_match('/^1[0-9]{9}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//越南
			case $country_code === '84':
				//以01开头的11位，以09开头的10位，以1开头的10位，以9开头的9位，
				if (preg_match(
					'/^09[0-9]{8}$|^01[0-9]{9}$|^1[0-9]{9}$|^9[0-9]{8}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//朝鲜
			case $country_code === '850':
				//030或者30开头跟8位，总共10-11位参考维基百科
				if (preg_match('/^030[0-9]{8}$|^30[0-9]{8}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//香港手机号码验证
			case $country_code === '852':
				//香港手机号码验证（维基百科说手机号都是9或者6或者5或者8开头（不需要加前缀0），并且只有8位,但是谷歌的手机发送是只要八位就可以，此处参考维基百科）
				if (preg_match(
					'/^5[0-9]{7}$|^6[0-9]{7}$|^9[0-9]{7}$|^8[0-9]{7}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//****//澳门手机号码验证
			case $country_code === '853':
				//澳门手机号码验证（8位6开头,或者9位06开头，这个谷歌没有，维基百科也没有。无处参考 ）
				if (preg_match('/^6[0-9]{7}$|^06[0-9]{7}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//柬埔寨
			case $country_code === '855':
				//以0+下面数组开头的9-10位，不以0开头的8-9位。参考维基百科和百度 ，不确定
				$array = array(
					11, 99, 85, 76, 12, 17, 92, 95, 89, 77, 78, 79, 13, 80,
					83, 84, 15, 16, 81, 98, 90, 67, 68, 10, 93, 69, 70, 66, 18,
					88, 97, 19
				);
				foreach ($array as $Uzbekistan)
				{
					if (preg_match(
						'/^0'.$Uzbekistan.'[0-9]{6,7}$|^'.$Uzbekistan.
							'[0-9]{6,7}$/', $tel)
					)
					{
						$mark = 1;
						return $mark;
					}
				}
				return $mark;
			//老挝
			case $country_code === '856':
				//20开头跟6位，总共8位,参考维基百科
				if (preg_match('/^20[0-9]{6}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//中国大陆
			case $country_code === '86':
				//没有154
				//13,15,18开头的手机号码，和145,147开头的  (参考谷歌，前面加0也可以)
				if (preg_match(
					"/^(13[0-9]|(15[0-3])|(15[5-9])|18[0-9]|145|147)[0-9]{8}$/",
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					if (preg_match(
						"/^(013[0-9]|015[0-3]|015[5-9]|018[0-9]|0145|0147)[0-9]{8}$/",
						$tel)
					)
					{
						$mark = 1;
						return $mark;
					}
					else
					{
						return $mark;
					}
				}
			//   //孟加拉国Bangladesh
			case $country_code === '880':
				//孟加拉国手机号码合法性验证（参考谷歌手机发送，只要8-11位的号码就可以,以0开头则是9-12位，此处参考谷歌，看了維基百科，沒有明確的答案）
				if (preg_match('/^[0-9]{8,11}$|^0[0-9]{8,11}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//台湾手机号码验证
			case $country_code === '886':
				//******前端注意			//台湾手机号码验证（以9开头的后面跟着8位，参考维基百科）
				if (preg_match("/^9[0-9]{8}$/", $tel))
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//土耳其
			case $country_code === '90':
				//参考维基百科10位
				if (preg_match('/^[0-9]{10}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//印度  india
			case $country_code === '91':
				//印度手机号码验证（9,8，7开头的10位,参考维基百科）
				if (preg_match('/^[7-9][0-9]{9}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//Pakistan     巴基斯坦
			case $country_code === '92':
				//巴基斯坦手机号码验证（以数组$array开头的跟7位，参考维基百科）
				$array = array(
					300, 301, 302, 303, 304, 305, 306, 307, 308, 309, 364,
					341, 342, 343, 344, 345, 346, 347, 321, 322, 323, 324, 331,
					332, 333, 334, 335, 336, 312, 313, 314, 315, 355
				);
				foreach ($array as $Pakistan)
				{
					if (preg_match('/^'.$Pakistan.'[0-9]{7}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				return $mark;
			//阿富汗
			case $country_code === '93':
				//700-708，795-799，786-789,771-779开头的跟6位，参考维基百科
				if (preg_match(
					'/^70[0-8][0-9]{6}$|^79[5-9][0-9]{6}$|^78[6-9][0-9]{6}$|^77[1-9][0-9]{6}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//Sri Lanka       斯里兰卡
			case $country_code === '94':
				//斯里兰卡手机号码验证（71,72,75,77,78开头的跟7位，参考维基百科）
				if (preg_match(
					'/^71[0-9]{7}$|^72[0-9]{7}$|^75[0-9]{7}$|^77[0-9]{7}$|^78[0-9]{7}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//缅甸
			//				cdma450手机号码=09后边加  七  位数【旧式】
			//cdma450手机号码=09后边加  八  位数【新式】
			//cdma800手机号码=09后边加  八  位数
			//旧式gsm 手机号码=09后边加  七  位数【旧式】
			//新式gsm 手机号码=09后边加  八  位数【新式】
			//
			//七位数的手机sim卡【旧式】是以前政府卖一百五十万缅币的手机卡
			//八位数的手机sim卡【旧式】是目前政府卖       五十万缅币的手机卡
			case $country_code === '95':
				//(09开头的跟五位，或者9开头的跟6位。总共是7位。参考维基百科)
				if (preg_match('/^09[0-9]{5}$|^9[0-9]{6}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//马尔代夫
			case $country_code === '960':
				//76-79,73，96-99开头的跟5位，参考维基百科
				if (preg_match(
					'/^7[6-9][0-9]{5}$|^73[0-9]{5}$|^9[6-9][0-9]{5}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			// 黎巴嫩
			case $country_code === '961':
				//70,71,76开头的跟6位，参考维基百科
				if (preg_match(
					'/^70[0-9]{6}$|^71[0-9]{6}$|^76[0-9]{6}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			// 约旦
			case $country_code === '962':
				//77,78,79开头的跟7位，参考维基百科
				if (preg_match(
					'/^77[0-9]{7}$|^78[0-9]{7}$|^79[0-9]{7}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			// 叙利亚  Syria
			case $country_code === '963':
				//以$array数组开头的跟6位，参考维基百科
				$array = array(
					931, 932, 933, 935, 988, 990, 991, 992, 993, 994, 998,
					999, 942, 944, 945, 947, 949, 943, 944, 945, 955, 956, 962,
					966, 969
				);
				foreach ($array as $Syria)
				{
					if (preg_match('/^'.$Syria.'[0-9]{6}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				return $mark;
			// 伊拉克  Iraq
			case $country_code === '964':
				//73-79开头的跟8位，总共10位，参考维基百科
				if (preg_match('/^7[3-9][0-9]{8}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			// 科威特  Kuwait
			case $country_code === '965':
				//以$array数组开头的跟6位，参考维基百科
				$array = array(
					60, 65, 66, 67, 69, 90, 94, 96, 97, 99, 50, 55
				);
				foreach ($array as $Kuwait)
				{
					if (preg_match('/^'.$Kuwait.'[0-9]{6}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				return $mark;
			//Saudi Arabia      沙特阿拉伯 
			case $country_code === '966':
				//沙特阿拉伯 手机号码验证（参考维基百科，以50,53-56,58-59开头的9位，以050,053-056,058-059开头的10位，）
				if (preg_match(
					'/^05[3-6][0-9]{7}$|^050[0-9]{7}$|^05[8-9][0-9]{7}$|^5[3-6][0-9]{7}$|^50[0-9]{7}$|^5[8-9][0-9]{7}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			// 也门  Yemen
			case $country_code === '967':
				//71，73,77开头的跟7位，总共9位，参考维基百科
				if (preg_match(
					'/^71[0-9]{7}$|^73[0-9]{7}$|^77[0-9]{7}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			// 阿曼 Oman
			case $country_code === '968':
				//92-99开头的跟6位，总共8位，参考维基百科
				if (preg_match('/^9[2-9][0-9]{6}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//阿拉伯联合酋长国    United Arab Emirates 
			case $country_code === '971':
				//以50，55,56，开头的9位数，维基百科内容不够详细，此处参考维基百科+已经存在的用户号码+百度
				if (preg_match(
					'/^50[0-9]{7}$|^55[0-9]{7}$|^56[0-9]{7}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//以色列   israel
			case $country_code === '972':
				//以色列手机号码验证（以50,52,54，56,57,59开头的跟7位。参考维基百科）
				if (preg_match(
					'/^50[0-9]{7}$|^52[0-9]{7}$|^54[0-9]{7}$|^56[0-9]{7}$|^57[0-9]{7}$|^59[0-9]{7}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//巴林
			case $country_code === '973':
				//以3开头的跟7位，总共八位，参考维基百科
				if (preg_match('/^3[0-9]{7}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//卡塔尔
			case $country_code === '974':
				//以3,5,6,7开头的跟7位，总共八位，参考维基百科
				if (preg_match(
					'/^3[0-9]{7}$|5[0-9]{7}$|6[0-9]{7}$|7[0-9]{7}$/', $tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//不丹
			case $country_code === '975':
				//以17开头的跟6位，总共八位，参考维基百科
				if (preg_match('/^17[0-9]{6}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//蒙古 Mongolia
			case $country_code === '976':
				//以数组$array开头的跟6位，总共八位，参考维基百科
				$array = array(
					99, 95, 91, 96, 88
				);
				foreach ($array as $Mongolia)
				{
					if (preg_match('/^'.$Mongolia.'[0-9]{6}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				return $mark;
			//尼泊尔  Nepal
			case $country_code === '977':
				//总共八位，参考维基百科
				if (preg_match('/^[0-9]{8}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//伊朗   Iran 
			case $country_code === '98':
				//以9开头的10位数，或者以09开头的11位数。不是很清楚国外打电话给伊朗，要不要去掉前面的0.参考维基百科
				if (preg_match('/^9[0-9]{9}$|^09[0-9]{9}$/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				else
				{
					return $mark;
				}
			//塔吉克斯坦
			case $country_code === '992':
				//（以917开头的跟六位，以9186,9188开头的跟5位，以981开头的跟六位，951开头的跟6六位。总共9位，参考维基百科）
				if (preg_match(
					'/^917[0-9]{6}$|9186[0-9]{5}$|9188[0-9]{5}$|981[0-9]{6}$|951[0-9]{6}$|/',
					$tel)
				)
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//土库曼斯坦
			case $country_code === '993':
				//总共八位，参考维基百科
				if (preg_match('/^[0-9]{8}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//阿塞拜疆  Azerbaijan
			case $country_code === '994':
				//以数组$array开头的跟7位，60540开头的跟4位，总共都是9位。参考维基百科
				$array = array(
					50, 51, 55, 70, 77, 40, 44
				);
				foreach ($array as $Azerbaijan)
				{
					if (preg_match('/^'.$Azerbaijan.'[0-9]{7}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				if (preg_match('/^60540[0-9]{4}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//格鲁吉亚
			case $country_code === '995':
				//以数组$array开头的跟6位。总共9位。参考维基百科
				$array = array(
					514, 551, 555, 557, 558, 568, 570, 571, 574, 577, 591,
					592, 593, 595, 596, 597, 598, 599, 790, 791
				);
				foreach ($array as $Georgia)
				{
					if (preg_match('/^'.$Georgia.'[0-9]{6}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				return $mark;
			//吉尔吉斯斯坦  Kyrgyzstan
			case $country_code === '996':
				//以数组$array开头的跟7位，700开头的跟6位，总共都是9位。参考维基百科
				$array = array(
					51, 54, 55, 57, 56, 77
				);
				foreach ($array as $Kyrgyzstan)
				{
					if (preg_match('/^'.$Kyrgyzstan.'[0-9]{7}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				if (preg_match('/^700[0-9]{6}$/', $tel))
				{
					$mark = 1;
					return $mark;
				}
				return $mark;
			//乌兹别克斯坦  Uzbekistan
			case $country_code === '998':
				//以数组$array开头的跟7位，总共9位，参考维基百科
				$array = array(
					90, 91, 92, 93, 97, 98, 99
				);
				foreach ($array as $Uzbekistan)
				{
					if (preg_match('/^'.$Uzbekistan.'[0-9]{7}$/', $tel))
					{
						$mark = 1;
						return $mark;
					}
				}
				return $mark;
			default:
				return $mark;
		}
	}
} // End international
