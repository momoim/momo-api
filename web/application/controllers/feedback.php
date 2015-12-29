<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * 用户反馈模块
 * @author Administrator
 *
 */
class Feedback_Controller extends Controller {

    public function __construct() {
        parent::__construct();
        $this->model	= new Feedback_Model;
    }

    /**
     * 取得反馈
     */

    public function index() {
        if ($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        $data = $this->get_data();

        $text = isset($data['text'])?trim($data['text']):'';
        $contact = isset($data['contact'])?trim($data['contact']):'';
        $kind = isset($data['kind'])?trim($data['kind']):'';
        $source = $this->get_source();

        if(empty($text)) {
            $this->send_response(400, NULL, '400901:反馈内容不能为空');
        }
        if(empty($source)) {
            $this->send_response(400, NULL, '400902:客户端类型id不能为空');
        }

        $this->user_id	= $this->getUid();
        $name = sns::getrealname($this->user_id);
        $text = html::specialchars($text);
        $array = array(
                'uid' => $this->user_id,
                'name' => $name,
                'content' => $text,
                'contact' => $contact,
                'kind' => $kind,
                'client_id' => $source,
                'addtime' => time()
        );

        $return = $this->model->saveData($array);
        $this->send_response(200);
    }

    


}
?>