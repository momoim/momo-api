<?php

class Tui
{
    protected $api = 'http://tui3.com/api/send/?';  // api地址
    protected $key = '098b460ba826e1f503e50ead09dc5059';  // 产品的APIKEY
    protected $product = '1';  // 实时通道1则填1，如果是实时通道2则填2
    protected $format = 'json'; // 返回的结果格式，xml json

    /**
     * 返回响应
     *
     *
     * @param string $mobile 接收手机号
     * 1.发送单条消息时，此字段填写11位的手机号码。
     * @param string $msg 发送消息内容
     * @return bool
     */
    public function send($mobile, $msg)
    {

        if (!$mobile) {
            return false;
        }

        // 消息参数
        $data = array(
            'k' => $this->key,
            'p' => $this->product,
            't' => $mobile,
            'c' => $msg, // 发送的内容(UTF8格式), 如果为GBK编码的内容,请使用cn参数
            'r' => $this->format,
        );

        $query = http_build_query($data);

        // 使用get方式发送消息，设置连接/响应超时时间 30s
        try {
            $res = $this->get($this->api . $query, 30);
            if ($res) {
                if ($res['status_code'] == 200) {
                    $result = json_decode($res['text'], true);
                    if (!empty($result['err_code'])) {
                        error_log('send sms fail' . $result['err_msg']);
                    } else {
                        return true;
                    }
                }
            }
        } catch (Exception $e) {
            error_log('send sms fail' . $e->getMessage());
        }

        return false;
    }

    public function get($url, $timeout = 30)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        $output = curl_exec($ch);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return array('status_code' => $httpcode, 'text' => $output);
    }
}

//$tui = new Tui();
//var_dump($tui->send('18559172578', '尊敬的用户,您的注册验证码是1234,感谢您使用momo通讯录！'));
