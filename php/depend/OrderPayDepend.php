<?php namespace Depend;

use Service\Order\Payment\OrderPayment;


/**
 * 订单支付依赖，支付要先确认订单已存在
 */
class OrderPayDepend extends DependAbstract
{
    protected $order_id;
    protected $pay_info = [];
    protected $order;

    protected function init()
    {
        if (!$this->order_id || empty($this->pay_info)) {
            throw new \BaseException('invalid params for OrderPayDepend');
        }
        $this->order = \Order::where('order_id', $this->order_id)->first();
    }

    protected function isValid()
    {
        $this->depend_msg = '订单'. $this->order_id .'不存在，不能支付';
        return ($this->order && $this->order instanceof \Order);
    }


    protected function handle()
    {
        return OrderPayment::moneyPay($this->order, $this->pay_info);
    }
}
