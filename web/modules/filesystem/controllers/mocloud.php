<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * 
 * 照片备份
 *
 */
class Mocloud_Controller extends Controller implements FS_Gateway_Core {

    public function __construct() {
        parent::__construct();
        if(!$this->user_id) {
            Core::fault(403);
        }
        Core::setUserID($this->user_id);
        Core::setClientID($this->source);
        Core::setAPPID($this->appid);
        Core::set_exception_handler();
    }
    
    public function file_upload_once(){
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Methods:POST, GET, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers:X-FILENAME");
        
        if(strtolower($_SERVER["REQUEST_METHOD"]) == "options"){
            exit;
        }
        //绑定设备
        $p_device_id = $this->input->get('device_id','web_91_time_machine');
        $mcModel = new Models\ Mocloud();
        if(!$mcModel->get_bind_device($p_device_id)) {
            if($p_device_id == 'web_91_time_machine'){
                $device_info=array(
                        'device_name'=>'91时光机网站',
                        'device_os'=>'Web',
                        'device_model'=>'Web'
                        );
                $mcModel->set_bind_device($p_device_id, $device_info);
            }else{
                $this->response('40041:设备未绑定');
            }
        }
        
        $uploader = new Uploader();
        if(!$uploader->upload()) {
            $this->response('40042:无法获取上传文件');
        }
        //图片mime类型
        $p_mime = $uploader->getMIME();
        if(!in_array($p_mime, array('image/jpeg', 'image/pjpeg', 'image/png'))) {
            $this->response('40043:不支持的图片格式');
        }
        //文件大小
        $p_size = $uploader->getLength();
        
        if($p_size > Core::config('photo_max_size')) {
            $this->response('40044:大小超过限制');
        }
        
        $stats = $mcModel->get_stats();
        if(($stats['quota_used'] + $p_size) > $stats['quota_all']) {
            $this->response('40045:空间满额');
        }
        
        $p_name = $uploader->getTitle();
        $p_md5 = $uploader->getMD5();
        $p_type = 1;
        
        if(!$p_name || !$p_md5 || !$p_type || !$p_size || !$p_device_id) {
            $this->response('40046:参数不完整');
        }
        //客户端记录图片修改时间
        $p_datetime = $this->input->get('datetime');
        
        $mcModel->name = $p_name;
        $mcModel->md5 = $p_md5;
        $mcModel->type = $p_type;
        $mcModel->size = $p_size;
        $mcModel->device_id = $p_device_id;
        $mcModel->datetime = (string) $p_datetime;
        $mcModel->md5_thumb = '';

        if($id = $mcModel->create()) {
            if($mcModel->uploaded) {
                $result['uploaded'] = TRUE;
                $result['id'] = $id;
                $result['src'] = $mcModel->get_uri(array('id'=>$mcModel->get_id(),'name'=>$mcModel->name));
                $mcModel->update_last_upload_time();
                $this->response('20020:上传成功', $result);
            } else {
                if(!$mcModel->append($p_size, $uploader)) {
                    $this->response('40047:文件储存失败');
                }
                
                if($mcModel->end()) {
                    $result['uploaded'] = TRUE;
                    $result['id'] = $mcModel->get_id();
                    $result['src'] = $mcModel->get_uri(array('id'=>$mcModel->get_id(),'name'=>$mcModel->name));
                    $this->response('20020:上传成功', $result);
                } else {
                    $mcModel->destroy();
                    $this->response('40050:上传失败');
                }
            }
        }
        $this->response('40050:上传失败');
    }

