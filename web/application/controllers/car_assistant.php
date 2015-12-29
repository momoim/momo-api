<?php
defined('SYSPATH') OR die('No direct script access.');
/*
 * [MOMO API] (C)1999-2012 ND Inc.
 * 应用模型文件
 */
/**
 * 应用模型
 */
class Car_Assistant_Controller extends Controller {

	protected $model;

	public function __construct()
	{
		define("TOKEN", "928f57054e1107e61b1602f04fb302fd");
		parent::__construct();
		$this->model = Car_Assistant_Model::instance();
	}

	public function index()
	{
		$echoStr = $_GET["echostr"];
        //valid signature , option
        if($this->checkSignature()){
        	$this->responseMsg();
        }
	}
	
	public function query() {
		require_once Kohana::find_file('vendor', 'wechat/car_violation');
    	$data = $this->get_data();
    	$plate_number = $data['plate_number']?trim($data['plate_number']):'';
    	$vehicle_number = $data['vehicle_number']?trim($data['vehicle_number']):'';
    	$user_id = $data['user_id']?trim($data['user_id']):'';
    	$nickname = $data['nickname']?trim($data['nickname']):'';
    	$violation_result = $return = array();
    	if($plate_number && $vehicle_number) {
    		$car_assistant = $this->model->get_car_assistant($plate_number);
    		if($car_assistant && $car_assistant['vehicle_number']!=$vehicle_number) {
    			$this->send_response(400, NULL,'车架号错误');
    		}elseif($car_assistant['last_break_time']) {
    			$break_rule_arr = $this->model->get_break_rules($plate_number);
    			$return['total'] = '您最近总共有'.$car_assistant['break_total'].'项未处理的违法';
				$return['data'] = $break_rule_arr;
    		} else {
    			$cv = new car_violation('fz');
				$violation_result = $cv->get($plate_number,$vehicle_number);
				if($violation_result && !$violation_result['error']) {
					$total_num = $violation_result['total'];
					if($car_assistant['last_break_time'] < $violation_result['data'][$total_num-1]['break_time']) {
						$setters = array('break_total'=>$total_num,'last_break_time'=>$violation_result['data'][$total_num-1]['break_time']);
						if(empty($car_assistant['user_id']))
							$setters['user_id'] = $user_id;
						if(empty($car_assistant['nickname']))
							$setters['nickname'] = $nickname;
						$this->model->update_car_assistant($plate_number,$setters);
						if($violation_result['data'] && is_array($violation_result['data'])) {
							$user_id = $car_assistant['user_id']?$car_assistant['user_id']:$user_id;
							$break_rule_no = $this->get_break_rule_no($plate_number);
							foreach($violation_result['data'] as $k => $v) {
								if(!in_array($v['no'],$break_rule_no))
									$this->model->add_break_rules($user_id,$v['plate_number'],$v['no'],$v['break_time'],$v['break_address'],$v['break_code'],$v['detail_url']);
							}
						}
					}
					$return['total'] = '您最近总共有'.$total_num.'项未处理的违法';
					$return['data'] = $violation_result['data'];
				} else {
					$this->send_response(400, NULL,$violation_result['error']);
				}
    		}
			$this->send_response(200, $return);
    	}
    	$this->send_response(400, NULL,'车牌号或者车架号为空');
	}
	
	private function update_user($username,$createtime,$keyword) {
//	public function update_user() {
///    	$data = $this->get_data();
//    	$keyword = $data['keyword']?trim($data['keyword']):'';
//    	$createtime = $data['createtime']?trim($data['createtime']):'';
//    	$username = $data['username']?trim($data['username']):'';
		require_once Kohana::find_file('vendor', 'wechat/car_violation');
		$mpname = 'drvier.helpers@gmail.com';
		$cv = new car_violation('fz');
		$res = $cv->get_user($mpname,$createtime,$keyword);
		$res = json_decode($res['data']);
		if($res->code == 200) {
			$res = json_decode($res->data);
			if($res->nickName && $res->fakeId) {
				if($this->model->update_userinfo_by_name($username,$res->fakeId,$res->nickName)) {
					$content = '请点击下面的链接：<a href="http://momo.im?fakeid='.$res->fakeId.'">去绑定</a>';
					$cv->send_sms($mpname,$res->fakeId,$content);
				} else {
					$content = $username.'_'.$res->fakeId.'_'.$res->nickName.'_'.$createtime;
					$cv->send_sms($mpname,$res->fakeId,$content);
				}
			}
		} else {
			return $res->data.'_'.$createtime.'_'.$keyword;
		}
	}
	
