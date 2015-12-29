<?php
/*
 * [MOMO API] (C)1999-2011 ND Inc.
 * 资源模型文件
 */
class Uploader {
    const FILETYPE_UNDEFINED = 0;
    const FILETYPE_AUDIO = 1;
    const FILETYPE_IMAGE = 2;
    const FILETYPE_VIDEO = 4;
    /**
     * 
     * 文件名
     * @var string
     */
    public $filename;
    /**
     * 
     * 临时文件的路径
     * @var string
     */
    public $tmpfile;
    /**
     * 
     * 临时文件的mime
     * @var string
     */
    protected $mime;
    /**
     * 
     * 临时文件的大小
     * @var int
     */
    protected $length;
    /**
     * 
     * 临时文件的MD5校验
     * @var string
     */
    protected $md5;
    /**
     * 
     * 文件类型
     * @var int
     */
    protected $filetype;
    /**
     * 
     * 文件的额外信息比如图片要获得长、宽和图片类型
     * @var array
     */
    protected $info;
    /**
     * 
     * 文件的元信息如exif、id3等
     * @var array
     */
    protected $meta;
    /**
     * 
     * 是否是gif动画
     * @var bool
     */
    protected $animated;

    public function __construct() {
    }

    public function upload() {
        if(isset($_SERVER['HTTP_CONTENT_TYPE'])) 
            $contentType = $_SERVER['HTTP_CONTENT_TYPE'];
        if(isset($_SERVER['CONTENT_TYPE'])) 
            $contentType = $_SERVER['CONTENT_TYPE'];
        $file_field = '';
        if($_FILES) {
            foreach($_FILES as $key => $value) {
                $file_field = $key;
            }
        }
        // php://input 不支持 enctype="multipart/form-data"
        if(strpos($contentType, 'multipart') !== false && $file_field) {
            if(isset($_FILES[$file_field]['tmp_name']) && is_uploaded_file($_FILES[$file_field]['tmp_name']) && (int) $_FILES[$file_field]['error'] === UPLOAD_ERR_OK) {
                // 已上传的临时文件
                $tmp_file = $_FILES[$file_field]['tmp_name'];
                $tmp_size = $_FILES[$file_field]['size'];
                //$tmp_type=$_FILES[$file_field]['type'];
                //$tmp_name=$_FILES[$file_field]['name'];
            } else {
                return FALSE;
            }
        } else {
            $tmp_file = Core::tempname();
            $tmp_handler = fopen($tmp_file, 'wb');
            if($tmp_handler) {
                $in = fopen("php://input", "rb");
                if($in) {
                    while($buff = fread($in, 8096)) 
                        fwrite($tmp_handler, $buff);
                    fclose($in);
                } else {
                    return FALSE;
                }
                fclose($tmp_handler);
            } else {
                return FALSE;
            }
        }
        return $this->process($tmp_file, '', $tmp_size);
    }
    /**
     * 
     * 构建上传类
     * @param string $tmp 临时文件的路径
     * @param int $length 临时文件的大小
     * @param string $mime 临时文件的mime
     */
    public function process($tmp, $mime = '', $length = 0, $md5 = '') {
        if(is_readable($tmp)) {
            $this->tmpfile = $tmp;
        } else {
            return FALSE;
        }
        $this->mime = $mime;
        $this->length = $length;
        $this->md5 = $md5;
        return TRUE;
    }

