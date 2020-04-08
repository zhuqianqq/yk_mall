<?php
namespace wstmart\home\controller;
use wstmart\common\model\LogMoneys as M;
/**
 * 资金流水控制器
 */
class Logmoneys extends Base{
    protected $beforeActionList = ['checkAuth'];
    /**
     * 查看用户资金流水
     */
    public function usermoneys(){
        $rs = model('Users')->getFieldsById((int)session('WST_USER.userId'),['lockMoney','userMoney','rechargeMoney']);
        $this->assign('object',$rs);
        return $this->fetch('users/logmoneys/list');
    }

    /**
     * 获取用户数据
     */
    public function pageUserQuery(){
        $userId = (int)session('WST_USER.userId');
        $data = model('logMoneys')->pageQuery(0,$userId);
        return WSTReturn("", 1,$data);
    }
    
    /**
     * 充值[用户]
     */
    public function toUserRecharge(){
        if((int)WSTConf('CONF.isOpenRecharge')==0)return;
    	$payments = model('common/payments')->recharePayments('1');
    	$this->assign('payments',$payments);
    	$chargeItems = model('common/ChargeItems')->queryList();
    	$this->assign('chargeItems',$chargeItems);
    	return $this->fetch('users/recharge/pay_step1');
    }
}
