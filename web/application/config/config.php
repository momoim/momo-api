<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Base path of the web site. If this includes a domain, eg: localhost/kohana/
 * then a full URL will be used, eg: http://localhost/kohana/. If it only includes
 * the path, and a site_protocol is specified, the domain will be auto-detected.
 */
$config['site_domain'] = '/';

/**
 * Force a default protocol to be used by the site. If no site_protocol is
 * specified, then the current protocol is used, or when possible, only an
 * absolute path (with no protocol/domain) is used.
 */
$config['site_protocol'] = 'http';

/**
 * Name of the front controller for this application. Default: index.php
 *
 * This can be removed by using URL rewriting.
 */
$config['index_page'] = 'index.php';

/**
 * Fake file extension that will be added to all generated URLs. Example: .html
 */
$config['url_suffix'] = '';

/**
 * Length of time of the internal cache in seconds. 0 or FALSE means no caching.
 * The internal cache stores file paths and config entries across requests and
 * can give significant speed improvements at the expense of delayed updating.
 */
$config['internal_cache'] = FALSE;

/**
 * Internal cache directory.
 */
$config['internal_cache_path'] = APPPATH.'cache/';

/**
 * Enable internal cache encryption - speed/processing loss
 * is neglible when this is turned on. Can be turned off
 * if application directory is not in the webroot.
 */
$config['internal_cache_encrypt'] = FALSE;

/**
 * Encryption key for the internal cache, only used
 * if internal_cache_encrypt is TRUE.
 *
 * Make sure you specify your own key here!
 *
 * The cache is deleted when/if the key changes.
 */
$config['internal_cache_key'] = 'foobar-changeme';

/**
 * Enable or disable gzip output compression. This can dramatically decrease
 * server bandwidth usage, at the cost of slightly higher CPU usage. Set to
 * the compression level (1-9) that you want to use, or FALSE to disable.
 *
 * Do not enable this option if you are using output compression in php.ini!
 */
$config['output_compression'] = FALSE;

/**
 * Enable or disable global XSS filtering of GET, POST, and SERVER data. This
 * option also accepts a string to specify a specific XSS filtering tool.
 */
$config['global_xss_filtering'] = TRUE;

/**
 * Enable or disable hooks.
 */
$config['enable_hooks'] = FALSE;

/**
 * Log thresholds:
 *  0 - Disable logging
 *  1 - Errors and exceptions
 *  2 - Warnings
 *  3 - Notices
 *  4 - Debugging
 */
$config['log_threshold'] = 1;

/**
 * Enable or disable displaying of Kohana error pages. This will not affect
 * logging. Turning this off will disable ALL error pages.
 */
$config['display_errors'] = TRUE;

/**
 * Enable or disable statistics in the final output. Stats are replaced via
 * specific strings, such as {execution_time}.
 *
 * @see http://docs.kohanaphp.com/general/configuration
 */
$config['render_stats'] = TRUE;

/**
 * Filename prefixed used to determine extensions. For example, an
 * extension to the Controller class would be named MY_Controller.php.
 */
$config['extension_prefix'] = 'MY_';

/**
 * Additional resource paths, or "modules". Each path can either be absolute
 * or relative to the docroot. Modules can include any resource that can exist
 * in your application directory, configuration files, controllers, views, etc.
 */
$config['modules'] = array
(
	// MODPATH.'auth',      // Authentication
	// MODPATH.'kodoc',     // Self-generating documentation
	// MODPATH.'gmaps',     // Google Maps integration
	// MODPATH.'archive',   // Archive utility
	// MODPATH.'payment',   // Online payments
	// MODPATH.'unit_test', // Unit testing
       MODPATH.'filesystem', // File System
       MODPATH.'tropo',     // Tropo API 国际短信
);
//定义密码加密密钥
define('PWD_ENCRYPT_KEY', 'fdjf,jkgfkl');
//主动态的mongo库
define('MONGO_DB_FEED', 'momo_v3');
//生活信息的mongo库
//define('MONGO_DB_LIFE', 'life_service_simulate');
//URL打开赠送免费短信
define('PRESENT_SMS_URL', 100);
//体验用户赠送免费短信
define('PRESENT_SMS_EXP', 10);
//升级赠送免费短信
define('PRESENT_SMS_UPGRADE', 100);
//用户注册赠送免费短信
define('PRESENT_SMS_REG', 100);
//来电秀的mongo库
define('MONGO_DB_CALLSHOW', 'callshow');


if(IN_PRODUCTION === TRUE) {
    $config['log_directory'] = '/data/weblogs/v3.api.momo.im/weblogs/';
    $config['ttserver'] = array('10.1.242.51', '10.1.242.209');

    define('API_PATH', 'http://v3.api.momo.im/');
    define('TRACKER_SERVER', '10.1.242.51');
    //定义段地址
    define('YOURLS_SITE','http://t.momo.im/');
    //3g
    define('WAP', 'http://3g.momo.im');
    //mo短信跳转地址
    define('MO_SMS_JUMP', 'http://m.momo.im/');
    //mo短信注册地址
    define('MO_SMS_REG', 'http://momo.im/');
    //活动网页地址
    define('MO_EVENT', 'http://event.momo.im/');
    define('CACHE_PRE', 'momoim_');
} else {
    $config['log_directory'] = '/logs';
    $config['ttserver'] = array('192.168.94.20');

    define('TRACKER_SERVER', '192.168.9.128');
    //内网
    define('API_PATH', 'http://new.api.uap26.91.com/');
    //定义短地址
    define('YOURLS_SITE','http://t.uap26.91.com/');
    //3g
    define('WAP', 'http://3g.uap26.91.com');
    //mo短信跳转地址
    define('MO_SMS_JUMP', 'http://uap26.91.com/');

    define('CACHE_PRE', 'momo26_');
}
