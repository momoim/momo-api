<?php
class Classic_sms_Model extends ORM
{
	protected $table_name = 'classic_sms';
	
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
	
	public function getSms($lastMaxId, $count)
	{
		$remainder = $this->getRemaidSmsAmount($lastMaxId);
		
		if ($remainder < $count) {
			$rest1 = $this->where('id > ', $lastMaxId)->where('type =', 0)->limit($remainder, 0)->find_all();
			$rest2 = $this->where('id > ', 0)->where('type =', 0)->limit($count - $remainder ,0)->find_all();
			foreach ($rest1 as $row) {
				$retn[$row->id] = trim($row->content);
			}
			foreach ($rest2 as $row) {
				$retn[$row->id] = trim($row->content);
			}
			return ($retn);
		} else {
			$rest = $this->where('id > ', $lastMaxId)->where('type =', 0)->limit($count,0)->find_all();
			foreach ($rest as $row) {
				$retn[$row->id] = trim($row->content);
			}
			return ($retn);
		}
	}
	
	public function getRemaidSmsAmount($lastMaxId) {
		$smsAmount = $this->where('id > ', $lastMaxId)->count_all();
		return $smsAmount;
	}
	
	public function getSmsAmount() {
		$cache= Cache::instance();
		$smsAmount = $cache->get('sms_amount');
 
		if ( !$smsAmount) {
		    $smsAmount = $this->count_all();
		    $cache->set('sms_amount', $smsAmount, array('sms_amount'), 3600);
		} 
		return $smsAmount;
	}
}