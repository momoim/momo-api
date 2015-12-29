<?php

defined('SYSPATH') OR die('No direct access allowed.');

/*
 * [UAP Portal] (C)1999-2010 ND Inc.
 * call_records配置文件
 */

//分库数
$config['divide_db'] = 8;
//分库数
$config['divide_table'] = array('call'=>800,'backup_batch'=>80,'backup_history'=>80,'restore_history'=>80,'delete_history'=>40);
?>