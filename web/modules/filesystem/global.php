<?php
define('DS', DIRECTORY_SEPARATOR);
define('FS_ROOT', dirname(__FILE__) . DS);
$path = get_include_path();
$path .= PATH_SEPARATOR . FS_ROOT . 'include';
set_include_path($path);
spl_autoload_register('include_loader');
//自动加载include里面的类支持命名空间
function include_loader($class) {
    $path = str_replace('\\', DS, $class) . '.php';
    if(!file_exists(FS_ROOT . 'include' . DS . $path))
        return;
    include $path;
}
/*
spl_autoload_register('models_loader');

//自动加载models里面的类
function models_loader($class){
	$filename=strtolower(str_replace('_Model', '', $class));
    $path = FS_ROOT . 'models' . DS . $filename . '.php';
    if(file_exists($path)){
    	@include $path;
    }
}
*/
if(isset($_SERVER['HTTP_MOMO_DEBUG_TOKEN']) && $_SERVER['HTTP_MOMO_DEBUG_TOKEN'] == 'momofs') {
    Core::set_exception_handler();

    function trace() {
        $args = func_get_args();
        return call_user_func_array(array('Core', 'addTrace'), $args);
    }
} else {

    function trace() {
    }
}
