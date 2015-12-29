<?php
defined('SYSPATH') or die('No direct access allowed.');
/*
 * [MOMO API] (C)1999-2012 ND Inc.
 * 联系人状态常量
 */
//联系人修改状态
// 成功
if (!defined('SUCCESS'))
{
	define('SUCCESS', 1);
}
// 合并成功
if (!defined('MERGE_SUCCESS'))
{
	define('MERGE_SUCCESS', 2);
}
// 冲突
if (!defined('CONFLICT'))
{
	define('CONFLICT', 3);
}
// 合并成功
if (!defined('NO_MODIFY_MERGE_SUCCESS'))
{
	define('NO_MODIFY_MERGE_SUCCESS', 4);
}
// 不存在
if (!defined('NO_EXIST'))
{
	define('NO_EXIST', 5);
}
// 失败
if (!defined('FAIL'))
{
	define('FAIL', 0);
}

interface Contact_Interface {}