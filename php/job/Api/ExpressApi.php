<?php namespace Job\Api;


use Job\Config;
use linslin\yii2\curl\Curl;

class ExpressApi
{
    /**
     * 从快递服务获取地区名字
     */
    public static function getAreaName($code)
    {
        $data = self::request('/area_service.php', ['area_code' => $code]);
        if (empty($data['area_name'])) {
            return '';
        }
        return $data['area_name'];
    }


    public static function getAreaNames($codes)
    {
        if (is_array($codes)) {
            $codes = implode(',', $codes);
        }
        return self::request('/area_query.php', ['method' => 'get-names', 'codes' => $codes]);
    }


    protected static function request($uri, $params)
    {
        if (empty($params['app'])) {
            $params['app'] = 'mall';
        }
        $url = Config::get('express_domain') . $uri . '?' . http_build_query($params);
        //初始化
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, false);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_BINARYTRANSFER, true) ; // 在启用 CURLOPT_RETURNTRANSFER 时候将获取数据返回
        //执行命令
        $response = json_decode(curl_exec($curl), true);
        //关闭URL请求
        curl_close($curl);

        if (empty($response['data'])) {
            return [];
        }

        return $response['data'];
    }


}