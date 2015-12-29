<?php
$config = array();
//$config['rabbitmq'] = array(
//    'host' => '192.168.94.20',
//    'port' => 5672,
//    'login' => 'sifusf*4&5&343!',
//    'password' => 'ndpassw0rd',
//    'vhost' => '/'
//);
//    
//$config['mqslave'] = array(
//    'host' => '192.168.94.20',
//    'port' => 5672,
//    'login' => 'sifusf*4&5&343!',
//    'password' => 'ndpassw0rd',
//    'vhost' => '/'
//);    
    
$config['rabbitmq'] = array(
    'host' => '121.207.242.244',
    'port' => 5672,
    'login' => 'sifusf*4&5&343!',
    'password' => 'ndpassw0rd',
    'vhost' => '/'
);
    
$config['mq_slave'] = array(
    'host' => '121.207.242.244',
    'port' => 5672,
    'login' => 'sifusf*4&5&343!',
    'password' => 'ndpassw0rd',
    'vhost' => '/'
);

$exchange = 'momo_sys';
$message = '{"kind":"test"}';
$routekey = 7;
foreach ($config as $server) {
    try{
		if(!class_exists('AMQPConnect')){
			return;
		}
	    $cnn = new AMQPConnect($server);
		$exchange_amqp = new AMQPExchange($cnn, $exchange);
		echo 'send message: ';
		var_dump($exchange_amqp->publish($message, $routekey));
	}catch (Exception $e) {
	    echo "send message = " . $message . " fail \n" .
             $e->getTraceAsString();
	}
    echo '<hr>';
}