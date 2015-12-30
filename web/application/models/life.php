<?php defined('SYSPATH') or die('No direct access allowed.');
/**
* 生活信息模块模型
* 
* 
* @package Life_Model
* @author Yufeng <ivwsai@gmail.com>
* @copyright (c) 2010-2011 MOMO Team ND Inc.
*/
class Life_Model extends Model {
	
	public $mg;
	
	public $mongo;

    public static $instances = null;
    
    private $only_mysql = false;

    const ENABLE_MG = TRUE; //是否开启mongodb支持
    
    public function __construct()
    {
        // 加载数据库类。以下可以使用 $this->db 操作数据库（如果不要求可以省略）
        parent::__construct();
        
        if (self::ENABLE_MG) {
            $this->mg = new MongoClient(Kohana::config('uap.mongodb'));
            $this->mongo = $this->mg->selectDB(MONGO_DB_LIFE);
        }
    }
    
    public function __destruct()
    {
        if (self::ENABLE_MG) {
            $this->mg->close();
        }
    }
    
    /**
     * 设置只使用mysql
     * 
     * 对主表查询是否只使用msyql
     */
    public function set_only_mysql($bool = true){
        $this->only_mysql = (bool)$bool;
    }
    
    /**
     * 
     * @return Life_Model
     */
    public static function &instance()
    {
        if (!is_object(Life_Model::$instances)) {
            // Create a new instance
            Life_Model::$instances = new Life_Model();
        }
        return Life_Model::$instances;
    }
    
    
    /**
     * 根据内容取得摘要
     * 
     * @access private
     * @param string $html //纯文本内容
     * @param int $size //要截取的长度
     * @return string
     */
    private static function get_brief(&$html, $size)
    {
        $text   = preg_replace("'[\s]*[\r\n][\s]*'", " ", strip_tags($html));
        $result = mb_substr($text, 0, $size, 'UTF-8');
        $offset = strlen($result);
        
        preg_match_all('#https?://[-A-Z0-9+&@\#/%\?=~_|!:,.;]*[-A-Z0-9+&@\#/%=~_|]#i', $text, $match, PREG_OFFSET_CAPTURE);        
        foreach($match[0] as $val) {
            if ($val[1] < $offset && $offset < ($val[1]+strlen($val[0]))) {
                //偏移量保证了substr截取不会乱码
                return substr($text, 0, $val[1]) . $val[0];
                break;
            }
        }
        
        return $result;
    }
    
    
    /**
    * 保存二手信息
    * @param int $user_id 用户ID
    * @param array $field mysql表字段
    * @param array $pic 上传的图片URL
    * @return  mixed
    */   
    public function create($user_id, $field, $pic = null)
    {
        $user_id = (int)$user_id;
        if (! $user_id) {
            return FALSE;
        }
        
        $time = time();
        $has_pic = (is_array($pic) && !empty($pic)) ? 1 : 0;
        $default = array(
                "title"=>"", 
                "description"=>"", 
                "price"=>0,
                "user_id"=>$user_id,
                "name"=>sns::getrealname($user_id),
                "category"=>0,
                "type"=>0, 
                "trading_places"=>"", //交易地点
                "city"=>"", 
                "privacy"=>0, //1好友，2群友，4同公司，8同城 (2^n形式存储)
                "has_pic"=>$has_pic, 
                "create_at"=>$time, 
                //"modify_at"=>0, 
                //"status"=>0, 
                //"status_at"=>0, 
                "longitude"=>0, 
                "latitude"=>0, 
                "client"=>0);
        
        $field = array_merge($default, (array)$field);
        $field = array_intersect_key($field, $default);
        
        if (isset($field["description"])) {            
            $field["brief"] = self::get_brief($field["description"], 50);
        } else {
            $field["brief"] = "";
        }
        
        //添加隐藏属性
        $species = $this->get_species();
        $haystack = $field["title"] . $field["brief"];
        foreach ($species as $val) {
        	if (strpos($haystack, $val["name"]) !== FALSE) {
        		$field["species"] = $val["id"];
        		break;
        	}
        }
        
        //写数据库
        $result = $this->db->insert('flea_market', $field);
        if (!$result) {
            return FALSE;
        } 
        
        $insert_id = $result->insert_id();
        
        //@todo 根据分词转换标签
        
        if (self::ENABLE_MG) {
            unset($field["has_pic"]);
            unset($field["description"]);
            
            //种类_类型_对像ID_作者ID
            $field["_id"] = sprintf("%s_%s_%s_%s", $field["category"], $field["type"], $insert_id, $user_id);
            $field["price"] = (float) $field["price"];
            $field["category"] = (int) $field["category"];
            $field["type"] = (int) $field["type"];
            $field["privacy"] = (int) $field["privacy"];
            $field["create_at"] = (int) $field["create_at"];
            $field["modify_at"] = 0;
            $field["status"] = 0;
            $field["status_at"] = 0;
            $field["longitude"] = (float) $field["longitude"];
            $field["latitude"] = (float) $field["latitude"];
            $field["client"] = (int) $field["client"];
            
            $pic_mg = array();
            if ($has_pic) {
                foreach ($pic as $key=>$val) {
                    $pic_mg[$key]["user_id"] = (int)$user_id;
                    $pic_mg[$key]["object_id"] = $insert_id;
                    $pic_mg[$key]["src"] = $val;
                    $pic_mg[$key]["create_at"] = $time;
                }
            }
            $field["pic"] = $pic_mg;
            $field["object_id"] = $insert_id;
            
            //初始化 所有评论与赞放内嵌文档可行？
            $field["c_count"] = 0;
            $field["comment"] = array();
            $field["l_count"] = 0;
            $field["likes"] = array();
            $field["storage"] = array();
            $field["lasttime"] = 0; //最新评论时间
            ksort($field);
            
            //写mongodb
            $this->mongo->selectCollection('flea_market')->insert($field);
        }
        
        //保存图片
        if ($insert_id && $has_pic) {
            $tmp = array();
            foreach($pic as $val){
                $tmp[] = sprintf("(%d, %d, '%s', %d)", $user_id, $insert_id, $val, $time);
            }
            if ($tmp) {
                $this->db->query("INSERT INTO `market_pic` (`user_id`, `object_id`, `src`, `create_at`) VALUES ". join(",", $tmp));
            }
        }
        
        return $insert_id;
    }
    
    
    /**
     * 编辑二手信息
     * 
     * @access public
     * @param int $id
     * @param int $user_id
     * @param array $field
     * @param array $pic
     * @return mixed
     */
    public function edit($id, $user_id, $field, $pic = null)
    {
        $id = (int) $id;
        $user_id = (int) $user_id;
        if (!$id || ! $user_id) {
            return FALSE;
        }
        
        $time = time();
        $has_pic = (is_array($pic) && !empty($pic)) ? 1 : 0;
        $default = array(
                "title"=>"", 
                "description"=>"", 
                "price"=>0,
                "category"=>0,
                "type"=>0, 
                "trading_places"=>"", 
                "city"=>"", 
                "privacy"=>0, //1好友，2群友，4同公司，8同城 (2^n形式存储)
                "status"=>0, 
                "longitude"=>0, 
                "latitude"=>0);
                
        $field = array_intersect_key($field, $default);
        
        if (isset($field["status"])) {
            $field["status_at"] = $time;
        }
        $field["modify_at"] = $time;
        $field["has_pic"] = $has_pic;
        
        if (isset($field["description"])) {
            $field["brief"] = self::get_brief($field["description"], 50);
        } else {
            $field["brief"] = "";
        }
        
        $result = $this->db->from("flea_market")->set($field)->where(array("object_id"=>$id, "user_id"=>$user_id))->update();
        
        if (self::ENABLE_MG) {
            unset($field["has_pic"]);
            unset($field['description']);
            
            $field["price"] = isset($field["price"]) ? (float) $field["price"] : 0;
            $field["category"] = isset($field["category"]) ? (int) $field["category"] : 0;
            $field["type"] = isset($field["type"]) ? (int) $field["type"] : 0;
            $field["privacy"] = isset($field["type"]) ? (int) $field["privacy"] : 0;
            $field["status"] = isset($field["status"]) ? (int) $field["status"] : 0;
            $field["longitude"] = isset($field["longitude"]) ? (float) $field["longitude"] : 0.0;
            $field["latitude"] = isset($field["latitude"]) ? (float) $field["latitude"] : 0.0;
            if ($has_pic == 1) {
                $pic_mg = array();
                foreach ($pic as $key=>$val) {
                    $pic_mg[$key]["user_id"] = (int)$user_id;
                    $pic_mg[$key]["object_id"] = $id;
                    $pic_mg[$key]["src"] = $val;
                    $pic_mg[$key]["create_at"] = $time;
                }
                $field['pic'] = $pic_mg;
            }
            ksort($field);
            
            $this->mongo->selectCollection('flea_market')->update(array("object_id"=>$id, "user_id"=>$user_id), array('$set'=>$field));
        }
        
        if ($result && ($has_pic == 1)) {
            //执行图片处理
            $query = $this->db->from("market_pic")->where(array("object_id"=>$id, "user_id"=>$user_id))->get();
            $res_pic = $query->result_array(FALSE);
            
            $old_pic = array();
            foreach ($res_pic as $val) {
                $old_pic[] = $val["src"];
            }
            
            $del_pic = array_diff($old_pic, $pic);
            if ($del_pic) {
                $this->db->where(array("user_id"=>$user_id, "object_id"=>$id))->in("src", $del_pic)->delete("market_pic");
            }
            
            $pic = array_diff($pic, $old_pic);
            $tmp = array();
            foreach ($pic as $val) {
                $tmp[] = sprintf("(%d, %d, '%s', %d)", $user_id, $id, $val, $time);
            }
            if ($tmp) {
                $this->db->query("INSERT INTO `market_pic` (`user_id`, `object_id`, `src`, `create_at`) VALUES ". join(",", $tmp));
            }            
        }
        
        return $result;
    }
    
    
    /**
     * 检查是否该信息的作者
     * 
     * @access public
     * @param mixed $id
     * @param mixed $user_id
     * @return void
     */
    public function is_owner($id, $user_id)
    {
        if (self::ENABLE_MG) {
            $status = $this->mongo->selectCollection('flea_market')->find(array("object_id"=>(int)$id, "user_id"=>(int)$user_id), array("_id"))->count();
            if ($status) {
                return $status;
            }
        }
        return $this->db->count_records("flea_market", array("object_id"=>$id, "user_id"=>$user_id));
    }
    
    
    /**
    * 删除二手信息 
    * @param int $user_id 发布者ID
    * @param int $id 对象ID
    * @return mixed
    */
    public function destroy($user_id, $id)
    {
        $id = (int)$id;
        $user_id = (int)$user_id;
        if (!$id || !$user_id) {
            return FALSE;
        }
        
        $status = $this->db->delete("flea_market", array("object_id"=>$id, "user_id"=>$user_id));
        
        if ($status) {
            if (self::ENABLE_MG) {
                $this->mongo->selectCollection('flea_market')->remove(array("user_id"=>$user_id, "object_id"=>$id), array("fsync"=>false));
            }
            
            //删除收藏
            $this->db->delete('market_favorite', array("object_id"=>$id));
            
            //删除隐藏
            $this->db->delete('market_hide', array("object_id"=>$id));
        }
        return $status;
    }
    
    
    /**
    * 取得信息详情
    * 当`description` 超出varchar(255)时 mongodb功能拿掉
    * @param int $id 信息id
    * @return  array
    */
    public function get_detail($id, $find_way = 'mysql')
    {
        if (self::ENABLE_MG && $find_way != 'mysql') {
            $result = $this->mongo->selectCollection('flea_market')->findOne(array("object_id"=>(int)$id));
            if ($result) {
                return $result;
            }
        }
        
        $query = $this->db->where(array("object_id"=>$id))->get("flea_market");
        if ($query->count()) {
            $result = $query->result_array(FALSE);
            
            if ($result[0]["has_pic"]) {
                $result[0]["pic"] = self::get_detail_pic($result[0]["object_id"]);
            } else {
                $result[0]["pic"] = array();
            }
            unset($result[0]["has_pic"]);
            
            return $result[0];
        }
        return FALSE;
    }
    
    
    /**
    * 取得信息详情中的图片(mysql)
    * 
    * @access public
    * @param int $id 信息id
    * @return array
    */
    public function get_detail_pic($id)
    {
        $query = $this->db->where(array("object_id"=>(int)$id))->get("market_pic");
        $result = $query->result_array(FALSE);
        foreach ($result as &$val) {
            $val["user_id"] = (int)$val["user_id"];
            $val["object_id"] = (int)$val["object_id"];
            $val["create_at"] = (int)$val["create_at"];
        }
        
        return $result;
    }
    
    
    /**
    * 根据条件取得二手信息(默认不找失效信息)
    * 
    * @access public
    * @param array $where status:all 时已失效信息也查找
    * @param string $search //用户输入模糊查找
    * @param int $offset
    * @param int $limit
    * @param array $field //要返回那些字段
    * @param array $order //排序
    * @return void
    */
    public function flea_market_info($where = array(), $search = "", $offset = 0, $limit = 30, $field = array(), $order = array("object_id"=>"DESC"))
    {
        $default = array("status"=>0);
        $expired = (isset($where["status"]) && $where["status"] =="all") ? false : true;
        
        $where = array_merge($default, (array)$where);
        if (!$expired) {
            unset($where["status"]);
        }
        
        $where = self::built_where($where, $search);
        
        if (self::ENABLE_MG && !$this->only_mysql) {
            //处理排序
            $mongo_sort = array();
            foreach ($order as $key=>$val){
                $mongo_sort[$key] = (strtoupper($val) == "DESC") ? -1 : 1;
            }
            
            $field["comment"] = array('$slice'=>-4);
            
            $result = $this->mongo->selectCollection('flea_market')->find($where["mongo_where"], $field)->sort($mongo_sort)->skip((int)$offset)->limit((int)$limit);
            unset($field["comment"]);
            //print_r($result->explain());exit;
            
            $result = iterator_to_array($result, false);

            return $result;
        }
        
        //处理返回字段
        $pic = true;
        if (!empty($field) && is_array($field)) {
            if ($ik = array_search('pic', $field)) {
                unset($field[$ik]);
            } else {
                $pic = false;
            }
        	
            $field = "`" . implode("`,`", $field) . "`";
        } else {
            $field = "*";
        }
        
        $sql = "SELECT {$field}, `has_pic` AS `pic` FROM `flea_market`";
        $sql .=$where["mysql_where"];
        
        if ($order) {
            $sql .= " ORDER BY";
            foreach ($order as $key=>$val) {
               $sql .= " " . $this->db->escape_column(trim($key)) . " " . strtoupper($val) . ",";
            }
            $sql = rtrim($sql, ",");
        }
        
        if ($limit) {
            $sql .= " LIMIT ". (int)$offset .", " . (int)$limit;
        }
        
        $query = $this->db->query($sql);
        $result = $query->result_array(FALSE);
        
        foreach ($result as &$val) {
            if (isset($val["has_pic"])) {
                unset($val["has_pic"]);
            }
            
            if ($pic) {
	            if ($val["pic"] ==1) {
	                $val["pic"] = self::get_detail_pic($val["object_id"]);
	            } else {
	                $val["pic"] = array();
	            }
            }
        }
        return $result;
    }
    
    
    /**
     * 根据条件取得二手信息总记录数(默认不找失效信息)
     * 
     * @access public
     * @param array $where
     * @param string $search
     * @return int
     */
    public function flea_market_info_count($where = array(), $search="")
    {
        $default = array("status"=>0);
        $expired = (isset($where["status"]) && $where["status"] =="all") ? false : true;
        
        $where = array_merge($default, (array)$where);
        if (!$expired) {
            unset($where["status"]);
        }
        
        $where = self::built_where($where, $search);
        if (self::ENABLE_MG && !$this->only_mysql) {
            return $this->mongo->selectCollection('flea_market')->find($where["mongo_where"], array("_id"=>true))->count();
        }
        
        $sql = "SELECT count(*) AS `total` FROM `flea_market`";
        $sql .=$where["mysql_where"];
        
        $query = $this->db->query($sql);
        if ($query->count()){
            $result = $query->result_array(FALSE);
            return (int)$result[0]["total"];
        }
    }
    
    
    /**
     * 改变信息状态，交易完成、取消等。。
     * 
     * @access public
     * @param int $id
     * @param int $user_id
     * @param int $status
     * @return mixed
     */
    public function ch_market_info_status($id, $user_id, $status)
    {
        $id = (int)$id;
        $user_id = (int)$user_id;
        $status = (int)$status;
        if (!$id || !$user_id || !$status) {
            return FALSE;
        }
        
        $time = time();
        if (self::ENABLE_MG) {
            $this->mongo->selectCollection('flea_market')->update(array("object_id"=>$id, "user_id"=>$user_id), array('$set'=>array("status"=>$status, "status_at"=>$time)));
        }
        
        return $this->db->from("flea_market")->set(array("status"=>$status, "status_at"=>$time))->where(array("object_id"=>$id, "user_id"=>$user_id))->update();
    }
    
    
    /**
     * 删除信息中的图片
     * 
     * @access public
     * @param int $id
     * @param string $src
     * @param int $user_id
     * @return mixed
     */
    public function delete_img($id, $src, $user_id)
    {
        $id = (int)$id;
        $user_id = (int)$user_id;
        if (!$id || !$user_id) {
            return FALSE;
        }
        
        if (self::ENABLE_MG) {
            $this->model->mgdb->selectCollection('flea_market')->update(array("object_id"=>$id, "user_id"=>$user_id), array('$pull'=>array('pic'=>array("src"=>$src))));
        }
        return $this->db->delete("market_pic", array("user_id"=>$user_id, "object_id"=>$id, "src"=>$src));
    }
    
    
    /**
     * 构造mongodb AND mysql where 条件
     * 
     * @access private
     * @param array $where
     * @param string $search 最多20个汉字
     * @return array
     */
    private function built_where(&$where, &$search)
    {
        //处理where条件
        $mg_where = array();
        if (self::ENABLE_MG) {
            foreach ($where as $key=>$val) {
                if ("has_pic" == $key) {
                    if ($val == 1) {
                         $mg_where["pic"] = array('$ne'=>array());
                    } else {
                         $mg_where["pic.0.src"] = array('$exists'=>false);
                    }
                    continue;
                }
                
                if (is_array($val)) {
                    //$mg_where[$key] = array('$in'=>$val);
                    $mg_where[$key] = $val;
                    continue;
                }
                
                if (strpos($key, '!=')){
                    $mg_where[rtrim($key, '!=')] = array('$ne'=>$val);
                    continue;
                }
                
                if (strpos($key, '>=')) {
                    $mg_where[rtrim($key, '>=')] = array('$gte'=>$val);
                    continue;
                }
                
                if (strpos($key, '>')) {
                    $mg_where[rtrim($key, '>')] = array('$gt'=>$val);
                    continue;
                }
                
                if (strpos($key, '<=')) {
                    $mg_where[rtrim($key, '<=')] = array('$lte'=>$val);
                    continue;
                }
                
                if (strpos($key, '<')) {
                    $mg_where[rtrim($key, '<')] = array('$lt'=>$val);
                    continue;
                }
                
                $mg_where[$key] = $val;
            }
            
            if ($search) {
                //preg_split("/[\s　]+/", $search);
                //$regex = new MongoRegex("/$search/i");
                $mg_where['$or'] = array(array("title"=>new MongoRegex("/$search/i")), array("brief"=>new MongoRegex("/$search/im")));
            }            
        }
        
        //以下是mysql的处理
        $sql = '';
        if ($where || $search) {
            $sql = " WHERE";
        }
        
        foreach ($where as $key=>$val) {
            if (is_array($val)) {
                if (isset($val['$in'])) {
                    $sql .= " `$key` IN (".implode(",", $val['$in']).") AND";
                }
                
                if (isset($val['$nin'])) {
                    $sql .= " `$key` NOT IN (".implode(",", $val['$nin']).") AND";
                }
            } else {
                preg_match('/^(.+?)([<>!=]+|\bIS(?:\s+NULL))\s*$/i', $key, $matches);
                if (isset($matches[1]) AND isset($matches[2])) {
                    $key = $this->db->escape_column(trim($matches[1])) . ' ' . trim($matches[2]);
                } else {
                    $key = $this->db->escape_column($key) . ' = ';
                }
                $sql .=  " $key " . $this->db->escape($val) . " AND"; 
            }
        }
        
        if ($search) {
            $search = $this->db->escape_str($search);
            $search = '%'.str_replace('%', '\\%', $search).'%';
            
            $sql .= " (`title` LIKE '$search' OR `brief` LIKE '$search')";
        } else {
            $sql = rtrim($sql, "AND");
        }
        $where = $search = null;
        
        return array("mongo_where"=>$mg_where, "mysql_where"=>$sql);
    }
    
    
    /**
     * 取得我的二手总记录数(如果主表有定期清空则不可信)
     * 
     * @access public
     * @param int $user_id
     * @return int
     */
    public function get_used_count($user_id)
    {
        $user_id = (int) $user_id;
        if (!$user_id) {
            return FALSE;
        }
        
        if (self::ENABLE_MG && !$this->only_mysql) {            
            return $this->mongo->selectCollection('flea_market')->find(array("user_id"=>$user_id, "category"=>1), array("_id"=>true))->count();
        }
        
        return $this->db->count_records("flea_market", array("user_id"=>$user_id, "category"=>1));
    }
    
    
    /**
     * 取得我的租房总记录数(如果主表有定期清空则不可信)
     * 
     * @access public
     * @param int $user_id
     * @return int
     */
    public function get_rent_count($user_id)
    {
        $user_id = (int) $user_id;
        if (!$user_id) {
            return FALSE;
        }
        
        if (self::ENABLE_MG && !$this->only_mysql) {
            return $this->mongo->selectCollection('flea_market')->find(array("user_id"=>$user_id, "category"=>2), array("_id"=>true))->count();
        }
        
        return $this->db->count_records("flea_market", array("user_id"=>$user_id, "category"=>2));
    }
    
    
    /**
     * 取得我的收藏总记录数(二手信息中的关注)
     * 
     * @access public
     * @param int $user_id
     * @return int
     */
    public function get_favorite_count($user_id)
    {
        $user_id = (int) $user_id;
        if (!$user_id) {
            return FALSE;
        }
        
        return $this->db->count_records("market_favorite", array("user_id"=>$user_id));
    }
    
    
    /**
     * 取得我的收藏ids(二手信息中的关注)
     * 
     * @access public
     * @param int $user_id
     * @param int $offset
     * @param int $limit
     * @return void
     */
    public function get_favorite_ids($user_id, $offset = 0, $limit = 20)
    {
        $user_id = (int) $user_id;
        if (!$user_id) {
            return FALSE;
        }
        
        $query = $this->db->select(array("object_id"))->where(array("user_id"=>$user_id))->get("market_favorite", $limit, $offset);
        $result = $query->result_array(FALSE);
        array_walk($result, function(&$item){$item = (int)$item["object_id"];});
        
        return $result;
    }
    
    
    /**
     * 检查是否己收藏(二手信息中的关注)
     * 
     * @access public
     * @param int $object_id
     * @param int $user_id
     * @return bool
     */
    public function check_is_favorite($object_id, $user_id)
    {
        return (bool) $this->db->count_records("market_favorite", array("user_id"=>$user_id, "object_id"=>$object_id));
    }
    
    
    /**
     * 添加收藏(二手信息中的关注)
     * 
     * @access public
     * @param int $object_id
     * @param int $user_id
     * @return mixed
     */
    public function add_favorite($object_id, $user_id)
    {
        $time = time();
        $object_id = (int) $object_id;
        $user_id = (int) $user_id;
        if (!$object_id || !$user_id) {
            return FALSE;
        }
        
        if (self::ENABLE_MG) {
            
            //冗余到flea_market主表
            $this->mongo->selectCollection('flea_market')->update(array("object_id"=>$object_id), array('$addToSet'=>array("storage"=>$user_id)));
        }
        return $this->db->query("REPLACE INTO `market_favorite` (`object_id`, `user_id`, `create_at`) VALUES ($object_id, $user_id, $time)");
    }
    
    
    /**
     * 取消收藏(二手信息中的关注)
     * 
     * @access public
     * @param int $object_id
     * @param int $user_id
     * @return void
     */
    public function destroy_favorite($object_id, $user_id)
    {
        $object_id = (int) $object_id;
        $user_id = (int) $user_id;
        if (!$object_id || !$user_id) {
            return FALSE;
        }
        
        if (self::ENABLE_MG) {
            
            //同步删除冗余到flea_market主表数据
            $this->mongo->selectCollection('flea_market')->update(array("object_id"=>$object_id), array('$pull'=>array("storage"=>$user_id)));
        }
        return $this->db->delete('market_favorite', array("object_id"=>$object_id, "user_id"=>$user_id));
    }
    
    
    /**
     * 取得一段时间内评论数最多的 (mongo特有)
     * 
     * @access public
     * @param string $city 条件限定 在那个城市
     * @param enum(1-12) $month 条件限定 几个月内
     * @param int $limit
     * @return array
     */
    public function get_on_top($city = '', $month = 1, $limit = 10)
    {
        $month = (int) $month;
        $limit = (int) $limit;
        $fields = array("c_count", "category", "object_id", "name", "create_at", "user_id", "title", "type");
        if (self::ENABLE_MG) {
            $where = array("status"=>0);
            
            if ($city) {
                $where["city"] = $city;
            }
            
            if ($month) {
                $time = time() -  $month*2592000;// $month*30*24*60*60
                $where["create_at"] = array('$gte'=>$time);
            }
            
            $result = $this->mongo->selectCollection('flea_market')->find($where, $fields)->sort(array("c_count"=>-1))->limit($limit);
            return iterator_to_array($result, false);
        }
        
        return array();
    }
    
    
    /**
     * 最近完成的交易
     * 
     * @access public
     * @param string $city
     * @param int $limit
     * @return array
     */
    public function recent_done_trading($city = '福州', $limit = 10)
    {
        $fields = array("category", "object_id", "name", "status_at", "user_id", "title", "type");
        
        if (self::ENABLE_MG) {
            $limit = (int) $limit;
            $fields[] = 'c_count';
            
            $result = $this->mongo->selectCollection('flea_market')->find(array("status"=>3, "city"=>$city), $fields)->sort(array("status_at"=>-1))->limit($limit);
            return iterator_to_array($result, false);
        }
        
        $this->db->select($fields)->where(array("status"=>3, "city"=>$city))->orderby(array("status_at"=>"DESC"));
        $query = $this->db->get("flea_market", $limit);
        return $query->result_array(FALSE);
    }
    
