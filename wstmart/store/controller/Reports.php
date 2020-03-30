<?php
namespace wstmart\store\controller;
use wstmart\store\model\Reports as M;
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

}