    public function getTitle() {
        if($this->filename) 
            return $this->filename;
        if($_FILES) {
            foreach($_FILES as $key => $value) {
                return $value['name'];
            }
        }
        if(isset($_GET['filename'])){
            return $_GET['filename'];
        }
        return '';
    }
    /**
     * 
     * 获取文件的mime
     */
    public function getMIME() {
        if(!$this->mime) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($this->tmpfile);
            $this->mime = strtolower($mime);
            if(!preg_match('/.+\/.+/', $this->mime)){
                $this->mime = 'application/octet-stream';
            }
        }
        return $this->mime;
    }

    public function isAnimated() {
        $mime = $this->getMIME();
        if($mime == 'image/gif' && !is_bool($this->animated)) {
            $fh = fopen($this->tmpfile, 'rb');
            if($fh) {
                $count = 0;
                $chunks = '';
                while(!feof($fh) && $count < 2) {
                    $chunks .= fread($fh, 20480);
                    //20k
                    $count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00\x2C#s', $chunks, $matchs);
                }
                if($count > 1) 
                    $this->animated = TRUE;
                fclose($fh);
                return $this->animated;
            }
            $this->animated = FALSE;
        }
        return $this->animated;
    }
    /**
     * 
     * 获取文件大小
     */
    public function getLength() {
        if(!$this->length) {
            $fh = fopen($this->tmpfile, 'rb');
            if($fh) {
                $stat = fstat($fh);
                $this->length = $stat['size'];
                fclose($fh);
            }
        }
        return $this->length;
    }
    /**
     * 
     * 获取文件MD5签名
     */
    public function getMD5() {
        if(!$this->md5) {
            $this->md5 = md5_file($this->tmpfile);
        }
        return $this->md5;
    }
    /**
     * 
     * 获取文件类型
     */
    public function getType() {
        if(!$this->filetype) {
            $image_mime = array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/bmp', 'image/x-ms-bmp', 'image/x-bmp', 'image/vnd.wap.wbmp', 'image/tiff', 'image/vnd.microsoft.icon',);
            $mime = $this->getMIME();
            if($mime == 'application/octet-stream') {
                if(@getimagesize($this->tmpfile)) {
                    $this->filetype = self::FILETYPE_IMAGE;
                }
            }
            if(!$this->filetype) {
                if(in_array($mime, $image_mime)) {
                    $this->filetype = self::FILETYPE_IMAGE;
                } elseif($mime && preg_match('#^audio/|^video/#', $mime)) {
                    if(extension_loaded('ffmpeg') && false) {
                        $movie = new ffmpeg_movie($this->tmpfile);
                        if($movie->hasVideo()) {
                            $this->filetype = self::FILETYPE_VIDEO;
                        } else {
                            $this->filetype = self::FILETYPE_AUDIO;
                        }
                    } else {
                        require_once FS_ROOT.'include/ffmpeg/FFmpegAutoloader.php';
                        $ffmpegOutput = new FFmpegOutputProvider(Core::config('ffmpeg_binary'));
                        $ffmpeMovie = new FFmpegMovie($this->tmpfile, $ffmpegOutput, Core::config('ffmpeg_binary'));
                        if($ffmpeMovie->hasVideo()) {
                            $this->filetype = self::FILETYPE_VIDEO;
                        } else {
                            $this->filetype = self::FILETYPE_AUDIO;
                        }

                        //$this->filetype = self::FILETYPE_AUDIO| self::FILETYPE_VIDEO;
                    }
                } else {
                    $this->filetype = self::FILETYPE_UNDEFINED;
                }
            }
        }
        return $this->filetype;
    }
    /**
     * 
     * 获取文件信息
     */
    public function getInfo() {
        if(!$this->info) {
            switch($this->getType()) {
                case self::FILETYPE_IMAGE:
                $this->info = $this->_getImageInfo();
                break;
                
                case self::FILETYPE_AUDIO:
                case self::FILETYPE_VIDEO:
                $this->info = $this->_getMovieInfo();
                break;
                
                case self::FILETYPE_UNDEFINED:
                default:
                break;
            }
        }
        return $this->info;
    }
    
    public function getThumbInstance(){
        if($this->getType() == self::FILETYPE_VIDEO){
            require_once FS_ROOT.'include/ffmpeg/FFmpegAutoloader.php';
            $ffmpegOutput = new FFmpegOutputProvider(Core::config('ffmpeg_binary'));
            $ffmpeMovie = new FFmpegMovie($this->tmpfile, $ffmpegOutput, Core::config('ffmpeg_binary'));
//             $duration = $ffmpeMovie->getDuration();
//             $ffmpegFrame = $ffmpeMovie->getFrameAtTime($duration/2);
            $ffmpegFrame = $ffmpeMovie->getFrame();
            if($ffmpegFrame){
                $im = $ffmpegFrame->toGDImage();
                $tn_tmp = Core::tempname();
                imagejpeg($im, $tn_tmp);
                imagedestroy($im);
                $tnUploader = new Uploader();
                $tnUploader->process($tn_tmp);
                return $tnUploader;
            }
            
            return NULL;
        }
    }

    private function _getMovieInfo() {
        $movieinfo = NULL;
        if(extension_loaded('ffmpeg') && false) {
            $movie = new ffmpeg_movie($this->tmpfile);
            $duration = $movie->getDuration();
            $movieinfo = array('duration'=>$duration);
        }else{
            require_once FS_ROOT.'include/ffmpeg/FFmpegAutoloader.php';
            $ffmpegOutput = new FFmpegOutputProvider(Core::config('ffmpeg_binary'));
            $ffmpeMovie = new FFmpegMovie($this->tmpfile, $ffmpegOutput, Core::config('ffmpeg_binary'));
            $duration = $ffmpeMovie->getDuration();
            $artist = $ffmpeMovie->getArtist();
            $rotate = $ffmpeMovie->getRotate();
            $movieinfo = array('duration'=>$duration, 'artist'=>$artist, 'orientation'=>$rotate);
        }
        return $movieinfo;
    }

    private function _getImageInfo() {
        $imageinfo = NULL;
//         if($size = @getimagesize($this->tmpfile)) {
//             if($size[0] > 0 && $size[1] > 0) {
//                 $imageinfo['width'] = $size[0];
//                 $imageinfo['height'] = $size[1];
//                 //$imageinfo['image_type']=$size[2];
//             }
//         }
        if(!$imageinfo && class_exists('Imagick')) {
            $img = new Imagick($this->tmpfile);
            $imageinfo['width'] = $img->getImageWidth();
            $imageinfo['height'] = $img->getImageHeight();
            try{
                $imageinfo['orientation'] = $img->getImageOrientation();
            }catch(ImagickException $e){
                $imageinfo['orientation'] = 0;
            }
            //$imageinfo['image_type']=@exif_imagetype($this->tmpfile);
        }
        return $imageinfo;
    }
    /**
     * 
     * 获取文件的元信息
     */
    public function getMeta() {
        if(!$this->meta) {
            switch($this->getType()) {
                case self::FILETYPE_IMAGE:
                $this->meta = @exif_read_data($this->tmpfile);
                break;
                case self::FILETYPE_UNDEFINED:
                default:
                break;
            }
        }
        return $this->meta;
    }
}