    /**
     * 添加一条隐藏信息
     * @param int $object_id
     * @param int $user_id
     * @return mixed
     */
    public function create_hidden($object_id, $user_id)
    {
        $object_id = (int) $object_id;
        $user_id = (int) $user_id;
        if (!$object_id || !$user_id) {
            return FALSE;
        }
        
        $query = $this->db->select(array("create_at"))->where(array("object_id"=>$object_id))->get("flea_market");
        
        if (! $query->count()){
            return FALSE;
        }
        $result = $query->result_array(FALSE);
        $time = time();
        
        return $this->db->query("REPLACE INTO `market_hide` (`object_id`, `user_id`, `object_time`, `create_at`) VALUES ($object_id, $user_id, {$result[0]['create_at']}, $time)");
    }
    
    /**
     * 去掉一条隐藏信息
     * @param int $object_id
     * @param int $user_id
     * @return mixed
     */
    public function destroy_hidden($object_id, $user_id)
    {
        $object_id = (int) $object_id;
        $user_id = (int) $user_id;
        if (!$object_id || !$user_id) {
            return FALSE;
        }
        return $this->db->delete('market_hide', array("object_id"=>$object_id, "user_id"=>$user_id));
    }
    
    /**
     * 取得某用户的隐藏总数
     * @param int $user_id
     * @return int
     */
    public function get_hidden_count($user_id)
    {
        $user_id = (int) $user_id;
        if (!$user_id) {
            return FALSE;
        }
        
        return $this->db->count_records("market_hide", array("user_id"=>$user_id));
    }
    
