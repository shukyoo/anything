<?php

class Curl
{
    public static $last_errmsg;
    public static $last_errcode;

    public static function get($url, $params = [])
    {
        return self::request($url, $params, 'GET');
    }

    public static function post($url, $params)
    {
        return self::request($url, $params, 'POST');
    }

    public static function request($url, $params, $method = 'GET')
    {
        $curl = new \Curl\Curl();
        $curl->setTimeout(20);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setDefaultJsonDecoder(true);
        // follow redirect post
        //$curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
        //$curl->setOpt(CURLOPT_POSTREDIR, 3);
        if ($method == 'POST') {
            $res = $curl->post($url, $params);
        } else {
            $res = $curl->get($url, $params);
        }

        if ($curl->error) {
            self::$last_errmsg = $curl->errorMessage;
            self::$last_errcode = $curl->errorCode;
            \Logger::error('curl', $curl->errorMessage .'['. $curl->errorCode .']', array(
                'url' => $url,
                'param' => $params,
                'res' => $res
            ));
            return false;
        } else {
            \Logger::info('curl', '', array(
                'url' => $url,
                'param' => $params,
                'res' => $res
            ));
        }
        if ($res instanceof stdClass) {
            $res = json_encode($res);
            $res = json_decode($res, true);
        } elseif (!is_array($res)) {
            $res = json_decode($res, true);
        }
        return $res;
    }

}