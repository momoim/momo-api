<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * 短信model
 */
class Sms_Model extends Model 
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
	 */
	protected static $instance;

	/**
	 * 单例模式
	 * @return Contact_Model
	 */
	public static function &instance() {
		if (! isset(self::$instance)) {
			self::$instance = new Sms_Model();
		}
		return self::$instance;
	}
	
	public function __construct() {
		$mg_instance = new Mongo(Kohana::config('uap.mongodb'));
        $this->mongo = $mg_instance->selectDB(MONGO_DB_FEED);
        $this->mongo_sms = $this->mongo->selectCollection ('sms');
	}
	
	/**
	 * 
	 * 批量添加短信
	 */
	public function add_batch($user_id,$data) {
		$success_count = 0;
		$error_count = 0;
		foreach($data['data'] as $key => $var) {
			$sms_data = $this->_format_data($var);
			$result = $this->add($history_id,$user_id,$sms_data,$data['device_id'],$data['batch_number']);
			if($result)
				$success_count++;
			else 
				$error_count++;
		}
		return array('success'=>$success_count,'fail'=>$error_count,'batch_total_sms'=>$this->get_sms_count($user_id,$data['device_id'],$data['batch_number']));
	}
	
	/**
	 * 
	 * 批量获取短信
	 * @param int $user_id
	 * @param string $device_id
	 */
	public function get_sms_batch($user_id,$device_id,$batch_number,$start,$size) {
		$result = array();
		$last_backup_history = $this->get_last_backup_history($user_id,$device_id,$batch_number);
		if($last_backup_history['id']) {
			$sms_data = $this->get_batch($user_id,$device_id,$batch_number,$start,$size);
			return array('device_id'=>$device_id,'phone_model'=>$last_backup_history['phone_model'],'device_alias'=>Brand_Model::instance()->get_by_model($last_backup_history['phone_model']),'batch_number'=>$last_backup_history['batch_number'],'dateline'=>$last_backup_history['dateline'],'backup_total_sms'=>$last_backup_history['backup_total_sms'],'data'=>$sms_data);
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
    	$query = $this->db->fetchData($table, 'batch_number,uid,device_id,phone_model,backup_total_sms,created,completed,status',$setters,array('id'=>'DESC'));
		$result = $query->result_array(FALSE);
		if($query->count() > 0) {
			foreach($result as $key => $var) {
				$history[$key] = $var;
				//if($var['status']==2) {
				//	$history[$key]['batch_total_sms'] = 0;
				//	$history[$key]['message_id'] = array();
				//} else {
				$history[$key]['device_alias'] = Brand_Model::instance()->get_by_model($var['phone_model']);
					$sms = $this->get_message_id($user_id,$var['device_id'],$var['batch_number']);
					$history[$key]['message_id'] = $sms;
					$history[$key]['batch_total_sms'] = count($sms);
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
		$sql = "SELECT device_id,batch_number,phone_model,backup_total_sms,dateline FROM {$table} WHERE uid='{$user_id}' ";
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
		return array_values($history);
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
		$sql = sprintf('SELECT device_id, batch_number, phone_model, backup_total_sms, dateline
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
	 * @param string $total_sms
	 */
	public function apply_batch_number($user_id,$device_id,$phone_model,$total_sms,$appid,$client_id) {
		$setters = array(
			'uid'=>$user_id,
			'device_id'=>$device_id,
			'status'=>0,
			'backup_total_sms'=>$total_sms
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
	public function get_sms_count($user_id,$device_id,$batch_number) {
		$setters = array(
			'uid'=>$user_id,
			'device_id'=>$device_id,
			'batch_number'=>$batch_number
		);
		$table = $this->get_table($user_id,'sms');
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
	public function backup_done($user_id,$device_id,$batch_number,$phone_model,$total_sms,$appid,$client_id) {
		$table = $this->get_table($user_id,'backup_batch');
		$sqls[] = "UPDATE {$table} SET status=1,completed='".time()."' WHERE uid='{$user_id}' AND device_id='{$device_id}' AND batch_number='{$batch_number}'";
		
		$table = $this->get_table($user_id,'backup_history');
		$sqls[] = "INSERT INTO {$table} (`batch_number`, `appid`, `client_id`,`uid`, `device_id`, `phone_model`, `backup_total_sms`, `dateline`) VALUES ('".$batch_number."','".$appid."','".$client_id."','".$user_id."','".$device_id."','".$phone_model."','".$total_sms."','".time()."')";

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
		$sms_total = $this->get_sms_count($user_id,$device_id,$batch_number);
		if($truncate==false) {
			$table = $this->get_table($user_id,'backup_batch');
			$sqls[] = "UPDATE {$table} SET status=2 WHERE uid='{$user_id}' AND device_id='{$device_id}' AND batch_number='{$batch_number}'";
		}
		$table = $this->get_table($user_id,'sms');
		$sqls[] = "DELETE FROM {$table} WHERE uid='{$user_id}' AND device_id='{$device_id}'  AND batch_number='{$batch_number}'";
		$this->db->begin();
		foreach ($sqls as $sql) {
			$query = $this->db->query($sql);
			if (! $query) {
				$this->db->rollback();
			}
		}
		if($this->db->commit()) {
			//if(is_array($sms_data) && count($sms_data) > 0) {
			//	foreach ($sms_data as $v) 
			//		$this->del_sms($v['sms_id']);
			//}
			return $sms_total;
		}
		return false;
	}
	
	public function dump_sms($db) {
		set_time_limit(10000);
		$device_db = 'sms_'.$db;
		if (! isset($this->db_instances[$device_db])) {
			$this->db_instances[$device_db] = Database::instance($device_db);
		}
		$this->db = $this->db_instances[$device_db];
		$j = 0;
		for($i=0;$i<800;$i++) {
			if($i%8==$db) {
				$table = 'sms_'.$db.'_'.$i;
				$query = $this->db->fetchData($table, '*',array(),array('id'=>'DESC'));
				$result = $query->result_array(FALSE);
				if($query->count() > 0) {
					foreach($result as $v) {
						if($v['sms_id'] && empty($v['text'])) {
							$sms = $this->get_sms($v['sms_id']);
							if($sms) {
								$j++;
								$setters = array('read'=>(int)$sms['read'],'draft'=>(int)$sms['draft'],'served'=>(int)$sms['served'],'text'=>trim($sms['text']));
								$query = $this->db->update($table, $setters,array('id' => $v['id']));
							}
						}
					}
				}
				continue;
			}
		}
		return $j;
	}
	
	/**
	 * 
	 * 获取分表表名
	 * @param int $user_id
	 * @param string $table
	 */
	public function get_table($user_id, $table) {
		static $tables = array();
		$device_db_id = $user_id % Kohana::config('sms.divide_db');
		$device_db = 'sms_'.$device_db_id;
		$this->db = isset($this->db_instances[$device_db]) ? $this->db_instances[$device_db] : NULL;
		$key = md5($user_id . '|' . $table);
		if (empty($tables[$key]) || !$this->db) {
			if (! isset($this->db_instances[$device_db])) {
				$this->db_instances[$device_db] = Database::instance($device_db);
			}
			$this->db = $this->db_instances[$device_db];
			$table = $table.'_'.$device_db_id.'_'.($user_id % Kohana::config('sms.divide_table.'.$table));
			$tables[$key] = $table;	
		}
		return $tables[$key];
	}
	
	/**
	 * 根据组获取短信
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
			$table = $this->get_table($user_id,'sms');
			$sql = "SELECT *,COUNT(*) AS total From (SELECT * FROM {$table} ORDER BY dateline DESC) t WHERE uid={$user_id} AND batch_number={$last_backup_history['batch_number']} AND status=1 GROUP BY address ORDER BY dateline DESC LIMIT {$start},{$size}";
			$query = $this->db->query($sql);
			$result = $query->result_array(FALSE);
			if($query->count() > 0) {
				foreach($result as $key => $var) {
					$sms[$key]['id'] = $var['id'];
					$sms[$key]['address'] = $var['address'];
					$sms[$key]['name'] = $this->_match_address($contacts,$var['address']);
					$sms[$key]['total'] = $var['total'];
					$sms[$key]['dateline'] = date('Y-m-d H:i',$var['dateline']);
					$sms[$key]['sms'] = array('address'=>$var['address'],'read'=>$var['read'],'inbox'=>$var['inbox'],'draft'=>$var['draft'],'text'=>$var['text'],'attach'=>'','served'=>$var['served']);
				}
			}
			$total_group = $this->_get_group_total($user_id,$last_backup_history['batch_number']);
			$result = array('total'=>$total_group,'size'=>$size,'page'=>(int)($start/$size+1),'batch_number'=>$last_backup_history['batch_number'],'data'=>$sms);
		}
		return $result;
	}
	
	/**
	 * 根据联系人获取短信
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
		$sms = array();
		if($last_backup_history['id']) {
			$table = $this->get_table($user_id,'sms');
			$sql = "SELECT `id`,`inbox`, `message_id`, `address`, `read`, `draft`, `served`,`dateline`, `text` FROM {$table} WHERE address='{$address}' AND uid={$user_id} AND batch_number={$last_backup_history['batch_number']}  AND status=1 ORDER BY `dateline` DESC LIMIT {$start},{$size}";
			$query = $this->db->query($sql);
			$result = $query->result_array(FALSE);
			if($query->count() > 0) {
				foreach($result as $key => $var) {
					$sms[$key]['id'] = $var['id'];
					$sms[$key]['address'] = $var['address'];
					$sms[$key]['name'] = $var['inbox']==1?($this->_match_address($contacts,$var['address'])):'我';
					$sms[$key]['dateline'] = date('Y-m-d H:i',$var['dateline']);
					$sms[$key]['sms'] = array('address'=>$var['address'],'read'=>$var['read'],'inbox'=>$var['inbox'],'draft'=>$var['draft'],'text'=>$var['text'],'attach'=>'','served'=>$var['served']);
				}
			}
			$total_contact = $this->_get_contact_total($user_id,$last_backup_history['batch_number'],$address);
			$result = array('total'=>$total_contact,'size'=>$size,'page'=>(int)($start/$size+1),'batch_number'=>$last_backup_history['batch_number'],'data'=>$sms);
		}
		return $result;
	}
	
	/**
	 * 批量删除短信
	 * @param $user_id
	 * @param $batch_number
	 * @param $ids
	 * @return 
	 */
	public function delete_batch($user_id,$batch_number,$ids=array(),$address='') {
		$table = $this->get_table($user_id,'sms');
		$batch_number = $this->db->escape($batch_number);
		$address_formated = $this->_format_address($address);
		if(!empty($ids) && empty($address)) {
			$sqls[] = "UPDATE {$table} SET status=2 WHERE uid={$user_id} AND batch_number={$batch_number} AND id IN (".join(',',$ids).")";
			$total_sql = "SELECT COUNT(*) as total FROM {$table} WHERE uid={$user_id} AND batch_number={$batch_number} AND id IN (".join(',',$ids).")";
		} else {
			$sqls[] = "UPDATE {$table} SET status=2 WHERE uid={$user_id} AND batch_number={$batch_number} AND address IN (".join(',',$address_formated).")";
			$total_sql = "SELECT COUNT(*) as total FROM {$table} WHERE uid={$user_id} AND batch_number={$batch_number} AND address IN (".join(',',$address_formated).")";		
		}
			
		$table = $this->get_table($user_id,'delete_history');
		if(!empty($ids) && empty($address)) {
			$sqls[] = "INSERT INTO {$table} (`uid`, `batch_number`, `ids`, `dateline`, `total`) VALUES ('{$user_id}',{$batch_number},'".join(',',$ids)."','".time()."','".$delete_count."')";
		}else{
			$address_formated = '"'.join(',',$address_formated).'"';
			$sqls[] = "INSERT INTO {$table} (`uid`, `batch_number`, `address`, `dateline`, `total`) VALUES ('{$user_id}',{$batch_number},".$address_formated.",'".time()."','".$delete_count."')";
		}
		//删除总数
		$query = $this->db->query($total_sql);
		$result = $query->result_array(FALSE);
		$delete_total = (int)$result[0]['total'];
		$table = $this->get_table($user_id,'backup_history');
		$sqls[] = "UPDATE {$table} SET backup_total_sms=backup_total_sms-{$delete_total} WHERE uid={$user_id} AND batch_number={$batch_number}";
			
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
	 * 
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
	 * 短信导出
	 * @param $user_id
	 * @param $batch_number
	 * @param $ids
	 * @param $address
	 * @return 
	 */
	public function export($user_id,$batch_number,$format,$ids=array(),$address=array(),$all=0) {
		$table = $this->get_table($user_id,'sms');
		$contacts = $this->_get_contact_lsits($user_id);
		$content = '';
		if($all) 
			$sql = "SELECT `id`,`inbox`, `message_id`, `address`, `read`, `draft`, `served`,`dateline`, `text` FROM {$table} WHERE uid={$user_id}  AND batch_number='{$batch_number}' AND status=1 ORDER BY `dateline` DESC";
		elseif(!empty($ids) && empty($address))
			$sql = "SELECT `id`,`inbox`, `message_id`, `address`, `read`, `draft`, `served`,`dateline`, `text` FROM {$table} WHERE uid={$user_id}  AND id in (".join(',',$ids).") AND status=1 ORDER BY `dateline` DESC";
		else
			$sql = "SELECT `id`,`inbox`, `message_id`, `address`, `read`, `draft`, `served`,`dateline`, `text` FROM {$table} WHERE uid={$user_id}  AND batch_number='{$batch_number}' AND address in (".join(',',$address).") AND status=1 ORDER BY `dateline` DESC";
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
		foreach($data as $key => $var) {
			$type = $var['inbox']==1?'收件':'发件';
			$match_name = $this->_match_address($contacts,$var['address']);
			$name = $match_name?$match_name:$var['address'];
			$read = $var['read']==1?'':'未读';
			$dateline = date('Y-m-d H:i',$var['dateline']);
			$content .= "类型:{$type}\r\n姓名: {$name}<{$var['address']}>;\r\n时间: {$dateline}\r\n短信内容: {$var['text']}\r\n标志:{$read}\r\n\r\n";
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
		foreach($data as $key => $var) {
			$type = $var['inbox']==1?'收件':'发件';
			$match_name = $this->_match_address($contacts,$var['address']);
			$name = $match_name?$match_name:$var['address'];
			$read = $var['read']==1?'':'未读';
			$dateline = date('Y-m-d H:i',$var['dateline']);
			$out[] = array($type,$name,$var['address'],$dateline,$var['text']);
		}
		return $out;
	}
	
	/**
	 * 获取短信组总数
	 * @param $user_id
	 * @param $batch_number
	 * @return 
	 */
	private function _get_group_total($user_id,$batch_number) {
		$table = $this->get_table($user_id,'sms');
		$sql = "SELECT COUNT(distinct address) AS total From {$table} WHERE uid={$user_id} AND batch_number={$batch_number} AND status=1";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		return (int)$result[0]['total'];	
	}

	
	/**
	 * 获取短信组总数
	 * @param $user_id
	 * @param $batch_number
	 * @return 
	 */
	private function _get_contact_total($user_id,$batch_number,$address) {
		$table = $this->get_table($user_id,'sms');
		$sql = "SELECT COUNT(*) AS total From {$table} WHERE uid={$user_id} AND batch_number={$batch_number} AND address='{$address}' AND status=1 ";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		return (int)$result[0]['total'];	
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
	 * 添加短信数据
	 * @param $sms_data
	 */
	private function add($history_id,$user_id,$sms_data,$device_id,$batch_number) {
		$table = $this->get_table($user_id,'sms');
		$this->db->begin();
		//$sms_id = api::uuid();
		$setters = array(
			'batch_number'=>$batch_number,
			//'sms_id'=>$sms_id,
			'inbox'=>(int)$sms_data['sms']['inbox'],
			'read'=>(int)$sms_data['sms']['read'],
			'draft'=>(int)$sms_data['sms']['draft'],
			'served'=>(int)$sms_data['sms']['served'],
			'text'=>$sms_data['sms']['text'],
			'address'=>trim($sms_data['sms']['address']),
			'message_id'=>$sms_data['message_id'],
			'group_id'=>$sms_data['group_id'],
			'data_type'=>$sms_data['data_type'],
			'uid'=>$user_id,
			'device_id'=>$device_id,
			'dateline'=>$sms_data['dateline']
		);
		$query = $this->db->insert($table,$setters);
		if (! $query) {
			$this->db->rollback();
		}
		$id = $query->insert_id();
		if ($id) {
			if ($this->db->commit()) {
				//$sms_data['sms']['sms_id'] = $sms_id;
				//$sms_data['sms']['mix_id'] = $user_id;
				//$this->put_sms($sms_data['sms']);
				return true;
			}
		}
		return false;
	}
	
	/**
	 * 
	 * 获取短信内容
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
	 * 获取短信内容
	 * @param int $user_id
	 * @param string $device_id
	 * @param string $history_id
	 */
	private function get_batch($user_id,$device_id,$batch_number,$start=0,$size=50) {
		$sms = array();
		$table = $this->get_table($user_id,'sms');
		$sql = "SELECT `id`, `batch_number`,`address`, `sms_id`,`inbox`, `message_id`, `group_id`,`read`, `draft`, `served`, `text`, `data_type`, `uid`, `device_id`, `dateline` FROM {$table} WHERE uid='{$user_id}' AND device_id='{$device_id}' AND batch_number='{$batch_number}' AND status=1  ORDER BY id DESC LIMIT $start,$size";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		if($query->count() > 0) {
			foreach($result as $key => $var) {
				$sms[$key]['id'] = $var['id'];
				$sms[$key]['message_id'] = $var['message_id'];
				$sms[$key]['group_id'] = $var['group_id'];
				$sms[$key]['data_type'] = $var['data_type'];
				$sms[$key]['dateline'] = $var['dateline'];
				//$sms[$key]['sms'] = $this->get_sms($var['sms_id']);
				//if(!empty($var['text'])) {
					$sms[$key]['sms'] = array('address'=>$var['address']?$var['address']:'','read'=>(int)$var['read'],'inbox'=>(int)$var['inbox'],'draft'=>(int)$var['draft'],'text'=>$var['text']?$var['text']:'','attach'=>'','served'=>(int)$var['served']);
				//} else {
				//	$sms[$key]['sms'] = $this->get_sms($var['sms_id']);
				//}
			}
		}
		return $sms;
	}

	/**
	 * 
	 * 获取短信消息id
	 * @param int $user_id
	 * @param string $device_id
	 * @param string $history_id
	 */
	private function get_message_id($user_id,$device_id,$batch_number) {
		$sms = array();
		$table = $this->get_table($user_id,'sms');
		$sql = "SELECT `message_id` FROM {$table} WHERE uid='{$user_id}' AND device_id='{$device_id}' AND batch_number='{$batch_number}' ORDER BY id DESC";
		$query = $this->db->query($sql);
		$result = $query->result_array(FALSE);
		if($query->count() > 0) {
			foreach($result as $key => $var) {
				$sms[] = $var['message_id'];
			}
		}
		return $sms;
	}
	
	/**
	 * 
	 * 将短信内容存储到mongo
	 * @param array $sms
	 */
	private function put_sms($sms){
        $res=$this->mongo_sms->insert($sms,array('safe'=>TRUE));
        if($res['ok']) 
        	return (string) $message['_id'];
        return null;
    }
	
	/**
	 * 
	 * 从mongo中获取短信内容
	 * @param array $sms
	 */
	private function get_sms($sms_id){
       $cols=$this->mongo_sms->findOne(array('sms_id'=>$sms_id));
       unset($cols['_id']);
       unset($cols['sms_id']);
       return $cols;
    }
	
	/**
	 * 
	 * 从mongo中删除短信内容
	 * @param array $sms
	 */
	private function del_sms($sms_id){
       $cols=$this->mongo_sms->remove(array('sms_id'=>$sms_id));
       return $cols;
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
		return null;
	}
	
	/**
	 * 
	 * 新增备份记录
	 * @param int $user_id
	 * @param string $device_id
	 * @param string $phone_model
	 */
	private function add_backup_history($user_id,$device_id,$phone_model,$batch_number,$total_sms) {
		$table = $this->get_table($user_id,'backup_history');
		$this->db->begin();
		$query = $this->db->insert($table, array('uid'=>$user_id,'device_id'=>$device_id,'phone_model'=>$phone_model,'batch_number'=>$batch_number,'backup_total_sms'=>$total_sms,'dateline'=>time()));
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
		$result_data['message_id'] = $data['message_id']?trim($data['message_id']):'';
		$result_data['group_id'] = $data['group_id']?trim($data['group_id']):'';
		$result_data['data_type'] = $data['data_type']?trim($data['data_type']):'common';
		$result_data['dateline'] = $data['dateline']?(float)$data['dateline']:0;
		$result_data['sms']['address'] = isset($data['sms']['address'])?$data['sms']['address']:'';
		$result_data['sms']['read'] = isset($data['sms']['read'])?(int)$data['sms']['read']:0;
		$result_data['sms']['inbox'] = isset($data['sms']['inbox'])?(int)$data['sms']['inbox']:1;
		$result_data['sms']['draft'] = isset($data['sms']['draft'])?(int)$data['sms']['draft']:0;
		$result_data['sms']['text'] = $data['sms']['text']?$data['sms']['text']:'';
		$result_data['sms']['attach'] = $data['sms']['attach']?$data['sms']['attach']:'';
		$result_data['sms']['served'] = isset($data['sms']['served'])?(int)$data['sms']['served']:1;
		return $result_data;
	}
}