    /**
     * 取得某用户的隐藏的ids
     * @param int $user_id
     * @return int
     */
    public function get_hidden_ids($user_id, $offset = null, $limit = null, $time = null)
    {
        $user_id = (int) $user_id;
        if (!$user_id) {
            return FALSE;
        }
        
        $where = array("user_id"=>$user_id);
        if ($time) {
            $where["object_time>="] = (int)$time;
        }
        
        $query = $this->db->select(array("object_id"))->where($where)->orderby(array("object_id"=>"DESC"))->get("market_hide", $limit, $offset);
        $result = $query->result_array(FALSE);
        array_walk($result, function(&$item){$item = (int)$item["object_id"];});
        
        return $result;
    }
    
    /**
     * 取得某用户隐藏最新的信息id与该信息的创建时间
     * @param int $user_id
     */
    public function get_recent_hide($user_id)
    {
        $user_id = (int) $user_id;
        if (!$user_id) {
            return FALSE;
        }
        
        $query = $this->db->where(array("user_id"=>$user_id))->get("market_hide", 1);
        if ($query->count()) {
            $result = $query->result_array(FALSE);
            return $result[0];
        }
        
        return FALSE;
    }
    
    /**
     * 更新隐藏属性字典
     * @param int $object_id 对象ID
     * @param int $species_id 属性ID
     * @param int $user_id 用户ID
     */
    public function update_market_species($object_id, $species_id, $user_id)
    {
    	$object_id = (int) $object_id;
    	$species_id = (int) $species_id;
    	$user_id = (int) $user_id;
    	
    	 if (self::ENABLE_MG) {
    	 	 $result = $this->mongo->selectCollection('flea_market')->update(array("object_id"=>$object_id, "user_id"=>$user_id), array('$set'=>array("species"=>$species_id)));
    	 }
    	 return $this->db->update("flea_market", array("species"=>$species_id), array("object_id"=>$object_id, "user_id"=>$user_id));
    }
    
