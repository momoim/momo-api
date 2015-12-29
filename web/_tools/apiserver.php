<?php
/**
 * MOMO API 测试接口中转
 *
 */
session_start();

include_once ('config.php');
include_once ('weibooauth.php');
$oauth_token = trim($_REQUEST['oauth_token']);
$oauth_token_secret = trim($_REQUEST['oauth_token_secret']);

if (empty($oauth_token) or empty($oauth_token_secret)) {
    $oauth_token = @$_SESSION['last_key']['oauth_token'];
    $oauth_token_secret = @$_SESSION['last_key']['oauth_token_secret'];
}

$c = new WeiboClient(WB_AKEY, WB_SKEY, $oauth_token, 
$oauth_token_secret);
error_reporting(0);
$method = trim($_REQUEST['method']);
$rtype = strtolower($_REQUEST['rtype']);
$reqtype = trim($_REQUEST['reqtype']);
$reqbody = trim($_REQUEST['reqbody']);
if ($method == 'photo/upload.json') {
    $pic_path = $_FILES['pic']['tmp_name'];
    $result = $c->upload('', $pic_path);
} else {
    switch (strtoupper($reqtype)) {
        case 'GET':
            $urls = explode('?', $method);
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
            $result = $c->oauth->get(API_PATH . $method, $query, true);
            break;
        case 'POST':
            $result = $c->oauth->post(API_PATH . $method, array(), false, $reqbody, 
            true);
            break;
    }
}
$result = str_replace(array('<', '>'), array('&lt;', '&gt;'), $result);
if ($rtype == 'json') {
    echo $result;
} else {
    list ($header, $body) = explode("\r\n\r\n", $result);
    echo $header . "\r\n";
    print_r(json_decode($body, true));
}