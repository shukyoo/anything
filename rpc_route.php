<?php
define('API_PATH', dirname(__DIR__));
require API_PATH .'/../../boot.php';


/**
 * RPC入口
 * 由于rpc路由的简洁性，以及为了提升性能，所以不使用fast-route
 */
// $class = isset($_GET['class']) ? $_GET['class'] : '';
$class = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

if (!$class) {
    header($_SERVER["SERVER_PROTOCOL"]." 405 Method Not Allowed");
    echo '405 Method Not Allowed';
    exit;
}

$name = strtolower($class);
$name = str_replace('-', '', ucwords($name, '-')) .'Adapter';
$rpc_file = API_PATH .'/adapter/'. $name .'.php';
if (!is_file($rpc_file)) {
    header($_SERVER["SERVER_PROTOCOL"]." 405 Method Not Allowed");
    echo 'Invalid RPC call '. $class;
    exit;
}
include API_PATH .'/adapter/AdapterAbstract.php';
include $rpc_file;

$rpc = new $name();
JsonRPCServer::handle($rpc);
