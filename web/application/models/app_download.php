<?php defined('SYSPATH') or die('No direct script access.');
class App_download_Model extends Model 
{
	protected $_table;
	
	public function __construct()
    {
        // 加载数据库类。以下可以使用 $this->db 操作数据库（如果不要求可以省略）
        parent::__construct();
        $this->session = Session::instance();
        $this->uid = $this->getUid();
        $this->_table = 'app_download';
    }
    
    public function saveDownloadRecord($platform, $channel, $brand, $phone_model, $now) {
    	$data['platform'] = $platform;
    	$data['channel'] = $channel;
    }
    
    public function updateDownloadRecord($platform, $channel, $brand = 0, $phone_model = 0) 
    {
    	
    	if (!$this->isExist($platform, $channel, $brand, $phone_model)) {
    		// 插入
    		$data['platform'] = $platform;
    		$data['channel'] = $channel;
    		$data['brand_id'] = $brand;
    		$data['phone_model'] = $phone_model;
    		$data['dl_times'] = 1;
    		$this->db->insertData($this->_table, $data);
    	} else {
    		// 更新
    		$data['platform ='] = $platform;
	    	$data['channel ='] = $channel;
	    	$data['brand_id ='] = $brand;
	    	$data['phone_model ='] = $phone_model;
	    	$dataInsert['dl_times'] = new Database_Expression('dl_times+1');
	    	$this->db->update($this->_table, $dataInsert, $data);
    	}
    }
    
    public function isExist($platform, $channel, $brand, $phone_model)
    {
    	$data['platform ='] = $platform;
    	$data['channel ='] = $channel;
    	$data['brand_id ='] = $brand;
    	$data['phone_model ='] = $phone_model;
    	$result = $this->db->from($this->_table)->where($data)->count_records();
    	// echo Kohana::debug($result);exit;
    	return $result;
    }
}