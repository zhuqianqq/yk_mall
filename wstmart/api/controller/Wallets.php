<?php
namespace wstmart\api\controller;
use wstmart\common\model\Orders as OM;
use wstmart\common\model\TUserMap;

/**
 * 余额控制器
 */
class Wallets extends Base{
	// 前置方法执行列表
	protected $beforeActionList = [
			'checkAuth'
	];
	/**
	 * 跳去支付页面
	 */
	public function payment()
    {
        $pkey = WSTBase64urlDecode(input("pkey"));
        $pkey = explode('@', $pkey);
        $orderNo = $pkey[0];
        $isBatch = (int)$pkey[1];
        $data = [];
        $result = [];
        //$userId = (int)session('WST_USER.userId');
        $userId = (int)input('post.user_id', 0); //直播用户id
        if ($userId > 0) {
            $userId = TUserMap::getMallUserId($userId);
        } else {
            $userId = (int)session('WST_USER.userId');
        }
        $data['orderNo'] = $orderNo;
        $data['isBatch'] = $isBatch;
        $data['userId'] = $userId;
        //$this->assign('data',$data);
        $result['data'] = $data;
        $m = new OM();
        $rs = $m->getOrderPayInfo($data);

        $list = $m->getByUnique($userId, $orderNo, $isBatch);
        //$this->assign('rs',$list);
        $result['rs'] = $list;
        if (empty($rs) || $rs['needPay'] <= 0) {
            //$this->assign('type','');
            $result['type'] = '';
            //return $this->fetch("users/orders/orders_list");
            return $this->outJson(0, "不需要支付", $result);
        } else {
            //$this->assign('needPay',$rs['needPay']);
            $result['needPay'] = $rs['needPay'];
            //获取用户钱包
            $user = model('users')->getFieldsById($data['userId'], 'userMoney,payPwd');
            //$this->assign('userMoney',$user['userMoney']);
            $result['userMoney'] = $user['userMoney'];
            $payPwd = $user['payPwd'];
            $payPwd = empty($payPwd) ? 0 : 1;
            //$this->assign('payPwd',$payPwd);
            $result['payPwd'] = $payPwd;
        }
        //return $this->fetch('users/orders/orders_pay_wallets');
        return $this->outJson(0, '', $result);
    }
	/**
	 * 钱包支付
	 */
	public function payByWallet(){
		$m = new OM();
		return $m->payByWallet();
	}
}
