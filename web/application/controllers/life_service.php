<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * 生活信息服务控制器
 * 
 * @package Life_Service_Controller
 * @author Yufeng <ivwsai@gmail.com> 
 * @copyright (c) 2010-2011 MOMO Team ND Inc.
 */
class Life_Service_Controller extends Controller {

    const MAX_PAGESIZE = 100;
    
    protected $model;
    
    protected $type;
    
    protected $unit;

    public function __construct()
    {
        parent::__construct();
        
        $this->model = Life_Model::instance();
        
        //$this->user_id = 10614401;
        $this->type = array(0 => "", 1 => array(1 => "转让", 2 => "求购"), 2 => array(1 => "出租", 2 => "求租"));
        $this->unit = array(0 => "", 1 => "元", 2 => "元/月");
    }

    /**
     * 取得列表json数据
     * @method GET
     * 
     * fail=1 失效
     * sale=1 供　dealer=2 求
     * city=城市名(eg:福州)
     * circle:1、好友; 6、熟人; 8、同城
     * cate:1二手物品,2租房,3售房,4团购
     * keyword:用户输入
     * |myfav=1 我的收藏
     * |mytrade=1 我的二手
     * |myrent=1 我的租房
     * |replymy=1 回复我的
     * |hide=1 我的隐藏
     * 
     * @access public
     * @return void
     */
    public function index()
    {
        if ($this->input->get("hide", 0)) {
            $start = (int)$this->input->get('page', 1);
            if ($start <= 0) {
                $this->send_response(400, NULL, "输入有误");
            }
            
            $pos = (int)$this->input->get('pagesize', 20);
            if ($pos <= 0 || $pos > self::MAX_PAGESIZE) {
                $this->send_response(400, NULL, "输入有误");
            }
            
            $start = abs(($start - 1) * $pos);
            
            $result = $this->model->get_hidden_ids($this->user_id, $start, $pos);
            self::get_market_json(array("object_id" => array('$in'=>$result), "status" => "all"), "", false);
            return null;
        }
        
        if ($this->input->get("myfav", 0)) {
            $start = (int)$this->input->get('page', 1);
            if ($start <= 0) {
                $this->send_response(400, NULL, "输入有误");
            }
            
            $pos = (int)$this->input->get('pagesize', 20);
            if ($pos <= 0 || $pos > self::MAX_PAGESIZE) {
                $this->send_response(400, NULL, "输入有误");
            }
            
            $start = abs(($start - 1) * $pos);
            
            $result = $this->model->get_favorite_ids($this->user_id, $start, $pos);
            self::get_market_json(array("object_id" => array('$in'=>$result), "status" => "all"), "", false);
            return null;
        }
        
        if ($this->input->get("mytrade", 0)) {
            self::get_market_json(array("user_id" => ( int )$this->user_id, "category" => 1, "status" => "all"), "", false);
            return null;
        }
        
        if ($this->input->get("myrent", 0)) {
            self::get_market_json(array("user_id" => ( int )$this->user_id, "category" => 2, "status" => "all"), "", false);
            return null;
        }
        
        if ($this->input->get("replymy", 0)) {
            $this->send_response(501, null, "暂不支持");
            return null;
        }
        
        $fail = $this->input->get("fail", 0);
        $sale = $this->input->get("sale", 0);
        $city = $this->input->get("city", "");
        $circle = $this->input->get("circle", 0);
        $cate = $this->input->get("cate", 0);
        $keyword = $this->input->get("keyword", "");
        $keyword = $keyword ? strtr(trim($keyword), array("." => "", "*" => "", "+" => "", "?" => "", "[" => "", "]" => "", "(" => "", ")" => "", "，" => "", "," => "")) : "";
        
        $where = array();
        if ($fail) {
            $where['status'] = 'all';
        }
        
        if ($sale && in_array($sale, array('1', '2'))) {
            $where['type'] = ( int )$sale;
        }
        
        if ($city) {
            $where['city'] = $city;
        }
        
        if ($cate && in_array($cate, array('1', '2', '3', '4', '5'))) {
            $where['category'] = (int)$cate;
        }
        
        //取得两个月内的隐藏数据
        $lastmonth = mktime(0, 0, 0, date("m")-2, date("d"),   date("Y"));
        $not_id = $this->model->get_hidden_ids($this->uid, null, null, $lastmonth);
        if (!empty($not_id)) {
            $where['object_id'] = array('$nin'=>$not_id);
        }
        
        //权限处理 没选权限默认是同城
        if ($circle && in_array($circle, array('1', '6'))) {
            do {
                $user_ids = array();
                if ($circle == 1) {
                    $user_ids = Friend_Model::instance()->getAllFriendIDs($this->user_id);
                    array_walk($user_ids, function (&$item){$item = ( int )$item;});
                    
                    $user_ids[] = ( int )$this->user_id;
                    
                    $where['privacy'] = array('$in'=>array(1, 7, 9, 15));
                    break;
                }
                
                if ($circle == 6) {
                    $sub_model = new Company_Model();
                    $cids = $sub_model->getCompanyList($this->user_id);
                    foreach ($cids as $v) {
                        $uids = $sub_model->getCompanyMemberIds($v["cid"]);
                        $user_ids = array_merge($user_ids, $uids);
                    }
                    
                    $sub_model = new Group_Model();
                    $gids = $sub_model->getUserAllGroupId($this->user_id);
                    foreach ($gids as $v) {
                        $uids = $sub_model->getGroupAllMember($v["gid"]);
                        array_walk($uids, function (&$item){$item = $item["uid"];});
                        $user_ids = array_merge($user_ids, $uids);
                    }
                    unset($cids, $gids, $sub_model);
                    
                    array_walk($user_ids, function (&$item){$item = ( int )$item;});
                    $user_ids[] = ( int )$this->user_id;
                    
                    $user_ids = array_unique($user_ids);
                    
                    $where['privacy'] = array('$in'=>array(6, 7, 14, 15));
                    break;
                }
            } while ( 0 );
            
            $where['user_id'] = array('$in'=>$user_ids);
        } else {
            // 同城，（钩上同城选项）或者（好友、群友、同事、在同城）
            $where['privacy'] = array('$in'=>array(0, 8, 9, 14, 15));
            
            if (!$city) {
                $where['city'] = self::city_visitors();
            }
        }
        
        self::get_market_json($where, $keyword);
    }

