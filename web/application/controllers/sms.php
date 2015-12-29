<?php defined('SYSPATH') or die('No direct access allowed.');
/**
* [MOMO API] (C)1999-2011 ND Inc.
* 短信备份控制器文件
*/

class Sms_Controller extends Controller {

	/**
	 * 短信备份模型
	 * @var Contact_Model
	 */
	protected $model;
	private $data_type = array('common');
	
    public function __construct()
    {
        parent::__construct();
        $this->model = Sms_Model::instance();
    }

    public function index()
    {
        $this->send_response(405, NULL, '请求的方法不存在');
    }
    
    /**
     * 
     * 申请备份批号
     */
    public function apply_batch_number() {
    	$data = $this->get_data();
    	$device_id = $data['device_id']?trim($data['device_id']):'';
    	$phone_model = $data['phone_model']?trim($data['phone_model']):'';
    	$total_sms = $data['total_sms']?trim($data['total_sms']):0;
    	if (empty($device_id)) {
    		$this->send_response(400, NULL,'401201:设备id为空');
    	}
    	if (empty($total_sms)) {
    		$this->send_response(400, NULL,'401202:总短信条数为空');
    	}
    	$number = $this->model->apply_batch_number($this->user_id,$device_id,$phone_model,$total_sms,$this->appid,$this->source);
    	if($number)
    		$this->send_response(200, array('batch_number'=>$number));
    	$this->send_response(400, NULL,'401203:申请备份批号失败');
    }
    
    /**
     * 
     * 短信备份
     */
    public function backup() {
    	$data = $this->get_data(false);
    	$device_id = $data['device_id']?trim($data['device_id']):'';
    	$phone_model = $data['phone_model']?trim($data['phone_model']):'';
    	$batch_number = $data['batch_number']?trim($data['batch_number']):'';
    	if (empty($device_id)) {
    		$this->send_response(400, NULL,'401201:设备id为空');
    	}
    	if (empty($batch_number)) {
    		$this->send_response(400, NULL,'401204:备份批号为空');
    	}
		if(empty($data['data']) || !is_array($data['data']) || count($data['data'])==0) {
			$this->send_response(400, NULL,'401205:短信数据非法');
		}
		foreach ($data['data'] as $v) {
			if(empty($v['group_id']))
				$this->send_response(400, NULL,'401206:group_id为空');
			if(empty($v['message_id']))
				$this->send_response(400, NULL,'401207:message_id为空');
			if(empty($v['dateline']))
				$this->send_response(400, NULL,'401208:dateline为空');
			if(empty($v['data_type']))
				$this->send_response(400, NULL,'401209:data_type为空');
			if(!in_array($v['data_type'], $this->data_type))
				$this->send_response(400, NULL,'401210:data_type非法');
			if(!$v['sms']['draft'] && empty($v['sms']['address'])) {
				$this->send_response(400, NULL,'401211:address为空');
			}
		}
    	if (count($data['data']) > 100) {
			$this->send_response(400, NULL,'401212:超过最大短信上传数量');
		}
    	$batch_info = $this->model->get_batch_info($this->user_id,$device_id,$batch_number);
    	if(!$batch_info) {
    		$this->send_response(400, NULL,'401213:备份批号非法');
    	}
		$result = $this->model->add_batch($this->user_id,$data);
		$result['backup_total_sms'] = $batch_info['backup_total_sms'];
		$this->send_response(200, $result);
    }
    
