<?php
/**
 * 支付退款
 */
namespace wstmart\api\controller;

class Refund extends Base
{
    public function refund()
    {
        $userId = (int)$this->user_id;
        $orderId = (int)input('post.orderid');
        $refundType = (int)input('post.refundType'); // 1 退货退款 2 仅退款
        if ($refundType != 1) {
            $refundType = 2;
        }
        if (empty($user_id) || empty($order_id)) {
            return $this->outJson(100, "缺少参数!");
        }
        $order = \wstmart\common\model\Orders::get($order_id);
        if (empty($order)) {
            return $this->outJson(100, "没有数据!");
        }
        if (0 == $order['orderStatus']) {
            // 未发货订单只能申请仅退款，退款金额不可修改
        }
    }
}
