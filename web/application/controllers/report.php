<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * 举报控制器
 * 
 * 
 * @package Report_Controller
 * @copyright (c) 2010-2011 MOMO Team
 */
 
class Report_Controller extends Controller {

    const ALLOW_PRODUCTION = TRUE;

    public function __construct()
    {
        parent::__construct();
        
        $this->model = Report_Model::instance();
        
        $this->privacys = array(/*0=>'任何人可见',*/ 1=>'所有好友可见', /*4=>'指定好友可见', 2=>'凭密码访问',*/ 3=>'仅自己可见', 5=>'仅指定群可见');
        
        //$this->user_id = 10614401;
    }
    
    public function add() {
    	$post = $this->get_data ();
		$source = $post ['source']?intval ( $post ['source'] ):0;
		$reason = $post ['reason']?trim ( $post ['reason'] ):'';
		$report_phone = $post ['report_phone']?trim ( $post ['report_phone'] ):'';
		$description = $post ['description']?trim ( $post ['description'] ):'';
		$url_code = $post ['url_code']?trim ( $post ['url_code'] ):'';
		if($this->model->add($source,$reason,$report_phone,$description,$url_code)) {
			$this->send_response ( 200);
		}
		$this->send_response ( 400, NULL, '添加失败' );
    }
}
