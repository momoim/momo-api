<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * 取得手机品牌模块
 * 
 * @package None
 * @author Yufeng <ivwsai@gmail.com>
 * @copyright (c) 2010-2011 MOMO Team
 */

class Brand_Model extends Model {

    public static $instances = null;

	/**
	 * 缓存
	 * @var Cache
	 */
	protected $cache;

	/**
	 * 缓存前缀
	 * @var string
	 */
	protected $cache_pre;

    public function __construct($sid = null)
    {
        parent::__construct();

	    $this->cache_pre = CACHE_PRE . 'brand_';
	    $this->cache = Cache::instance();
    }
    
    /**
     * 
     * @return Brand_Model
     */
    public static function & instance()
    {
        if ( !is_object(Brand_Model::$instances) ){
            // Create a new instance
            Brand_Model::$instances = new Brand_Model;
        }
        
        return Brand_Model::$instances;
    }
    
    /**
     * 
     * @param int $platform 平台ＩＤ//1:android，2:iphone，3:windows mobile，4:s60v3，5:s60v5，6:java, 7:webos，8:blackberry, 9:ipad，10:web客户端，11:web客户端触屏版
     * @return array
     */
    public function brandlist($platform = null)
    {
        if (is_numeric($platform)) {
            $query = $this->db->query("SELECT `id`, `brand`, `brand_en`, `ishot` FROM `phone_brand` WHERE FIND_IN_SET('$platform', `platform`) ORDER BY `rank` DESC");
        } else {
            $query = $this->db->select(array("id", "brand", "brand_en", "ishot"))->from("phone_brand")->orderby(array("rank"=>"DESC"))->get();
        }
        
        return $query->result_array(FALSE);
    }
    
    /**
     * 
     * @param int $id 品牌ID
     * @return array
     */
    public function marque_list($id, Array $filter = null)
    {
        $where = array("brand_id"=>$id);
        
        if ($filter) {
            $where = array_merge($where, $filter);
        }
        
        $query = $this->db->select(array("id","os","marque","dpi","ver"))->from("phone_marque")->where($where)->orderby(array("rank"=>"DESC"))->get();
        
        return $query->result_array(FALSE);
    }
    
    /**
     * 
     * @param int $id 品牌ID
     * @return array
     */
    public function brand($id)
    {
        $query = $this->db->from("phone_brand")->where(array("id"=>$id))->get();
        
        $result = $query->result_array(FALSE);
        
        if (isset($result[0])) {
            return $result[0];
        } else {
            return FALSE;
        }
    }

	/**
	 * 更新手机名
	 * @param $data
	 * @return bool
	 */
	public function update($data)
	{
		$values = array();
		foreach($data as $val) {
			$values[] = '('.$this->db->escape($val['model']).', '.$this->db->escape($val['name']).')';
			$this->cache->set($this->cache_pre.$val['model'], $val['name'], NULL, 0);
		}
		$query = $this->db->query('REPLACE INTO phone_model (model, name) VALUES '. implode(',', $values));
		if($query) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * 根据手机型号获取手机名
	 * @param $model
	 * @return string
	 */
	public function get_by_model($model)
	{
		static $name = array();
		if(!isset($name[$model])) {
			$result = $this->cache->get($this->cache_pre.$model);
			if($result) {
				$name[$model] = $result;
			} else {
				// 默认不查询数据库
				/*
				if(FALSE) {
					$query = $this->db->select('name')->from("phone_model")->where(array("model"=>$model))->get();
					$result = $query->result_array(FALSE);
					if($result) {
						$name = $result['0']['name'];
					} else {
						$name = '';
					}
				} else {
				*/
					$name[$model] = '';
//				}
			}
		}
		return $name[$model];
	}
}
