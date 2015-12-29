<?php
$config = array();
//$config['mongodb'] = 'mongodb://192.168.94.26:27017';
//$config['mongodb2'] = 'mongodb://192.168.94.26:27017';

$config['mongodb'] = 'mongodb://fsnew23FS4:fs24234FWdkfFKW@localhost:27017';
$config['mongodb2'] = 'mongodb://fsnew23FS4:fs24234FWdkfFKW@localhost:27017';
foreach ($config as $server) {
    // connect
    $m = new Mongo($server);
    
    // select a database
    $db = $m->momo;
    
    // select a collection (analogous to a relational database's table)
    $collection = $db->feed_new;
    
    // find everything in the collection
    $cursor = $collection->find();
    
    // iterate through the results
    foreach ($cursor as $obj) {
        echo $obj["text"] . "\n";
    }
    echo '<hr />';
}