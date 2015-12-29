<?php defined('SYSPATH') or die('No direct access allowed.');
/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * 图片控制器文件
 */
class Photo_Controller extends Controller implements FS_Gateway_Core {

    public function __construct() {
        //必须继承父类控制器
        parent::__construct();
        Core::setUserID($this->user_id);
        Core::initGridFS('photo');
    }
    /**
     * 
     * 生成图片地址的公共调用函数
     * @param mixed $pid 图片id
     * @param int $size 图片大小
     * @return array 
     */
    public static function geturl($pid, $size = '') {
        return Models\ Photo::instance()->geturi($pid, $size);
    }

    public static function getinfo($pid) {
        return Models\ Photo::instance()->getInfo($pid);
    }
    /**
     * 
     * 根据uid批量获取头像数据公共调用函数
     * @param mixed $uid 用户id
     * @param int $size 头像大小
     * @param boolean $needmeta 是否取得头像的元数据
     * @return array 如果$uid是单个则直接返回他的头像地址
     */
    public static function getavatar($uid = '', $size = 130, $needmeta = FALSE) {
        return Models\ Photo::instance()->getavatar($uid, $size, $needmeta);
    }
    /**
     * 
     * POST avatar.json
     * {
     * 	"id":[],
     * 	"size":130
     * }
     * @param mixed $uid
     * @param int $size
     */
    public function avatar() {
        $data = $this->get_data();
        $uid = $data['id'];
        $size = $data['size'];
        $result = Models\ Photo::instance()->getavatar($uid, $size);
        $this->response(ResponseType::PHOTO_GET_OK, '', $result);
    }
    /**
     * 
     * 根据图片地址获取原图的md5
     * @param mixed $url 图片地址
     * @return array
     */
    public static function getoriginmd5($url) {
        if(!is_array($url)) {
            $url = array($url);
        }
        $r = array();
        foreach($url as $u) {
            if($u && $data = @file_get_contents($u)) {
                $r[] = md5($data);
            } else {
                $r[] = '';
            }
        }
        return $r;
        //return Models\Photo::instance()->getOriginMD5($url);
    }