    public function file_upload($upload_id=0) {
        if($upload_id) {
            //第二步
            $length = $this->input->get('length', 0);
            if(!$length) 
                $this->response('40040:参数不完整');
            $mcModel = new Models\ Mocloud();
            //上传id存在
            if($mcModel->where(array('id' => $upload_id))->get()) {
                if($mcModel->uploaded) {
                    $this->response('40015:此文件已经上传过');
                }
                //上传未结束，追加文件内容
                if($mcModel->size > $mcModel->offset) {
                    /*
                     * 追加文件内容
                     * 1、传的length参数和实际数据大小是否相等
                     * 2、追加数据到FS
                     * 3、读取FS文件offset信息
                     */
                    if(!$mcModel->append($length)) {
                        $this->response('40013:文件储存失败');
                    }
                }
                /*
                 * 上传结束
                 * 1、FS上下载文件到本地计算
                 * 2、生成缩略图，上传缩略
                 * 3、存储此md5的meta信息
                 * 4、计算完成删除本地文件
                 */
                if($mcModel->offset >= $mcModel->size) {
                    $mcSource = $mcModel->get_source();
                    $md5 = $mcSource->get_md5();
                    if($md5 && $md5 == $mcModel->md5) {
                        //上传成功
                        $mime = $mcSource->get_mime();
                        if($mcModel->type == 1) {
                            if(!in_array($mime, array('image/jpeg', 'image/pjpeg', 'image/png'))) {
                                $mcModel->destroy();
                                $this->response('40012:不支持的文件格式');
                            }
                        } else {
                            $mcModel->destroy();
                            $this->response('40012:不支持的文件格式');
                        }
                        if($mcModel->end()) {
                            $result['uploaded'] = TRUE;
                            $result['id'] = $mcModel->get_id();
                            $this->response('20020:上传成功', $result);
                        } else {
                            $mcModel->destroy();
                            $this->response('40050:上传失败');
                        }
                    } else {
                        $mcModel->destroy();
                        $this->response('40014:文件不完整，重新获取上传id');
                    }
                }
                $result['uploaded'] = FALSE;
                $result['upload_id'] = $upload_id;
                $result['offset'] = $mcModel->offset;
                $this->response('20011:文件上传未结束需继传', $result);
            } else {
                $this->response('40010:上传id不存在');
            }
        } else {
            //第一步
            $input = $this->get_data();
            $p_name = trim($input['name']);
            $p_md5 = strtolower($input['md5']);
            $p_type = 1;
            $p_size = intval($input['size']);
            $p_device_id = trim($input['device_id']);
            $p_md5_thumb = strtolower($input['md5_thumb']);
            $p_datetime = $input['datetime'];
            if(!$p_name || !$p_md5 || !$p_type || !$p_size || !$p_device_id) {
                $this->response('40040:参数不完整');
            }
            $mcModel = new Models\ Mocloud();
            if(!$mcModel->get_bind_device($p_device_id)) {
                $this->response('40021:设备未绑定');
            }
            $stats = $mcModel->get_stats();
            if(($stats['quota_used'] + $p_size) > $stats['quota_all']) {
                $this->response('40016:空间满额');
            }
            $mcModel->name = $p_name;
            $mcModel->md5 = $p_md5;
            $mcModel->type = $p_type;
            $mcModel->size = $p_size;
            $mcModel->device_id = $p_device_id;
            $mcModel->datetime = (string) $p_datetime;
            $mcModel->md5_thumb = (string) $p_md5_thumb;
            /*
             * 创建资源id
             * 1、查找uid,md5,device_id的记录，已存在则直接返回id，此id可能上传完整也可能没有上传完整
             * 2、查找md5,uploaded=1，则复制这条记录给uid并返回新id，此id一定是上传完整的
             * 3、都不存在则插入一条新记录并返回新id，此id一定是没有上传过的
             */
            if($id = $mcModel->create()) {
                if($mcModel->uploaded) {
                    $result['uploaded'] = TRUE;
                    $result['id'] = $id;
                    $mcModel->update_last_upload_time();
                } else {
                    $result['uploaded'] = FALSE;
                    $result['upload_id'] = $id;
                    $result['offset'] = $mcModel->offset;
                }
                $this->response('20010:创建上传事务成功', $result);
            }
            $this->response('40050:创建上传事务失败');
        }
    }
    /**
     * 
     * 批量删除
     */
    public function file_delete() {
        $perf_start_time = microtime(TRUE);
        $data = $this->get_data();
        $field = 'id';
        $arr = (array) $data['id'];
        if(!$arr) {
            $field = 'device_id';
            $arr = (array) $data['device_id'];
        }
        if(!$arr) {
            $field = 'md5';
            $arr = (array) $data['md5'];
        }
        if(!$arr) {
            $this->response('40040:参数不完整');
        }
        $mcModel = new Models\ Mocloud();
        if($mcModel->delete($arr, $field)) {
            $this->response('20010:删除成功');
        }
        $this->response('40050:删除失败');
    }