    /**
     * 内部调用　取得josn数据
     * 
     * @access private
     * @param arrar $where
     * @param string $search
     * @param bool $index
     * @return void
     */
    private function get_market_json($where, $search, $index = true)
    {
        $lasttime = $this->input->get('lasttime', 0);
        
        $start = $this->input->get('page', 1);
        if ($start <= 0) {
            $this->send_response(400, NULL, "输入有误");
        }
        
        $pos = $this->input->get('pagesize', 20);
        if ($pos <= 0 || $pos > self::MAX_PAGESIZE) {
            $this->send_response(400, NULL, "输入有误");
        }
        $start = ($start - 1) * $pos;
        
        // 更新搜索关键字热度
        if ($search) {
            // 用户输入搜索词长度限制最多26个汉字
            if (strlen($search) > 76) {
                $search2 = substr($search, 0, 76);
                $ascnum = ord($search{75});
                if ($ascnum >= 224) {
                    $search2 .= $search{76} . $search{77};
                } elseif ($ascnum >= 192) {
                    $search2 .= $search{76};
                } elseif ($ascnum >= 128 && ord($search{76}) < 224) {
                    $search2 .= $search{76};
                }
                $search = $search2;
            }
            // END.
            
            if (empty($where['city'])) {
                $city = self::city_visitors();
            } else {
                $city = $where['city'];
            }
            
            $this->model->up_tags_hot($search, $city, $this->user_id);
        }
        
        $result = $this->model->flea_market_info($where, $search, $start, $pos);
        
        if (!$result) {
            $this->send_response(200, array("data" => array(), "lasttime" => $lasttime));
        }
        
        $data = array(); $i = 0;
        
        foreach ($result as $key => $val) {
            $pic = array();
            foreach ($val["pic"] as $v) {
                $pic[] = $v["src"];
            }
            
            if ($val["price"] < 0){
                $price = "价格面议";
            } else {
                $price = $val["price"] .$this->unit[$val["category"]];
            }
            
            $data[$i] = array(
                    "user_id" => $val["user_id"], 
                    "name" => $val["name"], 
                    "id" => $val["object_id"], 
                    "typeid" => 34, 
                    "status" => $val["status"], 
                    "images" => $pic, 
                    "text" => $this->type[$val["category"]][$val["type"]] . $val["title"] . "，" . $price ."，" . $val["brief"], 
                    "create_at" => $val["create_at"], 
                    "client" => $val["client"]
            );
            
            if (Life_Model::ENABLE_MG) {
                $data[$i]["storage"] = ( int )in_array($this->user_id, $val["storage"]);
                
                $tmp = array();
                $likeList = $val["likes"];
                
                foreach ($likeList as $l_v) {
                    if ($l_v["id"] == $this->user_id) {
                        $tmp[0] = array("id" => $l_v["id"], "name" => "我");
                    } else {
                        $tmp[] = array("id" => $l_v["id"], "name" => $l_v["name"]);
                    }
                }
                
                if (count($tmp) >= 2) {
                    $fruit = array_shift($tmp);
                    $tmp = array_slice($tmp, -2);
                    array_unshift($tmp, $fruit);
                }
                
                $data[$i]['like'] = $tmp;
                $data[$i]["l_count"] = $val["l_count"];
                
                $comment = $val["comment"];
                $tmp = array();
                
                $comment_count = $val["c_count"];
                foreach ($comment as $key => $c_v) {
                    if ($key < 2 && ($c_v['time'] + 1800 < time()) && $comment_count > 2) {
                        $comment_count--;
                        continue;
                    }
                    
                    $tmp[] = array(
                            "id" => $c_v["id"],
                            "user_id" => $c_v["user_id"], 
                            "name" => $c_v["name"], 
                            "text" => $c_v['text'], 
                            "at" => $c_v['at'], 
                            "create_at" => $c_v['time'], 
                            "client" => isset($c_v["client"]) ? (int)$c_v["client"] : 0,
                            "like" => array(), 
                            "l_count" => 0
                    );
                }
                $data[$i]["comment"] = $tmp;
                $data[$i]["c_count"] = $val["c_count"];
            } else {
                $data[$i]["storage"] = 0;
                $data[$i]["l_count"] = 0;
                $data[$i]["like"] = array();
                $data[$i]["c_count"] = 0;
                $data[$i]["comment"] = array();
            }
            
            $i++;
        }
        
        // 据据前端规则lasttime=0时为第一页，这时记录当前用户己查看过的最新资讯
        if (!$lasttime && $index) {
            $this->model->set_seeifno_status($result[0]["object_id"], $this->user_id);
        }
        unset($result, $likeList, $comment);
        
        $lasttime = $val["create_at"] ? $val["create_at"] : $lasttime;
        
        $this->send_response(200, array("data" => $data, "lasttime" => $lasttime));
    }

