<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * 日记控制器
 * 
 * 
 * @package Blog_Controller
 * @author Yufeng <ivwsai@gmail.com>
 * @copyright (c) 2010-2011 MOMO Team
 */
 
class Blog_Controller extends Controller {

    // Allow all controllers to run in production by default
    const ALLOW_PRODUCTION = TRUE;

    public function __construct()
    {
        parent::__construct();
        
        $this->model = Blog_Model::instance();
        
        $this->privacys = array(/*0=>'任何人可见',*/ 1=>'所有好友可见', /*4=>'指定好友可见', 2=>'凭密码访问',*/ 3=>'仅自己可见', 5=>'仅指定群可见');
        
        //$this->user_id = 10614401;
    }
    
    /**
     * 查看日记列表
     * @method GET
     */
    public function index()
    {
        if($this->get_method() != 'GET') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
    }
    
    /**
     * 查看单篇日记(.html返回的是HTML结构)
     * @method GET
     */
    public function show()
    {
        if($this->get_method() != 'GET') {
            echo "404 Not Found.";
            return null;
        }
        
        $path_parts = pathinfo($this->uri->string());
        if (!$path_parts) {
            echo "404 Not Found.";
            return null;
        }
        
        if ($path_parts["extension"] == 'html') {
            
            $id = (int)$path_parts["filename"];
            $result = $this->model->get_oneblog($id);
            if ($result) {
                $data = array();
                //访问权限
                $data['seepower'] = 'allow';    //允许访问
                $data['denyCal'] = '';
                
                // 如果非本人
                if ($result['uid'] != $this->user_id) {
                    $data = self::seePower($result);
                    
                    //可访问时 才记录访问者
                    if ('allow' == $data['seepower']) {
                        $this->model->diary_read($this->user_id, $id, time());
                    }
                }
                
                $data['row'] = $result;
                $data['privacys'] = $this->privacys;
                $data['uid'] = $this->user_id;
                $data['blogid'] = $id;
                
                $data['cateName'] = '';
                $data['content'] = '';
                
                //本人或允许访问
                if ($result['uid'] == $this->user_id || 'allow' == $data['seepower']) {
                    $data['cateName'] = $result['dtype']==1 ? '原创' : '转载';
                    
                    //取得正文内容
                    $result = $this->model->get_blog_content($id);
                    if ($result) {
                        require_once Kohana::find_file('vendor', 'htmlpurifier/HTMLPurifier.auto', TRUE);    
                        require 'HTMLPurifier.func.php';
                        
                        // Set configuration
                        $config = HTMLPurifier_Config::createDefault();
                        $config->set('Core.Encoding', 'UTF-8'); // replace with your encoding
                        $config->set('HTML.Doctype', 'XHTML 1.0 Transitional'); // replace with your doctype
                        $config->set('HTML.TidyLevel', 'light'); // Only XSS cleaning now
                        $config->set('Attr.AllowedClasses', array()); // 设置允许使用的class名
                        //$config->set('HTML.Allowed', 'p,b,a[href],img[src],u,i,ul,ol,li,div,table,tr,th,td'); //设置允许的html元素
                        $config->set('HTML.ForbiddenElements', array('span')); // 设置不允许使用的html元素
                        
                        $config->set('HTML.AllowedAttributes', array('src', 'href')); // 设置允许使用的属性
                        
                        // Run HTMLPurifier
                        $data['content'] = HTMLPurifier($result['content'], $config);
                        
                        //$data['content'] = nl2br(strip_tags($result['content']));
                        $data['row']['quoturl'] = $result['quoturl'];
                    } else {
                        $data['content'] = "";
                        $data['row']['quoturl'] = "";
                    }
                    unset($result);
                }
                
                $view = new View('blog/detail', $data);
                $view->render(true);
                return null;
            }
            echo "404 Not Found.";
            return null;
            //$this->send_response(404, NULL, "输入有误");
        }
    }
    
    /**
     * 创建日记
     * @method POST
     */
    public function create()
    {
        if($this->get_method() != 'POST') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        
        $post = $this->get_data();
    }
    
