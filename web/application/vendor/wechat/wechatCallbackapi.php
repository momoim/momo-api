<?php 

/**
 * PHP SDK for wechat
 * 
 */

class wechatCallbackapi
{
	protected $model;
	
	public function __construct($model)
	{
		$this->model = $model;
	}
	
	public function valid()
    {
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if($this->checkSignature()){
        	$this->responseMsg();
        }
    }

    public function responseMsg()
    {
		//get post data, May be due to the different environments
		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

      	//extract post data
		if (!empty($postStr)){
                
              	$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
                $fromUsername = $postObj->FromUserName;
                $toUsername = $postObj->ToUserName;
                $keyword = trim($postObj->Content);
                $time = time();
	            $msgType = "text";
                $textTpl = "<xml>
							<ToUserName><![CDATA[%s]]></ToUserName>
							<FromUserName><![CDATA[%s]]></FromUserName>
							<CreateTime>%s</CreateTime>
							<MsgType><![CDATA[%s]]></MsgType>
							<Content><![CDATA[%s]]></Content>
							<FuncFlag>0</FuncFlag>
							</xml>";             
				if(!empty( $keyword ))
                {
                	if(preg_match_all('/闽([\w\d|\W\D]*)#([\w\d|\W\D]*)/is',$keyword,$match)) {
                		$plate_number = "闽".$match[1][0];
	                	$vehicle_number = $match[2][0];
	                	$contentStr = '尊敬的'.$plate_number."的车主您好，很高兴您订阅爱车微助手的交通违章服务，在接下来的日子里，如果您的车子有违章我们将在第一时间通知您，多谢您的支持。";
	                } else {
	                	$contentStr = "欢迎使用爱车微助手！目前我们提供福州市交通违章订阅服务，如果您需要订阅，请回复以下内容：车牌号#车架号后四位，例如：闽A88888#1234";
	                }
                }else{
                	$contentStr = "欢迎使用爱车微助手！目前我们提供福州市交通违章订阅服务，如果您需要订阅，请回复以下内容：车牌号#车架号后四位，例如：闽A88888#1234";
                }
                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
	            echo $resultStr;
        }else {
        	echo "";
        	exit;
        }
    }
		
	private function checkSignature()
	{
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];	
        		
		$token = TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
}
?>