    /**
     * 取得隐藏属性字典
     */
    public function get_species()
    {
    	if (self::ENABLE_MG) {
    		$result = $this->mongo->selectCollection('market_species')->findOne();
    		return isset($result["species"]) ? $result["species"] : array();
    	}

    	$query = $this->db->get("market_matchlibrary");
    	$result = $query->result_array(false);
    	
    	$tmp = array();
    	foreach ($result as $val) {
    		$tmp[] = array("id"=>$val["market_class_id"], "name"=>$val["name"]);
    	}
    	
    	return $tmp;
    }
    
    /**
     * 最新评论的二手信息 (mongo特有)
     * 
     * @access public
     * @param string $city
     * @param int $limit
     * @return array
     */
    public function latest_comment_theme($city = '福州', $limit = 10)
    {
        $limit = (int) $limit;
        $fields = array("category", "object_id", "name", "lasttime", "user_id", "title", "type");
        
        if (self::ENABLE_MG) {
            $fields[] = 'c_count';
            
            $result = $this->mongo->selectCollection('flea_market')->find(array("status"=>0, "city"=>$city), $fields)->sort(array("lasttime"=>-1))->limit($limit);
            return iterator_to_array($result, false);
        }
        
        return array();
    }
    
    
    /**
     * 把搜索关键字当标签并更新搜索热度 (mongo特有)
     * Notice:mysql market_terms表要求脚本定时更新过去备份
     * (30分钟内相同的只更新一次，需memcache支持) 英文大小写未处理
     * 
     * @access public
     * @param string $keyword
     * @param string $city 搜索者所在地区
     * @param int $user_id
     * @return void
     */
    public function up_tags_hot($keyword, $city, $user_id = 0)
    {
        $keyword = trim($keyword);
        if (!$keyword || !self::ENABLE_MG) {
            return FALSE;
        }
        
        $encode_keyword = urlencode($keyword);
        $key = md5($encode_keyword.$city);
        
        //处理一定时间内重复不计数开始
        $time = time();
        $data = Cache::instance()->get("momoim_tags_hos_".$user_id);
        if (is_array($data)) {
            if (isset($data[$key]) && $data[$key] > ($time-5*60)) {
                return TRUE;
            }
            
            $data[$key] = $time;
        } else {
            $data = array($key=>$time);
        }
        Cache::instance()->set("momoim_tags_hos_".$user_id, $data, NULL, 3600*24);
        //END.
        
        $newdoc = array('$set'=>array("name"=>$keyword, "slug"=>$encode_keyword, "city"=>$city, "hot"=>1),'$inc'=>array("s_count"=>1));
        $status = $this->mongo->selectCollection('market_terms')->update(array("_id"=>$key), $newdoc, array("upsert"=>true));
        
        return $status;
    }
    
    
    /**
     * 取得最热标签列表
     * 
     * @access public
     * @param string $city
     * @param int $limit
     * @return void
     */
    public function get_tags($city="福州", $limit = 20)
    {
        if (self::ENABLE_MG) {
            $limit = (int) $limit;
            
            $result = $this->mongo->selectCollection('market_terms')->find(array("city"=>$city, "verify"=>'1'))->sort(array("hot"=>-1, "s_count"=>-1))->limit($limit);
            return iterator_to_array($result, false);
        }
        
        $query = $this->db->where(array("city"=>$city, "verify"=>'1'))->orderby(array("hot"=>"DESC"))->get("market_terms", $limit);
        return $query->result_array(FALSE);
    }
    
    
    /**
     * 设置用户当前己查看过的最新资ID (mongo特有)
     * 
     * @access public
     * @param int $user_id
     * @param int $object_id
     * @return void
     */
    public function set_seeifno_status($object_id, $user_id)
    {
        $user_id = (int)$user_id;
        if (!$user_id || !self::ENABLE_MG) {
            return FALSE;
        }
        $result = $this->mongo->selectCollection('flea_market')->find()->sort(array("object_id"=>-1))->limit(1);
        $result = iterator_to_array($result, false);
        
        $where = array("_id"=>$user_id);
        $newdoc = array('$set'=>array("object_id"=>(int)$result[0]["object_id"]));
        return $this->mongo->selectCollection('market_viewed_maxid')->update($where, $newdoc, array("upsert"=>true));
    }
    
    
    /**
     * 取得当前用户有几条未读新资讯 (mongo特有)
     * Notice: 依赖set_seeifno_status函数中mongo的支持
     * 
     * @access public
     * @param int $user_id
     * @param string $city
     * @return void
     */
    public function newinfo_count($user_id, $city)
    {
        $user_id = (int)$user_id;
        if (!$user_id || !self::ENABLE_MG) {
            return FALSE;
        }
        $result = $this->mongo->selectCollection('market_viewed_maxid')->findOne(array("_id"=>$user_id));
        
        return $this->mongo->selectCollection('flea_market')->find(array("status"=>0, "city"=>$city, "privacy"=>array('$in'=>array(0, 8, 9, 14, 15)), "object_id"=>array('$gt'=>$result["object_id"])))->count();
    }
    
    
    /**
     * 赞冗余到flea_market主表操作 (mongo特有)
     * 
     * @access public
     * @param int $object_id
     * @param int $user_id
     * @param string $name
     * @return void
     */
    public function praise_add_market($object_id, $user_id, $name, $client = 0)
    {
        $object_id = (int) $object_id;
        $user_id = (int) $user_id;
        if (!$object_id || !$user_id || !self::ENABLE_MG) {
            return FALSE;
        }
        
        $newdata = array('$addToSet'=>array("likes"=>array("id"=>$user_id, "name"=>$name, "time"=>time(), "client"=>$client)),
                         '$inc' => array("l_count"=>1)
        );
        
        return $this->mongo->selectCollection('flea_market')->update(array("object_id"=>$object_id), $newdata);
    }
    
    
    /**
     * 赞删除 flea_market主表操作 (mongo特有)
     * 
     * @access public
     * @param int $object_id
     * @param int $user_id
     * @param string $name
     * @return void
     */
    public function praise_del_market($object_id, $user_id)
    {
        $object_id = (int) $object_id;
        $user_id = (int) $user_id;
        if (!$object_id || !$user_id || !self::ENABLE_MG) {
            return FALSE;
        }
        
        $newdata = array('$pull'=>array("likes"=>array("id"=>$user_id)),
                         '$inc' => array('l_count' => -1)
        );
        return $this->mongo->selectCollection('flea_market')->update(array("object_id"=>$object_id), $newdata);
    }
    
    
    /**
     * 评冗论余到flea_market主表操作 (mongo特有)
     * 
     * @access public
     * @param int $object_id
     * @param int $id //评论表ID
     * @param int $user_id
     * @param string $name
     * @param string $text //评论内容
     * @param string $a //评论内容中at到的用户 [{id:"用户ID", name:"用户名称"},...]
     * @param int $client //来自那个客户端的操作
     * @return void
     */
    public function comment_add_market($object_id, $id, $user_id, $name, $text, $at = array(), $client = 0)
    {
        $object_id = (int) $object_id;
        //$id = (int) $id;
        $user_id = (int) $user_id;
        $text = (string) $text;
        if (!$object_id || !$id || !$user_id || !$text || !self::ENABLE_MG) {
            return FALSE;
        }
        
        $newdata = array('$addToSet'=>array("comment"=>array("id"=>$id, "user_id"=>$user_id, "name"=>$name, "text"=>$text, "at"=>$at, "time"=>time(), "client"=>$client)),
                         '$inc' => array("c_count"=>1),
                         '$set' => array("lasttime"=>time())
        );
        
        //是否commentList超出5条评论就执行
        //$this->mongo->selectCollection('flea_market')->update(array("object_ids"=>$object_id), array('$pop'=>array("commentList"=>-1)));
        
        $status = $this->mongo->selectCollection('flea_market')->update(array("object_id"=>$object_id), $newdata);
        
        return $status;
    }
    
    
    /**
     * 评论删除 flea_market主表操作 (mongo特有)
     * 
     * @access public
     * @param int $object_id
     * @param int $user_id
     * @param int $comment_id //要删除的评论id
     * @return void
     */
    public function comment_del_market($object_id, $user_id, $comment_id)
    {
        $object_id = (int) $object_id;
        $user_id = (int) $user_id;
        //$comment_id = (int) $comment_id;
        if (!$object_id || !$user_id || !$comment_id || !self::ENABLE_MG) {
            return FALSE;
        }
        
        $coll = $this->mongo->selectCollection('flea_market');
        
        //检查用户是否有权限删除
        $where = array("object_id"=>$object_id, 
                       '$or'=>array(array("comment"=>array('$elemMatch'=>array("id"=>$comment_id, "user_id"=>$user_id))), 
                                    array("user_id"=>$user_id))
        );
        $result = $coll->findOne($where, array("_id"));
        if (!$result) {
            return FALSE;
        }
        //End.
        
        $newdata = array('$pull'=>array("comment"=>array("id"=>$comment_id)),
                         '$inc' => array('c_count' => -1)
        );
        
        return $coll->update(array("object_id"=>$object_id), $newdata);
    }
    
    
    /* ------------------------------------------评论 以后处理MYSQL操作------------------------------------- */
    public function get_comments($object_id, $offset = 0, $limit = 20)
    {
        $coll = $this->mgdb->selectCollection('flea_market');
        return $coll->findOne(array("object_id"=>(int)$object_id), array("comment"=>array('$slice'=>array($offset, $limit))));
    }
    
