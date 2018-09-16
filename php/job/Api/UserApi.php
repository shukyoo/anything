<?php namespace Job\Api;


use Job\Config;

class UserApi
{
    /**
     * 从用户服务获取用户信息
     */
    public static function getUserInfo($user_name)
    {
        $res = self::rpcAccount()->getByUsername($user_name);
        return new ApiResult($res);
    }

    protected static function rpcAccount()
    {
        static $rpc = null;
        if (null === $rpc) {
            $rpc = new \api\rpc\jsonRPCClient(Config::get('user_rpc_domain') .'/rpc/account?app=mall');
        }
        return $rpc;
    }
}