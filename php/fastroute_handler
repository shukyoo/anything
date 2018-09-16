<?php
require dirname(__DIR__) .'/boot.php';

$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {

    // 接口测试
    $r->addRoute('GET', '/api/test/hello', ['api', 'Test', 'hello']);

});


// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
        echo '404 Page Not Found';
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        header($_SERVER["SERVER_PROTOCOL"]." 405 Method Not Allowed");
        echo '405 Method Not Allowed';
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];

        if (is_array($handler)) {
            if ($handler[0] == 'web') {
                web_handler($handler[1], $handler[2], $vars);
            } elseif ($handler[0] == 'api') {
                api_handler($handler[1], $handler[2], $vars);
            }
        } elseif (function_exists($handler)) {
            $data = call_user_func_array($handler, $vars);
        } else {
            header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
            echo 'Invalid Handler '. $handler;
        }

        break;
}


function web_handler($class, $method, $vars = [])
{
    try {
        $class = $class .'Controller';
        $class_file = ROOT_PATH .'/app/web/controller/'. $class .'.php';
        include ROOT_PATH .'/app/web/controller/BaseController.php';
        include $class_file;
        $controller = new $class();
        $ct = count($vars);
        if ($ct == 0) {
            $controller->$method();
        } else {
            call_user_func_array([$controller, $method], $vars);
        }
    } catch (\Exception $e) {
        if (DEBUG) {
            throw $e;
        } else {
            header($_SERVER["SERVER_PROTOCOL"]." 500 Internal Server Error");
            echo '500 Internal Server Error';
        }
    }
}


function api_handler($class, $method, $vars = [])
{
    try {
        $class = $class .'Controller';
        $class_file = ROOT_PATH .'/app/api/controller/'. $class .'.php';
        include ROOT_PATH .'/app/api/controller/BaseController.php';
        include $class_file;
        $api = new $class();
        $ct = count($vars);
        if ($ct == 0) {
            $data = $api->$method();
        } else {
            $data = call_user_func_array([$api, $method], $vars);
        }
        if (!is_array($data)) {
            $data = ['data' => $data];
        }
        $data = array_merge(['res' => 1, 'code' => 0], $data);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    } catch (\Exception $e) {
        $data = ['res' => 0, 'code' => -1, 'msg' => $e->getMessage()];
        if ($e->getCode() != 0) {
            $data['code'] = $e->getCode();
        }
        if (DEBUG) {
            $data['file'] = $e->getFile();
            $data['line'] = $e->getLine();
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
