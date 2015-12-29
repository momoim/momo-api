<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * LBS helper class.
 *
 */
class lbs_Core {

    /**
     * 基于google map，根据经纬度获取地址

     * @param float $longitude
     * @param float $latitude
     * @param int $level
     */
	public static function get_address_by_location($longitude,$latitude,$level=0) {
		$res = json_decode(file_get_contents("http://ditu.google.cn/maps/geo?q=$longitude,$latitude&output=json"));
        if($res->Status->code==200){
            $res_address = $res->Placemark;
            $address = $res_address[$level]->address;
            $address_r = explode(' ',$address);
            if(is_array($address_r)) {
            	return $address_r[0];
            }
        }
        return null;
	}
}
