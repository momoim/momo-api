<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * oauth验证
 *
 * @package    Core
 * @author     xhh
 * @copyright  (c) 2007-2008 momo Team
 */
class Oauth_Controller extends Controller {

    protected $error = null;
    protected $is_mobile = false;

    public function __construct ()
    {
        parent::__construct();
        switch($this->second) {
            case 'request_token':
                $this->oauth_server->requestToken();
                exit;
            case 'authorize':
                if ($this->get_method() == 'POST' || !empty($_POST)) {
                    if(!empty($_POST)) {
                        $oauth_token = $_POST['oauth_token'];
                        $account = $_POST['account'];
                        $password = $_POST['password'];
                        $oauth_callback = $_POST['oauth_callback'];
                    } else {
                        $data = $this->get_data();
                        $oauth_token = $data['oauth_token'];
                        $account = isset($data['account'])?$data['account']:'';
                        $password = isset($data['password'])?$data['password']:'';
                        $oauth_callback = isset($data['oauth_callback'])?$data['oauth_callback']:'';
                        $this->is_mobile = true;
                    }
                    $to_post['account'] = $account;
                    $to_post['password'] = $password;
                    $result = $this->_uc_fopen(API_PATH.'user/verify.json', 0, $this->to_postdata($to_post), 'POST');
                    $result_obj = json_decode($result['data']);
                    if(isset($result_obj->error_code) && $result_obj->error_code == 400) {
                        $this->error = $result_obj->error;
                        if(empty($oauth_callback)) {
                            $this->send_response(400,NULL,$result_obj->error);
                        }
                    }
                    if(isset($result_obj->uid) && $result_obj->uid > 0) {
                        $this->oauth_server->authorizeVerify($oauth_token,$oauth_callback,$this->is_mobile);
                        $verifier = $this->oauth_server->authorizeFinish(true, $result_obj->uid,$oauth_token);
                        if(!empty($verifier)) {
                            if($this->is_mobile) {
                                $result = array();
                                $result['verifier'] = $verifier;
                                $result['user_id'] = $result_obj->uid;
                                $result['name'] = $result_obj->name;
                                $result['avatar'] = sns::getavatar($result_obj->uid);
                                $result['role'] = $result_obj->role;
                                $this->send_response(200,$result);
                            }
                            $this->render_authorize_verifier($verifier);
                        }
                    }
                }

                try {
                    $app_info = $this->oauth_server->authorizeVerify();
                    $this->assert_logged_in($app_info);
                }
                catch (OAuthException2 $e)  {
                    header('HTTP/1.1 400 Bad Request');
                    header('Content-Type: text/plain');
                    echo "Failed OAuth Request: " . $e->getMessage();
                }
                exit;
               
            case 'access_token':
                $this->oauth_server->accessToken();
                exit;
            default:
                header('HTTP/1.1 404 Not Found');
                header('Content-Type: text/plain');
                echo "Unknown request";
                exit;
        }
    }

    private function assert_logged_in($app_info)
    {
        if (empty($_SESSION['authorized']))
        {
            if($this->user_id) {
                $view = new View('oauth/authenticate');
            } else {
                $view = new View('oauth/authorize');
            }
            $view->title = '应用授权';
            $view->app_title = $app_info['application_title'];
            $view->oauth_token = $app_info['token'];
            $view->oauth_callback = $_SESSION['verify_oauth_callback'];
            $view->error = $this->error;
            
            $view->render(TRUE);
            exit;
        }
    }

    private function render_authorize_verifier($verifier) {
        $view = new View('oauth/authorize_verifier');
        $view->title = '应用授权';
        $view->verifier = $verifier;

        $view->render(TRUE);
        exit;
    }

} // End Oauth Controller
?>