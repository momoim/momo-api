<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * 通话记录model
 */
class Call_Records_Model extends Model 
{
	private $copy_number=10;
	private $operation_interval_time=3600;
	private $pull_history_id = array();
	
	/**
	 * 数据库连接
	 */
	protected $db;
	
	/**
	 * 数据库连接
	 */
	protected static $db_instances = array();
	
	/**
	 * 实例
	 * @var Contact_Model
	 */
	protected static $instance;

	/**
	 * 单例模式
	 * @return Contact_Model
	 */
	public static function &instance() {
		if (! isset(self::$instance)) {
			self::$instance = new Call_Records_Model();
		}
		return self::$instance;
	}
	
	public function __construct() {
	}
	
	/**
	 * 
	 * 批量添加通话记录
	 */
	public function add_batch($user_id,$data,$data_type='common') {
		$success_count = 0;
		$error_count = 0;
		foreach($data['data'] as $key => $var) {
			$call_data = $this->_format_data($var);
			$result = $this->add($user_id,$data['device_id'],$data['batch_number'],$data_type,$call_data);
			if($result)
				$success_count++;
			else 
				$error_count++;
		}
		return array('success'=>$success_count,'fail'=>$error_count,'batch_total_call'=>$this->get_call_count($user_id,$data['device_id'],$data['batch_number']));
	}
	
	/**
	 * 
	 * 批量获取通话记录
	 * @param int $user_id
	 * @param string $device_id
	 */
	public function get_call_batch($user_id,$device_id,$batch_number,$start,$size) {
		$result = array();
		$last_backup_history = $this->get_last_backup_history($user_id,$device_id,$batch_number);
		if($last_backup_history['id']) {
			$call_data = $this->get_batch($user_id,$device_id,$batch_number,$start,$size);
			return array('device_id'=>$device_id,'phone_model'=>$last_backup_history['phone_model'],'device_alias'=>Brand_Model::instance()->get_by_model($last_backup_history['phone_model']),'batch_number'=>$last_backup_history['batch_number'],'dateline'=>$last_backup_history['dateline'],'backup_total_call'=>$last_backup_history['backup_total_call'],'data'=>$call_data);
		}
		return false;
	}
	
	/**
	 * 
	 * 获取批号备份记录
	 * @param int $user_id
	 * @param string $device_id
	 * @param int $batch_number
	 */
	public function get_batch_history($user_id,$device_id='',$batch_number='') {
    	$setters = array(
			'uid'=>$user_id
		);
		$result = $history = array();
		if($device_id)
			$setters['device_id'] = $device_id;
		if($batch_number)
			$setters['batch_number'] = $batch_number;
			
		$table = $this->get_table($user_id,'backup_batch');
    	$query = $this->db->fetchData($table, 'batch_number,uid,device_id,phone_model,backup_total_call,created,completed,status',$setters,array('id'=>'DESC'));
		$result = $query->result_array(FALSE);
		if($query->count() > 0) {
			foreach($result as $key => $var) {
				$history[$key] = $var;
				//if($var['status']==2) {
				//	$history[$key]['batch_total_call'] = 0;
				//	$history[$key]['call_id'] = array();
				//} else {
					$call = $this->get_call_records_id($user_id,$var['device_id'],$var['batch_number'],'call_id');
					$history[$key]['device_alias'] = Brand_Model::instance()->get_by_model($var['phone_model']);
					$history[$key]['call_id'] = $call;
					$history[$key]['batch_total_sms'] = count($call);
				//}
			}
		}
		return $history;
	}
    
    /**
     * 
     * 获取备份记录
     * @param $user_id
     */
    public function get_backup_history($user_id,$device_id='',$num=1) {
    	$setters = array(
			'uid'=>$user_id
		);
		$result = $history = array();
		$table = $this->get_table($user_id,'backup_history');
		$sql = "SELECT device_id,batch_number,phone_model,backup_total_call,dateline FROM {$table} WHERE uid='{$user_id}' ";
		if($device_id)
			$sql .= " AND device_id='{$device_id}' ";
		$sql .= "ORDER BY dateline DESC LIMIT $num";	
    	$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		if($query->count() > 0) {
			foreach($result as $key => $var) {
				$history[$key] = $var;
				$history[$key]['dateline_alias'] = sns::gettime($var['dateline']);
				$history[$key]['device_alias'] = Brand_Model::instance()->get_by_model($var['phone_model']);
			}
		}
		return $history;
    }

