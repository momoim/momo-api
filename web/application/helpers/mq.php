<?php defined('SYSPATH') or die('No direct access allowed.');

/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * mq辅助文件
 */

class mq {
    private static  $exchange_amqp_momo_nd = null;
    private static  $exchange_amqp_momo_im = null;
    private static  $exchange_amqp_momo_feed = null;
    private static  $exchange_amqp_momo_sys = null;
    private static  $exchange_amqp_momo_event = null;
    private static  $exchange_amqp_direct = null;
    private static  $cnn = null;
    
    
    public static function send($message, $routekey, $exchange='momo_nd', $appid=0, $uid=0){
		try{
			if(!class_exists('AMQPConnect')){
				return;
			}
			if($exchange == 'momo_nd') {
				self::exchange_amqp_momo_nd()->publish($message, $routekey, AMQP_MANDATORY, array('app_id'=>"$appid", 'user_id'=>"$uid"));
			} elseif($exchange == 'momo_feed') {
				self::exchange_amqp_momo_feed()->publish($message, $routekey, AMQP_MANDATORY, array('app_id'=>"$appid", 'user_id'=>"$uid"));
			} elseif($exchange == 'momo_sys') {
				self::exchange_amqp_momo_sys()->publish($message, $routekey, AMQP_MANDATORY, array('app_id'=>"$appid", 'user_id'=>"$uid"));
			} elseif($exchange == 'momo_im') {
				self::exchange_amqp_momo_im()->publish($message, $routekey, AMQP_MANDATORY, array('app_id'=>"$appid", 'user_id'=>"$uid"));
			} elseif($exchange == 'momo_event') {
				self::exchange_amqp_momo_event()->publish($message, $routekey, AMQP_MANDATORY, array('app_id'=>"$appid", 'user_id'=>"$uid"));
			} else {
				self::exchange_amqp_direct()->publish($message, $routekey, AMQP_MANDATORY, array('app_id'=>"$appid", 'user_id'=>"$uid"));
			}
		}catch (Exception $e) {
		    Kohana::log('error', 
                "发送消息失败 message = " . $message . "\n" .
                 $e->getTraceAsString());
			
            self::resend($message, $routekey, $exchange, $appid, $uid);
		}
	}
	
	public static function resend($message, $routekey, $exchange='momo_nd', $appid=0, $uid=0){
    	try{
    		$obj_conn = new AMQPConnect(Kohana::config('uap.rabbitmq'));
            $obj_exchange = new AMQPExchange($obj_conn, $exchange);
            $obj_exchange->publish($message, $routekey, AMQP_MANDATORY,
					array('app_id'=>"$appid", 'user_id'=>"$uid"));
    	}catch (Exception $e) {
		    Kohana::log('error', 
                "再次发送消息失败 message = " . $message . "\n" .
                 $e->getTraceAsString());
		}   
	}
	
	public static function connect(){
		if (! is_object ( self::$cnn )) {
			self::$cnn = new AMQPConnect(Kohana::config('uap.rabbitmq'));
		}
		return self::$cnn;
	}

	public static function exchange_amqp_momo_im(){
		if (! is_object ( self::$exchange_amqp_momo_im)) {
			self::$exchange_amqp_momo_im = new AMQPExchange(self::connect(), 'momo_im');
		}
		return self::$exchange_amqp_momo_im;
	}
	public static function exchange_amqp_momo_event(){
		if (! is_object ( self::$exchange_amqp_momo_event)) {
			self::$exchange_amqp_momo_event = new AMQPExchange(self::connect(), 'momo_event');
		}
		return self::$exchange_amqp_momo_event;
	}
	public static function exchange_amqp_momo_nd(){
		if (! is_object ( self::$exchange_amqp_momo_nd )) {
			self::$exchange_amqp_momo_nd = new AMQPExchange(self::connect(), 'momo_nd');
		}
		return self::$exchange_amqp_momo_nd;
	}
	
	public static function exchange_amqp_momo_feed(){
		if (! is_object ( self::$exchange_amqp_momo_feed )) {
			self::$exchange_amqp_momo_feed = new AMQPExchange(self::connect(), 'momo_feed');
		}
		return self::$exchange_amqp_momo_feed;
	}
	
	public static function exchange_amqp_momo_sys(){
		if (! is_object ( self::$exchange_amqp_momo_sys )) {
			self::$exchange_amqp_momo_sys = new AMQPExchange(self::connect(), 'momo_sys');
		}
		return self::$exchange_amqp_momo_sys;
	}
	
	public static function exchange_amqp_direct(){
		if (! is_object ( self::$exchange_amqp_direct )) {
			self::$exchange_amqp_direct = new AMQPExchange(self::connect(), 'amq.direct');
		}
		return self::$exchange_amqp_direct;
	}
    
} 
