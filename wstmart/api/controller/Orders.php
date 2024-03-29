<?php
namespace wstmart\api\controller;
use think\facade\Cache;
use think\Db;
use util\Tools;
use wstmart\common\model\Member;
use wstmart\common\model\Orders as M;
use wstmart\common\model\Payments;
use wstmart\common\pay\AliPay;
use wstmart\common\pay\WeixinPay;

/**
 * 订单控制器
 */
class Orders extends Base{
	// 前置方法执行列表
    // protected $beforeActionList = [
    //     'checkAuth'
    // ];
	/*********************************************** 用户操作订单 ************************************************************/
	/**
	*  提醒发货
	*/
	public function noticeDeliver(){
		$m = new M();
		return $m->noticeDeliver();
	}

    /**
     * 在我的订单或者订单详情中提交订单付款
     */
	public function payment()
    {
//        $userId = (int)input('post.user_id', 0); //用户id
        $orderId = (int)input('post.orderId', 0); //订单ID
        $orderM = new \wstmart\common\model\Orders();
        $order = $orderM->get($orderId);
        if (empty($order)) {
            return $this->outJson(100, '没有数据');
        }
        try {
            $m = new M();
            $data['data'] = $order['orderunique'];
            $data['orderNo'] = $order['orderNo'];
            return $this->pay($m, $data, 0);
        } catch (\Exception $e) {
            return $this->outJson(100, $e->getMessage());
        }
    }

	/**
	 * 提交订单
	 */
	public function submit()
    {
        try {
            $orderunique = (string)input('post.orderunique', 0); // orderunique
            $m = new M();
            if (empty($orderunique)) {
                $rs = $m->submit(2);
                if ($rs["status"] == -1) {
                    throw new \Exception($rs["msg"], 100);
                }
                if ($rs["status"] == 1) {
                    return $this->pay($m,$rs);
                }
            } else {
                $oCnt = model('orders')
                    ->where(["isPay" => 1, "orderunique" => $orderunique])
                    ->field('orderId')
                    ->count();
                if ($oCnt) {
                    return $this->outJson(100, '请不要重复提交');
                }
                $rs['data'] = $orderunique;
                return $this->pay($m,$rs);
            }
        } catch (\Exception $e) {
            return $this->outJson(100, $e->getMessage());
        }
    }

