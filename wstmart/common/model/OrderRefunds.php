<?php
namespace wstmart\common\model;
use think\Db;
/**
 * 退款业务处理类
 */
class OrderRefunds extends Base{
    // 微信APP退款
    const REFUND_WX_NATIVE = 1;

    // 微信公众号(小程序)退款
    const REFUND_WX_JSAPI = 2;

    // 支付宝退款
    const REFUND_ALIPAY = 3;

	/**
	 * 用户申请退款
	 */
	public function refund($uId=0){
		$orderId = (int)input('post.id');
		$reason = (int)input('post.reason');
		$content = input('post.content');
		$money = (float)input('post.money');
		$userId = ($uId==0)?(int)session('WST_USER.userId'):$uId;
		if($money<0)return WSTReturn("退款金额不能为负数");
		$order = Db::name('orders')->alias('o')->join('__ORDER_REFUNDS__ orf','orf.orderId=o.orderId','left')->join('__SHOPS__ s','o.shopId=s.shopId','left')
		           ->where([['o.orderStatus','=',-3],['o.orderId','=',$orderId],['o.userId','=',$userId],['isRefund','=',0]])
		           ->field('o.orderId,s.userId,o.shopId,o.orderStatus,o.orderNo,o.realTotalMoney,o.isPay,o.payType,o.useScore,orf.id refundId')->find();
		$reasonData = WSTDatas('REFUND_TYPE',$reason);
		if(empty($reasonData))return WSTReturn("无效的退款原因");
		if($reason==10000 && $content=='')return WSTReturn("请输入退款原因");
		if(empty($order))return WSTReturn('操作失败，请检查订单是否符合申请退款条件');
		$allowRequest = false;
		if($order['isPay']==1 || $order['useScore']>0){
			$allowRequest = true;
		}
		if(!$allowRequest)return WSTReturn("您的退款申请已提交，请留意退款信息");
		if($money>$order['realTotalMoney'])return WSTReturn("申请退款金额不能大于实支付金额");
		//查看退款申请是否已存在
		$orfId = $this->where('orderId',$orderId)->value('id');
		Db::startTrans();
		try{
			$result = false;
			//如果退款单存在就进行编辑
			if($orfId>0){
				$object = $this->get($orfId);
				$object->refundReson = $reason;
				if($reason==10000)$object->refundOtherReson = $content;
				$object->backMoney = $money;
				$object->refundStatus = ($order['orderStatus']==-1)?1:0;;
				$result = $object->save();
			}else{
				$data = [];
				$data['orderId'] = $orderId;	
	            $data['refundTo'] = 0;
	            $data['refundReson'] = $reason;
	            if($reason==10000)$data['refundOtherReson'] = $content;
	            $data['backMoney'] = $money;
	            $data['createTime'] = date('Y-m-d H:i:s');
	            $data['refundStatus'] = ($order['orderStatus']==-1)?1:0;
	            $result = $this->save($data);
			}			
            if(false !== $result){
            	//拒收申请退款的话要给商家发送信息
            		$tpl = WSTMsgTemplates('ORDER_REFUND_CONFER');
	                if( $tpl['tplContent']!='' && $tpl['status']=='1'){
	                    $find = ['${ORDER_NO}'];
	                    $replace = [$order['orderNo']];
	                    
	                	$msg = array();
			            $msg["shopId"] = $order['shopId'];
			            $msg["tplCode"] = $tpl["tplCode"];
			            $msg["msgType"] = 1;
			            $msg["content"] = str_replace($find,$replace,$tpl['tplContent']);
			            $msg["msgJson"] = ['from'=>1,'dataId'=>$orderId];
			            model("common/MessageQueues")->add($msg);
	                }
	                //微信消息
					if((int)WSTConf('CONF.wxenabled')==1){
						$params = [];
						$params['ORDER_NO'] = $order['orderNo'];
					    $params['REASON'] = $reasonData['dataName'].(($reason==10000)?" - ".$content:"");             
						$params['MONEY'] = $money.(($order['useScore']>0)?("【退回积分：".$order['useScore']."】"):"");
				       
						$msg = array();
						$tplCode = "WX_ORDER_REFUND_CONFER";
						$msg["shopId"] = $order['shopId'];
			            $msg["tplCode"] = $tplCode;
			            $msg["msgType"] = 4;
			            $msg["paramJson"] = ['CODE'=>$tplCode,'URL'=>Url('wechat/orders/sellerorder','',true,true),'params'=>$params];
			            $msg["msgJson"] = "";
			            model("common/MessageQueues")->add($msg);
					}
            	Db::commit();
                return WSTReturn('您的退款申请已提交，请留意退款信息',1);
            }
		}catch (\Exception $e) {
		    Db::rollback();
	    }
	    return WSTReturn('操作失败',-1);
	}

