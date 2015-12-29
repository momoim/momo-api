<?php
use Models\Photo;
use Models\PhotoSource;

class Thumb {

    public $uploader;

    public $thumb_source;

    public $job;

    public $largesize = 1600;

    private function _getCorrectType($width, $height, $size) {
        //如果是取最大图，则按照宽等比例缩放
        if($this->_reqLarge()) {
            if($width <= $this->largesize) 
                return 0;
            //如果原图宽没有达到1600则取原图
            else 
                return $this->largesize;
        } else {
            $max_side = max(array($width, $height));
            if($max_side == 0) 
                $max_side = 1600;
            if($size >= $max_side) {
                $size = 0;
                //如果要的尺寸大于原图最大边则直接取原图
            }
            return $size;
        }
    }

    private function _jobInit($jobget, $pid, $size) {
        if($jobget) {
            $this->job = $jobget;
            echo "Received job: " . $this->job->handle() . "\n";
            echo "Workload: " . $this->job->workload() . "\n";
            Core::initGridFS('photo');
            return unserialize($this->job->workload());
        }
        return array($pid, $size);
    }

    private function _jobResult($r) {
        if($this->job) {
            if($r) 
                $r = $this->thumb_source->getID();
            echo "Result: " . (is_bool($r) ? ($r ? "True" : "False") : $r) . "\n";
            return serialize($r);
        }
        return $r;
    }

    private function _reqLarge() {
        if($this->needsize == $this->largesize) {
            return TRUE;
        }
        return FALSE;
    }
    
