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

    /**
     * 退款列表
     */
    public function refund(){
        $areaList = model('areas')->listQuery(0);
        $this->assign("p",(int)input("p"));
        $this->assign("areaList",$areaList);
        return $this->fetch("orderrefunds/list");
    }
    public function refundPageQuery(){
        $m = new M();
        return WSTGrid($m->refundPageQueryShop());
    }
    /**
     * 跳去退款界面
     */
    public function toRefund(){
        $m = new M();
        $object = $m->getInfoByRefund();
        $this->assign("object",$object);
        return $this->fetch("box_refund");
    }
    /**
     * 退款
     */
    public function orderRefund(){
        $m = new M();
        return $m->orderRefund();
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