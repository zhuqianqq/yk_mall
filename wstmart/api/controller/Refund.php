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
    CONST REFUND_APPLICATION = 1; //申请退款
    CONST REFUND_SUCCESS = 2; //2退款成功
    CONST REFUND_FAIL = 3;//退款失败
    CONST REFUND_AGREE = 4;//退货退款同意
    CONST REFUND_CANCEL = 5;//撤销退款
    CONST REFUND_DELETE = 6;//删除订单
    CONST REFUND_WAIT_RECIVE = 7;//等待商家收货

//    public function test()
//    {
//        $refundId = 21;
//        $orderRefund = \wstmart\common\model\OrderRefunds::get($refundId);
//        $refund = new \wstmart\common\pay\Refund();
//        $refund->refund($orderRefund);
//    }

    /**
     * 申请退款
     * @return array
     */
    public function toRefund()
    {
        $userId = (int)$this->user_id;
        $orderId = (int)input('post.orderId');
        $goodsId = (int)input('post.goodsId');
        $goodsSpecId = (int)input('post.goodsSpecId');
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
        if (empty($userId) || empty($orderId) || empty($goodsId) || empty($refundCode) || empty($refundReason)) {
            return $this->outJson(100, "缺少参数!");
        }
        $order = \wstmart\common\model\Orders::get($orderId);
        if (empty($order)) {
            return $this->outJson(100, "没有数据!");
        }
        if ($order['userId'] != $userId) {
            return $this->outJson(100, "没有数据!");
        }
        if ($goodsSpecId) {
            $orderGoods = OrderGoods::where("orderId = " . $orderId . " AND goodsId = " . $goodsId . " AND goodsSpecId = " . $goodsSpecId)->find();
        } else {
            $orderGoods = OrderGoods::where("orderId = " . $orderId . " AND goodsId = " . $goodsId)->find();
        }
        if (empty($orderGoods)) {
            return $this->outJson(100, "没有数据!");
        }
        $orderStatus = $order['orderStatus']; // -3：退款/拒收 -2：待付款 -1：已取消 0：待发货 1：待收货 2：待评价/已完成 6：取消订单 7：删除订单
        if (!in_array($orderStatus, [0, 1, 2])) {
            return $this->outJson(100, "不可操作!");
        }
        // 0未关闭 1 已关闭
        if (1 == $order['isClosed']) {
            return $this->outJson(100, "不可操作!");
        }
        // 已完成 超过15天也不能进行申请退款
        $afterSaleEndTime = $order['afterSaleEndTime'];
        if (!empty($afterSaleEndTime)) {
            if (strtotime($afterSaleEndTime) < time()) {
                return $this->outJson(100, "不可操作!");
            }
        }
        // 0初始 1 退款中 2 退款成功 3 退款失败 4 删除订单
        $orderGoodsStatus = $orderGoods['refundStatus'];
        if (!in_array($orderGoodsStatus, [0, 4])) {
            return $this->outJson(100, "不可操作!");
        }
        if (1 == $refundType) {
            // 如果是退货退款
            if (!in_array($orderStatus, [1, 2])) {
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
            if ($goodsSpecId) {
                $refundExist = \wstmart\common\model\OrderRefunds::where("orderId = " . $orderId .  ' and goodsId =' . $goodsId . " AND goodsSpecId = " . $goodsSpecId)->find();
            } else {
                $refundExist = \wstmart\common\model\OrderRefunds::where("orderId = " . $orderId .  ' and goodsId =' . $goodsId)->find();
            }
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
                $refundExist->createTime = date('Y-m-d H:i:s');
                $refundExist->refundStatus = 1;
                $refundExist->createTime = date('Y-m-d H:i:s');
                $refundExist->refundImgs = $refundImgs;
                $refundExist->lastStatus = $orderStatus;
                $refundExist->save();
            } else {
                // 1wxapp 2 xcx 3alipay
                switch ($order['payFrom']) {
                    case 'alipays':
                        $refundTo = 3;
                        break;
                    case 'xcx':
                        $refundTo = 2;
                        break;
                    case 'weixinpays':
                        $refundTo = 1;
                        break;
                    default:
                        $refundTo = 0;
                }
                if ($order['isBatch']) {
                    $trade_no = $order['orderunique'];
                } else {
                    $trade_no = $order['orderNo'];
                }
                // 不存在
                $refund = new \wstmart\common\model\OrderRefunds();
                $refund->orderId = $orderId;
                $refund->goodsId = $goodsId;
                $refund->goodsSpecId = $goodsSpecId;
                $refund->refundReson = $refundCode;
                $refund->refundType = $refundType;
                $refund->refundOtherReson = $refundReason;
                $refund->backMoney = bcdiv($refundMoney, 1, 2);
                $refund->totalMoney = bcdiv($order['realTotalMoney'], 1, 2);
                $refund->refundTradeNo = $refundNo;
                $refund->refundRemark = $refundMark;
                $refund->refundStatus = 1;
                $refund->createTime = date('Y-m-d H:i:s');
                $refund->refundImgs = $refundImgs;
                $refund->lastStatus = $orderStatus;
                $refund->goodsStatus = $goodsStatus;
                $refund->refundTo = $refundTo;
                $refund->trade_no = $trade_no;
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
        $goodsSpecId = (int)input('post.goodsSpecId');
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
        if ($goodsSpecId) {
            $orderGoods = OrderGoods::where("orderId = " . $orderId . " AND goodsId = " . $goodsId . " AND goodsSpecId = " . $goodsSpecId)->find();
        } else {
            $orderGoods = OrderGoods::where("orderId = " . $orderId . " AND goodsId = " . $goodsId)->find();
        }
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
            if ($goodsSpecId) {
                $refundExist = \wstmart\common\model\OrderRefunds::where("orderId = " . $orderId .  ' and goodsId =' . $goodsId . " AND goodsSpecId = " . $goodsSpecId)->find();
            } else {
                $refundExist = \wstmart\common\model\OrderRefunds::where("orderId = " . $orderId .  ' and goodsId =' . $goodsId)->find();
            }
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

    /***
     * 删除退款
     * @return array
     */
    public function delRefund()
    {
        $userId = (int)$this->user_id;
        $orderId = (int)input('post.orderId');
        $goodsId = (int)input('post.goodsId');
        $goodsSpecId = (int)input('post.goodsSpecId');
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
        if ($goodsSpecId) {
            $orderGoods = OrderGoods::where("orderId = " . $orderId . " AND goodsId = " . $goodsId . " AND goodsSpecId = " . $goodsSpecId)->find();
        } else {
            $orderGoods = OrderGoods::where("orderId = " . $orderId . " AND goodsId = " . $goodsId)->find();
        }
        if (empty($orderGoods)) {
            return $this->outJson(100, "没有数据!");
        }
        // 0初始 1 退款中 2 退款成功 3 退款失败
        $orderGoodsStatus = $orderGoods['refundStatus'];
        if (!in_array($orderGoodsStatus, [2, 0, 3])) {
            return $this->outJson(100, "不可操作!");
        }

        Db::startTrans();
        try {
            if ($goodsSpecId) {
                $refundExist = \wstmart\common\model\OrderRefunds::where("orderId = " . $orderId .  ' and goodsId =' . $goodsId . " AND goodsSpecId = " . $goodsSpecId)->find();
            } else {
                $refundExist = \wstmart\common\model\OrderRefunds::where("orderId = " . $orderId .  ' and goodsId =' . $goodsId)->find();
            }
            if (empty($refundExist)) {
                throw new \Exception('没有数据', 100);
            }
            $refundStatus = $refundExist['refundStatus']; // 1 申请退款 2退款成功 3 退款失败 4 退货退款同意 5 撤销退款 6 删除订单

            if (!in_array($refundStatus, [2, 5])) {
                // 1 申请退款 2 退款成功 3 退款失败 4 退货退款同意
                throw new \Exception('不可操作', 100);
            }
            $refundExist->refundStatus = 6;
            $refundExist->save();

            // 0初始 1 退款中 2 退款成功 3 退款失败 4删除退款
            $orderGoods->refundStatus =  4;
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
        $orderStatus = (int)input('post.orderStatus', 1); // 0 未发货 1 已发货
        $catId = 19;
        if ($type) {
            $catId = 22;
            if ($orderStatus == 0) {
                $catId = 4;
            }
        } else {
            if ($orderStatus == 0) {
                $catId = 21;
            }
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
        $rs = $m->refundPageQuery($userId);
        return $this->outJson(0, "success", $rs);
    }

    /**
     * 订单详情-退款
     */
    public function getRefundDetail()
    {
        $orderId = input('param.orderId','');
        $goodsId = input('param.goodsId','');
        if (empty($orderId)||empty($goodsId)) {
            return $this->outJson(100, "缺少参数");
        }
        $m = new \wstmart\common\model\OrderRefunds();
        $rs = $m->orderDetailrefund();
        return $this->outJson(0, "success", $rs);
    }


    /**
     * 填写用户的退款物流信息
     */
    public function setlogisticInfo()
    {
        $refundId = input('param.refundId','');
        $logisticInfo = input('param.logisticInfo','');
        $logisticNum = input('param.logisticNum','');
        if (!$logisticInfo||!$logisticNum||!$refundId) {
            return $this->outJson(100, "缺少参数");
        }
        Db::name('order_refunds')->where(['id'=>$refundId])->update(['logisticTime'=>date('Y-m-d H:i:s',time()),'logisticInfo'=>$logisticInfo,'logisticNum'=>$logisticNum,'refundStatus'=>7]);
        return $this->outJson(0, "success");
    }
}
