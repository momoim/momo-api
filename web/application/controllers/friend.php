<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * [MOMO API] (C)1999-2011 ND Inc.
 * 投票控制器文件
 */
class Friend_Controller extends Controller
{

    // Allow all controllers to run in production by default
    const ALLOW_PRODUCTION = TRUE;

    const MAX_PAGESIZE = 200;

    protected $model;

    public function __construct()
    {
        parent::__construct();

        $this->model = Friend_Model::instance();
    }

    /**
     * 查看好友列表
     * @method GET
     */
    public function index()
    {
        if ($this->get_method() != 'GET') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }

        $page = ( int )$this->input->get("page", 1);
        if ($page <= 0) {
            $this->send_response(400, NULL, "输入有误");
        }

        $pos = ( int )$this->input->get("pagesize", self::MAX_PAGESIZE);
        if ($pos < 0 || $pos > self::MAX_PAGESIZE) {
            $this->send_response(400, NULL, "输入有误");
        }

        $start = ($page - 1) * $pos;

        $result = $this->model->getAllFriendIDs($this->user_id, FALSE);
        $total = count($result);
        if (!$total) {
            $this->send_response(200, array("count" => $total, "start" => 0, "pos" => 0, "data" => array()));
        }

        $fids = array_slice($result, $start, $pos);
        $res = array();
        foreach ($fids as $fid) {
            $res[] = array(
                'id' => $fid,
                'avatar' => sns::getavatar($fid),
                'name' => sns::getrealname($fid)
            );
        }

        $this->send_response(200, array("count" => $total, "start" => $start, "pos" => $pos,
            "data" => $res));
    }


    /**
     * 判断是否是好友
     * @method GET
     */
    public function isfriend()
    {
        if ($this->get_method() != 'GET') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }

        $uid = $this->uri->segment(3);
        if ($uid = ( int )$uid) {
            $status = $this->model->check_isfriend($this->user_id, $uid);
            $this->send_response(200, array("status" => $status));
        }
        $this->send_response(400, NULL, "输入有误");
    }

    public function add()
    {
        if ($this->get_method() != 'GET') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }

        $uid = $this->uri->segment(3);
        if ($uid = ( int )$uid) {
            $status = $this->model->add_friend($this->user_id, $uid);
            $this->send_response(200, array("status" => $status));
        }
        $this->send_response(400, NULL, "输入有误");
    }

    /**
     * 精确查找用户
     * @method GET
     */
    public function search()
    {
        if ($this->get_method() != 'GET') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }

        $name = $this->input->get("name");
        $name = trim($name);

        if (empty($name)) {
            $this->send_response(400, NULL, "name不能为空");
        }

        $page = ( int )$this->input->get("page", 1);
        if ($page <= 0) {
            $this->send_response(400, NULL, "输入有误");
        }

        $pos = ( int )$this->input->get("pagesize", 20);
        if ($pos < 0 || $pos > self::MAX_PAGESIZE) {
            $this->send_response(400, NULL, "输入有误");
        }
        $start = ($page - 1) * $pos;

        $total = $this->model->get_user_counts_byname($name);
        if (!$total) {
            $this->send_response(200, array("count" => $total, "start" => 0, "pos" => 0, "data" => array()));
        }

        $result = $this->model->get_users_byname($name, $start, $pos);

        $tmp = array();
        $curYear = date('Y');
        //@FIXME birthplace residence 现只处理了中国
        $zipmap = include Kohana::find_file('vendor', 'cityarray', true);
        $residence = "";

        foreach ($result as $key => $val) {
            //居住地
            if (!empty($val["resideprovince"])) {
                $residence = isset($zipmap["province"][$val["resideprovince"]]) ? $zipmap["province"][$val["resideprovince"]] : "";
            }

            if (!empty($val["residecity"])) {
                $residence .= isset($zipmap["city"][$val["residecity"]]) ? (" " . $zipmap["city"][$val["residecity"]]) : "";
            }

            $tmp[$key]["id"] = (int)$val["uid"];
            $tmp[$key]["name"] = $val["realname"];
            $tmp[$key]["gender"] = (int)$val["sex"];
            $tmp[$key]["age"] = (string)$val["birthyear"] > 0 ? ($curYear - $val["birthyear"]) : '';
            $tmp[$key]["city"] = $residence;
            $tmp[$key]["company"] = $val["company"];
            $tmp[$key]["school"] = $val["college"];
            $tmp[$key]["zodiac"] = $val["astro"];
            $tmp[$key]["sign"] = $val["sign"];
            $tmp[$key]["avatar"] = sns::getavatar($val["uid"]);
            $tmp[$key]["modified_at"] = $val["updatetime"];//date('D M d H:i:s O Y', $val["updatetime"]);
        }
        unset($result);

        $this->send_response(200, array("count" => $total, "start" => $start, "pos" => count($tmp), "data" => $tmp));
    }
}