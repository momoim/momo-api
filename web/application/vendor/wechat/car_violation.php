<?php

include ('valite.php');
include ('car.php');

class car_violation extends car {
	var $city = 'fz';
	
	public function __construct($city='fz') {
		$this->city = $city;
	}
	
	public function get($plate_number,$vehicle_number) {
		$violation_name = 'violation_'.$this->city;
		return $this->$violation_name($plate_number,$vehicle_number);
	}
	
	public function get_user($mpname,$createtime,$keyword) {
		$to_post = array();
		$to_post = array('username'=>$mpname,'createTime'=>$createtime,'keyword'=>$keyword);
		$res = $this->_fopen('http://121.207.242.244:6789/getuser.json',0,http_build_query($to_post),'POST');
		return $res;
	}
	
	public function send_sms($mpname,$fakeid,$content) {
		$to_post = array('username'=>$mpname,'uid'=>$fakeid,'content'=>$content);
		$res = $this->_fopen('http://121.207.242.244:6789/sendmsg.json',0,http_build_query($to_post),'POST');
		return $res;
	}
	
	private function violation_fz($plate_number,$vehicle_number) {
		$result=array();
		$try_times = 0;
		preg_match_all('/闽([\w])([^<]*)/is',$plate_number,$match);
    	$province_number = strtoupper($match[1][0]);
    	$plate_number = trim($match[2][0]);
		while($try_times < 3){
			$res = $this->_fopen('http://fzjj.easdo.com/jtwf/',0,'','GET');
			preg_match("/Set\-Cookie:([^;]*)/i", $res['header'], $matches);
			$cookie = $matches[1];
			$cookie = trim($cookie);
			$res = $this->_fopen('http://fzjj.easdo.com/jtwf/image.jsp',0,'','GET',$cookie);
			file_put_contents('/tmp/captcha.jpg', $res['data']);
			$valite = new valite();
			$valite->setImage('/tmp/captcha.jpg');
			$valite->getHec();
			$captcha_code = $valite->run();
			$to_post = array('carNum'=>$plate_number,'carType'=>urldecode('%D0%A1%D0%CD%C6%FB%B3%B5'),'clsbdm'=>$vehicle_number,'num'=>$province_number,'rand'=>$captcha_code);
			$res = $this->_fopen('http://fzjj.easdo.com/jtwf/queryWfInfo.jsp',0,http_build_query($to_post),'POST',$cookie);
			if(strpos($res['data'],'search-error')) {
				if(preg_match_all('/<div class=\"err\">([^<]*)<\/div>/is',$res['data'],$matches3)) {
					$error = mb_convert_encoding($matches3[1][0],'UTF-8','gbk');
					if(!preg_match('/验证码/is',$error)) {
						$result['error'] = $error;
						break;
					}
				} else {
					$try_times++;
				}
			} else {
				break;
			}
		}
		if(strpos($res['data'],'search-result')) {
			unset($result['error']);
			$result = $this->parse($res['data'],$to_post,$captcha_code);
		}
		return $result;
	}
	
	private function parse($data,$to_post,$captcha_code) {
		$parse_name = 'parse_'.$this->city;
		return $this->$parse_name($data,$to_post,$captcha_code);
	}
	
	private function parse_fz($data,$to_post,$captcha_code,$result = array()) {
		preg_match_all('/<span class=\"empty\">([^<]*)<\/span>/is',$data,$match);
		$total = 0;
		if($match) {
			$total_des = mb_convert_encoding($match[1][0],'UTF-8','gbk');
			preg_match_all('/您最近总共有([\d]*)项未处理的违法/is',$total_des,$num);
			if($num) {
				$total = (int)$num[1][0];
			} else {
				return -1;
			}
		} else {
			return -1;
		}
		if($total) {
			preg_match_all('/<span class=\"co_01\">([\d]*)<\/span><span class=\"co_02\">([^<]*)<\/span><span class=\"co_03\">([^<]*)<\/span><span class=\"co_04\">([^<]*)<\/span><span class=\"co_05\"><a href=\"([^<]*)\" target=\"_blank\">([^<]*)\(/is',$data,$matches2);
			if($matches2[1]) {
				for($i=0;$i<5;$i++) {
					if($matches2[6][$i]) {
						$result[] = array('no'=>$matches2[1][$i],'plate_number'=>'闽'.$matches2[2][$i],'break_time'=>strtotime($matches2[3][$i]),'break_address'=>mb_convert_encoding($matches2[4][$i],'UTF-8','gbk'),'detail_url'=>$matches2[5][$i],'break_code'=>$matches2[6][$i]);
					}
				}
			}
			$page_res = mb_convert_encoding($data,'UTF-8','gbk');
			if(preg_match_all('/<a href=\"([^<]*)\" target=\"_self\">下一页<\/a>/is',$page_res,$matches3)) {
				$next_data = array();
				$next_url = 'http://fzjj.easdo.com/jtwf/'.$matches3[1][0];
				$next_res = $this->_fopen($next_url,0,'','GET');
				return $this->parse_fz($next_res['data'],$to_post,$captcha_code,$result);
			}
			return array('total'=>$total,'data'=>$result);
		}
		return array('total'=>0);
	}
}    



?>

