<?php

/*
 * Name: 域名建站系统
 * WebSite: https://github.com/thinkincloud/cmssuper
 */

// 系统
// error_reporting(0);
define("IN_SYS", true);

// 系统根目录
define("ROOT", __DIR__);

// 数据目录
define("DATA", ROOT . "/data");

// 时区
date_default_timezone_set('PRC');

// session
@ini_set('memory_limit',         '64M');
@ini_set('session.cache_expire',  180);
@ini_set('session.use_trans_sid', 0);
@ini_set('session.use_cookies',   1);
@ini_set('session.auto_start',    0);
session_save_path(DATA . DIRECTORY_SEPARATOR . 'session');

// cache 目录
set_include_path(DATA . DIRECTORY_SEPARATOR . 'cache' . PATH_SEPARATOR . get_include_path());

require ROOT . '/core/loader.php';
set_exception_handler(array('e', 'exception_handler'));
set_error_handler(array('e', 'exception_error_handler'));
register_shutdown_function(array('e', 'exception_shutdown_handler'));
spl_autoload_register(array('e', 'loader_handler'));

// 编码
header("Content-type: text/html; charset=utf-8");

// if DEV
header("Access-Control-Allow-Headers: Accept,Origin,X-Requested-With,Content-Type");
header("Access-Control-Allow-Methods: GET,POST,OPTIONS");
$http_origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
header('Access-Control-Allow-Origin: ' . $http_origin);
$ssid = gp('ssid');
if ($ssid) {
    session_id($ssid);
}
// endif
!defined('APPNAME') && loader::init()->run();