    /**
     * 更新日记
     * @method POST | PUT
     */
    public function update()
    {
        if($this->get_method() != 'POST' && $this->get_method() != 'PUT') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        
        $post = $this->get_data();
        
    }
    
    /**
     * 删除日记
     * @method POST | DELETE
     */
    public function destroy()
    {
        if($this->get_method() != 'POST' && $this->get_method() != 'DELETE') {
            $this->send_response(405, NULL, '请求的方法不存在');
        }
        
        $post = $this->get_data();
        
    }
    
    
    /**
     * 判断日记查看权限
     * 
     * @access private
     * @param array $result
     * @return array
     */
    private function seePower(&$result)
    {
        $data['seepower'] = 'allow';    //允许访问
        $data['denyCal'] = '';
        
        if (1 == $result['draft']) { //如果是草稿
            $data['seepower'] = 'deny';
            $data['denyCal'] = '链接出错';
            return $data;
        }
        
        //$account = new Account_Model();
        //$flag = $account->getUserLimit($result['uid'], $this->user_id, 'diarypermit');
        $flag = 0;
        if ($flag > 0) {
            $data['seepower'] = 'deny';    // 如果不允许访问
            $data['denyCal'] = '日记设置为藏';
        } else {
            switch ($result['privacy']) {
                case 0 : //任何人可见 public
                    $data['seepower'] = 'allow';
                    break;
                
                case 1 : //所有好友可见 protect
                    $list = Friend_Model::instance()->getAllFriendIDs($this->user_id);
                    if (empty($list) || !in_array($result['uid'], $list)) {
                        $data['seepower'] = 'deny';
                        $data['denyCal'] = '该日记仅好友能访问';
                    }
                    break;
                
                case 2 : //凭密码访问 encrypt
                    if ($this->input->get('encrypt') !== $result['password']) {
                        $data['seepower'] = 'encrypt';
                        $data['denyCal'] = '该日记凭密码访问';
                    }
                    break;
                
                case 3 : //仅自己可见 private
                    $data['seepower'] = 'deny';
                    $data['denyCal'] = '该日记只有作者可见';
                    break;
                
                case 4 : //指定好友可见 appoint
                    $data['seepower'] = 'deny';
                    $data['denyCal'] = '该日记只有指定者可见';
                    if ($result['appoint']) {
                        if (in_array($this->user_id, explode(',', $result['appoint']))) {
                            //是否还是好友
                            $list = Friend_Model::instance()->getAllFriendIDs($this->user_id);
                            if (!empty($list) && in_array($result['uid'], $list)) {
                                $data['seepower'] = 'allow';
                            }
                        }
                    }
                    break;
                    
                case 5 : //仅指定群的群成员可见 appoint
                    $data['seepower'] = 'deny';
                    $data['denyCal'] = '该日记只有指定群的成员可见';
                    break;
                default:
                    $data['seepower'] = 'deny';
                    $data['denyCal'] = '找不到对应权限';
            }
        }

        //个人身份不可见时并则有同步到群标识时  则以群成员身份查看是否可见  (好友可见时以群成员身份查看才成立)
        if (('deny' == $data['seepower']) 
            && !empty($result['appoint_group'])
            && (1 == $result['privacy'] || 4 == $result['privacy'] || 5 == $result['privacy'])) {
            
            $a_tmp = array();
            $appoint_group = explode(',', $result['appoint_group']);
            
            $group = new Group_Model();
            $ag = $group->getUserAllGroup($result['uid']);//作者所在群组
            foreach ($ag as $v) {
                if (in_array($v['gid'], $appoint_group)) {
                    $a_tmp[] = $v['gid'];//指定群主ID
                }
            }
            
            if (!empty($a_tmp)) {
                $vg = $group->getUserAllGroup($this->user_id);//访问者所在群组
                foreach ($vg as $v) {
                    if (in_array($v['gid'], $a_tmp)) {
                        $data['seepower'] = 'allow';
                        $data['denyCal'] = '以群成员身份查看';
                        break;
                    }
                }
            }
        }
        
        return $data;
    }
}