    public function file_download($id) {
        $size = $this->input->get('size');
        
        $mcModel = new Models\ Mocloud();
        if($mcModel->where(array('id' => $id, 'uploaded' => 1))->get()) {
            try {
                $ranges = Core::parse_request_ranges();
            }catch (Exception $e){
                Core::header('HTTP/1.1 416 Requested Range Not Satisfiable');
                Core::quit();
            }
            
            if($ranges){
                $range = $ranges[0];
                $offset = (int)$range[0];
            }else{
                $offset = 0;
            }
            
            if($offset > 0){
                Core::header('HTTP/1.1 206 PARTIAL CONTENT');
            }
            
            $header = array('name' => $mcModel->name, 'size' => $mcModel->size, 'md5' => $mcModel->md5);
            Core::header('x-mocloud-metadata:' . json_encode($header));
            Core::header('Content-Transfer-Encoding:binary');
            Core::header('Accept-Ranges:bytes');
            Core::header('Cache-Control: public');
            //若去掉则ie无法弹出下载框
            $ua = strtolower($_SERVER["HTTP_USER_AGENT"]);
            $pathinfo = pathinfo($mcModel->name);
            $filename = $pathinfo['basename'];
            $encoded_filename = str_replace("+", "%20", urlencode($filename));
            if(preg_match("/msie/", $ua)) {
                Core::header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
            } elseif(preg_match("/firefox/", $ua)) {
                Core::header('Content-Disposition: attachment; filename*=UTF-8\'\'' . rawurlencode($filename));
            } else {
                Core::header('Content-Disposition: attachment; filename="' . $filename . '"');
            }
            
            if($size){
                $mcModel->output_thumbnail($size, $offset);
            }else{
                $mcModel->output($offset);
            }
        } else {
            $this->response('40010:文件资源不存在');
        }
    }
    
    /**
     * 打包下载
     * @param string $ids 1,2,3,4
     */
    public function file_download_zip($ids){
        ini_set('memory_limit', '256M');
        ini_set('max_execution_time', 600);
        //最多支持120张
        $idarr=explode(',', $ids);
        $zip = new ZipStream(date('YmdHis').'.zip');
        foreach ($idarr as $id){
            $mcModel = new Models\ Mocloud();
            if($mcModel->where(array('id' => $id, 'uploaded' => 1))->get()) {
                $image_info = pathinfo($mcModel->name);
                $zip->add_file($id.'_'.iconv('UTF-8','GBK',$image_info['basename']), $mcModel->get_source()->output(0, FALSE));
            }
        }
        $zip->finish();
    }
    /**
     * 
     * 获取上传信息
     * GET mocloud/file_upload_info.json 
     * 或者
     * POST mocloud/file_upload_info.json 
     * ["文件md5","文件md5",…]
     * 
     * RESPONSE
        [
             {//中止过的上传
                  "uploaded":false,
                  "md5":"文件md5",
                  "upload_id":"上传的id",
                  "offset":"已上传大小"
             },
             {//已上传成功
                  "uploaded":true,
                  "md5":"文件md5",
                  "id":"资源的id"
             },
             "",//从未上传过
             ...
        ]
     *
     */
    public function file_upload_info() {
        $mcModel = new Models\ Mocloud();
        if($this->get_method() == 'POST') {
            $data = $this->get_data();
            $md5_list = isset($data['md5']) ? array_map('strtolower', (array) $data['md5']) : array();
            $md5_thumb_list = isset($data['md5_thumb']) ? array_map('strtolower', (array) $data['md5_thumb']) : array();
            if(!$md5_list && !$md5_thumb_list) {
                $this->response('40040:参数不完整');
            }
            $result['md5'] = $mcModel->get_upload_info($md5_list, 'md5');
            $result['md5_thumb'] = $mcModel->get_upload_info($md5_thumb_list, 'md5_thumb');
            $this->response('20010:获取数据成功', $result);
        } else {
            $dateline = intval($this->input->get('dateline', 0));
            $device_id = (string)($this->input->get('device_id', ''));
            $result = $mcModel->get_upload_info_all2($dateline, $device_id);
            $this->response('20010:获取数据成功', $result);
        }
    }
    /**
     * 
     * 预览列表
     * GET mocloud/file_list.json?type={类型}&desc={倒序字段}&asc={顺序字段}&page={当前页}&pagesize={分页大小}
     * &device_id={设备id}&keyword={文件名关键词}&otime_start={拍摄时间起点}&otime_end={拍摄时间终点} 
     * 
     * type必须指定（照片1，视频2，音频3，文件4）其中之一，其他参数均可选 
     * desc和asc目前只支持orig_time
     * 
     * RESPONSE
        [
             {
                  "id":"文件资源id",
                  "name":"文件名",
                  "size":"文件大小",
                  "mime":"文件mime类型",
                  "md5":"文件md5",
                  "src":"预览地址",
                  "ext":"文件扩展名",
                  "orig_time":"拍摄时间",
                  "device_id":"来源设备id"
             },
             ...
        ]
     *
     */
    public function file_list() {
        $type = intval($this->input->get('type'));
        $star = intval($this->input->get('star'));
        $desc = (string) $this->input->get('desc', '');
        $asc = (string) $this->input->get('asc', '');
        $page = intval($this->input->get('page', 1));
        $pagesize = intval($this->input->get('pagesize', 20));
        $device_id = (string) $this->input->get('device_id', '');
        $keyword = (string) $this->input->get('keyword', '');
        $otime_start = intval($this->input->get('otime_start', 0));
        $otime_end = intval($this->input->get('otime_end', 0));
        $utime_start = intval($this->input->get('utime_start', 0));
        $utime_end = intval($this->input->get('utime_end', 0));
        if(!in_array($type, array(1, 2, 3, 4))) {
            $this->response('40040:参数不完整');
        }
        $mcModel = new Models\ Mocloud();
        $result = $mcModel->get_upload_list($type, $asc, $desc, $page, $pagesize, $device_id, $keyword, $otime_start, $otime_end, $star, $utime_start, $utime_end);
        $this->response('20010:获取数据成功', $result);
    }
    