    /**
     * 大于等于780等比例缩放，小于780要裁剪
     * 
     */
    private function _getCorrectType2($width,$height,$size){
        $actions=array();
        //大图只需缩放
        if($size >= 780){
            if($size < $width){
                $actions['resize']=new Imagine\Image\Box($size, 0);
            }
        }else{//缩略图
            if($width * 3 > $height * 4){//宽图
                $size_height=intval($size*3/4);
                if($size_height < $height){
                    $actions['resize']=new Imagine\Image\Box(0, $size_height);
                }
                if($size < $width){
                    $actions['crop']=new Imagine\Image\Box($size, $size_height);
                }
            }elseif($height * 3 > $width * 4){//长图
                $size_height=intval($size*4/3);
                if($size < $width){
                    $actions['resize']=new Imagine\Image\Box($size, 0);
                }
                if($size_height < $height){
                    $actions['crop']=new Imagine\Image\Box($size, $size_height);
                }
            }else{//正常的图
                if($size < $width){
                    $actions['resize']=new Imagine\Image\Box($size, 0);
                }
            }
        }
        
        if(!$actions) $size=0;
        
        return array($size,$actions);
    }
    /**
     * 
     * 只有外部判断不存在$photosource_origin的$size缩略图的时候才调用
     * @param object $job
     * @param PhotoSource $photosource_origin
     * @param int $size
     */
    public function resize($jobget, $pid = 0, $size = 0, $is_animate=FALSE) {
        $this->needsize = $size;
        list($pid, $size) = $this->_jobInit($jobget, $pid, $size);
        $photoModel = new Photo();
        if(!$photoModel->findOne($pid, array('width', 'height', 'is_animated'))) {
            //获取原图信息
            return $this->_jobResult(FALSE);
        }
        //如果原图不是动画，只取非动画缩略图；如果是动画，根据实际参数获取
        if(!$photoModel->is_animated){
            $is_animate = FALSE;
        }
        //不需要size尺寸的缩略图而只要$correct_size尺寸的缩略图
        //$size = $this->_getCorrectType($photoModel->width, $photoModel->height, $size);
        list($size,$actions) = $this->_getCorrectType2($photoModel->width, $photoModel->height, $size);
        //判断$correct_size尺寸的缩略图是否存在如果存在直接取
        if($this->thumb_source = $photoModel->getSource($size, $is_animate)) {
            return $this->_jobResult(TRUE);
        }
        $originSource = $photoModel->getSource();
        if($originSource && $tmp_file = $originSource->downBuffer()) {
            $imagine = new Imagine\Imagick\Imagine();
            $image = $imagine->open($tmp_file);
            
            //如果是取最大图，则按照宽等比例缩放
            /*
            if($this->_reqLarge()) {
                $image->resize(new Imagine\ Image\ Box($size, 0))->save($thumb_file, array('quality' => Core::config('photo_quality')));
            } else {
                $image->thumbnail(new Imagine\ Image\ Box($size, $size))->save($thumb_file, array('quality' => Core::config('photo_quality')));
            }
            */
            $max_execution_time = ini_get('max_execution_time');
            ini_set('max_execution_time', 300);
            set_time_limit(300);
            if($is_animate){
                //$thumb_file = $tmp_file . '.gif';
                //$image->save($thumb_file, array('quality' => Core::config('photo_quality'), 'flatten' => 'false'));
                $thumb_file = $this->_processThumbAnimate($tmp_file, $actions);
            }else{
                $thumb_file = $tmp_file . '.jpg';
                //如果不需要动画缩略图只取一帧
                if($photoModel->is_animated){
//                     $layers = $image->layers();
//                     $frame = count($layers);
//                     $layer = $layers[$frame-1];
                    foreach($image->layers() as $ilayer){
                        $layer = $ilayer;
                        break;
                    }
                    
                    $this->_processThumb($layer, $actions);
                    //$layer->save($thumb_file, array('quality' => Core::config('photo_quality')));
                    $layer->save($thumb_file);
                }else{
                    $this->_processThumb($image, $actions);
                    $image->save($thumb_file);
                }
            }
            ini_set('max_execution_time', $max_execution_time);

            @unlink($tmp_file);
            $this->uploader = new Uploader();
            $this->uploader->process($thumb_file);
        } else {
            $this->uploader = NULL;
        }
        if($this->uploader) {
            //原图属性
            $updata['fmime'] = $photoModel->mime;
            $updata['flength'] = $photoModel->size;
            $updata['fmd5'] = $photoModel->md5;
            $updata['direction'] = $photoModel->direction;
            //缩略图属性
            $updata['type'] = $size;
            $updata['animate'] = $is_animate;
            $updata['mime'] = $this->uploader->getMIME();
            $updata['meta'] = FALSE;
            //缩略图不需要meta信息
            $photoinfo = $this->uploader->getInfo();
            $updata['width'] = $photoinfo['width'];
            $updata['height'] = $photoinfo['height'];
            if($this->uploader->getType() !== Uploader::FILETYPE_IMAGE) {
                return $this->_jobResult(FALSE);
            }
            if($originSource->upBuffer($this->uploader->tmpfile, $updata)) {
                $this->thumb_source = $originSource;
                return $this->_jobResult(TRUE);
            }
        }
        return $this->_jobResult(FALSE);
    }
    
    private function _processThumb($image, $actions){
        if(isset($actions['resize'])){
            $image->resize($actions['resize']);
        }
        if(isset($actions['crop'])){
            $image->crop(new Imagine\Image\Point(0, 0),$actions['crop']);
        }
    }
    
    private function _processThumbAnimate($input_file, $actions){
        $cmd = Core::config('imagick_convert_cmd');
        //输出文件就是输入文件
        $output_file = $input_file1 = $input_file2 = $input_file;
        
        $output_file1 = $input_file1 . '.gif';
        if(isset($actions['resize'])){
            $box = $actions['resize'];
            $w = $box->getWidth()?$box->getWidth():'';
            $h = $box->getHeight()?$box->getHeight():'';
            $cmdout1 = Core::cmdRun("{$cmd} '{$input_file1}' -coalesce -thumbnail {$w}x{$h} -layers optimize '{$output_file1}'", $code1);
            //第一次操作的输出作为第二次操作的输入
            $output_file = $input_file2 = $output_file1;
        }
        
        $output_file2 = $input_file2 . '.gif';
        if(isset($actions['crop'])){
            $box = $actions['crop'];
            $w = $box->getWidth();
            $h = $box->getHeight();
            $cmdout2 = Core::cmdRun("{$cmd} '{$input_file2}' -coalesce -repage 0x0 -crop {$w}x{$h}+0+0 +repage '{$output_file2}'", $code2);
            $output_file = $output_file2;
        }
        
        if($code1){
            Core::header('x-apiperf-imgcrt:'.json_encode($cmdout1));
            Core::fault(500);
        }
        if($code2){
            Core::header('x-apiperf-imgcrt:'.json_encode($cmdout2));
            Core::fault(500);
        }
        
        return $output_file;
    }

