<?php namespace Job\Api;


use Job\Config;

class OrderApi
{
    /**
     * @return \api\rpc\jsonRPCClient|null
     */
    protected static function rpcOrder()
    {
        static $rpc = null;
        if (null === $rpc) {
            require_once ROOT_PATH .'/api/rpc/jsonRPCClient.php';
            $rpc = new \api\rpc\jsonRPCClient(Config::get('order_rpc_domain') .'/mall-order');
        }
        return $rpc;
    }


    /**
     * 创建订单
     */
    public static function create($info)
    {
        $res = self::rpcOrder()->create($info);
        return new ApiResult($res);
    }

    /**
     * 订单取消
     */
    public static function cancel($order_id, $param)
    {
        $res = self::rpcOrder()->cancel($order_id, $param);
        return new ApiResult($res);
    }

    /**
     * 订单激活
     */
    public static function active($order_id)
    {
        $res = self::rpcOrder()->active($order_id);
        return new ApiResult($res);
    }

    /**
     * 订单支付
     */
    public static function orderPay($order_id, $info)
    {
        $res = self::rpcOrder()->orderPay($order_id, $info);
        return new ApiResult($res);
    }

    /**
     * 修改订单信息
     */
    public static function update($order_ids,$info)
    {
        $res = self::rpcOrder()->update($order_ids, $info);
        return new ApiResult($res);
    }


    /**
     * 订单全部发货
     */
    public static function deliveryAll($order_id, $info)
    {
        $res = self::rpcOrder()->deliveryAll($order_id, $info);
        return new ApiResult($res);
    }

    /**
     * 增加订单item
     */
    public static function addItem($order_id, $info)
    {
        $res = self::rpcOrder()->addItem($order_id, $info);
        return new ApiResult($res);
    }

    /**
     * 增加订单gift
     */
    public static function addGifts($order_id, $info)
    {
        $res = self::rpcOrder()->addGifts($order_id, $info);
        return new ApiResult($res);
    }

    /**
     * 订单已收货
     */
    public static function orderReceive($order_id, $info)
    {
        $res = self::rpcOrder()->orderReceive($order_id, $info);
        return new ApiResult($res);
    }

    /**
     * 订单完成
     */
    public static function orderFinish($order_id)
    {
        $res = self::rpcOrder()->orderFinish($order_id);
        return new ApiResult($res);
    }

    /**
     * 订单删除
     */
    public static function orderShow($order_id, $show)
    {
        $res = self::rpcOrder()->orderShow($order_id, $show);
        return new ApiResult($res);
    }

}