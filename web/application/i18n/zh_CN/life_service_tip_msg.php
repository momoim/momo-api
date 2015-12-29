<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * 生活信息语言包文件
 * 
 * 
 * @author Yufeng <ivwsai@gmail.com>
 * @copyright (c) 2010-2011 MOMO Team
 */
$lang = array(
        'title' => array('required' => '名称不能为空', 'length' => '长度限制在2-60个字', 'default' => '输入错误.'),
        'description' => array('required' => '描述不能为空', 'default' => '输入错误.'),
        'category' => array('required' => '信息种类不能为空', 'chars' => '值不在给定范围', 'default' => '输入错误.'),
        'type' => array('required' => '信息类型不能为空', 'chars' => '值不在给定范围', 'default' => '输入错误.'),
        'price' => array('required' => '价格不能为空', 'numeric' => '只能为数字', 'default' => '输入错误.'),
        'privacy' => array('default' => '输入错误.'),
        'file_photo' => array('is_array' => '参数类型不对', 'default' => '输入错误.')
);
