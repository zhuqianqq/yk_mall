<?php
namespace wstmart\shop\controller;
use wstmart\common\model\OrderInvoices as M;
/**
 * 发票详情控制器
 */

class OrderInvoices extends Base{
    protected $beforeActionList = ['checkAuth'];

    /******************************* 商家  ****************************************/
    /**
     * 商家-查看订单详情列表
     */

    public function shopInvoices(){
        $this->assign("shopId",(int)input('shopId'));
        $this->assign("p",(int)input("p"));
        return $this->fetch("orders/list_invoices");
    }

    /**
     * 获取商家订单详情列表
     */
    public function queryShopInvoicesByPage(){
        $rs = model('OrderInvoices')->queryShopInvoicesByPage();
        return WSTGrid($rs['data']);
    }

    /**
     * 导出订单
     */
    public function toExport(){
        $m = new M();
        $rs = $m->toExport();
        $this->assign('rs',$rs);
    }

    /**
     * 批量设置
     */
    public function setByBatch(){
        $m = new M();
        $rs = $m->setByBatch();
        return $rs;
    }

}
