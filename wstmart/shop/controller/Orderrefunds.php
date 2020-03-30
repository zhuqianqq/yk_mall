<?php
namespace wstmart\shop\controller;
use wstmart\common\model\OrderRefunds as M;
/**
 * 订单退款控制器
 */
class Orderrefunds extends Base{
    protected $beforeActionList = ['checkAuth'];
    /**
     * 商家处理是否同意
     */
    public function shopRefund(){
        $m = new M();
        $rs = $m->shopRefund();
        return $rs;
    }
}