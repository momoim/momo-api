<?php
defined('SYSPATH') or die ('No direct access allowed.');

/**
 * [MOMO API] (C)1999-2011 ND Inc.
 * 用户控制器文件
 */
class User_Controller extends Controller implements FS_Gateway_Core, User_Interface
{

    // Allow all controllers to run in production by default
    const ALLOW_PRODUCTION = TRUE;

    const MAX_PAGESIZE = 200;
    private $max_name_length = 20;

    protected $model;

    public function __construct()
    {
        parent::__construct();

        $this->model = User_Model::instance();
    }

    public function index()
    {
        $this->send_response(405, NULL, '请求的方法不存在');
    }

    /**
     * 用户登陆
     * @method POST
     */
    public function login()
    {
        $post = $this->get_data();
        $mobile = trim($post ['mobile']);
        $zone_code = $post ['zone_code'] ? trim($post ['zone_code']) : ($post ['zonecode'] ? trim($post ['zonecode']) : '86');
        $zone_code = str_replace('+', '', $zone_code);
        $password = trim($post ['password']);

        if (empty ($mobile)) {
            $this->send_response(400, NULL, '40001:手机号为空');
        }
        if (!international::check_is_valid($zone_code, $mobile)) {
            $this->send_response(400, NULL, '40002:手机号码格式不对');
        }
        if ($password == "") {
            $this->send_response(400, NULL, '40003:密码为空');
        }

        $user = $this->model->get_user_by_mobile($zone_code, $mobile);

        if (!$user) {
            $this->send_response(400, NULL, Kohana::lang('user.mobile_not_register'));
        }

        if (!password_verify($password, $user['password'])) {
            $this->send_response(400, NULL, Kohana::lang('user.username_password_not_match'));
        }

        $token = $this->model->create_token(3600, TRUE, array(
            'zone_code' => $user['zone_code'],
            'mobile' => $user['mobile'],
            'id' => (int)$user['id']
        ));

        $this->send_response(200, array(
                'uid' => $user ['uid'],
                'name' => $user ['username'],
                'avatar' => sns::getavatar($user ['uid']),
                'access_token' => $token ['access_token'],
                'refresh_token' => $token ['refresh_token'],
                'status' => $user ['status'],
            )
        );
    }


    public function init()
    {
        $data = $this->get_data();
        $username = isset($data['username']) ? $data['username'] : '';
        $password = isset($data['password']) ? $data['password'] : '';

        if ($password == "") {
            $this->send_response(400, NULL, '40003:密码为空');
        }
        if (strlen($password) < 6) {
            $this->send_response(400, NULL, '40003:密码过短');
        }

        $this->model->update_user($this->user_id, $password, $username);

        $this->send_response(200);
    }

    public function search()
    {
        $data = $this->get_data();
        $mobiles = isset($data['mobiles']) ? $data['mobiles'] : array();
        $result = array();
        foreach ($mobiles as $mobile) {
            $res = international::check_mobile($mobile);
            if ($res) {
                $user = $this->model->get_user_by_mobile($res['country_code'], $res['mobile']);
                if ($user) {
                    $result[] = array(
                        'id' => $user['id'],
                        'name' => $user['username'],
                        'avatar' => sns::getavatar($user['id'])
                    );
                }
            }
        }

        $this->send_response(200, $result);

    }

}