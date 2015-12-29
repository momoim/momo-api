<?php
set_time_limit(0);
//$config['default'] = array
//(
//	'benchmark' => TRUE,
//	'persistent' => FALSE, 
//	'connection' => array
//    (
//    'type' => 'mysql',
//	'user' => 'uap_space',
//	'pass' => 'uapspace_18$%',
//	'host' => '192.168.9.128',
//	'port' => FALSE, 
//	'socket' => FALSE,
// 	'database' => 'uap_space'
//    ),
// 	'character_set' => 'utf8', 
//	'table_prefix' => '', 
//	'object' => TRUE,
// 	'cache' => FALSE, 
//	'escape' => TRUE
//);
//
//$config['slave1'] = array
//(
//	'benchmark' => TRUE,
//	'persistent' => FALSE, 
//	'connection' => array
//    (
//    'type' => 'mysql',
//	'user' => 'uap_space',
//	'pass' => 'uapspace_18$%',
//	'host' => '192.168.9.128',
//	'port' => FALSE, 
//	'socket' => FALSE,
// 	'database' => 'uap_space'
//    ),
// 	'character_set' => 'utf8', 
//	'table_prefix' => '', 
//	'object' => TRUE,
// 	'cache' => FALSE, 
//	'escape' => TRUE
//);

$config['default'] = array
(
	'benchmark'     => TRUE,
	'persistent'    => FALSE,
	'connection'    => array
	(
		'type'     => 'mysql',
		'user'     => 'momo',
		'pass'     => 'dC4DqVCQKAZScY4M',
		'host'     => '10.1.242.206:3306',
		'port'     => FALSE,
		'socket'   => FALSE,
		'database' => 'momo'
	),
	'character_set' => 'utf8',
	'table_prefix'  => '',
	'object'        => TRUE,
	'cache'         => FALSE,
	'escape'        => TRUE
);

$config['slave1'] = array
(
	'benchmark'     => TRUE,
	'persistent'    => FALSE,
	'connection'    => array
	(
		'type'     => 'mysql',
		'user'     => 'momo',
		'pass'     => 'dC4DqVCQKAZScY4M',
		'host'     => '10.1.242.127:3306',
		'port'     => FALSE,
		'socket'   => FALSE,
		'database' => 'momo'
	),
	'character_set' => 'utf8',
	'table_prefix'  => '',
	'object'        => TRUE,
	'cache'         => FALSE,
	'escape'        => TRUE
);

$config['slave2'] = array
(
	'benchmark'     => TRUE,
	'persistent'    => FALSE,
	'connection'    => array
	(
		'type'     => 'mysql',
		'user'     => 'momo',
		'pass'     => 'dC4DqVCQKAZScY4M',
		'host'     => '10.1.242.128:3306',
		'port'     => FALSE,
		'socket'   => FALSE,
		'database' => 'momo'
	),
	'character_set' => 'utf8',
	'table_prefix'  => '',
	'object'        => TRUE,
	'cache'         => FALSE,
	'escape'        => TRUE
);
foreach ($config as $db) {
    $link = mysql_connect($db['connection']['host'], $db['connection']['user'], 
    $db['connection']['pass'], TRUE);
    if (!$link) {
        echo "Could not connect {$db['connection']['host']}: " . mysql_error();
        echo '<hr />';
        break;
    }
    $select_db = mysql_select_db($db['connection']['database'], $link);
    if (!$select_db) {
        echo 'Could not select database';
        echo '<hr />';
        break;
    }
    $query = 'SELECT id,name FROM apps LIMIT 1';
    $result = mysql_query($query);
    if (!$result) {
        echo 'Query failed: ' . mysql_error();
        echo '<hr />';
        break;
    }
    // Printing results in HTML
    echo "<table>\n";
    while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
        echo "\t<tr>\n";
        foreach ($line as $col_value) {
            echo "\t\t<td>$col_value</td>\n";
        }
        echo "\t</tr>\n";
    }
    echo "</table>\n";
    // Free resultset
    mysql_free_result($result);
    // Closing connection
    mysql_close($link);
    echo '<hr />';
}