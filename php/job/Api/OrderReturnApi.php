<?php namespace Job\Api;


use Job\Config;

class OrderReturnApi
{
    /**
     * @return \api\rpc\jsonRPCClient|null
     */
    protected static function rpc()
    {
        static $rpc = null;
        if (null === $rpc) {
            require_once ROOT_PATH .'/api/rpc/jsonRPCClient.php';
            $rpc = new \api\rpc\jsonRPCClient(Config::get('order_rpc_domain') .'/order-return');
        }
        return $rpc;
    }


    /**
     * 退款申请
     */
    public static function refundApply($order_id, $params, $restriction = null)
    {
        $res = self::rpc()->refundApply($order_id, $params, $restriction);
        return new ApiResult($res);
    }

    /**
     * 退货申请
     */
    public static function returnApply($order_id, $params, $restriction = null)
    {
        $res = self::rpc()->returnApply($order_id, $params, $restriction);
        return new ApiResult($res);
    }

    /**
     * 换货申请
     */
    public static function exchangeApply($order_id, $params, $restriction = null)
    {
        $res = self::rpc()->exchangeApply($order_id, $params, $restriction);
        return new ApiResult($res);
    }

}