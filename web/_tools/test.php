<?php
$mg_instance = new Mongo('mongodb://10.1.242.243:27027,10.1.242.241:27027');
$m = $mg_instance->selectDB ( 'momo_v3' );
$feed = $m->selectCollection ( 'feed_new' );
$col = $feed->find ( array('mix_id'=>'1'))->sort ( array ('last_updated' => - 1 ) )->limit (20);
$arr = iterator_to_array ( $col );
print_r($arr);
/*
phpinfo();die;
$queue_names = array(
'momoim_10244811',
'momoim_10260172',
'momoim_10434375',
'momoim_10724958',
'momoim_11063961',
'momo_sym_11518110',
'momo_sym_11582604',
'momo_sym_11888772',
'momo_sym_12138714',
'momo_sym_12138926',
'momoim_12138955',
'momoim_12139737',
'momo_sym_12139738',
'momoim_2113438', 
);
foreach($queue_names as $queue_name){
	$cnn = new AMQPConnect(array(
        'host' => '192.168.94.20',
        'port' => 5672,
        'login' => 'sifusf*4&5&343!',
        'password' => 'ndpassw0rd',
        'vhost' => '/'
    ));
    $exchange = new AMQPExchange($cnn, 'momo_nd');
    $exchange_im = new AMQPExchange($cnn, 'momo_im');
    $queue = new AMQPQueue($cnn);
    $queue->declare($queue_name, AMQP_DURABLE);
    $queue->delete($queue_name);
}
*/