	public function daily_rotate() {
		require_once Kohana::find_file('vendor', 'wechat/car_violation');
    	$data = $this->get_data();
    	$plate_number = $data['plate_number']?trim($data['plate_number']):'';
    	$vehicle_number = $data['vehicle_number']?trim($data['vehicle_number']):'';
    	$verify_token = $data['verify_token']?trim($data['verify_token']):'';
    	if($verify_token != md5('kljsf23498&*df?ss_'.$plate_number.$vehicle_number)) {
    		$this->send_response(403, NULL,'非法请求');
    	}
    	$violation_result = $return = array();
    	if($plate_number && $vehicle_number) {
    		$car_assistant = $this->model->get_car_assistant($plate_number);
    		$new_break_rule = array();
    		$cv = new car_violation('fz');
			$violation_result = $cv->get($plate_number,$vehicle_number);
			if($violation_result && !$violation_result['error']) {
				$total_num = $violation_result['total'];
				if($car_assistant['last_break_time'] < $violation_result['data'][$total_num-1]['break_time']) {
					$break_rule_no = $this->get_break_rule_no($plate_number);
					foreach($violation_result['data'] as $v) {
						if(!in_array($v['no'],$break_rule_no))
							$new_break_rule[] = $v;
					}
					if(count($new_break_rule) > 0) {
						$setters['last_rotate_time'] = time();
						$setters['last_break_time'] = $violation_result['data'][$total_num-1]['break_time'];
						$setters['break_total'] = $car_assistant['break_total']+count($new_break_rule);
						$this->model->update_car_assistant($plate_number,$setters);
						$add_time = time();
						
						foreach($new_break_rule as $k => $v) {
							$this->model->add_daily_rotate_log($v['plate_number'],$v['no'],$add_time);
							$this->model->add_break_rules($car_assistant['user_id'],$v['plate_number'],$v['no'],$v['break_time'],$v['break_address'],$v['break_code'],$v['detail_url'],0);
						}
					} else {
						$setters['last_rotate_time'] = time();
						$this->model->update_car_assistant($plate_number,$setters);
					}
				} else {
					$setters['last_rotate_time'] = time();
					$this->model->update_car_assistant($plate_number,$setters);
				}
    		}
			$this->send_response(200, array('count'=>count($new_break_rule)));
    	}
    	$this->send_response(400, NULL,'车牌号或者车架号为空');
	}
	
	public function rotate() {
		$return = array();
		$delivered_car = array();
		$result = $this->model->rotate();
		if($result) {
			foreach($result as $k => $v) {
				if(!in_array($v['plate_number'],$delivered_car))
					$delivered_car[] = $v['plate_number'];
				$return[$v['user_id']][] = $v;
			}
		}
		if(count($delivered_car) > 0) {
			foreach($delivered_car as $plate_number) {
				$this->model->update_break_rules($plate_number,array('delivered'=>1));
			}
		}
		$this->send_response(200, $return);
	}
	
	
	public function detail() {
		$result = array();
    	$data = $this->get_data();
    	$plate_number = $data['plate_number']?trim($data['plate_number']):'';
    	$verify_token = $data['verify_token']?trim($data['verify_token']):'';
    	if($verify_token != md5('kljsf23498&*df?ss_'.$plate_number)) {
    		$this->send_response(403, NULL,'非法请求');
    	}
    	$result = $this->model->get_break_rules($plate_number);
		$this->send_response(200, $result);
	}
	
	private function get_break_rule_no($plate_number) {
		$result = array();
		$break_rule_arr = $this->model->get_break_rules($plate_number);
		if($break_rule_arr) {
			foreach($break_rule_arr as $v) {
				$result[] = $v['no'];
			}
		}
		return $result;
	}
	