    private function _processUpload($uploader, $cid = 0) {
        $photoModel = new Models\ Photo();
        if($photoModel->create($uploader, array('cid' => $cid))) {
            $result['id'] = $photoModel->get_pid();
            $result['md5'] = $photoModel->md5;
            $result['width'] = $photoModel->width;
            $result['height'] = $photoModel->height;
            $result['mime'] = $photoModel->mime;
            $result['is_animated'] = $photoModel->is_animated;
            
            $imgurls = $photoModel->geturi($result['id'], 130);
            $result['src'] = $imgurls[0];
            $thumb = new Thumb();
            $thumb->resize(NULL, $result['id'], 130);
            $client = new GearmanClient();
            $client->addServers(Core::config('job_servers'));
            $client->addTaskLowBackground("thumbnail", serialize(array($result['id'], 320)));
            $client->addTaskLowBackground("thumbnail", serialize(array($result['id'], 780)));
            $client->addTaskLowBackground("thumbnail", serialize(array($result['id'], 1600)));
            @$client->runTasks();
            return $result;
        } else {
            return FALSE;
        }
    }
    /**
     * 
     * 单步上传接口，分类代码： [0相册照片,1头像照片,2联系人照片,3日记图片,4生活信息图片,5私聊图片]
     * GET upload.json?category={分类id}&redirect={完成后跳转地址}
     * FILES
     */
    public function upload() {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Methods:POST, GET, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers:X-FILENAME");
        
        if(strtolower($_SERVER["REQUEST_METHOD"]) == "options"){
            exit;
        }
        
        $cid = intval($_GET['category']);
        $redirect = $_GET['redirect'] ? urldecode($_GET['redirect']) : '';
        $uploader = new Uploader();
        if(!$uploader->upload()) {
            $this->response(ResponseType::PHOTO_UPLOAD_ERROR_INPUT, '', '', $redirect);
        }
        //不是图片类型
        if($uploader->getType() !== Uploader::FILETYPE_IMAGE) {
            $this->response(ResponseType::PHOTO_ERROR_IMAGETYPE, '', '', $redirect);
        }
        //文件超过5M
        if($uploader->getLength() > Core::config('photo_max_size')) {
            $this->response(ResponseType::PHOTO_ERROR_IMAGESIZE, '', '', $redirect);
        }
        if($result = $this->_processUpload($uploader, $cid)) {
            $this->response(ResponseType::PHOTO_UPLOAD_OK, '', $result, $redirect);
        } else {
            $this->response(ResponseType::PHOTO_UPLOAD_ERROR_SERVER, '', '', $redirect);
        }
    }
    /**
     * 
     * 分段上传接口
     * 第一步：
     * POST bp_upload.json
     * {
     * 	"md5":"照片md5",
     * 	"size":"尺寸",
     * 	"category":"分类id",
     *  "filename":"文件名"
     * }
     * 
     * 第二部：
     * GET bp_upload.json?upload_id={临时上传id}&offset={碎片偏移量}&length={分段大小}
     * FILES
     */
    public function bp_upload() {
        $upload_id = isset($_GET['upload_id']) ? $_GET['upload_id'] : 0;
        if(!$upload_id) {
            //创建临时文件
            $data = $this->get_data();
            if(isset($data['md5']) && isset($data['size'])) {
            } else {
                $this->response(ResponseType::ERROR_LACKPARAMS);
            }
            if($data['size'] > Core::config('photo_max_size')) {
                $this->response(ResponseType::PHOTO_ERROR_IMAGESIZE);
            }
            $tempModel = new Models\ Temp($this->user_id);
            $tempModel->md5 = strtolower($data['md5']);
            $tempModel->size = intval($data['size']);
            $tempModel->type = Uploader::FILETYPE_IMAGE;
            //图片应用
            $tempModel->cid = intval($data['category']);
            $tempModel->filename = $data['filename'];
            $photoModel = new Models\ Photo();
            $r = $photoModel->geturiByMD5(array($tempModel->md5), 130, TRUE);
            //自己曾经上传过
            if($r[0] && $r[0]['id']) {
                $result['id'] = $r[0]['id'];
                $result['src'] = $r[0]['src'];
                $result['md5'] = $tempModel->md5;
                $result['uploaded'] = TRUE;
                $this->response(ResponseType::PHOTO_BPUPLOAD_OK, '', $result);
            } else {
                if($tempModel->create()) {
                    $result['upload_id'] = $tempModel->get_upload_id();
                    $result['offset'] = 0;
                    $result['uploaded'] = FALSE;
                    $this->response(ResponseType::PHOTO_BPUPLOAD_OK_CREATETEMP, '', $result);
                }
            }
            $this->response(ResponseType::PHOTO_BPUPLOAD_ERROR_CREATETEMP);
        } else {
            //上传第二步
            $offset = isset($_GET['offset']) ? intval($_GET['offset']) : NULL;
            $length = isset($_GET['length']) ? intval($_GET['length']) : NULL;
            /*
            if(isset($_GET['offset'])) {
            	$offset=intval($_GET['offset']);
	        }else{
	        	$this->response(ResponseType::ERROR_LACKPARAMS);
	        }
	        */
            $tempModel = new Models\ Temp($this->user_id);
            if(!$tempModel->getTemp($upload_id)) {
                $this->response(ResponseType::PHOTO_BPUPLOAD_ERROR_NOTEMP);
            }
            //追加文件碎片
            if($tempModel->tmp_source->length < $tempModel->size) {
                if($_FILES) {
                    foreach($_FILES as $upfile) {
                        $in_path = $upfile['tmp_name'];
                    }
                } else {
                    $in_path = 'php://input';
                }
                $tempModel->append($in_path, $offset, $length);
            }
            //验证临时文件完整性
            $finish = FALSE;
            if($tempModel->tmp_source->length >= $tempModel->size) {
                //if($tempModel->md5 && $tempModel->md5 == $tempModel->tmp_source->md5){
                if($tempModel->size == $tempModel->tmp_source->length) {
                    $finish = TRUE;
                } else {
                    $tempModel->destroy();
                    $this->response(ResponseType::PHOTO_BPUPLOAD_ERROR_DAMAGETEMP);
                }
            }
            if($finish) {
                $tmp_file = $tempModel->tmp_source->downBuffer();
                //临时文件是否可访问
                if($tmp_file) {
                    $uploader = new Uploader();
                    $uploader->process($tmp_file, '', $tempModel->size, $tempModel->md5);
                } else {
                    $tempModel->destroy();
                    $this->response(ResponseType::PHOTO_BPUPLOAD_ERROR_NOTEMPSOURCE);
                }
                //不是图片类型
                if($uploader->getType() !== Uploader::FILETYPE_IMAGE) {
                    $tempModel->destroy();
                    $this->response(ResponseType::PHOTO_ERROR_IMAGETYPE);
                }
                //文件超过5M
                if($uploader->getLength() > Core::config('photo_max_size')) {
                    $tempModel->destroy();
                    $this->response(ResponseType::PHOTO_ERROR_IMAGESIZE);
                }
                //上传临时文件成功否
                if($result = $this->_processUpload($uploader, $tempModel->cid)) {
                    $tempModel->destroy();
                    $result['uploaded'] = TRUE;
                    $this->response(ResponseType::PHOTO_BPUPLOAD_OK, '', $result);
                } else {
                    $tempModel->destroy();
                    $this->response(ResponseType::PHOTO_BPUPLOAD_ERROR_SERVER);
                }
            } else {
                //需要继续上传文件碎片
                $result['offset'] = $tempModel->tmp_source->length;
                $result['uploaded'] = FALSE;
                $this->response(ResponseType::PHOTO_BPUPLOAD_OK_CONTINUE, '', $result);
            }
        }
    }
    /**
     * 
     * 更新头像接口
     * POST update_avatar.json
     * {
     * 	"pid":"原始照片的id",
     * 	"middle_content":"大头像base64数据"
     *  "original_content":"原图base64数据"
     * }
     */
    public function update_avatar() {
        if($_POST['data']) {
            //flash截取头像
            $data = json_decode($_POST['data'], TRUE);
        } else {
            $data = $this->get_data();
            //手机端
        }
        //如果是j2me平台直接取原图
        if($this->get_source() == 6) 
            $middle_data = @base64_decode($data['original_content']);
        else 
            $middle_data = @base64_decode($data['middle_content']);
        $pid = $data['pid'];
        //原图PID
        $original_data = @base64_decode($data['original_content']);
        //原图数据
        if($pid) {
            $photoModel = new Models\ Photo();
            if(!$photoModel->findOne($pid)) {
                $this->response(ResponseType::PHOTO_UPAVATAR_ERROR_INVALID);
            }
        }
        if(!$pid && $original_data) {
            $tmporigin = Core::tempname();
            file_put_contents($tmporigin, $original_data);
            $uploader = new Uploader();
            $uploader->process($tmporigin);
            //不是图片类型
            if($uploader->getType() !== Uploader::FILETYPE_IMAGE) {
                $this->response(ResponseType::PHOTO_ERROR_IMAGETYPE);
            }
            if($result = $this->_processUpload($uploader, 1)) {
                $pid = $result['id'];
            } else {
                $this->response(ResponseType::PHOTO_UPAVATAR_ERROR_INVALID);
            }
        }
        if(!$pid || !$middle_data) {
            $this->response(ResponseType::ERROR_LACKPARAMS);
        }
        $tmpfile = Core::tempname();
        if(!file_put_contents($tmpfile, $middle_data)) {
            $this->response(ResponseType::PHOTO_UPAVATAR_ERROR);
        }
        $uploader = new Uploader();
        $uploader->process($tmpfile);
        //不是图片类型
        if($uploader->getType() !== Uploader::FILETYPE_IMAGE) {
            $this->response(ResponseType::PHOTO_ERROR_IMAGETYPE);
        }
        $photoModel = new Models\ Photo();
        $updata['cid'] = 1;
        //我的头像相册
        $updata['oid'] = $pid;
        $updata['ctrl_type'] = 1;
        $updata['is_animated'] = 0;
        $updata['mtime'] = time();
        if($photoModel->create($uploader, $updata)) {
            $result['id'] = $photoModel->get_pid();
            $result['md5'] = $photoModel->md5;
            $imgurls = $photoModel->geturi($result['id'], 48);
            $result['src'] = $imgurls[0];
            list($set_avatar, $first_time) = $photoModel->setAvatar($photoModel->get_pid(), $updata['oid'], $updata['mtime']);
            if(!$set_avatar) {
                $this->response(ResponseType::PHOTO_UPAVATAR_ERROR);
            }
            $user_model = User_Model::instance();
            $member_field = array('updatetime' => time());
            if($first_time) {
                $sms_content = '您好，这是您第一次设置头像，系统赠送了100条短信给您';
                $user_model->present_sms($this->getUid(), 100, $sms_content, FALSE);
                $user_info = $user_model->get_user_info($this->getUid());
                $member_field['completed'] = $user_info['completed'] + 10;
            }
            //发送头像修改动态
            $feedModel = new Feed_Model();
            $accessory[] = array('id' => $result['id']);
            $feedModel->addFeed($this->user_id, 3, '更新头像', $this->get_source(), array(), array(), $accessory);
            //更新memberinfo表
            $user_model->update_user_info($this->getUid(), $member_field);
            $this->response(ResponseType::PHOTO_UPAVATAR_OK, '', $result);
            unlink($tmpfile);
        } else {
            $this->response(ResponseType::PHOTO_UPAVATAR_ERROR);
        }
    }
    /**
     * 
     * 图片旋转接口
     * GET rotate/:id.json?direction={方向}&size={尺寸}
     */
    public function rotate($pid) {
        $direction = intval($_GET['direction']);
        $size = intval($_GET['size']);
        $size = in_array($size, Core::config('photo_standard_type')) ? $size : 130;
        if($direction != - 1) 
            $direction = 1;
        if(!$pid) {
            $this->response(ResponseType::ERROR_LACKPARAMS);
        }
        $client = new GearmanClient();
        $client->addServers(Core::config('job_servers'));
        $client->setTimeout(3000);
        $result = @$client->doHigh("rotate", serialize(array($pid, $direction)));
        $result && $sid = @unserialize($result);
        if(!$sid) {
            $thumb = new Thumb();
            if($thumb->rotate(NULL, $pid, $direction)) {
                $sid = TRUE;
            }
        }
        if($sid) {
            $photoModel = new Models\ Photo();
            $imgurls = $photoModel->geturi($pid, $size);
            $return['src'] = $imgurls[0];
            $this->response(ResponseType::PHOTO_ROTATE_OK, '', $return);
        } else {
            $this->response(ResponseType::PHOTO_ROTATE_ERROR);
        }
    }
    /**
     * 分类id [0,1,2,3,4] 所有，头像，联系人头像，日记，生活信息
     * 
     * 更新接口
     * POST update/:id.json
     * {
     * 	"desc":'照片描述',
     * 	"category":'分类id'
     * }
     */
    public function update($pid) {
        $data = $this->get_data();
        if(!$data) 
            return FALSE;
        $updata = array();
        if($data['desc']) {
            $updata['desc'] = Core::htmlspecialchars_deep($data['desc']);
        }
        if($data['category']) {
            $updata['cid'] = intval($data['category']);
            if(!in_array($updata['cid'], array(0, 1, 2, 3, 4))) {
                $this->response(ResponseType::PHOTO_UPDATE_ERROR);
            }
        }
        $photoModel = new Models\ Photo();
        if($photoModel->findOne($pid, array('desc', 'cid', 'uid'))) {
            if($photoModel->uid != $this->user_id) {
                $this->response(ResponseType::PHOTO_UPDATE_ERROR);
            }
            if($photoModel->update($updata)) {
                $this->response(ResponseType::PHOTO_UPDATE_OK, '', $updata);
            } else {
                $this->response(ResponseType::PHOTO_UPDATE_ERROR);
            }
        } else {
            $this->response(ResponseType::PHOTO_UPDATE_ERROR_INVALID);
        }
    }
    /**
     * 
     * 批量删除接口
     * GET destroy/:id,:id,:id.json
     */
    public function destroy($pid) {
        $pidarr = explode(',', $pid);
        $photoModel = new Models\ Photo();
        if(!$pidarr) {
            $this->response(ResponseType::ERROR_LACKPARAMS);
        }
        $r = $photoModel->getInfo($pidarr);
        $success = $error = array();
        foreach($pidarr as $i => $id) {
            if($r[$i] && $r[$i]['uid'] == $this->user_id) 
                $success[] = $r[$i]['pid'];
            else 
                $error[] = $r[$i]['pid'];
        }
        $return = array('success' => $success, 'error' => $error);
        if($photoModel->deleteBatch($pidarr)) {
            $this->response(ResponseType::PHOTO_DELETE_OK, '', $return);
        }
        $this->response(ResponseType::PHOTO_DELETE_ERROR);
    }
    /**
     * 
     * 获得单张照片的信息
     * GET :id.json
     */
    public function index($pid) {
        $photoModel = new Models\ Photo();
        $rows = $photoModel->getInfo($pid);
        if($row = $rows[0]) {
            $result = array('id' => $row['pid'], 'src' => $row['src'], 'name' => $row['name'], 'desc' => $row['desc'], 'category' => $row['cid'], 'uid' => $row['uid'], 'created_at' => $row['created_at'],);
            $this->response(ResponseType::PHOTO_GET_OK, '', $result);
        } else {
            $this->response(ResponseType::PHOTO_GET_ERROR);
        }
    }
    /**
     * 获取照片信息接口
     * POST info.json
     * {
     * 	"id":[1,2,3...]
     * }
     */
    public function info() {
        $data = $this->get_data();
        $pid = $data['id'];
        if(!$pid) {
            $this->response(ResponseType::ERROR_LACKPARAMS);
        }
        $photoModel = new Models\ Photo();
        $result = $photoModel->getInfo($pid);
        $this->response(ResponseType::PHOTO_ALL_OK, '', $result);
    }
    /**
     * 
     * 获取某人的照片接口
     * GET all/:uid.json?category={分类id}&pagesize={每页条数}&page={当前分页}
     */
    public function all($uid) {
        if($uid == 'me') 
            $uid = $this->user_id;
        else 
            $uid = intval($uid);
        $query['cid'] = intval($_GET['category']);
        $query['pagesize'] = $_GET['pagesize'] ? intval($_GET['pagesize']) : 20;
        $query['page'] = $_GET['page'] ? intval($_GET['page']) : 1;
        if(!$uid) {
            $this->response(ResponseType::ERROR_LACKPARAMS);
        }
        $photo = new Models\ Photo();
        $result = $photo->getList($uid, $query);
        $this->response(ResponseType::PHOTO_ALL_OK, '', $result);
    }
    /**
     * 
     * 获取自己有权浏览的所有照片
     * GET share/type=[all,me,friend,group,action]/:id.json?pagesize={每页条数}&page={当前分页}
     */
    public function share($type = 'me', $id = 0) {
        $query['type'] = $type;
        $query['id'] = intval($id);
        $query['pagesize'] = $_GET['pagesize'] ? intval($_GET['pagesize']) : 20;
        $query['page'] = $_GET['page'] ? intval($_GET['page']) : 1;
        $photo = new Models\ Photo();
        $result = $photo->getShare($query);
        $this->response(ResponseType::PHOTO_ALL_OK, '', $result);
    }
    /**
     * 
     * 根据图片id获取图片地址的接口
     * POST url.json
     * {
     * 	"id":[1,2,3...],
     * 	"size":"缩略图大小"
     * }
     */
    public function url() {
        $data = $this->get_data();
        $size = intval($data['size']);
        $pid = $data['id'];
        if(!$pid) {
            $this->response(ResponseType::ERROR_LACKPARAMS);
        }
        $photoModel = new Models\ Photo();
        $result = $photoModel->geturi($pid, $size);
        $this->response(ResponseType::PHOTO_URL_OK, '', $result);
    }
    /**
     * 
     * 联系人根据MD5获得图片地址
     * POST origin.json
     * {
     * 	"md5":[md5,md5,md5...],
     * 	"size":"缩略图大小"
     * }
     */
    public function origin() {
        $data = $this->get_data();
        $size = intval($data['size']);
        if(!$data['md5']) {
            $this->response(ResponseType::ERROR_LACKPARAMS);
        }
        $photo = new Models\ Photo();
        $result = $photo->geturiByMD5($data['md5'], $size ? $size : 130);
        $this->response(ResponseType::PHOTO_GET_OK, '', $result);
    }
    