    /**
     * 支付
     * @param $m
     * @param $rs
     * @return array
     */
    private function pay($m, $rs, $batch = 1)
    {
        $userId = (int)input('post.user_id', 0); //直播用户id
        $deliverType = (int)input('post.deliverType', 1); // 1 支付宝 0 微信
        $orderNo = $rs["data"];
        if (!$batch) {
            $orderNo = $rs['orderNo'];
        }
        $isBatch = $batch;
        $obj = array();
        $obj["userId"] = $userId;
        $obj["orderNo"] = $orderNo;
        $obj["isBatch"] = $isBatch;
        $order = $m->getPayOrders($obj);
        switch ($deliverType) {
            case 1:
                //支付宝
                $pay = new  Alipay();
                $data = [
                    'orderunique' => (string)$rs['data'],
                    'alipay' => $pay->sdkExecute([
                        'tradeNo' => $orderNo,
                        'tradeMoney' =>  bcdiv($order["needPay"], 1 , 2),
                    ]),
                ];
                break;
            case 2:
                // 小程序
                $userId = $this->user_id;
                if (empty($userId)) {
                    throw new \Exception('缺少参数', 100);
                }
                $appKey = config('wxpay.xcx.key');
                $appId = config('wxpay.xcx.app_id');
                $mchId = config('wxpay.xcx.mch_id');
                if (empty($appKey) || empty($appId) || empty($mchId)) {
                    throw new \Exception('支付配置错误', 100);
                }
                $notifyUrl = config('wxpay.xcx.notify_url');
                $ip = Tools::getClientIp();
                $recharge['merOrderId'] = $orderNo;
                $recharge['money'] = $order["needPay"];

                $member = Member::where('user_id = ' . $this->user_id)->find();
                if (empty($member['openid'])) {
                    throw new \Exception('获取openid失败', 100);
                }
                $payer = new WeixinPay($appId, $mchId, $appKey, $notifyUrl, 'JKC_WEIXIN_XCX');
                $jsApiParams = $payer->getXcxJsApiParams($recharge, $member['openid'], $ip);
                $jsApiParams['timeStamp'] = $jsApiParams['timestamp'];
                unset($jsApiParams['timestamp']);
                $data = [
                    "xcx" => $jsApiParams,
                    'orderunique' => (string)$rs["data"],
                ];
                break;
            default:
                // 微信
                $key = config('wxpay.app.key');
                $appId = config('wxpay.app.app_id');
                $mchId = config('wxpay.app.mch_id');
                if (empty($key) || empty($appId) || empty($mchId)) {
                    throw new \Exception('支付配置错误', 100);
                }
                $notifyUrl = config('wxpay.app.notify_url');
                $payer = new WeixinPay($appId, $mchId, $key, $notifyUrl);
                $recharge['merOrderId'] = $orderNo;
                $recharge['money'] = $order["needPay"];
                $wxOrder = $payer->prepay($recharge);

                $prepayData = [
                    'appId' => $appId,
                    'partnerId' => $mchId,
                    'prepayId' => $wxOrder['prepay_id'],
                    'nonceStr' => $payer->createNonceString(),
                    'package' => 'Sign=WXPay',
                    'timestamp' => time()
                ];
                $signData = array_combine(array_map('strtolower', array_keys($prepayData)), array_values($prepayData));

                $prepayData['sign'] = $payer->sign($signData);
                $data['wxpay'] = $prepayData;
                $data['orderunique'] = (string)$rs['data'];
        }
        $oCnt = model('orders')
            ->where(["userId" => $userId, "orderunique" => $rs['data']])
            ->field('orderId')
            ->count();
        if ($oCnt > 1) {
            // 如果是多个则直接显示多个
            $isMany = 1;
            $orderId = 0;
        } else {
            $o = model('orders')
                ->where(["userId" => $userId, "orderunique" => $rs['data']])
                ->field('orderId')
                ->find();
            if (empty($o)) {
                $isMany = 1;
                $orderId = 0;
            } else {
                $cnt = model('order_goods')->where(["orderId" => $o['orderId']])->count();
                if ($cnt > 1) {
                    $isMany = 1;
                    $orderId = 0;
                } else {
                    $orderId = $o['orderId'];
                    $isMany = 0;
                }
            }
        }

        $data['isBatch'] = $isMany;
        $data['orderId'] = $orderId;
        return $this->outJson(0, "提交成功!", $data);
    }

    /**
     * 查询订单状态
     * @return array
     */
    public function getOrderStatus()
    {
        try {
            $userId = (int)input('post.user_id', 0); //直播用户id
            $deliverType = (int)input('post.deliverType', 1); // 1 支付宝 0 微信
            $orderunique = (int)input('post.orderunique', ''); // 订单号
            if (empty($userId) || empty($deliverType) || empty($orderunique)) {
                return $this->outJson(100, "缺少参数!");
            }
            if ($deliverType == 1) {
                // 1 支付宝
                $payFrom = 'alipays';
            } else {
                // 微信
                $payFrom = 'weixinpays';
            }
            $cnt = model('orders')
                ->where(["userId" => $userId, "orderunique" => $orderunique, 'payFrom' => $payFrom, 'isPay' => 1])
                ->count();
            if ($cnt) {
                // 支付成功
                return $this->outJson(0, "支付成功!");
            }
            return $this->outJson(100, "暂未成功!");
        } catch (\Exception $e) {
            return $this->outJson(100, $e->getMessage());
        }
    }
	/**
	 * 提交虚拟订单
	 */
	public function quickSubmit(){
		$m = new M();
		$rs = $m->quickSubmit();
		if($rs["status"]==1){
			$pkey = WSTBase64urlEncode($rs["data"]."@1");
			$rs["pkey"] = $pkey;
		}
		return \json_encode($rs);
	}
	/**
	 * 在线支付方式
	 */
	public function succeed(){
		//获取支付方式
		$pa = new Payments();
		$payments = $pa->getByGroup('2');
		$this->assign('payments',$payments);
		$this->assign('pkey',input("pkey"));
		return $this->fetch("users/orders/orders_pay_list");
	}
	/**
	 * 订单管理
	 */
	public function index(){
		$type = input('param.type','');
		$this->assign('type',$type);
		return $this->fetch("users/orders/orders_list");
	}

