<?php
namespace wstmart\common\model;
/**
 * 订单业务处理类
 */
class OrderGoods extends Base
{
    CONST STATUS_INITION = 0;//初始值
    CONST STATUS_REFUNDING = 1;//退款中
    CONST STATUS_REFUND_SUCCESS = 2;//退款成功
    CONST STATUS_REFUND_FAIL = 3;//退款失败
    CONST STATUS_REFUND_DELETE = 4;//删除退款
    CONST STATUS_REFUND_RECEIVE = 7;//等待商家收货
}