	private function responseMsg()
    {
		//get post data, May be due to the different environments
		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

      	//extract post data
		if (!empty($postStr)){
                
              	$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
                $fromUsername = $postObj->FromUserName;
                $toUsername = $postObj->ToUserName;
                $keyword = trim($postObj->Content);
                $createTime = (int)$postObj->CreateTime;
                $msgId = $postObj->MsgId;
                $time = time();
	            $msgType = "text";
                $textTpl = "<xml>
							<ToUserName><![CDATA[%s]]></ToUserName>
							<FromUserName><![CDATA[%s]]></FromUserName>
							<CreateTime>%s</CreateTime>
							<MsgType><![CDATA[%s]]></MsgType>
							<Content><![CDATA[%s]]></Content>
							<FuncFlag>0</FuncFlag>
							</xml>";             
				if(!empty( $keyword ))
                {
                	if(preg_match_all('/闽([\w\d|\W\D]*)#([\w\d|\W\D]*)/is',$keyword,$match)) {
                		$plate_number = '闽'.$match[1][0];
                		$vehicle_number = $match[2][0];
                		if(!$this->model->get_car_assistant($plate_number)) {
		                	if($this->model->add_car($fromUsername,$toUsername,$keyword,$plate_number,$vehicle_number,$msgType,$time)) {
		                		$contentStr = '尊敬的'.$plate_number."的车主您好，您已经成功订阅爱车微助手的交通违章服务!";
		                		$contentStr .= "\n在接下来的日子里，如果您的车子有违章我们将在第一时间通知您，多谢您的支持。";
		                	} else {
		                		$contentStr = '尊敬的'.$plate_number."的车主您好，当前服务器繁忙订阅失败，请稍候再试下，给您造成的不便非常抱歉";
		                	}
                		} else {
                			//$contentStr = '尊敬的'.$plate_number."的车主您好，您已经订阅了爱车微助手的交通违章服务";
                		}
	                } elseif (strtolower($keyword) == 'y') {
	                	$contentStr = '请输入以下3位数字:'.$this->_rand_num();
	                }  elseif (preg_match('/^[\d]{3}$/',$keyword)) {
	                	$contentStr = $this->update_user($fromUsername,$createTime,$keyword);
	                } elseif(true){
	                    $res = file_get_contents('http://api.ai.momo.im/ask?sid='.$fromUsername.'&msg='.urlencode($keyword));
	                    $res = json_decode($res,true);
	                    $contentStr = $res['data'];
	                }else {
	                	$this->im_send($fromUsername,$keyword);
	                	//$contentStr = "欢迎使用爱车微助手！目前我们提供福州市交通违章订阅服务，如果您需要订阅，请回复以下内容：车牌号#车架号后四位，例如：闽A88888#1234";
	                }
                }else{
                	$rand = $this->_rand_num();
                	$contentStr = "欢迎使用爱车微助手！目前我们提供福州市交通违章订阅服务，如果您需要订阅，请回复以下内容：车牌号#车架号后四位，例如：闽A88888#1234";
                }
                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
	            echo $resultStr;
        }else {
        	echo "";
        	exit;
        }
    }
    
	private function _rand_num($len=3) {
        $chars='0123456789';
        mt_srand((double)microtime()*1000000*getmypid());
        $rand='';
        while(strlen($rand)<$len)
            $rand.=substr($chars,(mt_rand()%strlen($chars)),1);
        return $rand;
    }
    
    private function im_send($fromUsername,$content) {
    	$name_to_uid = array(1=>'oeUjxjhtyIjVVKwsNbuSL85gevos',9=>'oeUjxjnDndKD-D0g-3NS8ISIKzgg',23=>'oeUjxjix_Wwhcd4Xg8w4jjt6kZTo');
    	if(!in_array($fromUsername,$name_to_uid)) {
    		return ;
    	}
		foreach($name_to_uid as $k => $v) {
			if($v == $fromUsername) {
				$sender_uid = $k;
				continue;
			}
		}
    	$receiver_uid = 1799;
    	$sender=array('id'=>$sender_uid,'name'=>sns::getrealname($sender_uid),'avatar'=>sns::getavatar($sender_uid));
    	$receiver=array(array('id'=>$receiver_uid,'name'=>sns::getrealname($receiver_uid),'avatar'=>sns::getavatar($receiver_uid)));
    	$content= array('text'=>$content);
		$sms=array(
			'kind'=>'sms',
 			'data'=>array(
    					'id'=>api::uuid(),
    					'sender'=>$sender,
    					'msgtype'=>1,
    					'receiver'=>$receiver,
    					'timestamp'=>ceil(microtime(TRUE)*1000),
						'opt'=>array(),
    					'content'=>$content,
    					'client_id'=>0,
    				)
    			);
		mq::send(json_encode($sms),$receiver_uid.'.'.$sender_uid, 'momo_im');
    }
		
	private function checkSignature()
	{
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];	
        		
		$token = TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}

}
