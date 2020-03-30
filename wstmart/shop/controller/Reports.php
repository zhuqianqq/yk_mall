<?php
namespace wstmart\shop\controller;
use wstmart\shop\model\Reports as M;
/**
 * 报表控制器
 */
class Reports extends Base{
    protected $beforeActionList = ['checkAuth'];
	/**
     * 商品销售排行
     */
    public function topSaleGoods(){
    	$this->assign("startDate",date('Y-m-d',strtotime("-1month")));
        $this->assign("endDate",date('Y-m-d'));
    	return $this->fetch('reports/top_sale_goods');
    }
    public function getTopSaleGoods(){
    	$n = new M();
        return WSTGrid($n->getTopSaleGoods());
    }
    /**
     * 获取销售额
     */
    public function statSales(){
    	$this->assign("startDate",date('Y-m-d',strtotime("-1month")));
        $this->assign("endDate",date('Y-m-d'));
        return $this->fetch('reports/stat_sales');
    }
    public function getStatSales(){
    	$m = new M();
        return $m->getStatSales();
    }

    /**
     * 获取销售订单
     */
    public function statOrders(){
        $this->assign("startDate",date('Y-m-d',strtotime("-1month")));
        $this->assign("endDate",date('Y-m-d'));
        return $this->fetch('reports/stat_orders');
    }
    public function getStatOrders(){
        $m = new M();
        return $m->getStatOrders();
    }

    /**
     * 导出商品销售排行Excel
     */
    public function toExportTopSaleGoods(){
        $m = new M();
        $rs = $m->toExportTopSaleGoods();
        $this->assign('rs',$rs);
    }
    /**
     * 导出销售额Excel
     */
    public function toExportStatSales(){
        $m = new M();
        $rs = $m->toExportStatSales();
        $this->assign('rs',$rs);
    }
    /**
     * 导出销售订单统计Excel
     */
    public function toExportStatOrders(){
        $m = new M();
        $rs = $m->toExportStatOrders();
        $this->assign('rs',$rs);
    }
}
