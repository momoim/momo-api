<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * 模型
 *
 */

class Feedback_Model extends Model {

    public function __construct() {
        parent::__construct();
		//$this->uid	= $this->getUid();
    }

    //保存记录
    public function saveData($array) {
    	
        return $this->db->insertData('feedback', $array);
    }
}

?>