    /**
     * 获取不同状态的订单数
     */
	public function getOrderStatusCount(){
        $userId = input('param.user_id');
        if (empty($userId)) {
            return $this->outJson(100, "缺少参数");
        }

        $flag = -1;
        $status = [
            'waitPay' => [-2], //待支付
            'waitReceive' => [1,0],//待收货
            'finish' => [2],//待评价/已完成
            'refund' => [-9999],//退款售后
            //'all' => [-2,-1,0,1,2,6],//全部订单
        ];

        $m = new M();
        $rs['waitPay'] = $m->userOrdersByPage($status['waitPay'],$flag,$userId,'waitPay','count')['total'];
        $rs['waitReceive'] = $m->userOrdersByPage($status['waitReceive'],$flag,$userId,'waitReceive','count')['total'];
        $rs['finish'] = $m->userOrdersByPage($status['finish'],$flag,$userId,'finish','count')['total'];
        $rs['refund'] = $m->getRefundCount($userId);

        return $this->outJson(0, "success", $rs);
    }

	/**
	* 订单列表
	*/
	public function getOrderList(){
		/* 
		 	-3:拒收、退款列表
			-2:待付款列表 
			-1:已取消订单
			0,1: 待收货
			2:待评价/已完成
		*/
		$flag = -1;
		$type = input('param.type','all');
		$userId = input('param.user_id');
		if (empty($userId)) {
            return $this->outJson(100, "缺少参数");
        }

		switch ($type) {
			case 'waitPay':
				$status=[-2];
				break;
			case 'waitDeliver':
				$status=[0,1];
				break;
			case 'waitReceive':
				$status=[1,0];
				break;
			case 'finish': 
				$status=[2];
				break;
			default:
				$status=[-2,-1,0,1,2,6];
				break;
		}
		$m = new M();
		$rs = $m->userOrdersByPage($status,$flag,$userId);
		//$rs = $m->userOrdersByPage2($status,$flag,$userId);

		foreach ($rs['data'] as $k=>$v) {
		    if (empty($v['list'])) continue;
            foreach($v['list'] as $k1=>$v1){
                $rs['data'][$k]['list'][$k1]['goodsImg'] = WSTImg($v1['goodsImg'],3);
            }
		}
		return $this->outJson(0, "success", $rs);
	}

	/**
	 * 订单详情
	 */
	public function getDetail(){
		$m = new M();
		$rs = $m->getByView((int)input('id'));
		$rs['status'] = WSTLangOrderStatus($rs['orderStatus']);
		$rs['payInfo'] = WSTLangPayType($rs['payType']);
		$rs['deliverInfo'] = WSTLangDeliverType($rs['deliverType']);
        $rs['orderCodeTitle'] = WSTOrderModule($rs['orderCode']);
		foreach($rs['goods'] as $k=>$v){
			$rs['goods'][$k]['goodsImg'] = WSTImg($v['goodsImg'],3);
		}
		// 优惠券钩子
		hook('mobileDocumentOrderSummaryView',['rs'=>&$rs]);
		// 满就送钩子
		hook('mobileDocumentOrderViewGoodsPromotion',['rs'=>&$rs]);
		return \json_encode($rs);
	}

    /**
     * 获取订单详情
     */