    /**
     * 发布一条资讯
     * @method POST
     * 
     * @access public
     * @return void
     */
    public function create()
    {
        if ($this->get_method() != 'POST') {
            $this->send_response(405, null, '请求的方法不存在');
        }
        
        $post = $this->get_data();
        
        $post = new Validation($post);
        $post->pre_filter('trim');
        $post->pre_filter('html::specialchars');
        $post->add_rules('title', 'required', 'length[2,60]');
        // $post->add_rules('description', 'required', 'length[1,255]');
        $post->add_rules('category', 'required', 'chars[1,2,3,4]');
        $post->add_rules('type', 'required', 'chars[1,2]');
        $post->add_rules('price', 'required', 'numeric');
        $post->add_rules('privacy', 'chars[0-9\,]');
        $post->add_rules('file_photo', 'is_array');
        
        if ($post->validate()) {
            $form = $post->as_array();
            
            $pic = null;
            if (isset($form["file_photo"])) {
                $pic = array_filter(array_values($form["file_photo"]));
                if (count($pic) > 5) {
                    $this->send_response(400, NULL, "file_photo:图片最多传5张");
                }
            }
            
            if (isset($form["privacy"])) {
                $form["privacy"] = array_reduce(explode(",", $form["privacy"]), function ($v, $w){return $v += $w;});
            }
            
            if (empty($form["city"])) {
                $form["city"] = self::city_visitors();
            }
            $form["client"] = $this->get_source();
            
            if (isset($form["create_at"])){
                unset($form["create_at"]);
            }
            
            if (isset($form["user_id"])){
                unset($form["user_id"]);
            }
            
            if (isset($form["name"])) {
                unset($form["name"]);
            }
            
            $status = $this->model->create($this->user_id, $form, $pic);
            if ($status) {
                // xxx mq推送 只推送在线的~
                $this->send_response(200);
            } else {
                $this->send_response(500, null, "发布失败");
            }
        } else {
            foreach ($post->errors('life_service_tip_msg') as $key=>$val) {
                $this->send_response(400, NULL, "$key:$val");
            }
            //$this->send_response(400, array("error" => $post->errors('life_service_tip_msg')));
        }
    }

