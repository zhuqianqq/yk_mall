<?php
namespace wstmart\shop\controller;
use wstmart\common\model\CashDraws as M;
use wstmart\common\model\Shops as MShops;
/**
 * 提现记录控制器
 */
class Cashdraws extends Base{
    protected $beforeActionList = ['checkAuth'];
    /**
     * 查看商家资金流水
     */
    public function shopIndex(){
        return $this->fetch('cashdraws/list');
    }
    /**
     * 获取用户数据
     */
    public function pageQueryByShop(){
        $shopId = (int)session('WST_USER.shopId');
        $data = model('CashDraws')->pageQuery(1,$shopId);
        return WSTGrid($data);
    }
    /**
     * 申请提现
     */
    public function toEditByShop(){
        $this->assign('object',model('shops')->getShopAccount());
        $m = new MShops();
        $shopId = (int)session('WST_USER.shopId');
        $shop = $m->getFieldsById($shopId,["shopMoney","rechargeMoney"]);
        $this->assign('shop',$shop);
        return $this->fetch('cashdraws/box_draw');
    }
    /**
     * 提现
     */
    public function drawMoneyByShop(){
        $m = new M();
        return $m->drawMoneyByShop();
    }
}
