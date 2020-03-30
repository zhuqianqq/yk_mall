<?php
namespace wstmart\store\controller;
/**
 * 售后控制器
 */
class Orderservices extends Base{
    protected $beforeActionList = ['checkAuth'];
    
    // 商家发货
    public function shopSend(){
        $rs = model('common/OrderServices')->shopSend();
        return $rs;
    }
    // 商家确认收货
    public function shopReceive(){
        $rs = model('common/OrderServices')->shopReceive();
        return $rs;
    }
    // 售后列表查询
    public function pageQuery(){
        $rs = model('common/OrderServices')->pageQuery(1);
        return WSTReturn('ok', 1, $rs);
    }
    /**
     * 处理退款
     */
    public function dealRefund(){
        $rs = model('common/OrderServices')->dealRefund();
        return $rs;
    }
    /**
     * 处理售后申请
     */
    public function dealApply(){
        $rs = model('common/OrderServices')->dealApply();
        return $rs;
    }
    /**
     * 处理售后申请页
     */
    public function deal(){
        $object = model('common/OrderServices')->getDetail(1);
        // 等待卖家发货
        if($object['serviceStatus']==3){
            // 取出快递公司
            $express = model('Express')->listQuery();
		    $this->assign('express',$express);
        }
        return $this->fetch('orderservices/deal',['object'=>$object,'id'=>(int)input('id'),'p'=>(int)input('p')]);
    }
    /**
    * 售后申请列表
    */
    public function index(){
        // $this->assign('object',$m->getShopCfg((int)session('WST_STORE.shopId')));
        return $this->fetch('orderservices/list',['p'=>(int)input('p')]);
    }

}