	/**
	 *
	 * 获取所有设备最新备份记录
	 * @param $user_id
	 * @return array
	 */
	public function get_latest_history($user_id) {
		$history = array();
		$table = $this->get_table($user_id,'backup_history');
		$sql = sprintf('SELECT device_id,batch_number,phone_model,backup_total_call,dateline
FROM (
SELECT *
FROM %s
WHERE uid = %d
ORDER BY dateline DESC
) AS tmp
GROUP BY device_id ORDER BY dateline DESC', $table, $user_id);
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		if($query->count() > 0) {
			foreach($result as $key => $var) {
				$history[$key] = $var;
				$history[$key]['device_alias'] = Brand_Model::instance()->get_by_model($var['phone_model']);
			}
		}
		return array_values($history);
	}
    
    
	/**
	 * 
	 * 检查备份记录，只保留$copy_number份记录
	 * @param int $user_id
	 * @param string $device_id
	 * @param string $phone_model
	 */
	public function check_restore_history($user_id,$from_device_id,$from_batch_number,$to_device_id,$to_phone_model) {
		$setters = array(
			'uid'=>$user_id,
			'from_device_id'=>$from_device_id,
			'from_batch_number'=>$from_batch_number,
			'to_device_id'=>$to_device_id
		);
		$table = $this->get_table($user_id,'restore_history');
		$query = $this->db->fetchData($table, 'dateline',$setters,array('id'=>'DESC'));
		$result = $query->result_array(FALSE);
		if($query->count() > 0 && (time()-$result[0]['dateline']) < $this->operation_interval_time) {
			return false;
		}
		return $this->add_restore_history($user_id,$from_device_id,$from_batch_number,$to_device_id,$to_phone_model);
	}
	
	/**
	 * 
	 * 申请备份批号
	 * @param int $user_id
	 * @param string $device_id
	 * @param string $phone_model
	 * @param string $total_call
	 */
	public function apply_batch_number($user_id,$device_id,$phone_model,$total_call,$appid,$client_id) {
		$setters = array(
			'uid'=>$user_id,
			'device_id'=>$device_id,
			'status'=>0,
			'backup_total_call'=>$total_call
		);
		$table = $this->get_table($user_id,'backup_batch');
		$query = $this->db->fetchData($table, 'batch_number',$setters,array('id'=>'DESC'));
		$result = $query->result_array(FALSE);
		if($query->count() > 0 && $result[0]['batch_number']) {
			$this->cancel_backup($user_id, $device_id, $result[0]['batch_number'],true);
			return $result[0]['batch_number'];
		}
			
		$batch_number = str::rand_number(8);
		$setters['phone_model'] =$phone_model;
		$setters['batch_number'] =$batch_number;
		$setters['appid'] =$appid;
		$setters['client_id'] =$client_id;
		$setters['created'] =time();
		if ($this->db->insert($table,$setters))
			return $batch_number;
		return null;
	}
    
	/**
	 * 
	 * 校验备份批号合法
	 * @param int $user_id
	 * @param string $device_id
	 * @param int $batch_number
	 */
	public function get_call_count($user_id,$device_id,$batch_number) {
		$setters = array(
			'uid'=>$user_id,
			'device_id'=>$device_id,
			'batch_number'=>$batch_number
		);
		$table = $this->get_table($user_id,'call');
		$query = $this->db->fetchData($table, 'count(*) as total',$setters);
		$result = $query->result_array(FALSE);
		return (int) $result[0]['total'];
	}
	
	/**
	 * 
	 * 校验备份批号合法
	 * @param int $user_id
	 * @param string $device_id
	 * @param int $batch_number
	 */
	public function get_batch_info($user_id,$device_id,$batch_number) {
		$setters = array(
			'uid'=>$user_id,
			'device_id'=>$device_id,
			'status'=>0,
			'batch_number'=>$batch_number
		);
		$table = $this->get_table($user_id,'backup_batch');
		$query = $this->db->fetchData($table, '*',$setters,array('id'=>'DESC'));
		$result = $query->result_array(FALSE);
		if($query->count() > 0 && $result[0]) 
			return $result[0];
		return false;
	}
    
	/**
	 * 
	 * 备份完成
	 * @param int $user_id
	 * @param string $device_id
	 * @param int $batch_number
	 */
	public function backup_done($user_id,$device_id,$batch_number,$phone_model,$total_call,$appid,$client_id) {
		$table = $this->get_table($user_id,'backup_batch');
		$sqls[] = "UPDATE {$table} SET status=1,completed='".time()."' WHERE uid='{$user_id}' AND device_id='{$device_id}' AND batch_number='{$batch_number}'";
		$table = $this->get_table($user_id,'backup_history');
		$sqls[] = "INSERT INTO {$table} (`batch_number`,  `appid`, `client_id`,`uid`, `device_id`, `phone_model`, `backup_total_call`, `dateline`) VALUES ('".$batch_number."','".$appid."','".$client_id."','".$user_id."','".$device_id."','".$phone_model."','".$total_call."','".time()."')";

		$this->db->begin();
		foreach ($sqls as $sql) {
			$query = $this->db->query($sql);
			if (! $query) {
				$this->db->rollback();
			}
		}
		if($this->db->commit()) {
			return true;
		}
		return false;
	}
	
	/**
	 * 
	 * 取消备份
	 * @param int $user_id
	 * @param string $device_id
	 * @param int $batch_number
	 */
	public function cancel_backup($user_id,$device_id,$batch_number,$truncate=false) {
		$call_data = $sqls = array();
		$call_data = $this->get_call_id($user_id,$device_id,$batch_number);
		if($truncate==false) {
			$table = $this->get_table($user_id,'backup_batch');
			$sqls[] = "UPDATE {$table} SET status=2 WHERE uid='{$user_id}' AND device_id='{$device_id}' AND batch_number='{$batch_number}'";
		}
		$table = $this->get_table($user_id,'call');
		$sqls[] = "DELETE FROM {$table} WHERE uid='{$user_id}' AND device_id='{$device_id}'  AND batch_number='{$batch_number}'";
		$this->db->begin();
		foreach ($sqls as $sql) {
			$query = $this->db->query($sql);
			if (! $query) {
				$this->db->rollback();
			}
		}
		if($this->db->commit()) {
			return count($call_data);
		}
		return false;
	}
	
	/**
	 * 
	 * 获取分表表名
	 * @param int $user_id
	 * @param string $table
	 */
	public function get_table($user_id, $table) {
		static $tables = array();
		$device_db_id = $user_id % Kohana::config('call_records.divide_db');
		$device_db = 'call_records_'.$device_db_id;
		$this->db = isset($this->db_instances[$device_db]) ? $this->db_instances[$device_db] : NULL;
		$key = md5($user_id . '|' . $table);
		if (empty($tables[$key]) || !$this->db) {
			if (! isset($this->db_instances[$device_db])) {
				$this->db_instances[$device_db] = Database::instance($device_db);
			}
			$this->db = $this->db_instances[$device_db];
			$table = $table.'_'.$device_db_id.'_'.($user_id % Kohana::config('call_records.divide_table.'.$table));
			$tables[$key] = $table;	
		}
		return $tables[$key];
	}
	
	/**
	 * 根据组获取通话记录
	 * @param $user_id
	 * @param $start
	 * @param $size
	 * @return array
	 */
	public function lists_by_group($user_id,$start,$size) {
		$result = array();
		$last_backup_history = $this->get_last_backup_history($user_id);
		$contacts = $this->_get_contact_lsits($user_id);
		if($last_backup_history['id']) {
			$table = $this->get_table($user_id,'call');
			$sql = "SELECT *,COUNT(*) AS total From (SELECT * FROM {$table} ORDER BY date DESC) t WHERE uid={$user_id} AND batch_number={$last_backup_history['batch_number']} AND status=1 GROUP BY number ORDER BY date DESC LIMIT {$start},{$size}";
			$query = $this->db->query($sql);
			$result = $query->result_array(FALSE);
			if($query->count() > 0) {
				foreach($result as $key => $var) {
					$call[$key]['id'] = $var['id'];
					$call[$key]['address'] = $var['number'];
					$call[$key]['name'] = $this->_match_address($contacts,$var['number']);
					$call[$key]['total'] = $var['total'];
					$call[$key]['location'] = $this->_get_tel_location($var['number']);
					$call[$key]['duration'] = $var['duration'];
					$call[$key]['type'] = $var['type'];
					$call[$key]['dateline'] = date('Y-m-d H:i',$var['date']);
				}
			}
			$total_group = $this->_get_group_total($user_id,$last_backup_history['batch_number']);
			$result = array('total'=>$total_group,'size'=>$size,'page'=>(int)($start/$size+1),'batch_number'=>$last_backup_history['batch_number'],'data'=>$call);
		}
		return $result;
	}
	
	/**
	 * 根据联系人获取通话记录
	 * @param $user_id
	 * @param $address
	 * @param $start
	 * @param $size
	 * @return array
	 */
	public function lists_by_contact($user_id,$address,$start,$size) {
		$result = array();
		$last_backup_history = $this->get_last_backup_history($user_id);
		$contacts = $this->_get_contact_lsits($user_id);
		if($last_backup_history['id']) {
			$table = $this->get_table($user_id,'call');
			$sql = "SELECT `id`,`date`, `duration`, `type`,`number` FROM {$table} WHERE number='{$address}' AND uid={$user_id} AND batch_number={$last_backup_history['batch_number']} AND status=1 ORDER BY `date` DESC LIMIT {$start},{$size}";
			$query = $this->db->query($sql);
			$result = $query->result_array(FALSE);
			if($query->count() > 0) {
				foreach($result as $key => $var) {
					$call[$key]['id'] = $var['id'];
					$call[$key]['address'] = $var['number'];
					$call[$key]['type'] = $var['type'];
					$call[$key]['location'] = $this->_get_tel_location($var['number']);
					$call[$key]['duration'] = $var['duration'];
					$call[$key]['name'] = ($var['type']==2 || $var['type']==4)?($this->_match_address($contacts,$var['number'])):'我';
					$call[$key]['dateline'] = date('Y-m-d H:i',$var['date']);
				}
			}
			$total_contact = $this->_get_contact_total($user_id,$last_backup_history['batch_number'],$address);
			$result = array('total'=>$total_contact,'size'=>$size,'page'=>(int)($start/$size+1),'batch_number'=>$last_backup_history['batch_number'],'data'=>$call);
		}
		return $result;
	}

	
	/**
	 * 批量删除通话记录
	 * @param $user_id
	 * @param $batch_number
	 * @param $ids
	 * @return 
	 */
	public function delete_batch($user_id,$batch_number,$ids=array(),$address='') {
		$table = $this->get_table($user_id,'call');
		$batch_number = $this->db->escape($batch_number);
		$address_formated = $this->_format_address($address);
		if(!empty($ids) && empty($address)) {
			$sqls[] = "UPDATE {$table} SET status=2 WHERE uid={$user_id} AND batch_number={$batch_number} AND id IN (".join(',',$ids).")";
			$total_sql = "SELECT COUNT(*) as total FROM {$table} WHERE uid={$user_id} AND batch_number={$batch_number} AND id IN (".join(',',$ids).")";
		} else {
			$sqls[] = "UPDATE {$table} SET status=2 WHERE uid={$user_id} AND batch_number={$batch_number} AND number IN (".join(',',$address_formated).")";			
			$total_sql = "SELECT COUNT(*) as total FROM {$table} WHERE uid={$user_id} AND batch_number={$batch_number} AND number IN (".join(',',$address_formated).")";		
		}
			
		$table = $this->get_table($user_id,'delete_history');
		if(!empty($ids) && empty($address)) {
			$sqls[] = "INSERT INTO {$table} (`uid`, `batch_number`, `ids`, `dateline`, `total`) VALUES ('{$user_id}',{$batch_number},'".join(',',$ids)."','".time()."','".$delete_count."')";
		} else {
			$address_formated = '"'.join(',',$address_formated).'"';
			$sqls[] = "INSERT INTO {$table} (`uid`, `batch_number`, `number`, `dateline`, `total`) VALUES ('{$user_id}',{$batch_number},".$address_formated.",'".time()."','".$delete_count."')";
		}
		//删除总数
		$query = $this->db->query($total_sql);
		$result = $query->result_array(FALSE);
		$delete_total = (int)$result[0]['total'];
		$table = $this->get_table($user_id,'backup_history');
		$sqls[] = "UPDATE {$table} SET backup_total_call=backup_total_call-{$delete_total} WHERE uid={$user_id} AND batch_number={$batch_number}";
			
		$this->db->begin();
		foreach ($sqls as $sql) {
			$query = $this->db->query($sql);
			if (! $query) {
				$this->db->rollback();
			}
		}
		if($this->db->commit()) {
			return $delete_total;	
		}
		return ;	
	}
	
	/**
	 * 通话记录恢复
	 * @param $user_id
	 * @param $device_id
	 * @param $batch_number
	 * @return 
	 */
	public function recover($user_id,$device_id,$batch_number) {
		$table = $this->get_table($user_id,'backup_history');
		$this->db->begin();
		$query = $this->db->update($table, array('dateline'=>time()),array('uid' => $user_id,'device_id'=>$device_id,'batch_number'=>$batch_number));
		if(!$query)
			$this->db->rollback();
		if($this->db->commit())
			return true;
		return false;
	}
	
	/**
	 * 
	 * @param $user_id
	 * @param $device_id
	 * @param $batch_number
	 * @return 
	 */
	public function get_backup_info($user_id,$device_id,$batch_number) {
		$table = $this->get_table($user_id,'backup_history');
		$query = $this->db->fetchData($table, '*',array('uid'=>$user_id,'device_id'=>$device_id,'batch_number'=>$batch_number),array('id'=>'DESC'));
		$result = $query->result_array(FALSE);
		if($query->count() > 0) {
			return $result[0];
		}
		return false;
	}
	
	/**
	 * 通话记录导出
	 * @param $user_id
	 * @param $batch_number
	 * @param $ids
	 * @param $address
	 * @return 
	 */
	public function export($user_id,$batch_number,$format,$ids=array(),$address=array(),$all=0) {
		$table = $this->get_table($user_id,'call');
		$contacts = $this->_get_contact_lsits($user_id);
		$content = '';
		if($all)
			$sql = "SELECT `id`,`date`, `duration`, `type`,`number` FROM {$table} WHERE uid={$user_id}  AND batch_number='{$batch_number}' AND status=1 ORDER BY `date` DESC";
		elseif(!empty($ids) && empty($address))
			$sql = "SELECT `id`,`date`, `duration`, `type`,`number` FROM {$table} WHERE uid={$user_id}  AND id in (".join(',',$ids).") AND status=1 ORDER BY `date` DESC";
		else
			$sql = "SELECT `id`,`date`, `duration`, `type`,`number` FROM {$table} WHERE uid={$user_id}  AND batch_number='{$batch_number}' AND number in (".join(',',$address).") AND status=1 ORDER BY `date` DESC";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		if($query->count() > 0) {
			if($format == 'txt') {
				$content = $this->_format_export_txt($result,$contacts);
			}else {
				$content = $this->_format_export_xls($result,$contacts);
			}
		}
		return $content;
	}

	private function _match_address($match_addrs,$addr) {
		foreach($match_addrs as $k => $v) {
			$k = trim($k,'+86');
			if(preg_match('/[+86]?('.$k.')/is',$addr)) {
				return $v;
			} 
		}
		return '';
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
	
	/**
	 * 格式化导出txt数据
	 * @param $data
	 * @param $contacts
	 * @return 
	 */
	private function _format_export_txt($data,$contacts) {
		$content = '';
		$call_type = array(1=>'打入',2=>'打出',3=>'打入未接',4=>'打出未接');
		foreach($data as $key => $var) {
			$type = ($var['type']==1||$var['type']==3)?'打入':'打出';
			$answer = ($var['type']==3||$var['type']==4)?'未接':'';
			$match_name = $this->_match_address($contacts,$var['number']);
			$name = $match_name?$match_name:$var['number'];
			$dateline = date('Y-m-d H:i',$var['date']);
			$datediff = date::diff($var['duration']);
			$content .= "类型:{$type}\r\n姓名: {$name}<{$var['number']}>;\r\n时间: {$dateline}\r\n时长: {$datediff}\r\n标志:{$answer}\r\n\r\n";
		}
		return $content;
	}

	
	/**
	 * 格式化导出xls数据
	 * @param $data
	 * @param $contacts
	 * @return 
	 */
	private function _format_export_xls($data,$contacts) {
		$out = array();		
		$call_type = array(1=>'打入',2=>'打出',3=>'打入未接',4=>'打出未接');
		foreach($data as $key => $var) {
			$type = $call_type[$var['type']];
			$location = $this->_get_tel_location($var['number'],1);
			$match_name = $this->_match_address($contacts,$var['number']);
			$name = $match_name?$match_name:$var['number'];
			$dateline = date('Y-m-d H:i',$var['date']);
			$datediff = date::diff($var['duration']);
			$out[] = array($dateline,$var['number'],$name,$location,$type,$datediff);
		}
		return $out;
	}

	/**
	 * 获取手机归属地
	 * @param $address
	 * @return unknown_type
	 */
	private function _get_tel_location($address,$type=0) {
		$key = $this->cache_pre .'_address_'.$address;
		$location = Cache::instance()->get($key);
		if(!$location) {
			$location = Location_Model::instance()->get_tel_location($address,$type);
			Cache::instance()->set($key, $location, NULL, 604800);
		}
		return $location;
	}
	

	/**
	 * 获取通话记录组总数
	 * @param $user_id
	 * @param $batch_number
	 * @return 
	 */
	private function _get_group_total($user_id,$batch_number) {
		$table = $this->get_table($user_id,'call');
		$sql = "SELECT COUNT(distinct number) AS total From {$table} WHERE uid={$user_id} AND batch_number={$batch_number} AND status=1";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		return (int)$result[0]['total'];	
	}

	
	/**
	 * 获取通话记录组总数
	 * @param $user_id
	 * @param $batch_number
	 * @return 
	 */
	private function _get_contact_total($user_id,$batch_number,$address) {
		$table = $this->get_table($user_id,'call');
		$sql = "SELECT COUNT(*) AS total From {$table} WHERE uid={$user_id} AND batch_number={$batch_number} AND number='{$address}' AND status=1";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		return (int)$result[0]['total'];	
	}

	/**
	 * 
	 * 获取最后一次备份
	 * @param $user_id
	 * @param $device_id
	 */
	private function get_last_backup_history($user_id,$device_id='',$batch_number='') {
		$table = $this->get_table($user_id,'backup_history');
		$condition = array('uid'=>$user_id);
		if($device_id)
			$condition['device_id'] = $device_id;
		if($batch_number)
			$condition['batch_number'] = $batch_number;
		$query = $this->db->fetchData($table, '*',$condition,array('dateline'=>'DESC'));
		$result = $query->result_array(FALSE);
		if($query->count() > 0) {
			return $result[0];
		}
		return ;
	}
	
	/**
	 * 获取联系人列表
	 * @param $user_id
	 * @return array
	 */
	private function _get_contact_lsits($user_id) {
		$res = array();
		$contact_lists = Contact_Model::instance()->get($user_id,null,'',1);
		if(count($contact_lists)>0 && is_array($contact_lists)) {
			foreach($contact_lists as $contact) {
				if(count($contact['tels'])>0 && is_array($contact['tels'])) {
					foreach($contact['tels'] as $tel) {
						if($tel['type'] == 'cell')
							$res[$tel['value']]=$contact['formatted_name'];
					}
				}
			}
		}
		return $res;
	}
	
    /**
     * 
     * 记录还原日志
     * @param string $from_device_id
     * @param string $to_device_id
     * @param string $to_phone_model
     */
    private function add_restore_history($user_id,$from_device_id,$from_batch_number,$to_device_id,$to_phone_model) {
    	$setters = array(
			'uid'=>$user_id,
			'from_device_id'=>$from_device_id,
			'from_batch_number'=>$from_batch_number,
			'to_device_id'=>$to_device_id,
			'to_phone_model'=>$to_phone_model,
			'dateline'=>time()
		);
		$table = $this->get_table($user_id,'restore_history');
		$this->db->begin();
		$query = $this->db->insert($table,$setters);
		if (! $query) {
			$this->db->rollback();
		}
		return $this->db->commit();
    }
	
    
	/**
	 * 
	 * 添加通话记录数据
	 * @param $call_data
	 */
	private function add($user_id,$device_id,$batch_number,$data_type,$call_data) {
		$table = $this->get_table($user_id,'call');
		$this->db->begin();
		$setters = array(
			'uid'=>$user_id,
			'device_id'=>$device_id,
			'batch_number'=>$batch_number,
			'data_type'=>$data_type,
			'call_id'=>$call_data['call_id'],
			'number'=>$call_data['number'],
			'date'=>$call_data['date'],
			'duration'=>$call_data['duration'],
			'type'=>$call_data['type'],
			'name'=>$call_data['name']
		);
		$query = $this->db->insert($table,$setters);
		if (! $query) {
			$this->db->rollback();
		}
		$id = $query->insert_id();
		if ($id) {
			if ($this->db->commit()) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * 
	 * 获取通话记录内容
	 * @param int $user_id
	 * @param string $device_id
	 * @param string $history_id
	 */
	private function update_batch_info($user_id,$device_id,$batch_number,$setters) {
		$table = $this->get_table($user_id,'backup_batch');
		$this->db->begin();
		$query = $this->db->update($table, $setters,array('uid' => $user_id,'device_id'=>$device_id,'batch_number'=>$batch_number,'status'=>0));
		if(!$query)
			$this->db->rollback();
		if($this->db->commit())
			return true;
		return false;
	}

	/**
	 * 
	 * 获取通话记录内容
	 * @param int $user_id
	 * @param string $device_id
	 * @param string $history_id
	 */
	private function get_call_id($user_id,$device_id,$batch_number) {
		$call = array();
		$table = $this->get_table($user_id,'call');
		$query = $this->db->fetchData($table, 'id',array('uid' => $user_id,'device_id'=>$device_id,'batch_number'=>$batch_number),array('id'=>'DESC'));
		$result = $query->result_array(FALSE);
		if($query->count() > 0) {
			return $result;
		}
		return false;
	}
	
	/**
	 * 
	 * 获取通话记录id
	 * @param int $user_id
	 * @param string $device_id
	 * @param string $history_id
	 */
	private function get_call_records_id($user_id,$device_id,$batch_number) {
		$call = array();
		$table = $this->get_table($user_id,'call');
		$sql = "SELECT `call_id` FROM {$table} WHERE uid='{$user_id}' AND device_id='{$device_id}' AND batch_number='{$batch_number}' ORDER BY id DESC";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		if($query->count() > 0) {
			foreach($result as $key => $var) {
				$call[] = $var['call_id'];
			}
		}
		return $call;
	}

	/**
	 * 
	 * 获取通话记录内容
	 * @param int $user_id
	 * @param string $device_id
	 * @param string $history_id
	 */
	private function get_batch($user_id,$device_id,$batch_number,$start=0,$size=50) {
		$call = array();
		$table = $this->get_table($user_id,'call');
		$sql = "SELECT `call_id`, `number`, `date`, `duration`, `type`, `name` FROM {$table} WHERE uid='{$user_id}' AND device_id='{$device_id}' AND batch_number='{$batch_number}' AND status=1 ORDER BY id DESC LIMIT $start,$size";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		if($query->count() > 0) {
			foreach($result as $key => $var) {
				$call[$key]['call_id'] = $var['call_id'];
				$call[$key]['number'] = $var['number'];
				$call[$key]['date'] = $var['date'];
				$call[$key]['duration'] = $var['duration'];
				$call[$key]['type'] = $var['type'];
				$call[$key]['name'] = $var['name'];
			}
		}
		return $call;
	}
	
	/**
	 * 
	 * 新增备份记录
	 * @param int $user_id
	 * @param string $device_id
	 * @param string $phone_model
	 */
	private function add_backup_history($user_id,$device_id,$phone_model,$batch_number,$total_call) {
		$table = $this->get_table($user_id,'backup_history');
		$this->db->begin();
		$query = $this->db->insert($table, array('uid'=>$user_id,'device_id'=>$device_id,'phone_model'=>$phone_model,'batch_number'=>$batch_number,'backup_total_call'=>$total_call,'dateline'=>time()));
		if(!$query)
			$this->db->rollback();
		if($this->db->commit())
			return true;
		return false;
	}
	
	/**
	 * 
	 * 格式化数据
	 * @param array $data
	 */
	private function _format_data($data) {
		$result_data = array();
		$result_data['call_id'] = $data['call_id']?$data['call_id']:0;
		$result_data['number'] = $data['number']?$data['number']:0;
		$result_data['date'] = $data['date']?$data['date']:0;
		$result_data['duration'] = $data['duration']?$data['duration']:0;
		$result_data['type'] = $data['type']?$data['type']:0;
		$result_data['name'] = $data['name']?$data['name']:'';
		return $result_data;
	}
}