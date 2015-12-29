<?php
//用户状态
// 体验用户
if (! defined('EXPERIENCE_USER')) {
    define('EXPERIENCE_USER', 0);
}
// 未激活用户
if (! defined('NO_ACTIVED_USER')) {
    define('NO_ACTIVED_USER', 1);
}

// 激活用户
if (! defined('ACTIVED_USER')) {
    define('ACTIVED_USER', 2);
}
// 普通用户
if (! defined('USER')) {
    define('USER', 3);
}
// 审核用户
if (! defined('REVIEWED_USER')) {
    define('REVIEWED_USER', 4);
}

// 机器人用户
if (! defined('ROBOT')) {
	define('ROBOT', 2);
}

interface User_Interface
{
    
}