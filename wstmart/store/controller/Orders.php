<?php
namespace wstmart\store\controller;
use wstmart\store\model\Orders as M;
/**
 * 订单控制器
 */
class Orders extends Base{
	protected $beforeActionList = ['checkAuth'];
	
	/**
	 * 获取用户拒收/退款列表
	 */
    public function abnormalByPage(){
		$m = new M();
		$rs = $m->userOrdersByPage([-3]);
		return WSTReturn("", 1,$rs);
	}
	
	
	
    /**
	 * 等待处理订单
	 */
	public function waitDelivery(){
        $this->assign("p",(int)input("p"));
		$express = model('Express')->listQuery();
		$this->assign('express',$express);
		return $this->fetch('orders/list_wait_delivery');
	}
	/**
	 * 待处理订单
	 */
	public function waitDeliveryByPage(){
		$m = new M();
		$rs = $m->storeOrdersByPage([0]);
		return WSTReturn("", 1,$rs);
	}

    /**
     * 获取单条订单的商品信息
     */
    public function waitDeliverById(){
        $m = new M();
        $rs = $m->waitDeliverById();
        return WSTReturn("", 1,$rs);
    }

	/**
	* 商家-已发货订单
	*/
	public function delivered(){
        $this->assign("p",(int)input("p"));
		$express = model('Express')->listQuery();
		$this->assign('express',$express);
		return $this->fetch('orders/list_delivered');
	}
	/**
	 * 待处理订单
	 */
	public function deliveredByPage(){
		$m = new M();
		$rs = $m->storeOrdersByPage(1);
		return WSTReturn("", 1,$rs);
	}

    /**
	 * 商家发货
	 */
	public function deliver(){
		$m = new M();
		$rs = $m->deliver();
		return $rs;
	}
	
	/**
	 * 商家-已完成订单
	 */
    public function finished(){
    	$this->assign("p",(int)input("p"));
		$express = model('Express')->listQuery();
		return $this->fetch('orders/list_finished');
	}
	/**
	 * 商家-已完成订单
	 */
	public function finishedByPage(){
		$m = new M();
		$rs = $m->storeOrdersByPage(2);
		return WSTReturn("", 1,$rs);
	}
    /**
	 * 商家-取消/拒收订单
	 */
    public function failure(){
    	$this->assign("p",(int)input("p"));
		return $this->fetch('orders/list_failure');
	}
	/**
	 * 商家-取消/拒收订单
	 */
	public function failureByPage(){
		$m = new M();
		$rs = $m->storeOrdersByPage([-1,-3]);
		return WSTReturn("", 1,$rs);
	}
	
	/**
	 * 商家-订单详情
	 */
	public function view(){
		$m = new M();
		$rs = $m->getByView((int)input('id'));
		$this->assign('object',$rs);
        $this->assign("p",(int)input("p"));
        $this->assign("src",input("src"));
		return $this->fetch('orders/view');
	}
	/**
	 * 订单打印
	 */
	public function orderPrint(){
        $m = new M();
		$rs = $m->getByView((int)input('id'));
		$this->assign('object',$rs);
		return $this->fetch('orders/print');
	}


	/**
	 * 商家-待付款订单
	 */
	public function waituserPay(){
        $this->assign("p",(int)input("p"));
		return $this->fetch('orders/list_wait_pay');
	}
	/**
	 * 商家-获取待付款列表
	 */
	public function waituserPayByPage(){
		$m = new M();
		$rs = $m->storeOrdersByPage(-2);
		return WSTReturn("", 1,$rs);
	}
	/**
	 * 导出订单
	 */
	public function toExport(){
		$m = new M();
		$rs = $m->toExport();
		$this->assign('rs',$rs);
	}
}
