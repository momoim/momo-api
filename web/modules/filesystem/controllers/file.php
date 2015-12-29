<?php defined('SYSPATH') or die('No direct access allowed.');
/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * 网盘控制器文件
 */
class File_Controller extends Controller implements FS_Gateway_Core {

    public function __construct() {
        parent::__construct();
        Core::setUserID($this->user_id);
        Core::initGridFS('photo');
        //ini_set('mongo.chunk_size', 1048576);
    }

    public static function getinfo($fid) {
        $fileModel = new Models\ FileEntry();
        if($fileModel->findOneByFID($fid)) {
            return array('fid' => $fileModel->get_fid(), 'size' => $fileModel->size, 'mime' => $fileModel->mime, 'name' => $fileModel->getFilename(), 'ext' => $fileModel->ext, 'uid' => $fileModel->uid, 'ctrl_type' => $fileModel->ctrl_type, 'src' => $fileModel->geturi($fid), 'meta' => $fileModel->getMeta());
        }
        return array();
    }
    /**
     * 生成资源地址的公共调用函数
     * @param bigint $fid
     * @return string
     */
    public static function geturl($fid) {
        return Models\ FileEntry::instance()->geturi($fid);
    }
    /**
     * 存储在动态的Accessory格式的公共调用函数
     * @param bigint $fid
     * @return array
     */
    public static function getAccessory($fid) {
        return Models\ FileEntry::instance()->getAccessory($fid);
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
        if(!$data['md5']) {
            $this->response(ResponseType::ERROR_LACKPARAMS);
        }
        $fileModel = new Models\FileEntry();
        $result = $fileModel->geturiByMD5($data['md5']);
        $this->response(ResponseType::FILE_OK, '', $result);
    }
    /**
     * 批量删除文件
     * POST delete.json
     * [
     * 	'path1','path2'
     * ]
     */
    public function delete() {
        $data = $this->get_data();
        if(!$data || !is_array($data)) {
            $this->response(ResponseType::ERROR_LACKPARAMS);
        }
        $fileModel = new Models\ FileEntry();
        $fileModel->deleteBatch($data);
        $this->response(ResponseType::FILE_OK_DELETE);
    }
    /**
     * 删除整个目录
     * POST rmdir.json
     * {
     * 	"dir":"目录地址",
     * 	"force":"不管是否为空强制删除（0或1）"
     * }
     */
    public function rmdir() {
        $data = $this->get_data();
        $dir = $data['dir'];
        $force = intval($data['force']);
        $dirModel = new Models\ FileDirectory();
        if(!$dirModel->findOne($dir)) {
            $this->response(ResponseType::FILE_ERROR_DIR_INVALID);
        }
        if($dirModel->delete($force)) {
            $this->response(ResponseType::FILE_OK_DELETE);
        } else {
            $this->response(ResponseType::FILE_ERROR_DELETE);
        }
    }
    /**
     * 获取文件夹的所有节点
     * GET get.json?basedir={文件夹路径}
     */
    public function get() {
        $basedir = urldecode($_GET['basedir']);
        $basedirModel = new Models\ FileDirectory();
        if(!$basedirModel->findOne($basedir)) {
            $this->response(ResponseType::FILE_ERROR_DIR_INVALID);
        }
        $result = $basedirModel->as_array();
        $this->response(ResponseType::FILE_OK, '', $result);
    }
    /**
     * 根据id获取文件信息
     * GET info/:id.json
     */
    public function info($fid) {
        $fileModel = new Models\ FileEntry();
        if($fileModel->findOneByFID($fid)) {
            $result = array('fid' => $fileModel->get_fid(), 'size' => $fileModel->size, 'mime' => $fileModel->mime, 'name' => $fileModel->getFilename(), 'ext' => $fileModel->ext, 'uid' => $fileModel->uid, 'ctrl_type' => $fileModel->ctrl_type, 'src' => $fileModel->geturi($fid));
            $this->response(ResponseType::FILE_OK, '', $result);
        }
        $this->response(ResponseType::FILE_ERROR_SERVER);
    }
    /**
     * 存储在动态的Accessory格式
     * GET accessory/:id
     */
    public function accessory($fid) {
        $fileModel = new Models\ FileEntry();
        $result = $fileModel->getAccessory($fid);
        if($result) {
            $this->response(ResponseType::FILE_OK, '', $result);
        } else {
            $this->response(ResponseType::FILE_ERROR_SERVER);
        }
    }
    /**
     * 上传文件
     * POST file/upload.json?type={文件解析类型 0文件，1音频，2视频}&basedir={文件夹路径}&redirect={回调地址}
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
        
        $ctrl_type = intval($_GET['type']);
        $redirect = $_GET['redirect'] ? urldecode($_GET['redirect']) : '';
        $basedir = urldecode($_GET['basedir']);
        if(!$basedir) 
            $basedir = '/home';
        $basedirModel = new Models\ FileDirectory();
        if(!$basedirModel->findOne($basedir)) {
            $this->response(ResponseType::FILE_ERROR_DIR_INVALID, '', '', $redirect);
        }
        $uploader = new Uploader();
        $uploader->ctrl_type = $ctrl_type;
        if(!$uploader->upload()) {
            $this->response(ResponseType::FILE_ERROR_UPLOAD, '', '', $redirect);
        }
        //音频文件
        /*
         if($ctrl_type==Uploader::FILETYPE_AUDIO && $uploader->getType() != Uploader::FILETYPE_AUDIO){
         $this->response(ResponseType::FILE_ERROR_AUDIOTYPE,'','',$redirect);
         }
         */
        //文件超过20M
        if($uploader->getLength() > Core::config('file_max_size')) {
            $this->response(ResponseType::FILE_ERROR_SIZELIMIT, '', '', $redirect);
        }
        if($fileModel = $basedirModel->makeFile($uploader)) {
            $result['id'] = $fileModel->get_fid();
            $result['src'] = $fileModel->geturi($result['id']);
            $result['mime'] = $fileModel->mime;
            $result['size'] = $fileModel->size;
            $result['name'] = $fileModel->getFilename();
            if($uploader->getInfo())
                $result['meta'] = $uploader->getInfo();
            
            if($fileModel->thumb_id){
                $photoModel = new Models\ Photo();
                if($photoModel->findOne($fileModel->thumb_id, array('width', 'height'))){
                    $result['thumb']['id'] = $photoModel->get_pid();
                    $result['thumb']['md5'] = $photoModel->md5;
                    $result['thumb']['width'] = $photoModel->width;
                    $result['thumb']['height'] = $photoModel->height;
                    $result['thumb']['mime'] = $photoModel->mime;
                    
                    $imgurls = $photoModel->geturi($fileModel->thumb_id, 130);
                    $result['thumb']['src'] = $imgurls[0];
                }
            }
            $this->response(ResponseType::FILE_OK, '', $result, $redirect);
        } else {
            $this->response(ResponseType::FILE_ERROR_SERVER, '', '', $redirect);
        }
    }
    /**
     *
     * 分段上传接口
     * 第一步：
     * POST bp_upload.json
     * {
     * 	"md5":"文件md5",
     * 	"size":"文件大小",
     * 	"basedir":"文件夹路径",
     * 	"type":"文件解析类型 0文件，1音频，4视频",
     * 	"filename":"文件名"
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
            if($data['size'] > Core::config('file_max_size')) {
                $this->response(ResponseType::FILE_ERROR_SIZELIMIT);
            }
            //存放路径是否存在
            $basedir = $data['basedir'];
            if(!$basedir) 
                $basedir = '/home';
            $basedirModel = new Models\ FileDirectory();
            if(!$basedirModel->findOne($basedir)) {
                $this->response(ResponseType::FILE_ERROR_DIR_INVALID);
            }
            $tempModel = new Models\ Temp($this->user_id);
            $tempModel->md5 = strtolower($data['md5']);
            $tempModel->size = intval($data['size']);
            $tempModel->type = intval($data['type']);
            $tempModel->cid = intval($data['category']);
            $tempModel->basedir = $basedir;
            if(!$data['filename']) {
                $tempModel->filename = sprintf('%.0f', microtime(TRUE) * 1000);
            } else {
                $tempModel->filename = $data['filename'];
            }
            if($tempModel->create()) {
                $result['upload_id'] = $tempModel->get_upload_id();
                $result['offset'] = 0;
                $result['uploaded'] = FALSE;
                $this->response(ResponseType::PHOTO_BPUPLOAD_OK_CREATETEMP, '', $result);
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
                if($tempModel->md5 && $tempModel->md5 == $tempModel->tmp_source->md5) {
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
                //不是音频类型
                /*
                 if($tempModel->type==Uploader::FILETYPE_AUDIO && $uploader->getType() != Uploader::FILETYPE_AUDIO){
                 $tempModel->destroy();
                 $this->response(ResponseType::FILE_ERROR_AUDIOTYPE);
                 }
                 */
                //文件超过限制
                if($uploader->getLength() > Core::config('file_max_size')) {
                    $tempModel->destroy();
                    $this->response(ResponseType::FILE_ERROR_SIZELIMIT);
                }
                //上传临时文件成功否
                $basedirModel = new Models\ FileDirectory();
                if(!$basedirModel->findOne($tempModel->basedir)) {
                    $this->response(ResponseType::FILE_ERROR_DIR_INVALID);
                }
                if($tempModel->type == Uploader::FILETYPE_VIDEO){
                    $ctrl_type = 2;
                }elseif($tempModel->type == Uploader::FILETYPE_AUDIO){
                    $ctrl_type = 1;
                }else{
                    $ctrl_type = 0;
                }
                $uploader->ctrl_type = $ctrl_type;
                $uploader->filename = $tempModel->filename;
                if($fileModel = $basedirModel->makeFile($uploader)) {
                    $result['id'] = $fileModel->get_fid();
                    $result['src'] = $fileModel->geturi($result['id']);
                    $result['mime'] = $fileModel->mime;
                    $result['size'] = $fileModel->size;
                    $result['name'] = $fileModel->getFilename();
                    $result['uploaded'] = TRUE;
                    $tempModel->destroy();
                    
                    if($fileModel->thumb_id){
                        $photoModel = new Models\ Photo();
                        if($photoModel->findOne($fileModel->thumb_id, array('width', 'height'))){
                            $result['thumb']['id'] = $photoModel->get_pid();
                            $result['thumb']['md5'] = $photoModel->md5;
                            $result['thumb']['width'] = $photoModel->width;
                            $result['thumb']['height'] = $photoModel->height;
                            $result['thumb']['mime'] = $photoModel->mime;
                    
                            $imgurls = $photoModel->geturi($fileModel->thumb_id, 130);
                            $result['thumb']['src'] = $imgurls[0];
                        }
                    }
                    
                    $this->response(ResponseType::FILE_OK, '', $result);
                } else {
                    $tempModel->destroy();
                    $this->response(ResponseType::FILE_ERROR_SERVER);
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
     * 创建文件夹
     * POST mkdir.json
     * {
     * 	"basedir":"文件夹路径",
     * 	"newdir":"新建文件夹名"
     * }
     */
    public function mkdir() {
        $data = $this->get_data();
        $basedir = $data['basedir'];
        $dirname = $data['newdir'];
        $basedirModel = new Models\ FileDirectory();
        if(!$basedirModel->findOne($basedir)) {
            $this->response(ResponseType::FILE_ERROR_DIR_INVALID);
        }
        if($dirModel = $basedirModel->makeDir($dirname)) {
            $result['id'] = $dirModel->get_fid();
            $this->response(ResponseType::FILE_OK, '', $result);
        } else {
            $this->response(ResponseType::FILE_ERROR_SERVER);
        }
    }

    protected function response($response_type, $params = array(), $body = NULL, $redirect = '') {
        $code = ResponseType::getCode($response_type);
        $msg = Core::sprintf($response_type, $params);
        /*
        $ifconfig_out=Core::cmdRun('/sbin/ifconfig', $cmd_error);
        if($ifconfig_out){
            preg_match('/10\.1\.242\.(\d+)/', $ifconfig_out, $ifconfig);
            Core::header('X-MOMO-SID: '.$ifconfig[1]);
        }
        */
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
