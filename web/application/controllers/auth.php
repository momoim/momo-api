<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Default Kohana controller. This controller should NOT be used in production.
 * It is for demonstration purposes only!
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Auth_Controller extends Controller
{

    // Disable this controller when Kohana is set to production mode.
    // See http://docs.kohanaphp.com/installation/deployment for more details.
    const ALLOW_PRODUCTION = TRUE;

    /**
     * @var User_Model
     */
    protected $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = User_Model::instance();
    }

    protected function is_test_mobile($mobile)
    {
        if (in_array($mobile, array('13800000000', '13800000001', '13800000002', '13800000003',
            '13800000004', '13800000005', '13800000006', '13800000007', '13800000008', '13800000009'))) {
            return true;
        }
        return false;
    }

    protected function is_super_mobile($mobile)
    {
        if (in_array($mobile, array())) {
            return true;
        }
        return false;
    }

    protected function check_verify_rate($username)
    {
        $now = time();
        $result = $this->model->get_verify_code($username);


        if ($result['count'] > 10 and $now - $result['last_timestamp'] > 1800) {
            return true;
        }
        if ($now - $result['last_timestamp'] > 50) {
            return true;
        }
        return false;
    }

    public function verify_code()
    {
        if ($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }

        $data = $this->get_data();
        $zone_code = isset($data['zone_code']) ? $data['zone_code'] : '';
        $mobile = isset($data['mobile']) ? $data['mobile'] : '';

        if (!international::check_is_valid($zone_code, $mobile)) {
            $this->send_response(400, NULL, Kohana::lang('authorization.mobile_invalid'));
        }

        $username = $this->model->get_full_mobile($zone_code, $mobile);
        $is_test_mobile = $this->is_test_mobile($mobile);
        if (!$is_test_mobile and !$this->check_verify_rate($username)) {
            $this->send_response(400, NULL, Kohana::lang('authorization.get_code_exceed_rate'));
        }

        $code = $this->model->create_verify_code();

        $this->model->set_verify_code($username, $code);

        if ($is_test_mobile) {
            $this->send_response(200, array('code' => $code, 'zone_code' => $zone_code, 'mobile' => $mobile));
        }

        $is_super = $this->is_super_mobile($mobile);
        if ($is_super) {
            $this->send_response(200, array('code' => $code, 'zone_code' => $zone_code, 'mobile' => $mobile));
        }

        if (!$this->model->send_message($zone_code, $mobile, $code)) {
            $this->send_response(400, NULL, Kohana::lang('authorization.send_fail'));
        }
        // 正式时不能返回code
        if (IN_PRODUCTION === TRUE) {
            $this->send_response(200, array('zone_code' => $zone_code, 'mobile' => $mobile));
        } else {
            $this->send_response(200, array('code' => $code, 'zone_code' => $zone_code, 'mobile' => $mobile));
        }
    }

    public function token()
    {
        if ($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }

        $data = $this->get_data();
        $zone_code = isset($data['zone_code']) ? $data['zone_code'] : '';
        $mobile = isset($data['mobile']) ? $data['mobile'] : '';
        $code = isset($data['code']) ? $data['code'] : '';

        $username = $this->model->get_full_mobile($zone_code, $mobile);

        if (!$this->is_test_mobile($mobile)) {
            if (!$this->model->check_verify_code($username, $code)) {
                $this->send_response(400, NULL, Kohana::lang('authorization.code_invalid'));
            }
        }

        $user = $this->model->get_user_by_mobile($zone_code, $mobile);
        if ($user) {
            $id = $user['id'];
        } else {
            $regip = $this->get_ip();

            $id = $this->model->create_user($zone_code, $mobile, '', $regip);
        }

        $token = $this->model->create_token(3600, TRUE, array(
            'zone_code' => $zone_code,
            'mobile' => $mobile,
            'id' => (int)$id
        ));

        $this->send_response(200, $token);
    }

    public function refresh_token()
    {
        if ($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }

        $data = $this->get_data();

        if (empty($data['refresh_token'])) {
            $this->send_response(400, NULL, Kohana::lang('authorization.input_invalid'));
        }

        $refresh_token = $this->model->get_refresh_token($data['refresh_token']);
        if (empty($refresh_token)) {
            $this->send_response(400, NULL, Kohana::lang('authorization.input_invalid'));
        }

        $token = $this->model->create_token(3600, FALSE, $refresh_token);
        $this->send_response(200, $token);
    }

} // End Auth Controller