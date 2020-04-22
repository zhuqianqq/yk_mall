<?php
/**
 * 支付退款
 */
namespace wstmart\api\controller;

use think\Db;
use wstmart\admin\model\Datas;
use wstmart\common\model\OrderGoods;

class Refund extends Base
{
    CONST REFUND_APPLICATION = 1;
    CONST REFUND_SUCCESS = 2;
    CONST REFUND_FAIL = 3;
    CONST REFUND_AGREE = 4;
    CONST REFUND_CANCEL = 5;

    /**
     * 申请退款
     * @return array
     */
    public function toRefund()
    {
        $userId = (int)$this->user_id;
        $orderId = (int)input('post.orderId');
        $goodsId = (int)input('post.goodsId');
        $goodsStatus = (int)input('post.goodsStatus');
        $refundType = (int)input('post.refundType', 1); // 1 退货退款 2 仅退款
        $refundCode = (int)input('post.refundCode'); // 退款CODE
        $refundReason = (string)input('post.refundReason'); // 退款原因
        $refundMoney = (float)input('post.refundMoney'); // 退款金额
        $refundMark = (string)input('post.refundMark'); // 退款说明
        $refundImgs = (string)input('post.refundImg'); // 退款凭证
        if ($refundType != 1) {
            $refundType = 2;
        }
        if (empty($userId) || empty($orderId) || empty($goodsId)) {
            return $this->outJson(100, "缺少参数!");
        }
        $order = \wstmart\common\model\Orders::get($orderId);
        if (empty($order)) {
            return $this->outJson(100, "没有数据!");
        }
        if ($order['userId'] != $userId) {
            return $this->outJson(100, "没有数据!");
        }
        $orderGoods = OrderGoods::where("orderId = " . $orderId . " AND goodsId = " . $goodsId)->find();
        if (empty($orderGoods)) {
            return $this->outJson(100, "没有数据!");
        }
        $orderStatus = $order['orderStatus']; // -3：退款/拒收 -2：待付款 -1：已取消 0：待发货 1：待收货 2：待评价/已完成 6：取消订单 7：删除订单
        // 0初始 1 退款中 2 退款成功 3 退款失败
        $orderGoodsStatus = $orderGoods['refundStatus'];
        if (!in_array($orderGoodsStatus, [0])) {
            return $this->outJson(100, "不可操作!");
        }
        if (1 == $refundType) {
            // 如果是退货退款
            if (1 != $orderStatus) {
                return $this->outJson(100, "不可操作");
            }
        }
        $realTotalMoney = bcmul($orderGoods['goodsPrice'], $orderGoods['goodsNum'], 2);
        if (0 == $order['orderStatus']) {
            // 未发货订单只能申请仅退款，退款金额不可修改
            $refundMoney = $realTotalMoney;
        } else {
            $rmoney = bcsub($refundMoney, $realTotalMoney);
            if ($rmoney < 0) {
                return $this->outJson(100, "最多只能退款" . $realTotalMoney);
            }
        }
        Db::startTrans();
        try {
            $refundExist = \wstmart\common\model\OrderRefunds::where("orderId = " . $orderId .  ' and goodsId =' . $goodsId)->find();
            // 订单号
            $refundNo = WSTOrderQnique();
            if (!empty($refundExist)) {
                // 如果存在
                $refundNum = (int)$refundExist['refundNum']; // 操作的次数
                $refundStatus = $refundExist['refundStatus'];
                if (2 == $refundStatus) {
                    // 1 申请退款 2 退款成功 3 退款失败
                    throw new \Exception('不可操作', 100);
                }
                if ($refundNum >= 3) {
                    // 最多只能撤销3次
                    throw new \Exception('最多只能撤销三次', 100);
                }
                $refundExist->refundReson = $refundCode;
                $refundExist->refundOtherReson = $refundReason;
                $refundExist->backMoney = bcdiv($refundMoney, 1, 2);
                $refundExist->refundTradeNo = $refundNo;
                $refundExist->refundRemark = $refundMark;
                $refundExist->refundStatus = 1;
                $refundExist->createTime = date('Y-m-d H:i:s');
                $refundExist->refundImgs = $refundImgs;
                $refundExist->lastStatus = $orderStatus;
                $refundExist->save();
            } else {
                // 不存在
                $refund = new \wstmart\common\model\OrderRefunds();
                $refund->orderId = $orderId;
                $refund->goodsId = $goodsId;
                $refund->refundReson = $refundCode;
                $refund->refundOtherReson = $refundReason;
                $refund->backMoney = bcdiv($refundMoney, 1, 2);
                $refund->refundTradeNo = $refundNo;
                $refund->refundRemark = $refundMark;
                $refund->refundStatus = 1;
                $refund->createTime = date('Y-m-d H:i:s');
                $refund->refundImgs = $refundImgs;
                $refund->lastStatus = $orderStatus;
                $refund->goodsStatus = $goodsStatus;
                $refund->save();
            }
            $order->isRefund = 1;
            $order->save();

            // 0初始 1 退款中 2 退款成功 3 退款失败
            $orderGoods->refundStatus =  1;
            $orderGoods->save();
            Db::commit();
            return $this->outJson(0,  '提交成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->outJson(100, $e->getMessage() ?? '接口异常');
        }
    }

    /***
     * 撤销退款
     * @return array
     */
    public function cancelRefund()
    {
        $userId = (int)$this->user_id;
        $orderId = (int)input('post.orderId');
        $goodsId = (int)input('post.goodsId');
        if (empty($userId) || empty($orderId)) {
            return $this->outJson(100, "缺少参数!");
        }
        $order = \wstmart\common\model\Orders::get($orderId);
        if (empty($order)) {
            return $this->outJson(100, "没有数据!");
        }
        if ($order['userId'] != $userId) {
            return $this->outJson(100, "没有数据!");
        }
        $orderGoods = OrderGoods::where("orderId = " . $orderId . " AND goodsId = " . $goodsId)->find();
        if (empty($orderGoods)) {
            return $this->outJson(100, "没有数据!");
        }
//        $orderStatus = $order['orderStatus']; // -3：退款/拒收 -2：待付款 -1：已取消 0：待发货 1：待收货 2：待评价/已完成 6：取消订单 7：删除订单
        // 0初始 1 退款中 2 退款成功 3 退款失败
        $orderGoodsStatus = $orderGoods['refundStatus'];
        if (!in_array($orderGoodsStatus, [1])) {
            return $this->outJson(100, "不可操作!");
        }

        Db::startTrans();
        try {
            $refundExist = \wstmart\common\model\OrderRefunds::where("orderId = " . $orderId . " AND goodsId = " . $goodsId)->find();
            if (empty($refundExist)) {
                throw new \Exception('没有数据', 100);
            }
            if (!empty($refundExist)) {
                // 如果存在
                $refundNum = (int)$refundExist['refundNum']; // 操作的次数
                $refundStatus = $refundExist['refundStatus']; // 1 申请退款 2退款成功 3 退款失败 4 退货退款同意 5 撤销退款

                if (in_array($refundStatus, [2])) {
                    // 1 申请退款 2 退款成功 3 退款失败 4 退货退款同意
                    throw new \Exception('不可操作', 100);
                }
                if ($refundNum >= 3) {
                    // 最多只能撤销3次
                    throw new \Exception('最多只能撤销三次', 100);
                }
                $refundExist->refundStatus = 5;
                $refundExist->createTime = date('Y-m-d H:i:s');
                $refundExist->refundNum = $refundNum + 1;
                $refundExist->save();
            }

            // 0初始 1 退款中 2 退款成功 3 退款失败
            $orderGoods->refundStatus =  0;
            $orderGoods->save();
            Db::commit();
            return $this->outJson(0,  '提交成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->outJson(100, $e->getMessage() ?? '接口异常');
        }
    }

    /**
     * 退换货原因
     * @return array
     */
    public function refundCode()
    {
        $type = (int)input('post.type'); // 0 退货原因 1 退款原因
        $catId = 1;
        if ($type) {
            $catId = 4;
        }
        $d = new Datas();
        $r = $d->where(['dataFlag' => 1, 'catId' => $catId])->select();
        $refund = [];
        foreach ($r as $v) {
            $refund[] = ['refundCode' => $v['id'], 'refundReason' => $v['dataName']];
        }
        return $this->outJson(0, '查询成功', $refund);
    }

    /**
     * 订单列表-退款
     */
    public function getRefundList()
    {
        $userId = $this->user_id;
        if (empty($userId)) {
            return $this->outJson(100, "缺少参数");
        }
        $m = new \wstmart\common\model\OrderRefunds();
        $rs = $m->refundPageQuery();

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
}