    public function file_list_group_by_date(){
        $type = intval($this->input->get('type'));
        $star = intval($this->input->get('star'));
        $device_id = (string) $this->input->get('device_id', '');
        
        $mcModel = new Models\ Mocloud();
        $result = $mcModel->get_upload_list_group_by_date($type, $device_id, $star);
        $this->response('20010:获取数据成功', $result);
    }
    
    public function file_list_recent(){
        $type = intval($this->input->get('type'));
        $page = intval($this->input->get('page', 1));
        $pagesize = intval($this->input->get('pagesize', 20));
        
        $mcModel = new Models\ Mocloud();
        $result = $mcModel->get_upload_list_recent($type, $page, $pagesize);
        $this->response('20010:获取数据成功', $result);
    }
    /**
     * 
     * 统计信息
     * GET mocloud/stats.json 
     * 
     * RESPONSE
        {
             "quota":{
                  "used":"已使用的配额",
                  "all":"总配额5,000,000,000 byte"
             },
             "count":{
                  "photo":"照片总数"
             }
        }
     * 
     */
    public function stats() {
        $mcModel = new Models\ Mocloud();
        $result = $mcModel->get_stats();
        $this->response('20010:获取数据成功', $result);
    }
    
    public function stats_device() {
        $mcModel = new Models\ Mocloud();
        $result = $mcModel->get_stats_device();
        $this->response('20010:获取数据成功', $result);
    }
    
    public function stats_star(){
        $mcModel = new Models\ Mocloud();
        $result = $mcModel->get_stats_star();
        $this->response('20010:获取数据成功', $result);
    }
    
    public function dashboard(){
        $mcModel = new Models\ Mocloud();
        $stats = $mcModel->get_stats();
        $stats['backup_time'] = 0;
        $stats_device = $mcModel->get_stats_device();
        foreach ($stats_device as $r){
            $r['backup_time'] > $stats['backup_time'] && $stats['backup_time'] = $r['backup_time'];
        }
        $stats_star = $mcModel->get_stats_star();
        $devices = $mcModel->get_bind_device(NULL, FALSE);
        
        $result = array(
                'stats_device'=>$stats_device, 
                'stats'=>$stats, 
                'stats_star'=>$stats_star, 
                'devices' => $devices
                );
        $this->response('20010:获取数据成功', $result);
    }

