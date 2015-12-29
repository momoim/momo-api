<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * @package  UAP
 *
 * UAP Server 连接配置
 */
//所有应用的ID
$config['apps'] = array(1,2,3,4,5,6,7);

//本群组应用ID,为群相册服务
$config['GroupAppid'] = 2;

//事件KEY所对应的应用ID
$config['app'] = array(
	'user' => 1,//用户
	'friend' => 2,//好友
	'photo' => 3,//照片
	'record' => 4,//广播
	'diary' => 5,//日记
	'vote' => 6,//投票
	'group' => 7,//群组
	'comment' => 9,//评论
	'index'=>8,//首页留言
	'groupphoto'=>10,//群组图片
	'album'=>11,//相册评论
	'groupalbum'=>12,//群组相册评论
	'praise'=>13,//赞
	'action'=>14,//活动
	'activity'=>15,//活动群
	'lifeservice'=>16//二手信息
);

//群动态事件类型
$config['group_feed'] = array(
	'add_topic' => 1, //创建群话题
	'upload_photo' => 2, //上传群图片
	'upload_file' => 3 //上传群文件
);

//Oauth key
$config['oauth'] = array(
	'weibo.com' => array('WB_AKEY'=>'4156114832', 'WB_SKEY'=>'1d537b180c28978e38bd6aa3b0fa7ea3','sms_present'=>100),
	't.qq.com' => array('WB_AKEY'=>'***', 'WB_SKEY'=>'***','sms_present'=>0),
	'renren.com' => array('WB_AKEY'=>'cf233d263dfe4aba82ff2479487a3970', 'WB_SKEY'=>'4dcea4837a674a3cbddb72173bf13835','sms_present'=>0),
	'kaixin001.com' => array('WB_AKEY'=>'733631211252fe29816fc5534a808493', 'WB_SKEY'=>'021738b9554ac0669f07da4626f07742','APP_ID'=>'100022536','sms_present'=>0),
	'pengyou.com' => array('WB_AKEY'=>'***', 'WB_SKEY'=>'***','sms_present'=>0),
	'douban.com' => array('WB_AKEY'=>'08613f9139bfddb01923beb3ad3f9910', 'WB_SKEY'=>'05f4a7c1274b1299','sms_present'=>0),
	't.sohu.com' => array('WB_AKEY'=>'lN8eSHfeT0HTrFOosBmL', 'WB_SKEY'=>'LTK5*w6y5V)qR-4W#T(gfe*#RJO-1bqKL3J#5PJ-','sms_present'=>0)
);

//群动态事件类型
$config['message_handle'] = array(
	'1', //对方请求加你为好友
	'102', //你忽略好友请求
	'5', //群组入群申请
	'502', //忽略对方入群申请请求
	'7', //对方邀请我加入群
	'702', //对方邀请我加入群,我忽略邀请
	'9', //对方邀请我参加活动
	'903', //对方邀请我参加活动,我拒绝参加
	'12', //公开群普通成员邀请我加入群
	'16' //公开群管理员邀请我加入群
);

$config['xiaomo_cid'] = '1'; //小秘联系人ID
//根据环境配置
if(IN_PRODUCTION === TRUE) {
    //本应用的ID
    $config['appid'] = 9;
    $config['mongodb'] = 'mongodb://10.1.242.242:27027,10.1.242.241:27027';
    $config['http_push'] = 'http://121.207.242.210/intime/publish?id=';
    $config['rabbitmq'] = array(
        'host' => '121.207.242.244',
        'port' => 5672,
        'login' => 'sifusf*4&5&343!',
        'password' => 'ndpassw0rd',
        'vhost' => '/'
    );
    $config['xiaomo'] = '353';
    $config['xiaomo_qun'] = '136';
    $config['ip_permit'] = array(
    	'121.207.242.118',
    	'121.207.242.119',
    	'121.207.242.120',
    	'121.207.242.121',
    	'121.207.242.122',
    	'121.207.242.123',
    	'121.207.242.210'
    );
    
    $config['sphinx_host'] = '10.1.242.128';
    $config['sphinx_port'] = 8817;
} else {
    //本应用的ID
    $config['appid'] = 5;
    $config['mongodb'] = 'mongodb://mongo:27017';
    $config['http_push'] = 'http://192.168.94.26/intime/publish?id=';
    $config['rabbitmq'] = array(
        'host' => 'rabbitmq',
        'port' => 5672,
        'login' => 'sifusf*4&5&343!',
        'password' => 'ndpassw0rd',
        'vhost' => '/'
    );
    $config['xiaomo'] = '10650764';
    $config['xiaomo_qun'] = '45';
}
