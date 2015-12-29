#!/usr/local/momo_album/php/bin/php 
<?php
error_reporting(E_ALL ^ E_NOTICE);
date_default_timezone_set("Asia/Shanghai");
define('SCRIPT_BASE',dirname(__FILE__).DIRECTORY_SEPARATOR);

require SCRIPT_BASE . '../global.php';

$worker= new GearmanWorker();
$worker->addServers(Core::config('job_servers'));

$thumb=new Thumb();
$worker->addFunction("thumbnail", array($thumb, 'resize'));
$worker->addFunction("rotate", array($thumb, 'rotate'));

echo "Waiting for job...\n";
while($worker->work()){
	if ($worker->returnCode() != GEARMAN_SUCCESS){
		echo "return_code: " . $worker->returnCode() . "\n";
		Core::debug('gmworkerlog', "return_code: " . $worker->returnCode());
		break;
	}
}
