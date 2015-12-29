<?php
defined('SYSPATH') or die ('No direct access allowed.');

/**
 * [MOMO API] (C)1999-2011 ND Inc.
 * 用户控制器文件
 */
class Register_Controller extends Controller
{

    /**
     * @var User_Model
     */
    protected $model;
    private $check_msg;
    private $max_name_length = 4;
    private $test_account = array('13225911432', '13699998454', '5085910202', '18650055751', '13500000001', '13500000002', '13500000003', '13500000004', '13500000005', '18250317793', '18606944709');

    public function __construct()
    {
        parent::__construct();

        $this->model = new User_Model ();
    }

    public function index()
    {
        $this->send_response(405, NULL, '请求的方法不存在');
    }

    /**
     * 获取验证码
     * @method POST
     */
    public function verifycode()
    {
        if ($this->get_method() == 'GET') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        $post = $this->get_data();
        $mobile = trim($post ['mobile']);
        //号码有效性检测
        if ($this->_check_mobile_valid($mobile)) {
            //生产验证码
            if (!$this->model->send_verifycode($mobile)) {
                $this->send_response(400, NULL, $this->model->get_return_msg());
            } else {
                $this->send_response(200, array('verifycode' => $this->model->get_return_msg()));
            }
        } else {
            $this->send_response(400, NULL, $this->check_msg);
        }
    }

    /**
     *
     * 用户注册:手机验证码校验
     */
    public function verify()
    {
        $data = $this->get_data();
        $mobile = $data ['mobile'] ? trim($data ['mobile']) : '';
        $verifycode = $data ['verifycode'] ? trim($data ['verifycode']) : '';
        $zone_code = $data ['zone_code'] ? mobile::zone_code_format($data ['zone_code']) : '86';
        $uid = 0;
        $present_sms = false;
        if (empty ($verifycode)) {
            $this->send_response(400, NULL, '4003002:验证码为空');
        }
        //检查手机端格式
        if (!international::check_is_valid($zone_code, $mobile)) {
            $this->send_response(400, NULL, "40002:手机号码格式不对");
        }
        //判断手机号对应的用户是否存在
        $user = $this->model->get_user_by_mobile($mobile, $zone_code);
        if ($user && $user ['uid'] > 0) {
            $uid = $user['uid'];
            $present_sms = $user['sms_count'] > 0 ? false : true;
            if ($user['status'] >= 3) {
                $this->send_response(400, NULL, '400117:手机号码已注册');
            }
        } else {
            $present_sms = true;
        }
        //检查手机号和密码是否匹配


        $this->send_response(200, array('uid' => $bind_uid, 'user_status' => NO_ACTIVED_USER, 'oauth_token' => $token ['oauth_token'], 'oauth_token_secret' => $token ['oauth_token_secret'], 'qname' => $qname));
    }


