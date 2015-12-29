<?php defined('SYSPATH') or die('No direct script access.');
//赞模型

class Platform_Model extends Model {
	private $msg = '';
    public static $instances = null;

    public function __construct() {
        parent::__construct();
    }
    
    public static function &instance()
    {
        if (!is_object(Platform_Model::$instances)) {
            // Create a new instance
            Platform_Model::$instances = new Platform_Model();
        }
        return Platform_Model::$instances;
    }

    public function get_msg()
    {
        if ($this->msg) {return $this->msg;}
        return null;
    }
    
	public function check_app_valid($app_id,$app_key) {
		$query = $this->db->where(array('id'=>$app_id))->get('oauth_app', 1, 0);
		if($res = $query->result_array(FALSE)){
			if($res[0]['app_key']==$app_key) {
				return true;
			}
			$this->msg = '40016:app_key无效';
		} else {
			$this->msg = '40012:app_id无效';
		}
		return false;
	}
	
}
