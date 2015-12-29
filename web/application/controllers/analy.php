<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * 用户反馈模块
 * @author Administrator
 *
 */
class Analy_Controller extends Controller {

    public function __construct() {
        parent::__construct();
        $this->model	= new Analy_Model;
    }
    
    public function add() {
        if ($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
    	$data = $this->get_data();
    	$appid = $data['appid']?(int)($data['appid']):0;
    	$type = $data['type']?trim($data['type']):'';
    	$code = $data['code']?(int)($data['code']):0;
    	$content = $data['content']?trim($data['content']):'';
    	$user_agent = $data['user_agent']?trim($data['user_agent']):'';
    	$client_id = $data['client_id']?(int)($data['client_id']):0;
        if(empty($appid))
            $this->send_response(400, NULL, '400901:appid不能为空');
        if(empty($type))
            $this->send_response(400, NULL, '400902:type不能为空');
    	$this->model->add($appid,$type,$code,$client_id,$content,$user_agent);
    	$this->send_response(200);
    }

    /**
     * 取得反馈
     */

    public function lists($id=NULL) {
        if ($this->get_method() != 'GET') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
		$page = (int)($this->input->get('page', 1));
		$size = (int)($this->input->get('size', 10));
		$type = trim($this->input->get('type', ''));
		$code = (int)($this->input->get('code', 0));

        if(empty($id)) {
            $this->send_response(400, NULL, '400901:appid不能为空');
        }
        $return = $this->model->lists($id,$type,$code,$page,$size);
        $this->send_response(200,$return);
    }

    public function stat($id=NULL) {
        if ($this->get_method() != 'GET') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
		$type = trim($this->input->get('type', ''));
		$code = (int)($this->input->get('code', 0));
		$start_date = trim($this->input->get('start_date', ''));
		$end_date = trim($this->input->get('end_date', ''));

        if(empty($id)) {
            $this->send_response(400, NULL, '400901:appid不能为空');
        }
        $return = $this->model->stat($id,$code,$type,$start_date,$end_date);
        $this->send_response(200,$return);
    }

    


}
?>