    /**
     * 编辑某条资讯
     * @method POST|PUT
     * 
     * @access public
     * @return void
     */
    public function edit()
    {
        if ($this->get_method() != 'POST' && $this->get_method() != 'PUT') {
            $this->send_response(405, null, '请求的方法不存在');
        }
        
        $id = ( int )$this->uri->segment(3);
        if (!$id) {
            $this->send_response(400, null, '输入有误');
        }
        
        $result = $this->model->get_detail($id);
        if (!$result || $result["user_id"] != $this->user_id) {
            $this->send_response(400, null, '输入有误');
        }
        
        $post = $this->get_data();
        
        $post = new Validation($post);
        $post->pre_filter('trim');
        $post->pre_filter('html::specialchars');
        $post->add_rules('title', 'required', 'length[2,60]');
        // $post->add_rules('description', 'required', 'length[1,255]');
        $post->add_rules('category', 'required', 'chars[1,2,3,4]');
        $post->add_rules('type', 'required', 'chars[1,2]');
        $post->add_rules('price', 'required', 'numeric');
        $post->add_rules('privacy', 'chars[0-9\,]');
        $post->add_rules('file_photo', 'is_array');
        
        if ($post->validate()) {
            $form = $post->as_array();
            
            $pic = null;
            if (isset($form["file_photo"])) {
                $pic = array_filter(array_values($form["file_photo"]));
                if (count($pic) > 5) {
                    $this->send_response(400, NULL, "file_photo:图片最多传5张");
                }
            }
            
            if (isset($form["privacy"])) {
                $form["privacy"] = array_reduce(explode(",", $form["privacy"]), function ($v, $w){return $v += $w;});
            }
            
            if (isset($form["city"]) && empty($form["city"])) {
                unset($form["city"]);
            }
            
            $status = $this->model->edit($id, $this->user_id, $form, $pic);
            if ($status) {
                $this->send_response(200);
            } else {
                $this->send_response(500, null, "修改失败");
            }
        } else {
            foreach ($post->errors('life_service_tip_msg') as $key=>$val) {
                $this->send_response(400, NULL, "$key:$val");
            }
            //$this->send_response(400, array("error" => $post->errors('life_service_tip_msg')));
        }
    }

