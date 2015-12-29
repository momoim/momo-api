<?php
class Url_click_statis_Model extends Model {
	
	protected $_id = 0;
	protected $_url = '';
	protected $_table = 'url_click_statis';
	
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

	public function addUrl($url) {
		$data = array();
		$data['url'] = $url;
		$data['url_md5'] = md5($url);
		$data['click'] = 0;
		
		if (!($id = $this->exist($data['url_md5']))) {
			return $this->db->insertData($this->_table, $data);
		} else {
			return '已经存在'.$id;
		}
	}
	
	public function exist($md5) {
		$sql = <<<EOQ
		select id from $this->_table where url_md5 = '$md5'
EOQ;
		$query = $this->db->query($sql);

		$result = $query->result_array();
		if (is_array($result)) {
			$result = $result[0];
		}
		if ($result->id) {
			return $result->id;
		} else {
			return false;
		}
	}
	
	public function update($id) {
		$sql = <<<EOF
		update $this->_table set click = click+1 where id = '$id'
EOF;
		return $this->db->query($sql);
	}
}
	