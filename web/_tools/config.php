<?php
define( "WB_AKEY" , 'ab35cf3f50c4a3a1b6b2761d8b3b4ad8050164315');
define( "WB_SKEY" , 'd20e41f8a371ff9b50945f6306d0d750');
//根据环境配置
define('API_PATH', sprintf('%s://%s/', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
	|| $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http', $_SERVER['HTTP_HOST']));
