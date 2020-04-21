<?php
namespace wstmart\common\model;

interface RefundInterface
{
    /**
     * 退款
     * @param OrderRefunds $order
     * @return mixed
     */
    public function refund(OrderRefunds $order);
}