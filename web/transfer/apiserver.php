<?php
/**
 * MOMO API 测试接口中转
 *
 */
header("content-Type: text/html; charset=utf-8");
session_start();
include_once ('config.php');
include_once ('weibooauth.php');
error_reporting(0);
$appKey = '';
$appSecret = '';
$class = '';
$method = '';

$classKey = array_keys($CLASS);
if(!isset($_REQUEST['class'])) {
	echo '非法请求类型';exit;
} elseif(in_array($_REQUEST['class'], $classKey)) {
	$class = $CLASS[$_REQUEST['class']];
} else {
	$class = $_REQUEST['class'];
}

$methodKey = array_keys($METHOD);
if(!isset($_REQUEST['method'])) {
	$methodId = 1;
	$method = $METHOD[$methodId];
} elseif(in_array($_REQUEST['method'], $methodKey)) {
	$methodId = $_REQUEST['method'];
	$method = $METHOD[$methodId];
} else {
	$method = $_REQUEST['method'];
}

//get app key and secret
if($_REQUEST['source']) {
	$source = array_keys($SOURCE);
	if(in_array($_REQUEST['source'], $source)) {
		$appKey = $SOURCE[$_REQUEST['source']]['key'];
		$appSecret = $SOURCE[$_REQUEST['source']]['secret'];
		$_SESSION['key'] = $appKey;
		$_SESSION['secret'] = $appSecret;
	} else {
		if(isset($_SESSION['key'])) {
			$appKey = $_SESSION['key'];
		}
		if(isset($_SESSION['secret'])) {
			$appSecret = $_SESSION['secret'];
		}
	}
} else {
	if(isset($_SESSION['key'])) {
		$appKey = $_SESSION['key'];
	}
	if(isset($_SESSION['secret'])) {
		$appSecret = $_SESSION['secret'];
	}
}

//get app oauth_token
if(isset($_REQUEST['oauth_token'])) {
	$oauthToken = trim($_REQUEST['oauth_token']);
	$_SESSION['oauth_token'] = $oauthToken;
} else if(isset($_SESSION['oauth_token'])) {
	$oauthToken = $_SESSION['oauth_token'];
}


//get app oauth_token_secret
if(isset($_REQUEST['oauth_token_secret'])) {
	$oauthTokenSecret = trim($_REQUEST['oauth_token_secret']);
	$_SESSION['oauth_token_secret'] = $oauthTokenSecret;
} else if(isset($_SESSION['oauth_token_secret'])) {
	$oauthTokenSecret = $_SESSION['oauth_token_secret'];
}

if(!$appKey || !$appSecret) {
	echo '非法应用';exit;
} else if(!$oauthToken || !$oauthTokenSecret) {
	echo '非法oauth认证';exit;
}
try {
	if(!isset($_REQUEST['timestamp']) && !isset($_SESSION['timestamp'])) {
		echo 'oauth认证失败';exit;
	} else if(isset($_REQUEST['timestamp'])) {
		$_SESSION['timestamp'] = $_REQUEST['timestamp'];
	} else if(isset($_SESSION['timestamp'])) {
		$_REQUEST['timestamp'] = $_SESSION['timestamp'];
	} 
	
	$c = new WeiboClient($appKey, $appSecret, $oauthToken, $oauthTokenSecret, $_REQUEST['timestamp']);
	$requestUrl = $class.'/'.$method.'/'.trim($_REQUEST['id']);
	if(($class == 'activity' || $class == 'activity_member') && isset($_REQUEST['web']) && $_REQUEST['web'] > 0) {
		$requestUrl .= '?web='.$_REQUEST['web'];
		if(isset($_REQUEST['applyType'])) {
			$requestUrl .= '&type='.$_REQUEST['applyType'];
		}
	}
	
	$type = 'GET';
	if(isset($_REQUEST['type'])) {
		$type = strtolower($_REQUEST['type']);
	}
	switch (strtoupper($type)) {
        case 'GET':
            $urls = explode('?', $requestUrl);
            $path = '';
            $query = array();
            if (! empty($urls)) {
                $path = isset($urls['0']) ? $urls['0'] : '';
                $array = isset($urls['1']) ? explode('&', $urls['1']) : array();
                if (! empty($array)) {
                    foreach ($array as $value) {
                        if ($value) {
                            list ($k, $v) = explode('=', $value);
                            $query[$k] = $v;
                        }
                    }
                }
            }
            $result = $c->oauth->get(API_PATH . $requestUrl, $query, false);
            break;
        case 'POST':
			$reqbody = '';
			if(strtolower($class) == 'activity_member') {
				$reqArray = array('type'=>intval($_REQUEST['applyType']), 'web'=>2);
				$reqbody = json_encode($reqArray);
			}
            $result = $c->oauth->post(API_PATH . $requestUrl, array(), false, $reqbody, false);
            break;
		default:
			echo '非法http请求类型 ';exit;
    }
    $arr = json_decode($result, true);
    if(gettype($arr) != "array"){
    	//$result = str_replace(array('<', '>'), array('&lt;', '&gt;'), $result);
		$result = str_replace("<!DOCTYPE HTML>", "", $result);
		echo $result;
    } else {
    	echo 'oauth认证失败';exit;
    }
	exit;
} catch(Exception $e) {
	echo 'oauth认证失败';exit;
}