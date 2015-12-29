<?php
class ResponseType {
    const ERROR_LACKPARAMS = '40001:缺乏参数';
    const PHOTO_ERROR_IMAGETYPE = '40010:不支持的图片类型或图片已损坏';
    const PHOTO_ERROR_IMAGESIZE = '40011:单张图片不得超过5Mb';
    const PHOTO_UPLOAD_ERROR_INPUT = '40012:无法获取上传文件';
    const PHOTO_UPLOAD_ERROR_SERVER = '40013:单步上传失败';
    const PHOTO_UPLOAD_OK = '20010:单步上传成功';
    const PHOTO_BPUPLOAD_ERROR_CREATETEMP = '40012:创建临时文件失败';
    const PHOTO_BPUPLOAD_OK_CREATETEMP = '20010:创建临时文件成功';
    const PHOTO_BPUPLOAD_ERROR_SERVER = '40013:分段上传失败';
    const PHOTO_BPUPLOAD_OK = '20011:分段上传成功';
    const PHOTO_BPUPLOAD_OK_CONTINUE = '20012:可继续上传';
    const PHOTO_BPUPLOAD_ERROR_NOTEMP = '40014:临时文件不存在或无权限';
    const PHOTO_BPUPLOAD_ERROR_DAMAGETEMP = '40015:文件不完整，请重新上传';
    const PHOTO_BPUPLOAD_ERROR_NOTEMPSOURCE = '40016:无法获取临时文件';
    const PHOTO_ROTATE_OK = '20010:旋转操作成功';
    const PHOTO_ROTATE_ERROR = '40012:旋转操作失败';
    const PHOTO_UPDATE_OK = '20010:更新成功';
    const PHOTO_UPDATE_ERROR = '40012:更新失败';
    const PHOTO_UPDATE_ERROR_INVALID = '40013:图片不存在';
    const PHOTO_DELETE_OK = '20010:删除成功';
    const PHOTO_DELETE_ERROR = '40012:删除失败';
    const PHOTO_URL_OK = '20010:获取地址成功';
    const PHOTO_ALL_OK = '20010:获取照片成功';
    const PHOTO_UPAVATAR_OK = '20010:头像更新成功';
    const PHOTO_UPAVATAR_ERROR_INVALID = '40012:头像照片不存在';
    const PHOTO_UPAVATAR_ERROR = '40013:头像更新失败';
    const PHOTO_GET_ERROR = '40012:照片不存在';
    const PHOTO_GET_OK = '20010:获取照片成功';
    const FILE_ERROR_DIR_INVALID = '40010:目标目录不存在';
    const FILE_ERROR_UPLOAD = '40011:无法获取上传文件';
    const FILE_ERROR_SIZELIMIT = '40012:单次上传的文件不得超过64Mb';
    const FILE_OK = '20010:文件上传成功';
    const FILE_ERROR_SERVER = '40013:文件上传失败';
    const FILE_OK_DELETE = '20011:文件删除成功';
    const FILE_ERROR_DELETE = '40014:文件删除失败';
    const FILE_ERROR_AUDIOTYPE = '40015:非音频文件';

    public static function getCode($response_type) {
        return substr($response_type, 0, 3);
    }
}