    /**
     * 取得某条资讯详细内容
     * @method GET
     * 
     * @access public
     * @return void
     */
    public function detail()
    {
        $id = ( int )$this->uri->segment(3);
        if (!$id) {
            $this->send_response(400, null, '输入有误');
        }
        
        $result = $this->model->get_detail($id);
        if (!$result) {
            $this->send_response(404, null, '未找到');
        }
        
        /**
         * $result["description"] = preg_replace_callback('#\b(https?)://[-A-Z0-9+&@\#/%?=~_|!:,.;]*[-A-Z0-9+&@\#/%=~_|]#i', function (&$matches){
         * return "<a href=\"{$matches[0]}\" target=\"_blank\">{$matches[0]}</a>";
         * }, $result["description"]
         * );
         */
         
        $result["id"] = (int)$result["object_id"];
        $result["price"] = (float)$result["price"];
        $result["user_id"] = (int)$result["user_id"];
        $result["category"] = (int)$result["category"];
        $result["type"] = (int)$result["type"];
        $result["privacy"] = (int)$result["privacy"];
        $result["create_at"] = (int)$result["create_at"];
        $result["modify_at"] = (int)$result["modify_at"];
        $result["status"] = (int)$result["status"];
        $result["status_at"] = (int)$result["status_at"];
        $result["longitude"] = (float)$result["longitude"];
        $result["latitude"] = (float)$result["latitude"];
        $result["client"] = (int)$result["client"];
        
        foreach ($result["pic"] as &$v) {
            unset($v["user_id"], $v["object_id"], $v["create_at"]);
        }
        unset($result["object_id"]);
        
        $this->send_response(200, $result);
    }

    /**
     * 取得某条资讯摘要内容
     * @method GET
     * 
     * @access public
     * @return void
     */
    public function brief()
    {
        $id = ( int )$this->uri->segment(3);
        if (!$id) {
            $this->send_response(400, null, '输入有误');
        }
        
        $result = $this->model->get_detail($id, 'mongodb');
        if (!$result) {
            $this->send_response(404, null, '未找到');
        }
        
        $pic = array();
        foreach ($result["pic"] as $v) {
            $pic[] = $v["src"];
        }
        
        $data = array(
                "user_id" => (int)$result["user_id"], 
                "name" => $result["name"], 
                "id" => (int)$result["object_id"], 
                "status" => (int)$result["status"], 
                "pic" => $pic, 
                "longitude" => (float)$result["longitude"], 
                "latitude" => (float)$result["latitude"], 
                "text" => $this->type[$result["category"]][$result["type"]] . $result["title"] . "，" . strip_tags($result["price"]) . $this->unit[$result["category"]] ."，" . $result["brief"], 
                "create_at" => (int)$result["create_at"], 
                "client" => $result["client"]
        );
        
        $this->send_response(200, $data);
    }

    /**
     * 改变某条资讯的交易状态
     * @method POST|PUT
     * 
     * @access public
     * @return void
     */
    public function change_status()
    {
        if ($this->get_method() != 'POST' && $this->get_method() != 'PUT') {
            $this->send_response(405, null, '请求的方法不存在');
        }
        
        $post = $this->get_data();
        
        // 状态:0，1过期，2取消，3完成交易
        if (isset($post["id"], $post["status"]) && in_array($post["status"], array("0", "1", "2", "3"))) {
            $status = $this->model->ch_market_info_status($post["id"], $this->user_id, $post["status"]);
            if ($status) {
                $this->send_response(200);
            } else {
                $this->send_response(500, null, "设置失败");
            }
        }
        
        $this->send_response(400, null, '输入有误');
    }

