<?php defined('SYSPATH') or die('No direct access allowed.');
/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * 资源控制器文件
 */
class Src_Controller extends Controller implements FS_Gateway_Core {

    public function __construct() {
        //必须继承父类控制器
        parent::__construct();
        Core::initGridFS('photo');
        Core::set_exception_handler();
    }

    public function photo($pname) {
        $is_animate = isset($_GET['animate']) && $_GET['animate'];
        
        preg_match_all('/^(.+?)(_48|_130|_320|_480|_780|_1024|_1600)?(\.jpg)?$/', $pname, $matchs);
        $source_id = $matchs[1][0];
        $type = intval(ltrim($matchs[2][0], '_'));
        if($raw = Core::validSourceID($source_id)) {
            $pid = $raw[0];
            $this->show_image($pid, $type, $is_animate);
        }
        $this->show_nophoto();
    }

    public function avatar($pname) {
        preg_match_all('/^(.+?)(_48|_130|_320|_480|_780|_1600)?(\.jpg)?$/', $pname, $matchs);
        $source_id = $matchs[1][0];
        $type = intval(ltrim($matchs[2][0], '_'));
        if($raw = Core::validSourceID($source_id)) {
            $pid = $raw[0];
            $oid = $raw[2];
            if(in_array($type, array(48, 130))) {
                $this->show_image($pid, $type);
            } else {
                $this->show_image($oid, $type);
            }
        }
        $this->show_nophoto();
    }
    
    public function file_thumb($pname) {
        preg_match_all('/^(.+?)(_48|_130|_320|_480|_780|_1024)?(\.jpg)?$/', $pname, $matchs);
        $source_id = $matchs[1][0];
        $type = intval(ltrim($matchs[2][0], '_'));
        if($raw = Core::validSourceID($source_id)) {
            $pid = $raw[0];
            $oid = $raw[2];
            $this->show_image($pid, $type);
        }
        $this->show_nophoto();
    }

    public function file($fname,$filename='') {
        $filetype = $this->input->get('filetype');
        if($raw = Core::validSourceID($fname)) {
            $fid = $raw[0];
            $session_uid = $raw[1];
            $time = $raw[2];
            /*
            if($time < time()-1800){//30min前的链接过期
            	$fkey=Core::authcode($fid,'ENCODE',Core::config('source_key'));
            	Core::header('Location: http://momo.im/file/download/'.$fkey);
            	Core::quit();
            }
            */
            $fileModel = new Models\ FileEntry();
            //if($fileModel->findOneByFID($fid) && $fileModel->isFileAccess($session_uid)){
            if($fileModel->findOneByFID($fid)) {
                $fileModel->download($filetype);
            }
        }
    }

    public function mocloud($key, $size, $filename) {
        $this->set_browsercache();
        $mocloud = new Models\ Mocloud();
        if($arr = $mocloud->extra_uri($key)) {
            if(time() <= (($arr['timestamp']+2)*Core::config('expire_mocloud'))){
                if($mocloud->where(array('id' => $arr['id']))->find()) {
                    if($size == 'o') {
                        $mocloud->output();
                    } else {
                        $mocloud->output_thumbnail($size);
                    }
                }
            }
        }
        $this->show_nophoto();
    }

    private function show_image($pid, $type, $is_animate=FALSE) {
        $this->set_browsercache();
        if($type > 0) {
            //如果是缩略图
            /*
            $client= new GearmanClient();
            $client->addServers(Core::config('job_servers'));
            $client->setTimeout(3000);
            $result = @ $client->doHigh("thumbnail", serialize(array($pid, $type)));
            $result && $sid = @ unserialize($result);
            */
            if($sid) {
                $source = new Models\ PhotoSource();
                $source->findOne($sid);
                $source->output();
                //异步缩略图
            } else {
                $thumb = new Thumb();
                if($thumb->resize(NULL, $pid, $type, $is_animate)) {
                    $thumb->output();
                    //同步缩略图
                }
            }
        } else {
            $photoModel = new Models\ Photo();
            $photoModel->findOne($pid);
            $photosource = $photoModel->getSource();
            if($photosource) {
                $photosource->output();
            }
        }
        
    }

    private function show_nophoto() {
        $this->show_error('nopicture');
    }

    private function set_browsercache() {
        $etag = time();
        $if_none_match = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? intval($_SERVER['HTTP_IF_NONE_MATCH']) : 0;
        //请求带有etag且etag未过期
        if($if_none_match && ($if_none_match + 604800) > $etag){
            $etag = $if_none_match;
        }
        
        //$etag = 1357653079;
        $lmstr = gmdate('D, d M Y H:i:s ', $etag) . 'GMT';
        
        Core::header('Pragma: cache');
        Core::header('Cache-Control: max-age=604800');
        Core::header('Expires: ' . gmdate('D, d M Y H:i:s ', (time()+31536000)) . 'GMT');
        Core::header('Etag: ' . $etag);
        Core::header('Last-Modified: ' . $lmstr);
        
        //$if_none_match = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? strtolower($_SERVER['HTTP_IF_NONE_MATCH']) : false;
        $if_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false;
        if (($if_none_match && $if_none_match == $etag)
                || ($if_modified_since && $if_modified_since == $lmstr))
        {
            Core::header('HTTP/1.1 304 Not Modified');
            Core::quit();
        }
    }

    private function show_error($msg) {
        if(!in_array($msg, array('nopicture'))) 
            $msg = 'nopicture';
        $data = file_get_contents(DOCROOT . 'style/images/' . $msg . '.jpg');
        header_remove('Etag');
        header_remove('Last-Modified');
        header_remove('Cache-Control');
        header_remove('Pragma');
        Core::header("HTTP/1.1 254");
        Core::header("Content-type: image/jpeg");
        echo $data;
        Core::quit();
    }
}