    public function getOrderDetail()
    {
        $m = new M();
        $pkey = input('pkey') ?? '';
        $role = input('role') ?? 2;
        $user_id = input('param.user_id'); // 直播用户ID
        if (empty($user_id)) {
            return $this->outJson(100, "缺少参数");
        }
        $userInfo = Db::name('users')->where("userId = {$user_id}")->field("userName, userPhoto")->find();
   
        if (empty($userInfo)) {
            return $this->outJson(100, "没有数据");
        }
        $shop = Db::name('shops')->where("userId = {$user_id}")->field("shopId")->find();
        // if (empty($shop)) {
        //     return $this->outJson(100, "没有数据");
        // }
        $shopId = $shop['shopId'] ?? '';
	
        $rs = $m->getByView((int)input('id'), $user_id, $shopId);
        $rsStatus = isset($rs['status']) ? $rs['status'] : 1;
        if ($rsStatus == -1) {
            return $this->outJson(100, $rs['msg']);
        }
        $orderStatus = $rs['orderStatus'];
        $rs['pkey'] = $pkey;
        $rs['status'] = WSTLangOrderStatus($orderStatus);
        $rs['payInfo'] = WSTLangPayType($rs['payType']);
        $rs['deliverInfo'] = WSTLangDeliverType($rs['deliverType']);
        $rs['orderCodeTitle'] = WSTOrderModule($rs['orderCode']);
        // foreach($rs['goods'] as $k=>$v){
        //     $rs['goods'][$k]['goodsImg'] = WSTImg($v['goodsImg'],3);
        // }

        //1为买家  2为卖家
        if ($role == 1) {
            $userInfo = Db::name('users')->where("userId = {$rs['shopUserId']}")->field("userName, userPhoto")->find();
            $express = [];
        } else {
            $express = model('Express')->listQuery();
        }
        $redisKey = "SHOP:UPDATE:EXPRESS:ORDERID:" . (int)input('id');
        $rs['userInfo'] = (object)$userInfo;
        $rs['express'] = (object)$express;
        $isUpdate = 1;
        if (1 == $orderStatus) {
            // 已发货的才有修改的功能
            $isUpdate = (int)Cache::get($redisKey);
        }
		$rs['isUpdate'] = $isUpdate;
		
        return $this->outJson(0, "查询成功", $rs);
    }

	/**
	 * 用户确认收货
	 */
	public function receive(){
        $userId = (int)input('post.user_id', 0); //用户id
        $orderId = (int)input('post.orderId', 0); //订单ID
        if(empty($userId) || empty($orderId)) {
            return WSTReturn('缺少参数',-1);
        }

		$m = new M();
		$rs = $m->receive($orderId, $userId);
        if ($rs['status'] == -1) {
            return $this->outJson(100, $rs['msg']);
        }
        return $this->outJson(0, '操作成功', $rs);
	}

	/**
	* 用户-评价页
	*/
	public function orderAppraise(){
		$m = model('Orders');
		$oId = (int)input('oId');
		//根据订单id获取 商品信息
		$data = $m->getOrderInfoAndAppr();
		$data['shopName']=model('shops')->getShopName($oId);
		$this->assign('data',$data);
		$this->assign('oId',$oId);
		return $this->fetch('users/orders/orders_appraises');
	}

    /**
     * 用户-评价详情页
     */
    public function orderAppraiseDetail(){
        $m = model('Orders');
        $oId = (int)input('oId');
        //根据订单id,订单商品id获取 商品信息
        $data = $m->getOrderInfoByGoodsId();
        $this->assign('data',$data);
        $this->assign('oId',$oId);
        return $this->fetch('users/orders/orders_appraises_detail');
    }
	
	/**
	 * 用户取消订单
	 */
	public function cancellation(){
        $m = new M();
        $rs = $m->cancel();
        return \json_encode($rs);
    }
    /**
     * 用户删除订单
     */
    public function deleteOrder(){
        $user_id = input('param.user_id'); 
        if (empty($user_id)) {
            return $this->outJson(100, "缺少参数");
        }
        $m = new M();
        $rs = $m->deleteOrder($user_id);
        if ($rs['status'] == -1) {
            return $this->outJson(100, $rs['msg']);
        }
        return $this->outJson(0, '删除成功', $rs);
    }

    /**
     * 用户取消订单
     */
    public function cancelOrder(){
        $user_id = input('param.user_id');
        if (empty($user_id)) {
            return $this->outJson(100, "缺少参数");
        }
        $m = new M();
        $rs = $m->quxiaoOrderH5($user_id);
        if ($rs['status'] == -1) {
            return $this->outJson(100, $rs['msg']);
        }
        return $this->outJson(0, '取消成功', $rs);
    }

