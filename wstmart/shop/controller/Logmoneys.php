<?php
namespace wstmart\shop\controller;
/**
 * 资金流水控制器
 */
class Logmoneys extends Base{
    protected $beforeActionList = ['checkAuth'];

    /**
     * 查看商家资金流水
     */
    public function shopmoneys(){
        $rs = model('Shops')->getFieldsById((int)session('WST_USER.shopId'),['lockMoney','shopMoney','noSettledOrderFee','paymentMoney']);
        $this->assign('object',$rs);
        return $this->fetch('logmoneys/list');
    }
    /**
     * 获取商家数据
     */
    public function pageShopQuery(){
        $shopId = (int)session('WST_USER.shopId');
        $data = model('logMoneys')->pageQuery(1,$shopId);
        return WSTGrid($data);
    }

	/**
	 * 充值[商家]
	 */
    public function toRecharge(){
        if((int)WSTConf('CONF.isOpenRecharge')==0)return;
    	$payments = model('common/payments')->recharePayments('1');
    	$this->assign('payments',$payments);
        $chargeItems = model('common/ChargeItems')->queryList();
        $this->assign('chargeItems',$chargeItems);
    	return $this->fetch('recharge/pay_step1');
    }
}
