<?php
defined('SYSPATH') or die('No direct access allowed.');

/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * 苹果消息推送控制器文件
 */
/**
 * 苹果消息推送控制器
 */
class Push_Controller extends Controller
{
    /**
     * 是否发布模式
     */
    const ALLOW_PRODUCTION = TRUE;
    /**
     * 苹果消息推送模型
     * @var Push_Model
     */
    protected $model;

    public function __construct ()
    {
        parent::__construct();
        $this->model = Push_Model::instance();
    }

    /**
     * 设备ID与用户关联
     */
    public function create ()
    {
        if ($this->get_method() != 'POST') {
            $this->send_response(405, NULL, 
            Kohana::lang('push.method_not_exist'));
        }
        $data = $this->get_data();
        $device_id = isset($data['device_id']) ? str_replace(' ', '', $data['device_id']) : '';
        if (empty($device_id)) {
            $this->send_response(400, NULL, 
            Kohana::lang('push.device_id_empty'));
        } elseif (strlen($device_id) != 64) {
            $this->send_response(400, NULL, 
            Kohana::lang('push.device_id_length_illegal'));
        } else {
            if ($this->model->add($this->user_id, $device_id)) {
                $this->send_response(200);
            } else {
                $this->send_response(500, NULL, 
                Kohana::lang('push.opetation_fail'));
            }
        }
    }
    
    /**
     * 删除iphone苹果设备的记录
     */
    public function delete(){
        if ($this->get_method() != 'POST') {
            $this->send_response(405, NULL, 
            Kohana::lang('push.method_not_exist'));
        }
        $data = $this->get_data();
        $device_id = isset($data['device_id']) ? str_replace(' ', '', $data['device_id']) : '';
        if(empty($device_id)){
            $this->send_response(400, NULL, 
            Kohana::lang('push.device_id_empty'));
        }elseif (strlen($device_id) != 64){
            $this->send_response(400, NULL, 
            Kohana::lang('push.device_id_length_illegal'));
        } else {
            $this->model->delete($this->user_id, $device_id);
            $this->send_response(200);
        }
        
    }
}