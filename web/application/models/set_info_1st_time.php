<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * 设置第一次填写个人信息
 */
class Set_info_1st_time_Model extends Model 
{
	const BIRTHDAY = 1;
	const SEX = 2;
	
	protected $_uid;
	protected $_field;
	protected $_support;	// 支持送短信的信息字段
	protected $_fieldMap;	// 个人信息字段与ID的映射
	protected $_isset;
	protected $_table = 'set_info_1st_time';
	
	static $_instance;
	
	public function __clone()
	{
		;
	}
	
	public static function getInstance()
	{
		if (!(self::$_instance instanceof self)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	public function __construct()
	{
		parent::__construct();
		$this->_support[self::BIRTHDAY] = 20;	// 生日短信，使用数字索引，加快查询速度
		$this->_support[self::SEX] = 10;	// 性别短信
	}
	
	/**
	 * 初始化字段信息
	 * @param int $uid 64位用户ID
	 * @param string $field 用户信息字段
	 */
	public function initInfo($uid, $field)
	{
		$this->_uid = $uid;
		$this->_field = $field;
	}
	
	public function setUid($uid)
	{
		$this->_uid = $uid;
	}
	
	public function setFirst()
	{
		// 获取要赠送的短信的条数
		$sms_count = 0;
		$fields = array();
		
		// 如果还没设置过信息，则进行设置，并送短信
		foreach ($this->_support as $field => $smsCount) {
			$this->_field = $field;
			if (!$this->hasSet() && $this->_support[$this->_field] > 0) {
				$sms_count += $smsCount;
				$fields[] = $field;
			}
		}
		
		if ($sms_count > 0) {
			// 赠送短信，并发送通知
			$user = User_Model::instance();
			$smsUpdateStr = $this->smsUpdateStr();
			$content = '您好，这是您第一次设置'.$smsUpdateStr.'，系统赠送了'.$sms_count.'条短信给您';
			// echo $content;
			try {
				// @todo 应该支持事务操作
				$updated = $this->updateField($fields);
				$user->present_sms($this->_uid, $sms_count, $content, false);
			} catch (Exception $e) {
				
			}
		}
		return $sms_count;
	}
	
	/**
	 * 查询是否设置了信息字段
	 * 
	 */
	public function hasSet()
	{
		$cache = Cache::instance();
		$cacheKey = CACHE_PRE . 'set_info_1st_time_id_' . $this->_field . '_' . $this->getUid();
		// echo $cacheKey;exit;
		$cacheVal = $cache->get($cacheKey);
		if ($cacheVal) {
			return true;
		}
		
		$query = $this->db->from($this->_table)
			->where(array('uid ='=>$this->_uid, 'field ='=>$this->_field))
			->get();
			
		$result = $query->result_array(FALSE);

		// 如果送短信了，则不设置缓存key，以便下次能送短信
		if ($this->_support[$this->_field] > 0)
			$cache->set($cacheKey, 1);
		
		if (!$result) {
			// 没有任何记录
			$data = array();
			$data['uid'] = $this->_uid;
			$data['field'] = $this->_field;
			$data['isset'] = 0;
			// echo Kohana::debug($result);exit;
			$this->db->insert($this->_table, $data);
			return false;
		} else {
			// 判断是否存在isset字段，是则更新，否则插入
			// echo Kohana::debug($result[0]['isset']);exit;
			if (empty($result[0]['isset'])) {
				return false;
			} else {
				
				return true;
			}
		}
	}
	
	public function setBefore($sex, $birthday) 
	{
		// 设置过性别，并且非空值，不送
		if (isset($sex) && !empty($sex)) {
			$this->_support[self::SEX] = 0;
		}
		
		if (!empty($birthday)) {
			$this->_support[self::BIRTHDAY] = 0;;
		}
		 // echo Kohana::debug($this->_support);exit;
	}
	
	public function smsUpdateStr() {
		// 字段说明
		$fieldNames = array(self::SEX=>'性别', self::BIRTHDAY=>'生日');
		
		$str = '';
		
		$fieldMap = array(self::SEX=>1, self::BIRTHDAY=>1);
		
		$givenCount = 0;
		
		foreach ($this->_support as $key => $value) {
			if ($this->_support[$key] > 0) {
				$givenCount++;
			}
			$fieldMap[$key] = $value;
		}
		
		$count = count($fieldMap);
		
		$i = 0;
		
		if ($givenCount > 1) {
			foreach ($fieldMap as $key => $value) {
				if ($value && $i < $count - 1) {
					$str = $fieldNames[$key] . '、' . $str;
				} elseif ($value) {
					$str .= $fieldNames[$key];
				}
				$i++;
			}
		} else {
			foreach ($fieldMap as $key => $value) {
				if ($value && $i < $count - 1) {
					$str = $fieldNames[$key];
				}
			}
		}
		
		return $str;
	}
	
	public function updateField($fields)
	{
		$data = $where = array();
		$where['uid ='] = $this->_uid;
		$data['isset'] = 1;
		$this->db->in('field', $fields)->update($this->_table, $data, $where);
		return 1;
	}
	
	public function check($sex, $birthday) 
	{
		if (!isset($sex) || empty($sex)) {
			$this->_support[self::SEX] = 0;
		}
		if (empty($birthday)) {
			$this->_support[self::BIRTHDAY] = 0;;
		}
	}
}