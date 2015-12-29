<?php
/*
 * [UAP Album] (C) 1999-2009 ND Inc.
 * 本类为图像处理类，用于生成缩略图
 * @TODO
*/
defined ( 'SYSPATH' ) or die ( 'No direct script access.' );

class Image_Core {
    /**
     * 构造函数
     */
    public function __construct() {
        //
    }

    /**
     * 根据图片宽度生成缩略图文件名
     */
    private static function getThumbName($filename, $width) {
        $ext = "." . pathinfo ( $filename, PATHINFO_EXTENSION );
        return str_replace ( $ext, '_' . $width . $ext, $filename );
    }
 
    /**
     * 将缩略图批量写入硬盘
     */
    private static function createImages($images) {
        foreach ( $images as $filename => $image ) {
            $image->writeImage($filename);
        }
    }

    /**
     * 获取图片的宽度、高度
     */
    public static function getImagesWidthHight($file_name) {
        try{
            $img = new Imagick($file_name);

            $width = $img->getImageWidth();
            $height = $img->getImageHeight();
            return array("width" => $width, "height" => $height);
        } catch (Exception $e) {
            return array("width" => 0, "height" => 0);
        }
    }

    public static function createThumbWithExif($file_name, $type) {
        $image = new Imagick ( $file_name );

        //缩放
        $image->resizeImage($type, $type, 0, 1, true);

        //缩略图路径文件名
        $thumb_path = Image::getThumbName ( $file_name, $type ) ;
        
        //写缩略图
        $image->writeImage($thumb_path);

        return $thumb_path;
    }
    
    public static function createThumbWithoutExif($file_name, $type) {

        $image = new Imagick ( $file_name );

        //缩放
        if($type == 80 ) {
            $image->cropThumbnailImage (80, 80);
        }  else {
            //判断大小
            $img_w_h = Image::getImagesWidthHight($file_name);
            $width = $img_w_h['width'];
            $height = $img_w_h['height'];
            $fitbyWidth = ($width >= $height) ? true : false ;
            if($fitbyWidth) {
                $image->thumbnailImage($type, 0, false);
            } else {
                $image->thumbnailImage(0, $type, false);
            }
        }

        //缩略图路径文件名
        $thumb_path = Image::getThumbName ( $file_name, $type ) ;

        //缩放
        //$image->resizeImage($type, $type, 0, 1, true);

        //写缩略图
        $image->writeImage($thumb_path);

        return $thumb_path;
    }

    /**
     * 生成制定（格式）大小的缩略图
     */
    public static function createThumb_by_type($file_name, $type) {

        $image = new Imagick ( $file_name );

        //缩放
        if($type == 80 ) {
            $image->cropThumbnailImage (80, 80);

        } else if($type == 120)  {
            $image->cropThumbnailImage (120, 120);

        } else if($type == 480) {
            //480 保留EXIF 信息
            $image->resizeImage(480, 480, 0, 1, true);
        } else {
            //判断大小
            $img_w_h = Image::getImagesWidthHight($file_name);
            $width = $img_w_h['width'];
            $height = $img_w_h['height'];
            $fitbyWidth = ($width >= $height) ? true : false ;
            if($fitbyWidth) { 
                $image->thumbnailImage($type, 0, false);
            } else {
                $image->thumbnailImage(0, $type, false);
            }
        }
        
        //缩略图路径文件名
        $thumb_path = Image::getThumbName ( $file_name, $type ) ;

        //缩放
        //$image->resizeImage($type, $type, 0, 1, true);

        //写缩略图
        $image->writeImage($thumb_path);

        return $thumb_path;
    }

   
    /**
     * 旋转图片
     */
    public static function rotate_by_degree($file_path, $degree) {
        $image = new Imagick ( $file_path );
        $image->rotateImage(new ImagickPixel(), $degree);
        $image->writeImage($file_path);

    }
  


} //end Image_Core
?>
