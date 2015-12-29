<?php

defined('SYSPATH') OR die('No direct access allowed.');
/*
 * [] (C)1999-2009 ND Inc.
 * 相册服务端配置文件
 */
//根据环境配置
if (IN_PRODUCTION === TRUE) {
    $config['thumb'] = 'http://momo.im/';
    $config['avatar'] = 'http://momo.im/';
    $config['recordThumb'] = 'http://momo.im/';
} else {
    $config['thumb'] = 'http://uap26.91.com/';
    $config['avatar'] = 'http://uap26.91.com/';
    $config['recordThumb'] = 'http://uap26.91.com/';
}

