<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<HTML>
    <HEAD>
        <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
        <TITLE> UAP 接口测试 </TITLE>
        <style>
            body {margin: 10px;padding: 10px;font-size:14px; line-height:160%}
            .input1 {width: 220px;}
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
        function show(id) {
            if(id=="") return false;
            ids = id.split(",");
            for(i=0;i<ids.length;i++) {
                G(ids[i]+'area').style.display = 'block';
            }
        }
        function hide(id) {
            if(id=="") return false;
            ids = id.split(",");
            for(i=0;i<ids.length;i++) {
                G(ids[i]+'area').style.display = 'none';
            }
        }
        function S(id) {
            var idx = id.options.selectedIndex;
            var val = id.options[idx].value;
			var v = new Array();
			var c = m = "";
			if(val!="") {
				v = val.split(".");
				c = v[0];
				m = v[1];
			}
            switch(c) {
				case "Login":
					hide('uid,sessionid,friendid,groupid,request');
					break;
				case "Logout":
					show('uid');
					hide('sessionid,friendid,groupid,request');
					break;
                case "User":
					show('uid,sessionid');
					hide('friendid,groupid,request');
					break;
                case "Friend":
					show('uid,friendid,sessionid');
					hide('groupid,request');
					break;
                case "FriendGroup":
					show('uid,groupid,sessionid');
					hide('friendid,request');
					break;
                case "BlackList":
					show('uid,sessionid');
					hide('friendid,groupid,request');
					break;
                default:
					show('sessionid,request');
					hide('uid','friendid','groupid');
                break;
            }
        }

        function B() {
            //$("#showarea").load("apiserver.php", {uid : G("uid").value, friendid : G('friendid').value, groupid : G('groupid').value, m : G("method").value, m2:G("method2").value, rtype:G('rtype').value, reqtype:G("reqtype").value, reqbody:G("reqbody").value, debug:G("debug").value});
            $("#showarea").load("apiserver.php", $("#form1").serialize());
            return false;
        }

        function G(id) {
            return document.getElementById(id);
        }

    </script>
    <BODY>
        <form id="form1">
        <h1>UAP 接口测试</h1>
        <hr size=1 />
        <div id="area">
            <div id="leftarea">
                <div id="uidarea">
                    用户Id<br />
                    <input type="text" name="uid" id="uid" value="1" />
                </div>
				<div id="sessionidarea">
                    Session Id<br />
                    <input type="text" name="sessionid" id="sessionid" value="" />
                </div>
                <div class="hidden" id="friendidarea">
                    好友Id<br />
                    <input type="text" name="friendid" id="friendid" value="0" />
                </div>
                <div class="hidden" id="groupidarea">
                    好友分组Id<br />
                    <input type="text" name="groupid" id="groupid" value="0" />
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
                        <option value="PUT">PUT</option>
                        <option value="POST">POST</option>
                        <option value="DELETE">DELETE</option>
                    </select>
                </div>
                <div id="methodarea">
                    方法<br />
                    <select name="method" id="method" onchange="S(this);">
                        <option value="">手工输入</option>
                        <option value="Login.post">Login.post</option>
						<option value="Logout.post">Logout.post</option>
						<option value="User.get">User.get</option>
						<option value="User.post">User.post</option>
						<option value="User.put">User.put</option>
                        <option value="Friend.get">Friend.get</option>
						<option value="Friend.post">Friend.post</option>
						<option value="Friend.put">Friend.put</option>
						<option value="Friend.delete">Friend.delete</option>
                        <option value="FriendGroup.get">FriendGroup.get</option>
                        <option value="BlackList.get">BlackList.get</option>
                    </select>
                    <br />或手工输入<br />
                    <input type="text" name="method2" id="method2" />
                </div>

                <div>
                    <label for="debug">DEBUG 模式</label> <input type="checkbox" id="debug" name="debug" value="1" />
                    <br />
                    <input type="submit" name="submit" value="  提交请求  " onclick="javascript:return B();return false;" />
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
	<script>
	hide('uid','friendid','groupid');
	</script>
    </BODY>
</HTML>