    /**
     * 删除某条资讯
     * @method POST|DELETE
     * 
     * @access public
     * @return void
     */
    public function destroy()
    {
        if ($this->get_method() != 'POST' && $this->get_method() != 'DELETE') {
            $this->send_response(405, null, '请求的方法不存在');
        }
        
        $post = $this->get_data();
        $id = isset($post["id"]) ? ( int )$post["id"] : null;
        if ($id) {
            if ($this->model->destroy($this->user_id, $id)) {
                $this->send_response(200);
            } else {
                $this->send_response(500, null, "删除失败");
            }
        }
        
        $this->send_response(400, null, '输入有误');
    }
    
    /**
     * 用户添加某资讯为隐藏
     * @method POST
     * 
     * @access public
     * @return void
     */
    public function create_hide()
    {
        if ($this->get_method() != 'POST') {
            $this->send_response(405, null, '请求的方法不存在');
        }
        
        $post = $this->get_data();
        if (empty($post["id"])) {
            $this->send_response(400, null, '输入有误');
        }
        
        $this->model->create_hidden($object_id, $this->uid);
        
        $this->send_response(200, array("status"=>(bool)$status));
    }
    
    /**
     * 用户取消某资讯的隐藏状态
     * @method POST|DELETE
     * 
     * @access public
     * @return void
     */
    public function destroy_hide()
    {
        if ($this->get_method() != 'POST' && $this->get_method() != 'DELETE') {
            $this->send_response(405, null, '请求的方法不存在');
        }
        
        $post = $this->get_data();
        if (empty($post["id"])) {
            $this->send_response(400, null, '输入有误');
        }
        
        $this->model->destroy_hidden($object_id, $this->uid);
        
        $this->send_response(200, array("status"=>(bool)$status));
    }

    /**
     * 删除某资讯中的某张图片
     * @method POST|DELETE
     * 
     * @access public
     * @return void
     */
    public function delimage()
    {
        if ($this->get_method() != 'POST' && $this->get_method() != 'DELETE') {
            $this->send_response(405, null, '请求的方法不存在');
        }
        //$this->send_response(510, null, '方法未实现');
        
        $post = $this->get_data();
        if (isset($post["id"], $post["src"])) {
            $status = $this->model->delete_img($post["id"], $post["src"], $this->user_id);
            
            if ($status) {
                $this->send_response(200);
            } else {
                $this->send_response(500, null, "删除失败");
            }
        }
        
        $this->send_response(400, null, '输入有误');
    }
    
    /**
     * 取得当前用户有几条未读新资讯，前端主动拉用
     * @method GET
     * 
     * @access public
     * @return void
     */
    public function life_new_count()
    {
        $city = self::city_visitors();
        
        $total = $this->model->newinfo_count($this->user_id, $city);
        
        $this->send_response(200, array("count" => $total));
    }

    /**
     * 取得当前登陆者所在城市
     * 
     * @access private
     * @return void
     */
    private static function city_visitors()
    {
        return '福州';
    }
    
    /**
     * 对某资讯得行评论
     * @method POST
     * 
     * @access public
     * @return void
     */
    public function comment_create()
    {
        if ($this->get_method() != 'POST') {
            $this->send_response(405, null, '请求的方法不存在');
        }
        
        $post = $this->get_data();
        
        $object_id = isset($post["id"]) ? ( int )$post["id"] : null;
        if (!$object_id) {
            $this->send_response(400, NULL, "输入有误");
        }
        
        $text = isset($post["text"]) ? trim($post["text"]) : null;
        if (!$text) {
            $this->send_response(400, NULL, "输入有误");
        }
        
        $parent_id = isset($post["parent_id"]) ? ( int )$post["parent_id"] : null;
        
        $result = $this->model->create_comment($object_id, $text, $this->user_id, $parent_id, $this->get_source());
        
        $this->send_response(200, array("status"=>$result));
    }
    
