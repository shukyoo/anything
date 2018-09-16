<?php

/**
 * api container
 * Api::user('Point')->gettest();
 */
class Api
{
    protected static $container = [];

    public static function load($name, $flag = null)
    {
        $key = $name .'_'. $flag;
        if (!isset(self::$container[$key])) {
            $name = ucfirst($name) .'Api';
            $api_path = ROOT_PATH .'/api/'. $name .'.php';
            require_once $api_path;
            $class = '\\Api\\'. $name;
            self::$container[$key] = new $class($flag);
        }
        return self::$container[$key];
    }


    public static function loadRpc($domain, $uri)
    {
        static $rpcs = [];
        $key = $domain .'_'. $uri;
        if (!isset($rpcs[$key])) {
            $uri = ltrim($uri, '/');
            $url = Config::get('api_domain')[$domain] . '/'. $uri;
            $rpcs[$url] = new \JsonRPCClient($url);
        }
        return $rpcs[$key];
    }
}