    /**
     * 
     * 备份完成
     */
    public function backup_done() {
    	$data = $this->get_data();
    	$device_id = $data['device_id']?trim($data['device_id']):'';
    	$batch_number = $data['batch_number']?trim($data['batch_number']):'';
    	if (empty($device_id)) {
    		$this->send_response(400, NULL,'401201:设备id为空');
    	}
    	if (empty($batch_number)) {
    		$this->send_response(400, NULL,'401204:备份批号为空');
    	}
    	$batch_info = $this->model->get_batch_info($this->user_id,$device_id,$batch_number);
    	if(!$batch_info) {
    		$this->send_response(400, NULL,'401213:备份批号非法');
    	}
    	$batch_total_sms = $this->model->get_sms_count($this->user_id,$device_id,$batch_number);
    	if($batch_info['backup_total_sms'] != $batch_total_sms) {
    		$this->send_response(400, NULL,'401214:短信数量校验不一致');
    	}
		if($this->model->backup_done($this->user_id,$device_id,$batch_number,$batch_info['phone_model'],$batch_info['backup_total_sms'],$this->appid,$this->source))
			$this->send_response(200, array('backup_total_sms'=>$batch_info['backup_total_sms']));
		$this->send_response(400, NULL,'401216:备份失败');	
    }
    
    /**
     * 
     * 取消备份
     */
    public function cancel_backup() {
    	$data = $this->get_data();
    	$device_id = $data['device_id']?trim($data['device_id']):'';
    	$batch_number = $data['batch_number']?trim($data['batch_number']):'';
    	if (empty($device_id)) {
    		$this->send_response(400, NULL,'401201:设备id为空');
    	}
    	if (empty($batch_number)) {
    		$this->send_response(400, NULL,'401204:备份批号为空');
    	}
    	$batch_info = $this->model->get_batch_info($this->user_id,$device_id,$batch_number);
    	if(!$batch_info) {
    		$this->send_response(400, NULL,'401213:备份批号非法');
    	}
    	$total_sms = $this->model->cancel_backup($this->user_id,$device_id,$batch_number);
    	if($total_sms)
			$this->send_response(200, array('batch_total_sms'=>$total_sms));
		$this->send_response(400, NULL,'401215:取消备份失败');	
    }

    /**
     * 
     * 获取备份批号记录
     */
    public function batch_history() {
    	$data = $this->get_data();
    	$device_id = $data['device_id']?trim($data['device_id']):'';
    	$batch_number = $data['batch_number']?trim($data['batch_number']):'';
    	$result = $this->model->get_batch_history($this->user_id,$device_id,$batch_number);
    	$this->send_response(200, array('batch'=>$result));
    }
    
    /**
     * 
     * 获取备份历史
     */
    public function backup_history() {
    	$data = $this->get_data();
    	$num = $data['num']?(int)$data['num']:1;
    	$device_id = $data['device_id']?trim($data['device_id']):'';
    	$result = $this->model->get_backup_history($this->user_id,$device_id,$num);
    	$this->send_response(200, array('backup'=>$result));
    }

	/**
	 *
	 * 获取所有设备最新备份历史
	 */
	public function latest_history() {
		$result = $this->model->get_latest_history($this->user_id);
		$this->send_response(200, array('backup'=>$result));
	}
	
	/**
	 * 根据组获取短信
	 * @return array()
	 */
	public function lists_by_group() {
    	$data = $this->get_data();
    	$start = (int)$this->input->get('start', 0);
    	$size = (int)$this->input->get('size', 50);
    	$result = array();
    	
		$result = $this->model->lists_by_group($this->user_id,$start,$size);
    	$this->send_response(200, $result);
	}
	
	/**
	 * 根据联系人获取短信
	 * @return array()
	 */
	public function lists_by_contact() {
    	$data = $this->get_data();
    	$start = (int)$this->input->get('start', 0);
    	$size = (int)$this->input->get('size', 50);
    	$address = $this->input->get('address', '');
    	$result = array();
		//if (empty($address)) {
    	//	$this->send_response(400, NULL,'401220:address为空');
    	//}
		$result = $this->model->lists_by_contact($this->user_id,$address,$start,$size);
    	if($result) {
    		$this->send_response(200, $result);
    	}
	}
    
