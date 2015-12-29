<?php

session_start();
//if( isset($_SESSION['last_key']) ) header("Location: weibolist.php");
include_once( 'config.php' );
include_once( 'weibooauth.php' );

$url = sprintf('%s://%s%s', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
	|| $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http', $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI']);

$o = new WeiboOAuth( WB_AKEY , WB_SKEY  );
$keys = $o->getRequestToken();
$aurl = $o->getAuthorizeURL( $keys['oauth_token'] ,false , $url.'callback.php');

$_SESSION['keys'] = $keys;

header("Content-type: text/html; charset=utf-8");
?>
<a href="<?php echo $aurl; ?>">使用oauth登陆</a>