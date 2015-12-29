<?php
define(IN_PRODUCTION, TRUE);
if(IN_PRODUCTION === TRUE) {
    define( 'API_PATH','http://v3.api.momo.im/');
} else {
    define( 'API_PATH','http://new.api.uap26.91.com/');
}

$SOURCE = array
(
	'1' => array('key'=>'9e5d178d8e8029e5e05d2a3cd035c63d04ddb4c21', 'secret'=>'c7c2aecad62ecc82d51d638e27c9af7d'), // android
	'2' => array('key'=>'dbda88d1d417f605980fa784b6aad41004ddb4c35', 'secret'=>'ace0770a1bd908ca62e8422f436e34f1')  // iphone
);

$CLASS = array(
	'5' => 'blog',
	'7' => 'activity',
	'701' => 'activity_member'
);

$METHOD = array(
	'1' => 'show',
	'2' => 'create'
);

?>