    public function upload_by_url(){
        $data = $this->get_data();
        $url = $data['url'];
        $scale_width = intval($data['scale_width']);
        $x = intval($data['x']);
        $y = intval($data['y']);
        $w = intval($data['w']);
        $h = intval($data['h']);
        
        if($scale_width < 1 
                || $x < 0 
                || $y < 0 
                || $w < 1 
                || $h < 1){
            $this->response(ResponseType::PHOTO_UPLOAD_ERROR_SERVER);
        }
        
        $tmpfile = Core::tempname();
        if(file_put_contents($tmpfile, file_get_contents($url))){
            
        }else{
            $this->response(ResponseType::PHOTO_UPLOAD_ERROR_SERVER);
        }
        
        $thumb = new Thumb();
        $uploader = $thumb->crop_by_url($tmpfile, $scale_width, $x, $y, $w, $h);
        
        $photoModel = new Models\ Photo();
        $updata['cid'] = 3;
        $updata['is_animated'] = 0;
        $updata['mtime'] = time();
        if($photoModel->create($uploader, $updata)) {
            $result['id'] = $photoModel->get_pid();
            $result['md5'] = $photoModel->md5;
            $imgurls = $photoModel->geturi($result['id']);
            $result['src'] = $imgurls[0];
            
            unlink($tmpfile);
            
            $this->response(ResponseType::PHOTO_GET_OK, '', $result);
        } else {
            $this->response(ResponseType::PHOTO_UPLOAD_ERROR_SERVER);
        }
    }

    protected function response($response_type, $params = array(), $body = NULL, $redirect = '') {
        $code = ResponseType::getCode($response_type);
        $msg = Core::sprintf($response_type, $params);
        if($redirect) {
            $result['code'] = $code;
            $result['msg'] = $msg;
            $result['data'] = $body;
            $href = $redirect . ((strpos($redirect, '?') !== FALSE) ? '&' : '?') . 'apiResult=' . urlencode(json_encode($result));
            Core::header('Location:' . $href);
        } else {
            Core::outTrace();
            $this->send_response($code, $body, $msg, FALSE);
        }
        
        Core::quit();
    }
}