    public function rotate($jobget, $pid = 0, $direction = 1, $is_step = TRUE) {
        list($pid, $direction) = $this->_jobInit($jobget, $pid, $direction);
        $photoModel = new Photo();
        if(!$photoModel->findOne($pid)) {
            //获取原图信息
            return $this->_jobResult(FALSE);
        }
        if(!$is_step){
            $photoModel->direction = $direction;
        }else{
            $photoModel->direction += $direction;
            if($photoModel->direction >= 4) 
                $photoModel->direction = 0;
            if($photoModel->direction < 0) 
                $photoModel->direction = 3;
        }
        //判断是否存在
        $this->thumb_source = $photoModel->getSource();
        if(!$this->thumb_source) {
            $raw = (array) $photoModel;
            $raw['direction'] = 0;
            $originSource = $photoModel->getRawSource($raw);
            if($originSource && $tmp_file = $originSource->downBuffer()) {
                $angle = $photoModel->direction * 90;
                $imagine = new Imagine\Imagick\Imagine();
                $image = $imagine->open($tmp_file);
                $thumb_file = $tmp_file . '.jpg';
                $image->rotate($angle)->save($thumb_file, array('quality' => Core::config('photo_quality')));
                @unlink($tmp_file);
                $this->uploader = new Uploader();
                $this->uploader->process($thumb_file);
                //原图属性
                $updata['fmime'] = $photoModel->mime;
                $updata['flength'] = $photoModel->size;
                $updata['fmd5'] = $photoModel->md5;
                $updata['direction'] = $photoModel->direction;
                //缩略图属性
                $updata['mime'] = $this->uploader->getMIME();
                $updata['meta'] = FALSE;
                //缩略图不需要meta信息
                $photoinfo = $this->uploader->getInfo();
                $updata['width'] = $photoinfo['width'];
                $updata['height'] = $photoinfo['height'];
                if($this->uploader->getType() !== Uploader::FILETYPE_IMAGE) {
                    return $this->_jobResult(FALSE);
                }
                if($originSource->upBuffer($this->uploader->tmpfile, $updata)) {
                    $this->thumb_source = $originSource;
                }
            }
        }
        if($this->thumb_source) {
            $row['width'] = $this->thumb_source->width;
            $row['height'] = $this->thumb_source->height;
            $row['direction'] = $photoModel->direction;
            $row['mtime'] = time();
            $photoModel->update($row);
            return $this->_jobResult(TRUE);
        }
        return $this->_jobResult(FALSE);
    }
    
    public function crop_by_url($tmpfile,$scale_width,$x,$y,$w,$h){
        $imagine = new Imagine\Imagick\Imagine();
        $image = $imagine->open($tmpfile);
        
        $image->resize(new Imagine\Image\Box($scale_width, 0))
            ->crop(new Imagine\Image\Point($x, $y), new Imagine\Image\Box($w, $h))
            ->save($tmpfile);
        
        $uploader = new Uploader();
        $uploader->process($tmpfile);
        
        return $uploader;
    }

    public function output() {
        if($this->uploader) {
            Core::header('Content-Type: ' . $this->uploader->getMIME());
            Core::header('Content-Transfer-Encoding: binary');
            Core::header('Content-Length: ' . $this->uploader->getLength());
            $f = fopen($this->uploader->tmpfile, 'r');
            if($f) {
                while($data = fread($f, 8096)) {
                    echo $data;
                }
                fclose($f);
                unlink($this->uploader->tmpfile);
                Core::quit();
            }
        } elseif($this->thumb_source) {
            $this->thumb_source->output();
        }
    }
}