    /**
     * 
     * 短信还原
     */
    public function restore() {
    	$data = $this->get_data();
    	$start = $data['start']?(int)($data['start']):0;
    	$size = $data['size']?(int)($data['size']):50;
    	$from_device_id = $data['from_device_id']?trim($data['from_device_id']):'';
    	$from_batch_number = $data['from_batch_number']?trim($data['from_batch_number']):'';
    	$to_device_id = $data['to_device_id']?trim($data['to_device_id']):'';
    	$to_phone_model = $data['to_phone_model']?trim($data['to_phone_model']):'';
    	if (empty($from_device_id)) {
    		$this->send_response(400, NULL,'401216:还原源设备id为空');
    	}
    	if (empty($from_batch_number)) {
    		$this->send_response(400, NULL,'401217:还原源备份批号为空');
    	}
    	if (empty($to_device_id)) {
    		$this->send_response(400, NULL,'401218:还原目标设备id为空');
    	}
    	$result = $this->model->get_sms_batch($this->user_id,$from_device_id,$from_batch_number,$start,$size);
    	if($result) {
    		$this->model->check_restore_history($this->user_id,$from_device_id,$from_batch_number,$to_device_id,$to_phone_model);
    		$this->send_response(200, $result);
    	}
    	$this->send_response(400, NULL,'401219:该设备id对应的备份记录不存在');
    }
    
    /**
     * 短信恢复
     * @return 
     */
    public function recover() {
    	$data = $this->get_data();
    	$device_id = $data['device_id']?trim($data['device_id']):'';
    	$batch_number = $data['batch_number']?trim($data['batch_number']):'';
    	$result = $this->model->get_backup_info($this->user_id,$device_id,$batch_number);
    	if(!$result)
    		$this->send_response(400, NULL,'401231:备份记录不存在');
    	$result = $this->model->recover($this->user_id,$device_id,$batch_number);
    	if($result)
    		$this->send_response(200);
    	$this->send_response(400, NULL,'401232:短信恢复失败');
    }
    
	public function dump_sms() {
    	$data = $this->get_data();
    	$db = $data['db']?(int)($data['db']):0;
    	$num = $this->model->dump_sms($db);
    	$this->send_response(200, array('num'=>$num));
	}	
    
	/**
	 * 删除短信
	 * @return 
	 */
	public function delete_batch() {
    	$data = $this->get_data();
    	$batch_number = $data['batch_number']?trim($data['batch_number']):'';
    	$address = $data['address']?trim($data['address']):'';
    	$ids = $data['ids']?trim($data['ids']):'';
    	if(empty($batch_number)) {
    		$this->send_response(400, NULL,'401204:备份批号为空');
    	}
    	if(empty($ids) && empty($address)) {
    		$this->send_response(400, NULL,'401221:id,address不能都为空');
    	}
    	if($ids) {
    		$ids = explode(',',$ids);
    	}
		$delete_total = $this->model->delete_batch($this->user_id,$batch_number,$ids,$address);
		$this->send_response(200, array('delete_total'=>(int)$delete_total));
	}
	
	/**
	 * 导出短信
	 * @return 
	 */
	public function export() {
    	$data = $this->get_data();
    	$batch_number = $data['batch_number']?trim($data['batch_number']):'';
    	$format = $data['format']?trim($data['format']):'txt';
    	$address = $data['address']?trim($data['address']):'';
    	$ids = $data['ids']?$data['ids']:'';
    	$all = $data['all']?(int)($data['all']):0;
    	if(empty($batch_number)) {
    		$this->send_response(400, NULL,'401204:备份批号为空');
    	}
    	if(!$all && empty($ids) && empty($address)) {
    		$this->send_response(400, NULL,'401221:id,address不能都为空');
    	}
    	if(!in_array($format,array('xls','txt'))) {
    		$this->send_response(400, NULL,'401222:导出格式不支持');
    	}
    	if($ids) {
    		$ids = explode(',',$ids);
    	}
    	if($address) {
    		$address = $this->_format_address($address);
    	}
    	$result = $this->model->export($this->user_id,$batch_number,$format,$ids,$address,$all);
    	$this->send_response(200, array('data'=>$result));
	}
	

	private function _format_address($address) {
		$addr = array();
		if($address) {
			$address_exp = explode(',',trim($address));
			foreach($address_exp as $v) {
				if($v) 
					$addr[] = "'".trim($v)."'";
			}
		}
		return $addr;
	}
}