    //$parent_id 被回复的某评论ID
    public function create_comment($object_id, $text, $user_id, $parent_id = null, $client = 0)
    {
        $name = sns::getrealname($user_id);
        $id = (string) (microtime(true) * 10000);;
        $this->at2id = array();
        $text = preg_replace_callback('#\b(https?)://[-A-Z0-9+&@\#/%?=~_|!:,.;]*[-A-Z0-9+&@\#/%=~_|]#i', array($this, 'bulid_hyperlinks'), $text);
        $text = preg_replace_callback('/@([^@]+?)\(([1-9][0-9]*)\)/', array($this, 'bulid_user_hyperlinks'), $text);
        
        return self::comment_add_market($object_id, $id, $user_id, $name, $text, $this->at2id, $client);
    }
    
    public function destroy_comment($object_id, $comment_id, $user_id = 0)
    {
        return self::comment_del_market($object_id, $user_id, $comment_id);
    }
    
    
    // 构建 @用户名(用户ID)的超链接（回调函数）
    private function bulid_user_hyperlinks(&$matches)
    {
        if ($matches[1] == sns::getrealname($matches[2]) || !$matches[2]) {
            $this->at2id[] = array('id' => $matches[2], 'name' => $matches[1]); 
            return '[@' . (count($this->at2id) - 1) . ']';
        } 
        return $matches[0];
    }
    
    
    // 构造文本域中的超链接（回调函数）
    private function bulid_hyperlinks($matches)
    {
        $matchUrl = str_replace('&amp;', '&', $matches[0]);
        $tmp = preg_split("/(&#039|&quot)/", $matchUrl);
        $debris = isset($tmp[1]) ? substr($matchUrl, strlen($tmp[0])) : "";
        $matchUrl = $tmp[0];

        if (stripos($matchUrl, YOURLS_SITE) !== false) {
            $shortUrl = $matchUrl;
        } else {
            $shortUrl = strlen($matchUrl) <= 18 ? $matchUrl : url::getShortUrl($matchUrl);
        } 

        return "<a href=\"{$shortUrl}\" target=\"_blank\">{$shortUrl}</a>{$debris}";
    }
}