    /**
     *
     * 用户注册:自动扫描短信url注册
     */
    public function auto_verify()
    {
        $data = $this->get_data();
        $url = $data ['url'] ? trim($data ['url']) : '';
        $uid = 0;
        $present_sms = false;
        if (empty($url)) {
            $this->send_response(400, NULL, '400138:url为空');
        }
        $urlInfoArray = parse_url($url);
        if (empty ($urlInfoArray) || empty ($urlInfoArray ['path'])) {
            $this->send_response(400, NULL, '400245:url非法');
        }
        $pathArray = explode('/', $urlInfoArray ['path']);
        $url_code = $pathArray [count($pathArray) - 1];
        if (empty ($url_code)) {
            $this->send_response(400, NULL, '400245:url为空');
        }
        $tmp_user = $this->model->get_tmp_account_by_urlcode($url_code);
        if (empty ($tmp_user)) {
            $this->send_response(400, NULL, '400245:url非法');
        }
        //判断手机号对应的用户是否存在
        $user = $this->model->get_user_by_mobile($tmp_user['mobile'], $tmp_user['zone_code']);
        if ($user && $user ['uid'] > 0) {
            $uid = $user['uid'];
            $present_sms = $user['sms_count'] > 0 ? false : true;
            if ($user['status'] >= 3) {
                $this->send_response(400, NULL, '400117:手机号码已注册');
            }
        } else {
            $present_sms = true;
        }
        if ($tmp_user['bind_uid'] > 0) {
            if (!$this->model->update($tmp_user['bind_uid'], $tmp_user['source'], $tmp_user['install_id'], $tmp_user['phone_model'], $tmp_user['phone_os'], $tmp_user['device_id'], NO_ACTIVED_USER)) {
                $this->send_response(500, NULL, '50001:用户注册失败');
            }
            $bind_uid = $tmp_user['bind_uid'];
        } else {
            $result = $this->model->create_account($tmp_user['mobile'], $tmp_user['zone_code'], $tmp_user['install_id'], $tmp_user['phone_model'], $tmp_user['phone_os'], $tmp_user['source'], $tmp_user['device_id']);
            if (!$result) {
                $this->send_response(400, NULL, $this->model->get_return_msg());
            }
            $bind_uid = $result['uid'];
        }
        //清空缓存
        $this->model->clear_cache($tmp_user['mobile'], $tmp_user['zone_code'], $bind_uid);
        if ($bind_uid >= $uid) {
            //更新帐号密码
            //$this->model->_reset_password($verifycode,$bind_uid,$mobile,$zone_code);
        }
        //分配token和队列
        $token = $this->model->request_access_token($tmp_user['source'], $bind_uid, $tmp_user['device_id']);
        $qname = 'momo_' . $tmp_user['source'] . '_' . $bind_uid . '_' . $token ['ost_timestamp'];
        //如果不是网站的请求，则直接分配mq主token
        if ($tmp_user['source'] != 0) {
            Mq_Model::instance()->add($qname, $bind_uid, $token ['oauth_token'], $token ['oauth_token'], $token ['ost_timestamp'], $tmp_user['source'], 1);
        }
        //注册赠送短信
        if ($present_sms) {
            $this->present_sms($bind_uid, 'reg');
        }
        $this->send_response(200, array('uid' => $bind_uid, 'user_status' => NO_ACTIVED_USER, 'mobile' => $tmp_user['mobile'], 'zone_code' => $tmp_user['zone_code'], 'oauth_token' => $token ['oauth_token'], 'oauth_token_secret' => $token ['oauth_token_secret'], 'qname' => $qname));
    }

    /**
     *
     * 赠送短信
     * @param unknown_type $uid
     * @param unknown_type $type
     */
    public function present_sms($uid, $type)
    {
        $num = 0;
        switch ($type) {
            case 'reg':
                $num = PRESENT_SMS_REG;
                $content = '感谢您注册,momo.im赠送' . $num . '条全球免费MO短信给您,您当前可用短信总数:' . $num;
                break;
        }
        if ($num > 0) {
            $this->model->present_sms($uid, $num, $content);
        }
    }

    /**
     *
     * 用户注册:重发验证码
     */
    public function resend_verifycode()
    {
        $post = $this->get_data();
        $mobile = trim($post ['mobile']);
        $zone_code = $post ['zone_code'] ? mobile::zone_code_format($post ['zone_code']) : '86';
        //检查手机端格式
        if (!international::check_is_valid($zone_code, $mobile)) {
            $this->send_response(400, NULL, "40002:手机号码格式不对");
        }
        //判断手机号对应的用户是否存在
        $user = $this->model->get_user_by_mobile($mobile, $zone_code);
        if ($user && $user['uid'] > 0) {
            if ($user ['status'] >= 3) {
                $this->send_response(400, NULL, '400117:手机号码已注册');
            }
        }
        $tmp_user = $this->model->get_tmp_account($mobile, $zone_code);
        if ($tmp_user) {
            if ($tmp_user ['status'] == 0) {
                if ($this->model->send_verifycode($mobile, $zone_code)) {
                    $this->send_response(200);
                } else {
                    $this->send_response(400, NULL, $this->model->get_return_msg());
                }
            }
            $this->send_response(400, NULL, '400301:该帐号已设置过密码不能发送初始化密码');
        } else {
            $this->send_response(400, NULL, '400302:帐号未注册');
        }
    }