	/**
	 * 用户拒收订单
	 */
	public function reject(){
		$m = new M();
		$rs = $m->reject();
		return \json_encode($rs);
	}

	/**
	* 用户退款
	*/
	public function getRefund(){
		$m = new M();
		return $m->getMoneyByOrder((int)input('id'));
	}




	/*********************************************** 商家操作订单 ************************************************************/

	/**
	* 商家-查看订单列表
	*/
	public function sellerOrder(){
		$this->checkShopAuth("list");
		$type = input('param.type','all');
		$this->assign('type',$type);
		$express = model('Express')->listQuery();
		$this->assign('express',$express);
		return $this->fetch('users/sellerorders/orders_list');
	}

	/**
	* 商家-订单列表
	*/
	public function getSellerOrderList(){
		/* 
		 	-3:拒收、退款列表
			-2:待付款列表 
			-1:已取消订单
			 0: 待发货
			1,2:待评价/已完成
		*/
		$type = input('param.type');
		$this->checkShopAuth($type);
		$status = [];
		switch ($type) {
			case 'waitPay':
				$status=-2;
				break;
			case 'waitDeliver':
				$status=0;
				break;
			case 'waitReceive':
				$status=1;
				break;
			case 'waitDelivery':
				$status=0;
				break;
			case 'finish': 
				$status=2;
				break;
			case 'abnormal': // 退款/拒收 与取消合并
				$status=[-1,-3];
				break;
			default:
				$status=[-5,-4,-3,-2,-1,0,1,2];
				break;
		}
		$m = new M();
		$rs = $m->shopOrdersByPage($status);
		foreach($rs['data'] as $k=>$v){
			if(!empty($v['list'])){
				foreach($v['list'] as $k1=>$v1){
					$rs['data'][$k]['list'][$k1]['goodsImg'] = WSTImg($v1['goodsImg'],3);
				}
			}
		}
		return WSTReturn('操作成功',1,$rs);
	}

	/**
	 * 商家发货
	 */
	public function deliver()
    {
		$this->checkShopAuth("waitDeliver");
        $user_id = input('param.mall_user_id'); // 直播用户ID
        if (empty($user_id)) {
            return $this->outJson(100, "缺少参数");
        }
        $shop = Db::name('shops')->where("userId = {$user_id}")->field("shopId")->find();
        if (empty($shop)) {
            return $this->outJson(100, "没有数据");
        }
        $shopId = $shop['shopId'];
		$m = new M();
		$rs = $m->deliver($user_id, $shopId);
		if ($rs['status'] == -1) {
            return $this->outJson(100, $rs['msg']);
        }
		return $this->outJson(0, '查询成功', $rs);
	}
    /**
     * 商家修改物流
     */
    public function updateExpress(){
        $this->checkShopAuth("waitDeliver");
        $user_id = input('param.mall_user_id'); // 直播用户ID
        if (empty($user_id)) {
            return $this->outJson(100, "缺少参数");
        }
        $shop = Db::name('shops')->where("userId = {$user_id}")->field("shopId")->find();
        if (empty($shop)) {
            return $this->outJson(100, "没有数据");
        }
        $shopId = $shop['shopId'];
        $m = new M();
        $rs = $m->updateExpress($user_id, $shopId);
        if ($rs['status'] == -1) {
            return $this->outJson(100, $rs['msg']);
        }
        return $this->outJson(0, '查询成功', $rs);
    }
	/**
	 * 商家修改订单价格
	 */
	public function editOrderMoney(){
		$this->checkShopAuth("waitPay");
		$m = new M();
		$rs = $m->editOrderMoney();
		return \json_encode($rs);
	}
	/**
	 * 商家-操作退款
	 */
	public function toShopRefund(){
		$this->checkShopAuth("abnormal");
		return model('OrderRefunds')->getRefundMoneyByOrder((int)input('id'));
	}

    /**
     * 获取单条订单的商品信息
     */
    public function waitDeliverById()
    {
        $m = new M();
        $rs = $m->waitDeliverById();
        return $this->outJson(1, '查询成功', $rs);
    }
}
