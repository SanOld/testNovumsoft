<?php
// HTTP
$host = $_SERVER['HTTP_HOST'];
define('HTTP_SERVER', 'http://'.$host.'/admin/');
define('HTTP_CATALOG', 'http://'.$host.'/');
define('HTTP_IMAGE', 'http://'.$host.'/image/');

// HTTPS
define('HTTPS_SERVER', 'http://'.$host.'/admin/');
define('HTTPS_CATALOG', 'http://'.$host.'/');
define('HTTPS_IMAGE', 'http://'.$host.'/image/');

// DIR
$dir = dirname(dirname(__FILE__));
define('DIR_APPLICATION', $dir . '/admin/');
define('DIR_CATALOG', $dir . '/catalog/');
define('DIR_TEMPLATE', $dir . '/admin/view/template/');

define('DIR_SYSTEM', $dir . '/system/');
define('DIR_DATABASE', $dir . '/system/database/');
define('DIR_LANGUAGE', $dir . '/admin/language/');
define('DIR_TEMPLATE', $dir . '/admin/view/theme/');
define('DIR_CONFIG', $dir . '/system/config/');
define('DIR_IMAGE', $dir . '/image/');
define('DIR_CACHE', $dir . '/system/storage/cache/');
define('DIR_DOWNLOAD', $dir . '/download/');
define('DIR_LOGS', $dir . '/system/storage/logs/');
define('DIR_MODIFICATION',  $dir .'/system/storage/modification/');
define('DIR_UPLOAD',  $dir .'/system/storage/upload/');





// DB
define('DB_DRIVER', 'mysqli');
define('DB_HOSTNAME', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_DATABASE', 'db_test');
define('DB_PORT', '3306');
define('DB_PREFIX', '');


////////////////////////