    /**
     * 退款
     */
    public function orderRefund()
    {
        $id = (int)input('post.id');
        if($id==0)return WSTReturn("操作失败!");
        $refund = $this->get($id);
        if(empty($refund) || !in_array($refund->refundStatus, [1, 4, 7]))return WSTReturn("该退款订单不存在或已退款!");

        $orderRefund = \wstmart\common\model\OrderRefunds::get($id);
        $refund = new \wstmart\common\pay\Refund();
        $rs = $refund->refund($orderRefund);

        if (-1 == $rs['status']) {
            // 1 申请退款 2退款成功 3 退款失败 4 退货退款同意 5 撤销退款 6删除订单 7等待商家收货
            $this->fail($orderRefund, $rs['msg']);
            return WSTReturn("退款失败:" . $rs['msg'],-1);
        }
        $this->success($orderRefund);
        // 成功进行逻辑处理
        return WSTReturn("退款成功",1);
    }

    /**
     * 退款失败更新数据库
     * @param OrderRefunds $orderRefund
     * @param $msg
     */
    public function fail(OrderRefunds $orderRefund, $msg)
    {
        Db::startTrans();
        try {
            // 1 申请退款 2退款成功 3 退款失败 4 退货退款同意 5 撤销退款 6删除订单 7等待商家收货
            $orderRefund->refundStatus = 3;
            $orderRefund->failReason = $msg;
            $orderRefund->save();

            $orderId = $orderRefund->orderId;
            $goodsId = $orderRefund->goodsId;
            $goodsSpecId = $orderRefund->goodsSpecId;
            if ($goodsSpecId) {
                $orderGoods = OrderGoods::where("orderId = " . $orderId . " AND goodsId = " . $goodsId . " AND goodsSpecId =" . $goodsSpecId)->find();
            } else {
                $orderGoods = OrderGoods::where("orderId = " . $orderId . " AND goodsId = " . $goodsId)->find();
            }
            if (!empty($orderGoods)) {
                // 0初始 1 退款中 2 退款成功 3 退款失败 4删除退款
                $orderGoods->refundStatus = 3;
                $orderGoods->save();
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
        }
    }

    /**
     * 退款成功进行操作
     * @param OrderRefunds $orderRefund
     */
    public function success(OrderRefunds $orderRefund)
    {
        Db::startTrans();
        try {
            // 1 申请退款 2退款成功 3 退款失败 4 退货退款同意 5 撤销退款 6删除订单 7等待商家收货
            $orderRefund->refundStatus = 2;
            $orderRefund->refundTime = date('Y-m-d H:i:s');
            $orderRefund->save();

            $orderId = $orderRefund->orderId;
            $goodsId = $orderRefund->goodsId;
            $goodsSpecId = $orderRefund->goodsSpecId;
            if ($goodsSpecId) {
                $orderGoods = OrderGoods::where("orderId = " . $orderId . " AND goodsId = " . $goodsId . " AND goodsSpecId =" . $goodsSpecId)->find();
            } else {
                $orderGoods = OrderGoods::where("orderId = " . $orderId . " AND goodsId = " . $goodsId)->find();
            }
            if (!empty($orderGoods)) {
                // 0初始 1 退款中 2 退款成功 3 退款失败 4删除退款
                $orderGoods->refundStatus = 2;
                $orderGoods->save();
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
        }
    }

	/**
	 * 获取订单价格以及申请退款价格
	 */
	public function getRefundMoneyByOrder($orderId = 0){
		return Db::name('orders')->alias('o')->join('__ORDER_REFUNDS__ orf','orf.orderId=o.orderId')->where('orf.id',$orderId)->field('o.orderId,orderNo,goodsMoney,deliverMoney,useScore,scoreMoney,totalMoney,realTotalMoney,orf.backMoney')->find();
	}

	/**
	 * 商家处理是否同意退款
	 */
	public function shoprefund($shopId=0){
        $id = (int)input('id');
        $shopId = $shopId > 0 ? $shopId : (int)session('WST_USER.shopId');
        $refundStatus = (int)input('refundStatus');
        $content = input('content');
        if($id==0)return WSTReturn('无效的操作');
        if(!in_array($refundStatus,[1,-1]))return WSTReturn('无效的操作');
        if($refundStatus==-1 && $content=='')return WSTReturn('请输入拒绝原因');
        Db::startTrans();
        try{
        	$object = $this->get($id);
        	if(empty($object))return WSTReturn('无效的操作');
        	$order = Db::name('orders')->where(['orderId'=>$object->orderId,'shopId'=>$shopId])->field('userId,orderNo,orderId,useScore')->find();
        	if(empty($order))return WSTReturn('无效的操作');
            $object->refundStatus = $refundStatus;
            if($object->refundStatus==-1)$object->shopRejectReason = $content;
            $result = $object->save();
            if(false !== $result){
            	//如果是拒收话要给用户发信息
            	if($refundStatus==-1){
            		$tpl = WSTMsgTemplates('ORDER_REFUND_FAIL');
	                if( $tpl['tplContent']!='' && $tpl['status']=='1'){
	                    $find = ['${ORDER_NO}','${REASON}'];
	                    $replace = [$order['orderNo'],$content];
	                    WSTSendMsg($order['userId'],str_replace($find,$replace,$tpl['tplContent']),['from'=>1,'dataId'=>$order['orderId']]);
	                } 
	                //微信消息
					if((int)WSTConf('CONF.wxenabled')==1){
						$reasonData = WSTDatas('REFUND_TYPE',$object->refundReson);
						$params = [];
						$params['ORDER_NO'] = $order['orderNo'];
					    $params['REASON'] = $reasonData['dataName'].(($object->refundReson==10000)?" - ".$object->refundOtherReson:"");
					    $params['SHOP_REASON'] = $object->shopRejectReason;             
						$params['MONEY'] = $object->backMoney.(($order['useScore']>0)?("【退回积分：".$order['useScore']."】"):"");
				        WSTWxMessage(['CODE'=>'WX_ORDER_REFUND_FAIL','userId'=>$order['userId'],'URL'=>Url('wechat/orders/index','',true,true),'params'=>$params]);
					}  
            	}else{
            		//判断是否需要发送管理员短信
					$tpl = WSTMsgTemplates('PHONE_ADMIN_REFUND_ORDER');
					if((int)WSTConf('CONF.smsOpen')==1 && (int)WSTConf('CONF.smsRefundOrderTip')==1 &&  $tpl['tplContent']!='' && $tpl['status']=='1'){
						$params = ['tpl'=>$tpl,'params'=>['ORDER_NO'=>$order['orderNo']]];
						$staffs = Db::name('staffs')->where([['staffId','in',explode(',',WSTConf('CONF.refundOrderTipUsers'))],['staffStatus','=',1],['dataFlag','=',1]])->field('staffPhone')->select();
						for($i=0;$i<count($staffs);$i++){
							if($staffs[$i]['staffPhone']=='')continue;
							$m = new LogSms();
							$rv = $m->sendAdminSMS(0,$staffs[$i]['staffPhone'],$params,'shoprefund','');
						}
					}
					//微信消息
					if((int)WSTConf('CONF.wxenabled')==1){
						//判断是否需要发送给管理员消息
		                if((int)WSTConf('CONF.wxRefundOrderTip')==1){
		                	$reasonData = WSTDatas('REFUND_TYPE',$object->refundReson);
		                	$params = [];
						    $params['ORDER_NO'] = $order['orderNo'];
					        $params['REASON'] = $reasonData['dataName'].(($object->refundReson==10000)?" - ".$object->refundOtherReson:"");           
						    $params['MONEY'] = $object->backMoney.(($order['useScore']>0)?("【退回积分：".$order['useScore']."】"):"");
			            	WSTWxBatchMessage(['CODE'=>'WX_ADMIN_ORDER_REFUND','userType'=>3,'userId'=>explode(',',WSTConf('CONF.refundOrderTipUsers')),'params'=>$params]);
		                }
					}
            	}
            	Db::commit();
            	return WSTReturn('操作成功',1);
            }
        }catch (\Exception $e) {
		    Db::rollback();
	    }
	    return WSTReturn('操作失败',-1);
	}

	//取消订单自动申请退款
    function autoApplyRefund($orderId,$reason,$realTotalMoney,$orderNo,$useScore){
        $result = false;
        $data = [];
        $data['orderId'] = $orderId;
        $data['refundTo'] = 0;
        $data['refundReson'] = 10000;
        $data['refundOtherReson'] = $reason;
        $data['backMoney'] = $realTotalMoney;
        $data['createTime'] = date('Y-m-d H:i:s');
        $data['refundStatus'] = 1;
        $result = $this->save($data);

        if(false !== $result) {
            //判断是否需要发送管理员短信
            $tpl = WSTMsgTemplates('PHONE_ADMIN_REFUND_ORDER');
            if ((int)WSTConf('CONF.smsOpen') == 1 && (int)WSTConf('CONF.smsRefundOrderTip') == 1 && $tpl['tplContent'] != '' && $tpl['status'] == '1') {
                $params = ['tpl' => $tpl, 'params' => ['ORDER_NO' => $orderNo]];
                $staffs = Db::name('staffs')->where([['staffId', 'in', explode(',', WSTConf('CONF.refundOrderTipUsers'))], ['staffStatus', '=', 1], ['dataFlag', '=', 1]])->field('staffPhone')->select();
                for ($i = 0; $i < count($staffs); $i++) {
                    if ($staffs[$i]['staffPhone'] == '') continue;
                    $m = new LogSms();
                    $rv = $m->sendAdminSMS(0, $staffs[$i]['staffPhone'], $params, 'refund', '');
                }
            }
            //微信消息
            if ((int)WSTConf('CONF.wxenabled') == 1) {
                //判断是否需要发送给管理员消息
                if ((int)WSTConf('CONF.wxRefundOrderTip') == 1) {
                    $params = [];
                    $params['ORDER_NO'] = $orderNo;
                    $params['MONEY'] = $realTotalMoney . (($useScore > 0) ? ("【退回积分：" . $useScore . "】") : "");
                    WSTWxBatchMessage(['CODE' => 'WX_ADMIN_ORDER_REFUND', 'userType' => 3, 'userId' => explode(',', WSTConf('CONF.refundOrderTipUsers')), 'params' => $params]);
                }
            }
        }
    }

    /**
     * 获取用户退款订单列表
     */
    public function refundPageQuery($userId = 0)
    {
        $where = [];
        $where[] = ['o.dataFlag', '=', 1];
        $where[] = ['o.userId', '=', $userId];
        $where[] = ['isRefund', '=', 1];

        // 1 申请退款 2退款成功 3 退款失败 4 退货退款同意 5 撤销退款 6 删除订单 7 等待商家收货
        $page = Db::name('orders')->alias('o')
            ->join('__ORDER_REFUNDS__ orf ','o.orderId=orf.orderId and orf.refundStatus in (1,2,3,4,5,7)')
            ->where($where)
            ->field('orf.id refundId,o.orderId,payType,payFrom,o.orderStatus,orderSrc,orf.backMoney,orf.refundRemark,isRefund,orf.createTime,o.orderCode')
            ->order('orf.createTime', 'desc')
            ->paginate(input('limit/d'))->toArray();
        $orderIds = [];
        $list = [];
        if (!empty($page['data'])) {
            foreach ($page['data'] as $key => $v){
                $orderId = $v['orderId'];
                $orderIds[] = $orderId;
            }
            $orderIds = array_unique($orderIds);
            $ids = implode(',', $orderIds);
            $list = Db::name('order_goods')->alias('og')
                ->join("__ORDER_REFUNDS__ orf", 'og.orderId = orf.orderId and og.goodsId = orf.goodsId', 'left')
                ->where("og.orderId in (" . $ids . ") and orf.refundStatus in (1,2,3,4,5,7)")
                ->field('og.orderId,og.goodsSpecId,og.goodsId,og.goodsNum,og.goodsPrice,og.goodsSpecNames, og.goodsName, og.goodsImg, orf.refundStatus, orf.createTime')
                ->order('orf.createTime', 'desc')
                ->paginate(input('limit/d'))->toArray();
            if (!empty($list['data'])) {
                foreach ($list['data'] as $k => $v) {
                    $list['data'][$k]['refundMoney'] = bcmul($v['goodsNum'], $v['goodsPrice'], 2);
                    $list['data'][$k]['refundStatusText'] = WSTLangOrderRefundStatus($v['refundStatus']);
                }
            }
        }
        return $list;
	}

    /**
     * 获取用户退款订单列表
     */
    public function refundPageQueryShop()
    {
        $startDate = input('startDate');
        $endDate = input('endDate');
        $where = [];
        $trade_no = input('trade_no'); // 订单编号
        $refundTo = (int)input('refundTo',-1); // 支付方式
        $refundStatus = (int)input('refundStatus',-1); // 退款状态
        if($trade_no != '') $where[] = ['orf.trade_no','like','%'.$trade_no.'%'];

        if($refundTo != -1) $where[] = ['orf.refundTo', '=', $refundTo];
        if($refundStatus != -1) {
            // 1 未退款 2 已退款
            // 1 申请退款 2退款成功 3 退款失败 4 退货退款同意 5 撤销退款 6删除订单 7等待商家收货
            if ($refundStatus == 1) {
                $where[] = ['orf.refundStatus', 'in', [1,3,4,7]];
            } else {
                $where[] = ['orf.refundStatus', '=', 2];
            }
        } else {
            $where[] = ['orf.refundStatus', 'in', [1,3,4,7]];
        }

        if($startDate!='' && $endDate!=''){
            $where[] = ['orf.createTime', 'between', [$startDate.' 00:00:00',$endDate.' 23:59:59']];
        } else if($startDate != '') {
            $where[] = ['orf.createTime','>=',$startDate.' 00:00:00'];
        } else if($endDate!=''){
            $where[] = ['orf.createTime','<=',$endDate.' 23:59:59'];
        }

        $shopId = session("WST_USER.shopId"); // 店铺ID
        if (empty($shopId)) {
            return [];
        }
        $where[] = ['o.shopId', '=', $shopId];
        $where[] = ['o.isRefund', '=', 1];

        // 1 申请退款 2退款成功 3 退款失败 4 退货退款同意 5 撤销退款 6 删除订单 7 等待商家收货
        $page = Db::name('orders')->alias('o')
            ->join('__ORDER_REFUNDS__ orf ','o.orderId=orf.orderId', 'left')
            ->where($where)
            ->field('orf.serviceId, orf.isServiceRefund, orf.id refundId,o.orderId,payType,payFrom,o.orderStatus,orderSrc,orf.backMoney,orf.totalMoney realTotalMoney,orf.refundRemark,o.isRefund,orf.createTime,orf.trade_no orderNo, orf.refundTo, orf.refundStatus')
            ->order('orf.createTime', 'desc')
            ->paginate(input('limit/d'))->toArray();
        return $page;
    }
	

	/**
     * 获取用户退款订单详情
     */
    public function orderDetailrefund()
    {
		$where = [];
        $where[] = ['og.orderId', '=', (int)input('param.orderId')];
		$where[] = ['og.goodsId', '=', (int)input('param.goodsId')];

		if(input('param.goodsSpecId','')){
			$where[] = ['og.goodsSpecId', '=', (int)input('param.goodsSpecId','')];
		}
		
		$orderDetail = Db::name('order_goods')->alias('og')
		->join("__ORDER_REFUNDS__ orf", 'og.orderId = orf.orderId and og.goodsId = orf.goodsId', 'left')
		->join("__ORDERS__ o", 'og.orderId = o.orderId', 'left')
		->where($where)
		->field('og.orderId,og.goodsId,og.goodsNum,og.goodsPrice,og.goodsSpecId,og.goodsSpecNames, og.goodsName, og.goodsImg,orf.id as refundId,orf.refundTo,
		 orf.refundStatus,orf.refundTradeNo,orf.refundReson,orf.refundOtherReson,orf.refundRemark,orf.logisticNum,orf.logisticInfo,orf.createTime,orf.refundTime,o.orderStatus,o.shopId,o.createTime as oCreateTime,o.payTime,o.receiveTime,o.deliveryTime')
		->order('orf.createTime', 'desc')
		->find() ?? [];

		if($orderDetail){
			$orderDetail['refundMoney'] = bcmul($orderDetail['goodsNum'], $orderDetail['goodsPrice'], 2);
			$orderDetail['refundStatusText'] = WSTLangOrderDetailRefundStatus($orderDetail['refundStatus']);
			$orderDetail['expressInfo'] = Db::name('express')->where("dataFlag = 1")->select()??[];
			//店铺退货地址信息
			$orderDetail['shopInfo'] = Db::name('shops')->field('shopkeeper,shopTel,shopAddress')->where("shopId",$orderDetail['shopId'])->find();
		}
		return $orderDetail;
	}

    /**
     * 获取退款资料
     */
    public function getInfoByRefund(){
        $where = [['orf.id','=',(int)input('get.id')],
            ['isRefund','=',1],
            ['refundStatus','in', [1, 7]]];
        $serviceId = (int)input('serviceId');
        if ($serviceId > 0) {
            $where = [
                'serviceId'=>$serviceId,
                'isRefund'=>0,
                'orf.id'=>(int)input('get.id'),
                'orderStatus'=> 2,
                'refundStatus' => 1
            ];
        }
        $rs = $this->alias('orf')->join('__ORDERS__ o','orf.orderId=o.orderId')
            ->where($where)
            ->field('orf.id refundId,orderNo,o.orderId,goodsMoney,refundReson,refundOtherReson,o.totalMoney,realTotalMoney,deliverMoney,payType,payFrom,backMoney,o.useScore,o.scoreMoney,tradeNo')
            ->find();
        if($serviceId>0 && $rs['useScore']>0){
            $rs['serviceId'] = $serviceId;
            // 替换数据
            $osData = Db::name('order_services')
                ->field('refundScore,useScoreMoney,getScoreMoney,refundableMoney')
                ->where(['id'=>$serviceId])
                ->find();
            // 退还积分
            $rs['useScore'] = $osData['refundScore'];
            // 积分可抵扣金额
            $rs['scoreMoney'] = $osData['useScoreMoney'];
            // 获得的积分可抵扣金额
            $rs['getScoreMoney'] = $osData['getScoreMoney'];

            // 售后单总金额 = 售后单可退款金额+获得的积分可抵扣金额
            $rs['totalMoney'] = $osData['refundableMoney'] + $osData['getScoreMoney'];
        }
        return $rs;
    }
}
