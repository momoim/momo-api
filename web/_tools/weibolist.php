<?php session_start(); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML>
    <HEAD>
        <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
        <TITLE> MOMO API 接口测试 </TITLE>
        <style>
            body {margin: 10px;padding: 10px;font-size:14px; line-height:160%}
            input {width: 220px;}
            select {width: 220px;}

            h1 {font-size:25px;}
            h3 {font-size:14px;}

            #area {width: 100%}
            #leftarea {width:250px;float:left;margin: 0;padding: 0;color: #666666;font-weight: bold;border-right:1px solid #666;}
            #rightarea {width:80%; float:left; margin-left:20px;padding: 0;}
            #showarea, #reqbody {width: 90%;height:200px;}

            #leftarea input {margin: 8px 0;}
            #leftarea select {margin: 8px 0;}

            textarea {
                font-size:12px;
                font-family:"Courier New";
            }

            .hidden {display: none;}
        </style>
    </HEAD>
    <script type="text/javascript" src="./jquery.min.js"></script>

    <script type="text/javascript">
        function B() {
            //兼容linux firefox
            $("#showarea").html('');
            $.post(
            'apiserver.php',
            $("#form1").serialize(),
            function(data) {
                $("#showarea").html(data);
            }
        );
            
            return false;
        }

        function G(id) {
            return document.getElementById(id);
        }

    </script>
    <body>
        <form id="form1">
            <h1>MOMO API 接口测试</h1>
            <hr size=1 />
            <div id="area">
                <div id="leftarea">
                	
                	<div id="typearea">
                        oauth_token<br />
                        <input type="text" name="oauth_token" id="oauth_token" value="<?php echo isset($_SESSION['last_key']['oauth_token']) ? $_SESSION['last_key']['oauth_token'] : ''; ?>" />
                    </div>
                    
                    <div id="typearea">
                        oauth_token_secret<br />
                        <input type="text" name="oauth_token_secret" id="oauth_token_secret" value="<?php echo isset($_SESSION['last_key']['oauth_token_secret']) ? $_SESSION['last_key']['oauth_token_secret'] : ''; ?>" />
                    </div>
                    
                    <div id="typearea">
                        返回格式<br />
                        <select name="rtype" id="rtype">
                            <option value="json" selected>JSON</option>
                            <option value="php">PHP</option>
                        </select>
                    </div>
                    <div id="requestarea">
                        请求方式<br />
                        <select name="reqtype" id="reqtype">
                            <option value="GET" selected>GET</option>
                            <option value="POST">POST</option>
                            <option value="PUT">PUT</option>
                            <option value="DELETE">DELETE</option>
                        </select>
                    </div>
                    <div id="methodarea">
                        方法<br />
                        <input type="text" name="method" id="method" />
                    </div>
                    <div>
                        <br />
                        <input type="submit" name="submit" value="  提交请求  " onclick="javascript:return B();return false;" />
                    </div>
                    <div>
                        <br />
                        <a href="photo_test.php">广播照片上传</a>
                        <br />
                        申请体验用户 source = 10
                    </div>
                </div>

                <div id="rightarea">
                    <h3>请求内容</h3>
                    <textarea name="reqbody" id="reqbody"></textarea>
                    <h3>响应内容</h3>
                    <textarea name="showarea" id="showarea"></textarea>
                </div>
            </div>
        </form>
    </BODY>
</HTML>