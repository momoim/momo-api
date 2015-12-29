<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * [MOMO API] (C)1999-2011 ND Inc.
 * 投票控制器文件
 */
class Vote_Controller extends Controller {

    // Allow all controllers to run in production by default
    const ALLOW_PRODUCTION = TRUE;

    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * 查看投票列表
     * @method GET
     */
    public function index()
    {
        if($this->get_method() != 'GET') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
    }
    
    /**
     * 查看投票
     * @method GET
     */
    public function show()
    {
        if($this->get_method() != 'GET') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
    }
    
    /**
     * 创建投票
     * @method POST
     */
    public function create()
    {
        if($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        
        $post = $this->get_data();
        
    }
    
    /**
     * 更新投票
     * @method POST | PUT
     */
    public function update()
    {
        if($this->get_method() != 'POST' && $this->get_method() != 'PUT' ) {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        
        $post = $this->get_data();
        
        //修改投票时间
        if(isset($post['deadline'])) {
            
        }
        
        //修改投票说明
        if(isset($post['message'])) {
            
        }
        
        //增加投票选项
        if(isset($post['items']) && is_array($post['items'])) {
            
        }
        
    }
    
    /**
     * 删除投票
     * @method POST | DELETE
     */
    public function destroy()
    {
        if($this->get_method() != 'POST' && $this->get_method() != 'DELETE' ) {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        $post = $this->get_data();
        
    }
}
