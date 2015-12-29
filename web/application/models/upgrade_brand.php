<?php
class Upgrade_brand_Model extends ORM {
	
	protected $table_name = 'upgrade_brand';
	
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
	
	public function saveCustomUpgrade($mobile_list, $upgradeId)
	{
		$mobile_list = explode(',', $mobile_list);
		foreach ($mobile_list as $mobileId) {
			$data['brand_id'] = $mobileId;
			$data['upgrade_id'] = $upgradeId;
			$this->db->insertData($this->table_name, $data);
		}
	}
	
	public function getDlId($phoneModel)
	{
		$result = $this->where('brand_id', $phoneModel)->orderby('id', 'desc')->limit(3)->find_all();
		$dlIdArr = array();
		foreach ($result as $row) {
			$dlIdArr[] = $row->upgrade_id;
		}
		$dlIds = implode(',', $dlIdArr);
		return $dlIds;
	}
}