    public function bind_device() {
        $mcModel = new Models\ Mocloud();
        if($this->get_method() == 'POST') {
            $data = $this->get_data();
            $device_info['device_id'] = (string) $data['device_id'];
            if(trim($device_info['device_id']) != '') {
                $device_info['device_name'] = (string) $data['device_name'];
                $device_info['device_os'] = (string) $data['device_os'];
                $device_info['device_model'] = (string) $data['device_model'];
                if(trim($device_info['device_name']) == '') {
                    $device_info['device_name'] = $device_info['device_id'];
                }
                $bind_device = $mcModel->get_bind_device();
                if($bind_device && count($bind_device)>4){
                    $this->response('40011:最多只能绑定5台设备');
                }
                $client_id = User_Model::instance()->get_client_id($this->user_id,$device_info['device_id']);
                Core::setClientID(intval($client_id));
                if($mcModel->set_bind_device($device_info['device_id'], $device_info)) {
                    $this->response('20010:设备绑定成功');
                }
            } else {
                $this->response('40040:参数不完整');
            }
            $this->response('40010:设备绑定失败');
        } else {
            $bind_device = $mcModel->get_bind_device();
            $result = array();
            foreach($bind_device as $device_info) {
                $device_info['alias'] = Brand_Model::instance()->get_by_model($device_info['model']);
                $result[] = array('device_name' => $device_info['name'], 'device_alias' => $device_info['alias'], 'device_id' => $device_info['device_id'], 'device_os' => $device_info['os'], 'device_model' => $device_info['model'], 'device_sync' => (int) $device_info['sync'], 'ctime' => $device_info['created_at']);
            }
            $this->response('20010:获取数据成功', $result);
        }
    }
    
    public function bind_device_all(){
        $mcModel = new Models\ Mocloud();
        $bind_device=$mcModel->get_bind_device(NULL, FALSE);
        $result = array();
        foreach($bind_device as $device_info) {
            $device_info['alias'] = Brand_Model::instance()->get_by_model($device_info['model']);
            $result[] = array('device_name' => $device_info['name'], 'device_alias' => $device_info['alias'], 'device_id' => $device_info['device_id'], 'device_os' => $device_info['os'], 'device_model' => $device_info['model'], 'device_sync' => (int) $device_info['sync'], 'ctime' => $device_info['created_at']);
        }
        $this->response('20010:获取数据成功', $result);
    }

    public function check_device($device_id) {
        $mcModel = new Models\ Mocloud();
        if($device_info = $mcModel->get_bind_device($device_id)) {
            $result['is_binded'] = 1;
            $result['auto_sync'] = intval($device_info['sync']);
        } else {
            $result['is_binded'] = 0;
            $result['auto_sync'] = 0;
        }
        $this->response('20010:获取数据成功', $result);
    }

    public function delete_device($device_id) {
        $mcModel = new Models\ Mocloud();
        if($mcModel->set_bind_device($device_id, NULL)) {
            $this->response('20010:删除绑定成功');
        } else {
            $this->response('40010:删除绑定失败');
        }
    }

    public function update_device($device_id) {
        $mcModel = new Models\ Mocloud();
        $device_info = $this->get_data();
        if($mcModel->upd_bind_device($device_id, $device_info)) {
            $this->response('20010:更新绑定信息成功');
        } else {
            $this->response('40010:更新绑定信息失败');
        }
    }

    public function history() {
        $mcModel = new Models\ Mocloud();
        $history = $mcModel->get_history();
        $this->response('20010:获取数据成功', $history);
    }
    
    public function file_star(){
        $data = $this->get_data();
        $mcModel = new Models\ Mocloud();
        if($mcModel->set_star($data['id'], 1)){
            $this->response('20010:加星标成功');
        }
        $this->response('40010:加星标失败');
    }
    
    public function file_star_remove(){
        $data = $this->get_data();
        $mcModel = new Models\ Mocloud();
        if($mcModel->set_star($data['id'], 0)){
            $this->response('20010:加星标成功');
        }
        $this->response('40010:加星标失败');
    }

    protected function response($response_type, $body = NULL, $redirect = '') {
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
