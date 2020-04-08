<?php
namespace wstmart\home\controller;
use wstmart\common\model\OrderRefunds as M;
/**
 * 订单退款控制器
 */
class Orderrefunds extends Base{
	protected $beforeActionList = ['checkAuth'];
    /**
	 * 用户申请退款
	 */
	public function refund(){
		$m = new M();
		$rs = $m->refund();
		return $rs;
	}
}