    /**
     * 删除某资讯的一条评论
     * @method POST|DELETE
     * 
     * @access public
     * @return void
     */
    public function comment_destroy()
    {
        if ($this->get_method() != 'POST' && $this->get_method() != 'DELETE') {
            $this->send_response(405, null, '请求的方法不存在');
        }
        
        $post = $this->get_data();
        
        $object_id = isset($post["id"]) ? ( int )$post["id"] : null;
        if (!$object_id) {
            $this->send_response(400, NULL, "输入有误");
        }
        
        $commentid = isset($post["commentid"]) ? ( int )$post["commentid"] : null;
        if (!$commentid) {
            $this->send_response(400, NULL, "输入有误");
        }
        
        $result = $this->model->destroy_comment($object_id, $commentid, $this->user_id);
        
        $this->send_response(200);
        //if ($result) {
        //    $this->send_response(200);
        //}
    }
    
    /**
     * 取得某资讯的评论列表
     * @method GET
     * 
     * @access public
     * @return void
     */
    public function comment_list()
    {
        $object_id = (int)$this->input->get('id', 0);
        if (!$object_id) {
            $this->send_response(400, NULL, "输入有误");
        }
        
        $start = (int)$this->input->get('page', 1);
        if ($start <= 0) {
            $this->send_response(400, NULL, "输入有误");
        }

        $pos = (int)$this->input->get('pagesize', 20);
        if ($pos <= 0 || $pos > self::MAX_PAGESIZE) {
            $this->send_response(400, NULL, "输入有误");
        }
        
        $order = (int)$this->input->get('order', 1); //-1时分页是按最后往前分
        if (-1 == $order) {
            $start = -abs($start * $pos);
        } else {
            $start = abs(($start - 1) * $pos);
        }
        
        $result = $this->model->get_comments($object_id, $start, $pos);
        
        if ($result) {
            $pic = array();
            foreach ($result["pic"] as $v) {
                $pic[] = $v["src"];
            }
            
            if ($result["price"] < 0){
                $price = "价格面议";
            } else {
                $price = $result["price"] .$this->unit[$result["category"]];
            }
            
            $data = array(
                    "user_id" => (int)$result["user_id"], 
                    "name" => $result["name"], 
                    "id" => (int)$result["object_id"], 
                    "status" => (int)$result["status"], 
                    "images" => $pic, 
                    "longitude" => (float)$result["longitude"], 
                    "latitude" => (float)$result["latitude"], 
                    "text" => $this->type[$result["category"]][$result["type"]] . $result["title"] . "，" . $price ."，" . $result["brief"], 
                    "create_at" => (int)$result["create_at"], 
                    "client" => $result["client"],
                    "comment_count" => (int)$result["c_count"], 
                    "comments" => $result["comment"]
            );
            unset($result);
            
            $this->send_response(200, $data);
        } else {
            $this->send_response(204);
        }
    }
    
    /**
     * 收藏某条资讯
     * @method POST
     * 
     * @access public
     * @return void
     */
    public function create_favorite()
    {
        if ($this->get_method() != 'POST' && $this->get_method() != 'DELETE') {
            $this->send_response(405, null, '请求的方法不存在');
        }
        
        $post = $this->get_data();
        
        if (empty($post['id'])) {
            $this->send_response(400, NULL, "输入有误");
        }
        
        $status = $this->model->add_favorite($post['id'], $this->user_id);
        
        $this->send_response(200, array("status"=>(bool)$status));
    }
    
    /**
     * 删已收藏的某条资讯
     * @method POST|DELETE
     * 
     * @access public
     * @return void
     */
    public function destroy_favorite()
    {
        if ($this->get_method() != 'POST' && $this->get_method() != 'DELETE') {
            $this->send_response(405, null, '请求的方法不存在');
        }
        
        $post = $this->get_data();
        
        if (empty($post['id'])) {
            $this->send_response(400, NULL, "输入有误");
        }
        
        $status = $this->model->destroy_favorite($post['id'], $this->user_id);
        
        $this->send_response(200, array("status"=>(bool)$status));
    }
} 