    /**
     * 获取体验用户资格
     *
     * @desc 仅供手机端
     * @method POST
     */
    public function exp()
    {
        if ($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        $data = $this->get_data();
        $device_id = $data ['device_id'];
        $source = $data ['source'];
        if (empty ($device_id)) {
            $this->send_response(400, NULL, '设备ID为空');
        }
        if (empty ($source)) {
            $this->send_response(400, NULL, '400121:客户端来源为空');
        }
        $oauth_consumer_key = api::get_source_app_key($source);
        if (empty ($oauth_consumer_key)) {
            $this->send_response(400, NULL, '400142:客户端来源不存在');
        }
        $uid = $this->model->exp($device_id, $source);

        if ($uid) {
            $token = $this->model->request_access_token($source, $uid, $device_id);
            $qname = 'momo_' . $source . '_' . $uid . '_' . $token ['ost_timestamp'];
            Mq_Model::instance()->add($qname, $uid, $token ['oauth_token'], $token ['oauth_token'], $token ['ost_timestamp'], $source, 1);
            //j2me
            if ($source == 6) {
                //	if (! $this->model->mq_create ( $qname, $uid )) {
                $qname = '';
                //	}
            }
            $this->send_response(200, array('uid' => ( int )$uid, 'name' => sns::getrealname($uid), 'oauth_token' => $token ['oauth_token'], 'oauth_token_secret' => $token ['oauth_token_secret'], 'qname' => $qname));
        } else {
            $this->send_response(500, NULL, '创建失败');
        }
    }

    /**
     * 体验用户注册为正式用户
     *
     * @desc 体验用户注册成为正式用户,并发送供手机自动登录的验证URL：http://momo.im/l/abcd
     * @method POST
     */
    public function exp_create()
    {
        if ($this->get_method() == 'GET') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        if (!$this->user_id && (!isset ($this->user_status) || $this->user_status != 0)) {
            $this->send_response(400, NULL, '4003001:非体验用户登录');
        }
        $url_code = '';
        $post = $this->get_data();
        $mobile = trim($post ['mobile']);
        $zone_code = $post ['zone_code'] ? mobile::zone_code_format($post ['zone_code']) : '86';
        $install_id = $post ['install_id'] ? trim($post ['install_id']) : '';
        $phone_model = $post ['phone_model'] ? trim($post ['phone_model']) : '';
        $os = $post ['os'] ? trim($post ['os']) : '';
        $source = $post ['client_id'] ? trim($post ['client_id']) : trim($post ['source']);
        $device_id = $post ['device_id'] ? trim($post ['device_id']) : '';
        $uid = $post ['uid'] ? trim($post ['uid']) : 0;
        if ($uid != $this->user_id || $device_id != $this->device_id) {
            $this->send_response(400, NULL, '4003002:体验用户非法');
        }
        if (empty ($source)) {
            $this->send_response(400, NULL, '400121:客户端来源为空');
        }
        //检查手机端格式
        if (!international::check_is_valid($zone_code, $mobile)) {
            $this->send_response(400, NULL, "40002:手机号码格式不对");
        }
        //判断手机号是否有绑定过用户
        $to_bind_uid = $this->user_id;
        $to_bind_user = $this->model->get_user_info_by_mobile($mobile, $zone_code);
        $bind_user = $this->model->get_user_info($this->user_id);
        if ($to_bind_user ['status'] > 2 || $to_bind_user ['binded'] > 0 || $bind_user ['status'] > 2 || $bind_user ['binded'] > 0) {
            $this->send_response(400, NULL, '400117:手机号码已注册');
        }
        if ($to_bind_user) {
            $to_bind_uid = $to_bind_user ['uid'];
        }
        //创建临时帐号
        $tmp_user = $this->model->get_tmp_account($mobile, $zone_code);
        if ($tmp_user ['status'] > 0) {
            $this->send_response(400, NULL, '400118:手机号码已激活');
        }
        if (!$tmp_user) {
            $password = $this->model->rand_number(3);
            $url_code = $this->model->create_tmp_account($this->user_id, $to_bind_uid, $password, $mobile, $zone_code, $install_id, $phone_model, $os, $source, $device_id, $this->get_ip());
        } else {
            $password = $tmp_user ['password'];
            $url_code = $tmp_user ['url_code'];
        }

        if (!$this->model->send_init_passwd($mobile, $zone_code, $password, $url_code)) {
            $this->send_response(400, NULL, $this->model->get_return_msg());
        }

        $this->send_response(200);
    }

    /**
     * 体验用户自动验证
     *
     * @desc 提供给支持短信URL扫描的客户端调用此接口，手机端扫描短信根据前一步注册下发的短信URL进行自动登录，如果符合绑定条件会将体验用户绑定到正式用户
     * @method POST
     */
    public function exp_auto_verify()
    {
        if ($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        $data = $this->get_data();
        $url = trim($data ['url']);
        $client_id = $this->source;

        if (!$this->user_id || (isset ($this->user_status) && $this->user_status != 0)) {
            $this->send_response(400, NULL, '4003001:非体验用户登录');
        }
        //TODO 检查客户端来源id是否合法
        if (empty ($client_id)) {
            $this->send_response(400, NULL, '400244:客户端来源id为空');
        }
        if (!$url) {
            $this->send_response(400, NULL, '400138:url为空');
        }
        $urlInfoArray = parse_url($url);
        if (empty ($urlInfoArray) || empty ($urlInfoArray ['path'])) {
            $this->send_response(400, NULL, '400245:url非法');
        }
        $pathArray = explode('/', $urlInfoArray ['path']);
        $url_code = $pathArray [count($pathArray) - 1];
        if (empty ($url_code)) {
            $this->send_response(400, NULL, '400245:url非法');
        }
        $device_id = $data ['device_id'] ? trim($data ['device_id']) : '';

        if (!$this->user_id && (!isset ($this->user_status) || $this->user_status != 0)) {
            $this->send_response(400, NULL, '4003001:非体验用户登录');
        }
        $tmp_account_info = $this->model->get_tmp_account_by_urlcode($url_code);
        if (empty ($tmp_account_info) || $tmp_account_info ['status'] > 0) {
            $this->send_response(400, NULL, '400245:url非法');
        }
        //检查是否需要进行用户绑定code
        $bind_account_info = $this->model->get_user_info($tmp_account_info ['bind_uid']);
        $to_bind_account_info = $this->model->get_user_info($tmp_account_info ['to_bind_uid']);

        if ($to_bind_account_info ['status'] > 2 || $to_bind_account_info ['binded'] == 1 || $bind_account_info ['status'] > 2 || $bind_account_info ['binded'] == 1) {
            $this->send_response(400, NULL, '400117:手机号码已注册');
        } else {
            //执行用户绑定操作
            if (!$this->model->bind_account($bind_account_info, $tmp_account_info)) {
                $this->send_response(400, NULL, '4003004:用户注册失败');
            }

            //体验用户升级，赠送短信
            if ($this->model->update_sms($this->user_id, PRESENT_SMS_UPGRADE)) {
                $content = '感谢您激活,momo.im赠送' . PRESENT_SMS_UPGRADE . '条全球免费MO短信给您,您当前可用短信总数:' . $this->model->get_sms_count($this->user_id);
                $xiaomo_uid = Kohana::config('uap.xiaomo');
                $this->model->present_mo_notice($xiaomo_uid, $this->user_id, $content);
            }

            $this->send_response(200, array('uid' => $this->user_id, 'bind' => 1, 'user_status' => 1, 'zone_code' => $tmp_account_info ['zone_code'], 'mobile' => $tmp_account_info ['mobile']));
        }
        $this->send_response(400, NULL, '4003005:非法请求');
    }

    /**
     * 体验用户验证
     *
     * @desc  不支持短信URL扫描的客户端调用此接口，执行登录，如果符合绑定条件会将体验用户绑定到正式用户
     * @method POST
     */
    public function exp_verify()
    {
        if ($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        $data = $this->get_data();
        $mobile = $data ['mobile'] ? trim($data ['mobile']) : '';
        $password = $data ['password'] ? trim($data ['password']) : '';
        $zone_code = $data ['zone_code'] ? mobile::zone_code_format($data ['zone_code']) : '86';
        if (!$this->user_id && (!isset ($this->user_status) || $this->user_status != 0)) {
            Feed_Model::instance()->add_log("zone_code:{$zone_code},mobile:{$mobile},非体验用户登录");
            $this->send_response(400, NULL, '4003001:非体验用户登录');
        }
        if (empty ($password)) {
            $this->send_response(400, NULL, '4003002:密码为空');
        }
        //检查手机端格式

        if (!international::check_is_valid($zone_code, $mobile)) {
            Feed_Model::instance()->add_log("uid:{$this->user_id},zone_code:{$zone_code},mobile:{$mobile},password:{$password},手机号码格式不对");
            $this->send_response(400, NULL, "40002:手机号码格式不对");
        }
        //检查手机号和密码是否匹配
        $tmp_account_info = $this->model->get_tmp_account($mobile, $zone_code);
        if ($tmp_account_info) {
            if ($tmp_account_info ['status'] > 0) {
                Feed_Model::instance()->add_log("uid:{$this->user_id},zone_code:{$zone_code},mobile:{$mobile},password:{$password},手机号码已激活");
                $this->send_response(400, NULL, '400117:手机号码已激活');
            }
            if ($tmp_account_info ['password'] != $password) {
                Feed_Model::instance()->add_log("uid:{$this->user_id},zone_code:{$zone_code},mobile:{$mobile},password:{$password},手机号与密码校验不通过");
                $this->send_response(400, NULL, '4003003:手机号与密码校验不通过');
            }
        } else {
            $user_info = $this->model->get_user_info_by_mobile($mobile, $zone_code);
            if ($user_info) {
                if ($user_info ['status'] < 3 || $user_info ['binded'] > 0) {
                    if ($this->model->get_md5_pass($password) == $user_info ['password']) {
                        $tmp_account_info['bind_uid'] = $this->user_id;
                        $tmp_account_info['to_bind_uid'] = $user_info['uid'];
                        $tmp_account_info['mobile'] = $user_info['mobile'];
                        $tmp_account_info['zone_code'] = $user_info['zone_code'];
                        $tmp_account_info['password_md5'] = $user_info['password'];
                    } else {
                        $this->send_response(400, NULL, '4003003:手机号与密码校验不通过');
                    }
                } else {
                    $this->send_response(400, NULL, '400117:手机号码已激活');
                }
            } else {
                $this->send_response(400, NULL, '4003005:非法请求');
            }
        }
        //检查是否需要进行用户绑定code
        $bind_account_info = $this->model->get_user_info($tmp_account_info ['bind_uid']);
        if (!empty($bind_account_info)) {
            if (isset($tmp_account_info['password_md5']) && !empty($tmp_account_info['password_md5'])) {
                $bind_account_info['password_md5'] = $tmp_account_info['password_md5'];
            } else {
                $bind_account_info['password'] = $tmp_account_info['password'];
            }
        }
        $to_bind_account_info = $this->model->get_user_info($tmp_account_info ['to_bind_uid']);

        if ($to_bind_account_info ['status'] > 2 || $to_bind_account_info ['binded'] == 1 || $bind_account_info ['status'] > 2 || $bind_account_info ['binded'] == 1) {
            Feed_Model::instance()->add_log("uid:{$this->user_id},zone_code:{$zone_code},mobile:{$mobile},password:{$password},手机号码已注册");
            $this->send_response(400, NULL, '400117:手机号码已注册');
        } else {
            //执行用户绑定操作
            if (!$this->model->bind_account($bind_account_info, $tmp_account_info)) {
                Feed_Model::instance()->add_log("uid:{$this->user_id},zone_code:{$zone_code},mobile:{$mobile},password:{$password},用户绑定失败");
                $this->send_response(400, NULL, '4003004:用户绑定失败');
            }
            //体验用户升级，赠送短信
            if ($this->model->update_sms($this->user_id, PRESENT_SMS_UPGRADE)) {
                $content = '感谢您激活,momo.im赠送' . PRESENT_SMS_UPGRADE . '条全球免费MO短信给您,您当前可用短信总数:' . $this->model->get_sms_count($this->user_id);
                $xiaomo_uid = Kohana::config('uap.xiaomo');
                $this->model->present_mo_notice($xiaomo_uid, $this->user_id, $content);
            }

            $this->send_response(200, array('uid' => $this->user_id, 'bind' => 1, 'user_status' => 1, 'zone_code' => $tmp_account_info ['zone_code'], 'mobile' => $tmp_account_info ['mobile'], 'user_status' => 1));
        }
        Feed_Model::instance()->add_log("uid:{$this->user_id},zone_code:{$zone_code},mobile:{$mobile},password:{$password},非法请求");
        $this->send_response(400, NULL, '4003005:非法请求');
    }

    /**
     *
     * 重发初始化密码
     */
    public function init_password()
    {
        $post = $this->get_data();
        $mobile = trim($post ['mobile']);
        $zone_code = $post ['zone_code'] ? mobile::zone_code_format($post ['zone_code']) : '86';
        //检查手机端格式
        if (!$this->_check_mobile_valid($mobile, $zone_code, false)) {
            $this->send_response(400, NULL, $this->check_msg);
        }
        $user = $this->model->get_tmp_account($mobile, $zone_code);
        if ($user) {
            if ($user ['status'] == 0) {
                if ($this->model->send_init_passwd($mobile, $zone_code, $user ['password'], $user ['url_code'])) {
                    $this->send_response(200, array('uid' => $user ['uid']));
                } else {
                    $this->send_response(400, NULL, $this->model->get_return_msg());
                }
            }
            $this->send_response(400, NULL, '400301:该帐号已设置过密码不能发送初始化密码');
        } else {
            $this->send_response(400, NULL, '400302:帐号未注册');
        }
    }

    /**
     * 删除测试用户
     * @method POST
     */
    public function destroy($mobile = '13225911432')
    {
        $post = $this->get_data();
        $mobile = trim($post ['mobile']);
        if ($mobile == '5085910202') {
            $zone_code = 1;
        } else {
            $zone_code = 86;
        }
        if (in_array($mobile, $this->test_account, TRUE)) {
            $this->model->delete_account($mobile, $zone_code);
            $this->send_response(200, array('status' => 'success'));
        }
        $this->send_response(200, array('status' => 'fail'));
    }

    /**
     * 检查手机号码有效性
     * @param <type> $mobile
     * @return <type>
     */
    private function _check_mobile_valid($mobile, $zone = '86', $check_registed = true)
    {
        if (empty ($mobile)) {
            $this->check_msg = '400115:手机号码为空';
            return false;
        } elseif (!$this->_check_mobile_format($mobile, $zone)) {
            $this->check_msg = '400116:手机号码格式不对';
            return false;
        } elseif ($check_registed && $this->model->check_mobile_registed($mobile, $zone)) {
            $this->check_msg = '400117:手机号码已注册';
            return false;
        } else {
            return true;
        }
    }

    /**
     *
     * 检查中国手机号是否合法
     * @param unknown_type $mobile
     */
    private function _check_mobile_format($mobile, $zone = '86')
    {
        switch ($zone) {
            default :
                if (!is_numeric($mobile)) {
                    return false;
                }
            case '086' :
            case '86' :
                if (!preg_match('/^1[3|5|8|4][0-9]{9}$/is', $mobile)) {
                    return false;
                }
                break;
        }
        return true;
    }

    /**
     * 检查姓名是否合法
     * @param <type> $check_name
     * @param <type> $max_len
     */
    public function _check_name_valid($check_name)
    {
        $str_len = mb_strlen($check_name, "utf-8");
        do {
            if ($str_len > $this->max_name_length || $str_len < 2) {
                $this->check_msg = '400127:姓名长度不合法';
                return false;
            }

            if (!preg_match("/^[\x{4e00}-\x{9fa5}]+$/u", $check_name)) {
                $this->check_msg = '400128:姓名不是中文';
                return false;
            }

            preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/", $check_name, $match);
            $bjx = Kohana::config_load('bjx');
            if (!in_array($match [0] [0], $bjx) && !in_array($match [0] [0] . $match [0] [1], $bjx)) {
                $this->check_msg = '400129:姓不合法';
                return false;
            }

            //如果是单姓
            if (in_array($match [0] [0], $bjx) && !in_array($match [0] [0] . $match [0] [1], $bjx)) {
                $firstname = join("", array_slice($match [0], 1));

                if ($str_len > $this->max_name_length - 1) {
                    $this->check_msg = '400127:姓名长度不合法';
                    return false;
                }
            } else {
                $firstname = join("", array_slice($match [0], 2));
            }

            //非法关键词检测
            $sensitive = Kohana::config('sensitive.sensitive');
            if (stripos($sensitive, '|' . $check_name . '|') !== false || stripos($sensitive, '|' . $firstname . '|') !== false) {
                $this->check_msg = '400130:姓名包含非法关键字';
                return false;
            }

            $json_result ['success'] = true;
        } while (0);

        return true;
    }

    /**
     * 分割姓和名@todo 假如复姓跟单姓有重复则优先复姓
     * @param <type> $realname
     * @return <type>
     */
    private function _split_realname($realname)
    {
        preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/", $realname, $match);
        $bjx_arr = Kohana::config_load('bjx');

        if (in_array($match [0] [0] . $match [0] [1], $bjx_arr) && mb_strlen($realname, "utf-8") > 2) {
            return array(array_shift($match [0]) . array_shift($match [0]), join("", $match [0]));
        }

        if (in_array($match [0] [0], $bjx_arr)) {
            return array(array_shift($match [0]), join("", $match [0]));
        }

        return array();
    }

    /**
     * 生成随机码
     * @param unknown_type $len
     * @return string
     */
    private function _rand_number($len = 6)
    {
        $chars = '1234567890';
        mt_srand(( double )microtime() * 1000000 * getmypid());
        $rand = '';
        while (strlen($rand) < $len)
            $rand .= substr($chars, (mt_rand() % strlen($chars)), 1);
        return $rand;
    }

    public function present_tmp_notice()
    {
        $now = $this->model->present_tmp_notice();
        $this->send_response(200, array('send_num' => $now));
    }

    /**
     *
     * 根据ip生成体验用户
     */
    public function exp_by_ip()
    {
        $post = $this->get_data();
        $ip = trim($post ['ip']);
        if (preg_match("/[\d\.]{7,15}/", $ip)) {
            $device_id = md5($ip);
            $uid = $this->model->exp($device_id, 0);
            if ($uid) {
                $this->send_response(200, array('uid' => $uid));
            }
            $this->send_response(400, NULL, '400302:用户创建失败');
        }
        $this->send_response(400, NULL, '400301:IP格式不正确');
    }

    /**
     *
     * 根据手机号创建用户
     */
    public function create_at()
    {
        $data = $this->get_data();
        //检查每组数据是否符合要求
        if (count($data) > 100) {
            $this->send_response(400, NULL, '400220:手机号码超出最大限制');
        }
        $result = $this->model->create_at($data, $this->user_id, $this->source);
        $this->send_response(200, $result);
    }


    /**
     *
     * 根据91通行证创建用户
     */
    public function create_91()
    {
        $data = $this->get_data();
        //检查每组数据是否符合要求
        if (count($data) == 0) {
            $this->send_response(400, NULL, '400225:数据为空');
        }
        if (count($data) > 100) {
            $this->send_response(400, NULL, '400228:创建91通行证帐号超出最大限制');
        }
        $result = $this->model->create_91($data, $this->user_id, $this->source, $this->appid);
        $this->send_response(200, $result);
    }

    /**
     *
     * 检查手机号是否注册
     */
    public function verify_mobile()
    {
        $data = $this->get_data();
        $mobile = trim($data ['mobile']);
        $zone_code = $data ['zone_code'] ? mobile::zone_code_format($data ['zone_code']) : '86';
        if (empty ($mobile)) {
            $this->send_response(400, NULL, '40001:手机号为空');
        }
        if (!international::check_is_valid($zone_code, $mobile)) {
            $this->send_response(400, NULL, '40002:手机号码格式不对');
        }
        $is_legal = 1;
        //判断手机号对应的用户是否存在
        $user = $this->model->get_user_by_mobile($mobile, $zone_code);
        if ($user && $user['uid'] > 0) {
            if ($user ['status'] >= 3) {
                $is_legal = 0;
            }
        }
        $this->send_response(200, array('is_legal' => $is_legal));
    }
}
