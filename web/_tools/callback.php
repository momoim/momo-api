<?php

session_start();
include_once( 'config.php' );
include_once( 'weibooauth.php' );

if(empty($_SESSION['last_key'])) {
	$o = new WeiboOAuth( WB_AKEY , WB_SKEY , $_SESSION['keys']['oauth_token'] , $_SESSION['keys']['oauth_token_secret']  );

	$last_key = $o->getAccessToken(  $_REQUEST['oauth_verifier'] ) ;

	$_SESSION['last_key'] = $last_key;
}

header('Content-Type: text/html; charset=utf-8');
?>
授权完成,<a href="weibolist.php">进入你的API测试页面</a>
<a href="chrome/">进入你的API测试页面chrome版</a>
