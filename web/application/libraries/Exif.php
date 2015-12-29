<?php
/*
 * [UAP Album] (C) 1999-2009 ND Inc.
 * 图像文件Exif信息读取
 * @TODO
 * $img_exif = Exif::get_exif_info($img);
*/
defined ( 'SYSPATH' ) or die ( 'No direct script access.' );

class Exif_Core {

    public static function null2space($arr_val) {
        return isset ( $arr_val ) ? $arr_val : "";
    }

    public static function get_img_val($exif_info, $val_arr) {
        $info_val = "未知";
        foreach ( $val_arr as $name => $val ) {
            if ($name == $exif_info) {
                $info_val = &$val;
                break;
            }
        }
        return $info_val;
    }

    public static function get_exif_arr($img) {
        return @exif_read_data($img, 0, true);
    }

    public static function get_exif_info($img) {

        $exif = Exif::get_exif_arr($img);

        $imgtype = array ("", "GIF", "JPG", "PNG", "SWF", "PSD", "BMP", "TIFF(intel byte order)", "TIFF(motorola byte order)", "JPC","JP2", "JPX", "JB2", "SWC", "IFF", "WBMP", "XBM" );

        $Orientation = array ("", "top left side", "top right side", "bottom right side", "bottom left side", "left side top", "right side top", "right side bottom", "left side bottom" );

        $ResolutionUnit = array ("", "", "英寸", "厘米" );

        $YCbCrPositioning = array ("", "the center of pixel array", "the datum point" );

        $ExposureProgram = array ("未定义", "手动", "标准程序", "光圈先决", "快门先决", "景深先决", "运动模式", "肖像模式", "风景模式" );

        $MeteringMode_arr = array ("0" => "未知", "1" => "平均", "2" => "中央重点平均测光", "3" => "点测", "4" => "分区", "5" => "评估", "6" => "局部", "255" => "其他" );

        $Lightsource_arr = array ("0" => "未知", "1" => "日光", "2" => "荧光灯", "3" => "钨丝灯", "10" => "闪光灯", "17" => "标准灯光A", "18" => "标准灯光B","19" => "标准灯光C", "20" => "D55", "21" => "D65", "22" => "D75", "255" => "其他" );

        $Flash_arr = array ("0" => "flash did not fire", "1" => "flash fired", "5" => "flash fired but strobe return light not detected", "7" => "flash fired and strobe return light detected" );

        $exif_info = array ("文件名" => isset ( $exif ['FILE'] ['FileName'] ) ? $exif ['FILE'] ['FileName'] : "",
                "文件类型" => isset($exif ['FILE'] ['FileType']) ? (isset ( $imgtype [$exif ['FILE'] ['FileType']] ) ? $imgtype [$exif ['FILE'] ['FileType']] : "") : "",
                "文件格式" => isset ( $exif ['FILE'] ['MimeType'] ) ? $exif ['FILE'] ['MimeType'] : "",
                "文件大小" => isset ( $exif ['FILE'] ['FileSize'] ) ? number_format ($exif ['FILE'] ['FileSize']  / 1000,  0). " KB" : "",
                "时间戳" => isset ( $exif ['FILE'] ['FileDateTime'] ) ? $exif ['FILE'] ['FileDateTime'] : "",
                "图片说明" => isset ( $exif ['IFD0'] ['ImageDescription'] ) ? $exif ['IFD0'] ['ImageDescription'] : "",
                "制造商" => isset ( $exif ['IFD0'] ['Make'] ) ? $exif ['IFD0'] ['Make'] : "",
                "型号" => isset ( $exif ['IFD0'] ['Model'] ) ? $exif ['IFD0'] ['Model'] : "",
                "方向" => isset($exif['IFD0']['Orientation']) ? (isset ( $Orientation[$exif['IFD0']['Orientation']] ) ? $Orientation[$exif['IFD0']['Orientation']] : "") : "",
                "水平分辨率" => isset ( $exif ['IFD0'] ['XResolution'] ) ? $exif ['IFD0'] ['XResolution'] : "" . isset($exif ['IFD0'] ['ResolutionUnit']) ? (isset ( $ResolutionUnit [$exif ['IFD0'] ['ResolutionUnit']] ) ? $ResolutionUnit [$exif ['IFD0'] ['ResolutionUnit']] : "") : "",
                "垂直分辨率" => isset ( $exif ['IFD0'] ['YResolution'] ) ? $exif ['IFD0'] ['YResolution'] : "" . isset($exif ['IFD0'] ['ResolutionUnit']) ? ( isset ( $ResolutionUnit [$exif ['IFD0'] ['ResolutionUnit']] ) ? $ResolutionUnit [$exif ['IFD0'] ['ResolutionUnit']] : "" ) : "",
                "创建软件" => isset ( $exif ['IFD0'] ['Software'] ) ? $exif ['IFD0'] ['Software'] : "",
                "修改时间" => isset ( $exif ['IFD0'] ['DateTime'] ) ? $exif ['IFD0'] ['DateTime'] : "",
                "作者" => isset ( $exif ['IFD0'] ['Artist'] ) ? $exif ['IFD0'] ['Artist'] : "",
                "YCbCr位置控制" => isset($exif ['IFD0'] ['YCbCrPositioning']) ? ( isset ( $YCbCrPositioning [$exif ['IFD0'] ['YCbCrPositioning']] ) ? $YCbCrPositioning [$exif ['IFD0'] ['YCbCrPositioning']] : "" ) : "",
                "版权" => isset ( $exif ['IFD0'] ['Copyright'] ) ? $exif ['IFD0'] ['Copyright'] : "",
                "摄影版权" => isset ( $exif ['COMPUTED'] ['Copyright.Photographer'] ) ? $exif ['COMPUTED'] ['Copyright.Photographer'] : "",
                "编辑版权" => isset ( $exif ['COMPUTED'] ['Copyright.Editor'] ) ? $exif ['COMPUTED'] ['Copyright.Editor'] : "",
                "Exif版本" => isset ( $exif ['EXIF'] ['ExifVersion'] ) ? $exif ['EXIF'] ['ExifVersion'] : "",
                "FlashPix版本" => isset ( $exif ['EXIF'] ['FlashPixVersion'] ) ? "Ver. " . number_format (
                        $exif ['EXIF'] ['FlashPixVersion'] / 100, 2 ) : "",
                "拍摄时间" => isset ( $exif ['EXIF'] ['DateTimeOriginal'] ) ? $exif ['EXIF'] ['DateTimeOriginal'] : "",
                "数字化时间" => isset ( $exif ['EXIF'] ['DateTimeDigitized'] ) ? $exif ['EXIF'] ['DateTimeDigitized'] : "",
                "拍摄分辨率高" => isset ( $exif ['COMPUTED'] ['Height'] ) ? $exif ['COMPUTED'] ['Height'] : "",
                "拍摄分辨率宽" => isset ( $exif ['COMPUTED'] ['Width'] ) ? $exif ['COMPUTED'] ['Width'] : "",
                "光圈" => isset ( $exif ['EXIF'] ['ApertureValue'] ) ? $exif ['EXIF'] ['ApertureValue'] : "",
                "快门速度" => isset ( $exif ['EXIF'] ['ShutterSpeedValue'] ) ? $exif ['EXIF'] ['ShutterSpeedValue'] : "",
                "快门光圈" => isset ( $exif ['COMPUTED'] ['ApertureFNumber'] ) ? $exif ['COMPUTED'] ['ApertureFNumber'] : "",
                "最大光圈值" => isset ( $exif ['EXIF'] ['MaxApertureValue'] ) ? "F" . $exif ['EXIF'] ['MaxApertureValue'] : "",
                "曝光时间" => isset ( $exif ['EXIF'] ['ExposureTime'] ) ? $exif ['EXIF'] ['ExposureTime'] : "",
                "F-Number" => isset ( $exif ['EXIF'] ['FNumber'] ) ? $exif ['EXIF'] ['FNumber'] : "",
                "测光模式" => isset ( $exif ['EXIF'] ['MeteringMode'] ) ? Exif::get_img_val ( $exif ['EXIF'] ['MeteringMode'],
                $MeteringMode_arr ) : "",
                "光源" => isset ( $exif ['EXIF'] ['LightSource'] ) ? Exif::get_img_val ( $exif ['EXIF'] ['LightSource'],
                $Lightsource_arr ) : "",
                "闪光灯" => isset ( $exif ['EXIF'] ['Flash'] ) ? Exif::get_img_val ( $exif ['EXIF'] ['Flash'], $Flash_arr ) : "",
                "曝光模式" => isset ( $exif ['EXIF'] ['ExposureMode'] ) ? ($exif ['EXIF'] ['ExposureMode'] == 1 ? "手动" : "自动") : "",
                "白平衡" => isset ( $exif ['EXIF'] ['WhiteBalance'] ) ? ($exif ['EXIF'] ['WhiteBalance'] == 1 ? "手动" : "自动") : "",
                "曝光程序" => isset($exif['EXIF']['ExposureProgram']) ? ( isset( $ExposureProgram[$exif['EXIF']['ExposureProgram']]) ? $ExposureProgram[$exif['EXIF']['ExposureProgram']] : "" ) : "",
                "曝光补偿" => isset ( $exif ['EXIF'] ['ExposureBiasValue'] ) ? $exif ['EXIF'] ['ExposureBiasValue'] . "EV" : "",
                "ISO感光度" => isset ( $exif ['EXIF'] ['ISOSpeedRatings'] ) ? $exif ['EXIF'] ['ISOSpeedRatings'] : "",
                "图像压缩率" => isset ( $exif ['EXIF'] ['CompressedBitsPerPixel'] ) ? $exif ['EXIF'] ['CompressedBitsPerPixel'] . "Bits/Pixel" : "",
                "对焦距离" => isset ( $exif ['COMPUTED'] ['FocusDistance'] ) ? $exif ['COMPUTED'] ['FocusDistance'] . "m" : "",
                "焦距" => isset ( $exif ['EXIF'] ['FocalLength'] ) ? $exif ['EXIF'] ['FocalLength'] . "mm" : "",
                "等价35mm焦距" => isset ( $exif ['EXIF'] ['FocalLengthIn35mmFilm'] ) ? $exif ['EXIF'] ['FocalLengthIn35mmFilm'] . "mm" : "",
                "用户注释编码" => isset ( $exif ['COMPUTED'] ['UserCommentEncoding'] ) ? $exif ['COMPUTED'] ['UserCommentEncoding'] : "",
                "用户注释" => isset ( $exif ['COMPUTED'] ['UserComment'] ) ? $exif ['COMPUTED'] ['UserComment'] : "",
                "色彩空间" => isset ( $exif ['EXIF'] ['ColorSpace'] ) ? ($exif ['EXIF'] ['ColorSpace'] == 1 ? "sRGB" : "Uncalibrated") : "",
                "Exif图像宽度" => isset ( $exif ['EXIF'] ['ExifImageLength'] ) ? $exif ['EXIF'] ['ExifImageLength'] : "",
                "Exif图像高度" => isset ( $exif ['EXIF'] ['ExifImageWidth'] ) ? $exif ['EXIF'] ['ExifImageWidth'] : "",
                "文件来源" => isset ( $exif ['EXIF'] ['FileSource'] ) ? (bin2hex ( $exif ['EXIF'] ['FileSource'] ) == 0x03 ? "digital still camera" : "unknown") : "",
                "场景类型" => isset ( $exif ['EXIF'] ['SceneType'] ) ? (bin2hex ( $exif ['EXIF'] ['SceneType'] ) == 0x01 ? "A directly photographed image" : "unknown") : "",
                "缩略图文件格式" => isset ( $exif ['COMPUTED'] ['Thumbnail.FileType'] ) ? $exif ['COMPUTED'] ['Thumbnail.FileType'] : "",
                "缩略图Mime格式" => isset ( $exif ['COMPUTED'] ['Thumbnail.MimeType'] ) ? $exif ['COMPUTED'] ['Thumbnail.MimeType'] : "" );

        $degree = 0;
        if(isset($exif['IFD0']['Orientation'])) {
            $ort = $exif['IFD0']['Orientation'];
            switch($ort) {
                case 1: // nothing
                    break;

                case 2:   // nothing
                    break;

                case 3:
                    $degree = 2;
                    break;

                case 4: // nothing
                    $degree = 2;
                    break;

                case 5:
                    $degree = 1;
                    break;

                case 6:
                    $degree = 1;
                    break;

                case 7:
                    $degree = 3;
                    break;

                case 8:
                    $degree = 3;
                    break;
            }

        }

        $height = isset ( $exif ['COMPUTED'] ['Height'] ) ? $exif ['COMPUTED'] ['Height'] : 0;
        $width  = isset ( $exif ['COMPUTED'] ['Width'] ) ? $exif ['COMPUTED'] ['Width'] : 0;
        //拍摄时间
        $datetime_original = isset ( $exif ['EXIF'] ['DateTimeOriginal'] ) ? $exif ['EXIF'] ['DateTimeOriginal'] : "";
        //相机型号
        $camera_model = isset ( $exif ['IFD0'] ['Model'] ) ? $exif ['IFD0'] ['Model'] : "";

        //文件格式
        $pic_type = isset ( $exif ['FILE'] ['MimeType'] ) ? $exif ['FILE'] ['MimeType'] : "";
        $exif_info['degree'] = $degree;
        $return = array("exif_info" => $exif_info,
                "degree" => $degree,
                "height" => $height,
                "width" => $width,
                "datetime_original" => $datetime_original,
                "pic_type" => $pic_type,
                "camera_model" => $camera_model);

        return $return;

    }


}

?>