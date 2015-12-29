<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML>
    <HEAD>
        <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
        <TITLE> UAP 接口测试 </TITLE>
    </HEAD>
    <BODY>
<a href="weibolist.php">返回接口测试</a>

<h2>照片base64</h2>
<form action="" method="post" enctype="multipart/form-data">
<input type="file" name="pic" style="width:300px" value="图片url" />
&nbsp;<input type="submit" />
</form>
照片base64后内容：<br/>
<textarea rows="10" cols="120">
<?php

if( $_FILES && !empty($_FILES['pic']['tmp_name'])) {
    echo base64_encode(file_get_contents($_FILES['pic']['tmp_name']));
} else {
    echo '';
}
?>
</textarea>
</BODY>
</HTML>