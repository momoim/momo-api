<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * 取得手机品牌
 * 
 * @package Brand_Controller
 * @author Yufeng <ivwsai@gmail.com>
 * @copyright (c) 2010-2011 MOMO Team
 */
 
class Brand_Controller extends Controller {

    // Allow all controllers to run in production by default
    const ALLOW_PRODUCTION = TRUE;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->model = Brand_Model::instance();
        
        //$this->user_id = 10614401;
    }
    
    /**
     * @method GET
     */
    public function index()
    {
        if($this->get_method() != 'GET') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        
        $platform = $this->input->get("platform", "");
        
        $result = $this->model->brandlist($platform);
        
        $this->send_response(200, $result);
    }
    
    /**
     * @method GET
     */
    public function marque()
    {
        if($this->get_method() != 'GET') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        
        $id = $this->input->get("id");
        
        if (!is_numeric($id)) {
            $this->send_response(400, NULL, '输入有误');
        }
        
        $result = $this->model->brand($id);
        
        if (!$result) {
            $this->send_response(404, NULL, '请求的品牌不存在');
        }
        
        $filter = array();
        if ($platform = $this->input->get("platform")) {
            $platform_arr = array(
                1=>"Android", 
                2=>"iOS", 
                3=>"WM", 
                4=>"s60v3", 
                5=>"s60v5",
                6=>"Kjava", 
                7=>"webos", 
                8=>"blackberry", 
                9=>"ipad" 
                //10=>"web客户端", 
                //11=>"web客户端触屏版"
            );
            
            if (isset($platform_arr[$platform])) {
                $filter["os"] = $platform_arr[$platform];
            }
        }
        
        if ($touch = $this->input->get("touch")) {
            $filter["touch"] = (int) $touch;
        }
        
        if ($dpi = $this->input->get("dpi")) {
            $filter["dpi_w >="] = (int) $dpi;            
        }
        
        $result["data"] = $this->model->marque_list($id, $filter);
        
        $this->send_response(200, $result);
    }

	/**
	 * 更新机型名称
	 */
	public function update()
	{

//		if(!in_array($this->input->ip_address(), array('0.0.0.0','127.0.0.1'), TRUE)) {
//			$this->send_response(403, NULL, '禁止访问');
//		}

		if($this->get_method() != 'POST') {
			$this->send_response(405, NULL, '请求的方法不存在');
		}

		$data = $this->get_data();

		if(empty($data)) {
			$this->send_response(400, NULL, '输入有误');
		}

		foreach($data as $val) {
			if(empty($val['model']) OR empty($val['name'])) {
				$this->send_response(400, NULL, '输入有误');
			}
		}

		$result["data"] = $this->model->update($data);

		$this->send_response(200);

	}

	/**
	 * 获取机型名称
	 * @param string $model
	 */
	public function get($model = '')
	{
		if($this->get_method() != 'GET') {
			$this->send_response(405, NULL, '请求的方法不存在');
		}
		$model = urldecode($model);

		if(empty($model)) {
			$this->send_response(400, NULL, '输入有误');
		}

		$name = $this->model->get_by_model($model);

		$this->send_response(200, array('name' => $name));
	}
}
