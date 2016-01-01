<?php
if(IN_PRODUCTION === TRUE) {
    //gearman集群地址
    $config['job_servers']='10.1.242.245,10.1.242.126:4730'; //如‘10.0.0.1,10.0.0.2:7003’
    
    //gridfs集群地址
    $config['gridfs_servers']=array(
    	'host'=>'mongodb://10.1.242.242:27027,10.1.242.241:27027', //如‘mongodb://[username:password@]host1[:port1][,host2[:port2:],...]’
    	'db'=>array(
            'photo'=>'momo_photo',
            'file'=>'momo_file',
            'fslog'=>'momo_fslog',
            'pcloud'=>'momo_pcloud'
    	),
    	'user'=>NULL,
    	'pwd'=>NULL,
    	'opt'=>array(
//          'persist' => '',
//          'replicaSet' => true,
    	)
    );
    
    $config['mysql_servers']=array(
    	'host'=>'10.1.242.206',
    	'user'=>'momo',
    	'pwd'=>'dC4DqVCQKAZScY4M',
    	'db'=>'momo_v3', //@todo 仿真:momo_simulate; v2:momo; v3:momo_v3
    	'pconnect'=>0,
    	'charset'=>'utf8'
    );
    
    $config['ndfs_servers']=array(
        'host'=>'10.1.11.47',
        'port'=>'3323',
        'user'=>'yycp_91cloud',
        'pwd'=>'91cloud2012)$@)'
    );
    //图片的key
    $config['source_key']='1zy-_z^jzd%CiLRYq9XD#6gEi@puxg2LnW_r3wecePoW5k';
    $config['source_key_mocloud']='ba*%#5cf8^f)_-//d3IO6?.2fdb!@d><';
    $config['expire_mocloud']=345600; //mocloud资源地址过期时间
    $config['media_mocloud']='http://v1.api.cloud.momo.im/src/mocloud/';
    $config['ndcs_mocloud']='/data/ndcs/';
    $config['ndcs_group']='mocloud';
    //图片地址
    //$config['photo_prefix']='http://api.simulate.momo.im/src/photo/'; //@todo 仿真
    $config['photo_prefix']='http://momoimg.com/photo/'; //@todo v2和v3
    //$config['avatar_prefix']='http://api.simulate.momo.im/src/avatar/'; //@todo 仿真
    $config['avatar_prefix']='http://momoimg.com/avatar/'; //@todo v2和v3
    //$config['thumb_prefix']='http://api.simulate.momo.im/src/file_thumb/'; //@todo 仿真
    $config['thumb_prefix']='http://momoimg.com/file_thumb/'; //@todo v2和v3
    //$config['file_prefix']='http://api.simulate.momo.im/src/file/'; //@todo 仿真
    $config['file_prefix']='http://momoimg.com/file/'; //@todo v2和v3
    //可上传的图片大小
    $config['photo_max_size']=10485760;
    //缩略图尺寸标准
    $config['photo_standard_type']=array(48,130,320,480,780,1024,1600);
    //缩略图质量
    $config['photo_quality']=70;
    //本地临时文件存放目录
    $config['dir_tmp']='/data/tmp/momofs/';
    //log存放目录
    //$config['dir_log'] = '/data/weblogs/api.simulate.momo.im/'; //@todo 仿真
    //$config['dir_log'] = '/data/weblogs/v2.api.momo.im/'; //@todo v2
    $config['dir_log'] = '/data/weblogs/v3.api.momo.im/'; //@todo v3
    //$config['dir_log'] = '/data/weblogs/momoimg.com/'; //@todo momoimg
    
    $config['file_max_size'] = 67108864;
    
    $config['imagick_convert_cmd'] = 'convert';
    
    $config['ffmpeg_binary'] = 'export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/usr/lib;/usr/bin/ffmpeg';
    
    $config['quota_mocloud'] = 5368709120;
} else {
    //gearman集群地址
    $config['job_servers']='127.0.0.1:4730'; //如‘10.0.0.1,10.0.0.2:7003’
    
    //gridfs集群地址
    $config['gridfs_servers']=array(
    	'host'=>'mongodb://mongo:27017', //如‘mongodb://[username:password@]host1[:port1][,host2[:port2:],...]’
    	'db'=>array(
            'photo'=>'momo_photo',
            'file'=>'momo_file',
            'fslog'=>'momo_fslog',
            'pcloud'=>'momo_pcloud'
    	),
    	'user'=>NULL,
    	'pwd'=>NULL,
    	'opt'=>array(
//          'persist' => '',
//          'replicaSet' => true,
    	)
    );
    
    $config['mysql_servers']=array(
    	'host'=>'mysql',
    	'user'=>'root',
    	'pwd'=>'123456',
    	'db'=>'momo_v3',
    	'pconnect'=>0,
    	'charset'=>'utf8'
    );
    $config['ndfs_servers']=array(
        'host'=>'192.168.152.6',
        'port'=>'5325',
        'user'=>'fidtest',
        'pwd'=>'123456'
    );
    //图片的key
    $config['source_key']='i7^O';
    $config['source_key_mocloud']='!@d><';
    $config['expire_mocloud']=345600; //mocloud资源地址过期时间
    $config['media_mocloud']='http://192.168.99.100:8080/src/mocloud/';
    $config['ndcs_mocloud']='/mnt/ndcs/';
    $config['ndcs_group']='develop';
    //图片地址
    $config['photo_prefix']='http://192.168.99.100:8080/src/photo/';
    $config['avatar_prefix']='http://192.168.99.100:8080/src/avatar/';
    $config['thumb_prefix']='http://192.168.99.100:8080/src/file_thumb/';
    $config['file_prefix']='http://192.168.99.100:8080/src/file/';
    //可上传的图片大小
    $config['photo_max_size']=10485760;
    //缩略图尺寸标准
    $config['photo_standard_type']=array(48,130,320,480,780,1024,1600);
    //缩略图质量
    $config['photo_quality']=70;
    //本地临时文件存放目录
    $config['dir_tmp']='/tmp/';
    //log存放目录
    $config['dir_log'] = FS_ROOT . 'logs' . DS;
    
    $config['file_max_size'] = 67108864;
    
    $config['imagick_convert_cmd'] = 'convert';
    
    $config['ffmpeg_binary'] = 'ffmpeg';
    
    $config['quota_mocloud'] = 5368709120;
}
