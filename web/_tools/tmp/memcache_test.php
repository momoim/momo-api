<?php
header("Content-type: text/html; charset=utf-8");
?>
<form action="" method="get">
<input type="text" name="key">
<input type="submit" name="cmd" value="get"> <input type="submit" name="cmd" value="delete">
</form>
<?php
//$config['servers'] = array
//    (
//    	array
//    	(
//    		'host' => '192.168.94.20',
//    		'port' => 11211,
//    		'persistent' => FALSE,
//    	),
//    	array
//    	(
//    		'host' => '192.168.94.20',
//    		'port' => 11211,
//    		'persistent' => FALSE,
//    	),
//    	
//    );
    
$config['servers'] = array
    (
    	array
    	(
    		'host' => '10.1.242.124',
    		'port' => 11211,
    		'persistent' => FALSE,
    	),
    	
    	array
    	(
    		'host' => '10.1.242.125',
    		'port' => 11211,
    		'persistent' => FALSE,
    	)
    	
    );
$cmd = isset($_GET['cmd']) && in_array($_GET['cmd'], array('get', 'delete'), TRUE) ?  $_GET['cmd'] : '';
$key = isset($_GET['key']) ?  $_GET['key'] : '';
if ($cmd && $key) {
    foreach ($config['servers'] as $server) {
        $mem1 = new Memcache();
        echo 'connect server'. $server['host'];
        var_dump($mem1->addServer($server['host'], $server['port']));
        echo '<br />' . $cmd . ' ' . $key . ' = ';
        var_dump(call_user_func(array($mem1, $cmd), $key));
        echo '<hr>';
    }
}