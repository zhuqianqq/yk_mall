<?php
namespace wstmart\home\controller;
use wstmart\common\model\Orders as OM;
use wstmart\common\model\Users as UM;
/**
 * 余额控制器
 */
class Wallets extends Base{
	/**
	 * 生成支付代码
	 */
	function getWalletsUrl(){
		$pkey = input('pkey');
        $data = [];
        $data['status'] = 1;
        $data['url'] = url('home/wallets/payment','pkey='.$pkey,'html',true);
		return $data;
	}
	
	/**
	 * 跳去支付页面
	 */
	public function payment(){
		if((int)session('WST_USER.userId')==0){
			$this->assign('message',"对不起，您尚未登录，请先登录!");
            return $this->fetch('error_msg');
		}
		$userId = (int)session('WST_USER.userId');
		$m = new UM();
		$user = $m->getFieldsById($userId,["payPwd"]);
		$this->assign('hasPayPwd',($user['payPwd']!="")?1:0);
		$pkey = input('pkey');
		$this->assign('pkey',$pkey);
        $pkey = WSTBase64urlDecode($pkey);
        $pkey = explode('@',$pkey);
        $data = [];
        $data['orderNo'] = $pkey[0];
        $data['isBatch'] = (int)$pkey[1];
        $data['userId'] = $userId;
		$m = new OM();
		$rs = $m->getOrderPayInfo($data);
		if(empty($rs)){
			$this->assign('message',"您的订单已支付，请勿重复支付~");
            return $this->fetch('error_msg');
		}else{
			$this->assign('needPay',$rs['needPay']);
			//获取用户钱包
			$user = model('users')->getFieldsById($data['userId'],'userMoney');
			$this->assign('userMoney',$user['userMoney']);
	        return $this->fetch('order_pay_wallets');
	    }
	}

	/**
	 * 钱包支付
	 */
	public function payByWallet(){
		$m = new OM();
        return $m->payByWallet();
	}

}
