<?php

session_start();
include_once( 'config.php' );
include_once( 'weibooauth.php' );


$c = new WeiboClient( WB_AKEY , WB_SKEY , $_SESSION['last_key']['oauth_token'] , $_SESSION['last_key']['oauth_token_secret']  );
header('Content-Type: text/html; charset=utf-8');

?>
<a href="weibolist.php">返回接口测试</a>

<h2>照片接口测试</h2>


<h2>广播照片上传</h2>
<form action="photo_test.php" method="post" enctype="multipart/form-data">
<input type="file" name="pic" style="width:300px" value="图片url" />
&nbsp;<input type="submit" />
</form>
<?php

if( $_FILES ) {
	$rr = $c ->upload( '' , $_FILES['pic']['tmp_name'] );
    print_r($rr);
}
?>