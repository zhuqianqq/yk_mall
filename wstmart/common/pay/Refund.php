<?php
namespace wstmart\common\pay;

use util\Tools;
use think\Db;
use wstmart\common\model\OrderRefunds;
use wstmart\common\model\RefundInterface;

class Refund
{
    /**
     * 支付接口对象缓存
     * @var array
     */
    private static $payers = [];

    public function refund(OrderRefunds $refund)
    {
        Db::startTrans();
        try {
            // 调用第三方退款接口
            $payer = $this->getPayer($refund->type);
            $payerClass = get_class($payer);
            $result = $payer->refund($refund);
            Tools::addLog('refund_succ', "#Record {$refund->id} 退款成功 $payerClass:" . var_export($result, true));
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            Tools::addLog('refund_fail', "#Record {$refund->id} 退款失败: " .  $e->getMessage());
        }
    }

    /**
     * @param $refundType
     * @return RefundInterface
     * @throws \Exception
     */
    private function getPayer($refundType) {
        if (isset(self::$payers[$refundType])) {
            return self::$payers[$refundType];
        }

        switch ($refundType) {
            case OrderRefunds::REFUND_WX_JSAPI:
                $appId = config('wxpay.xcx.app_id');
                $mchId = config('wxpay.xcx.mch_id');
                $appKey = config('wxpay.xcx.key');
                $payer = new WeixinPay($appId, $mchId, $appKey);
                break;
            case OrderRefunds::REFUND_WX_NATIVE:
                $appId = config('wxpay.app.app_id');
                $mchId = config('wxpay.app.mch_id');
                $appKey = config('wxpay.app.key');
                $payer = new WeixinPay($appId, $mchId, $appKey);
                break;

            case OrderRefunds::REFUND_ALIPAY:
                $payer = new AliPay();
                break;

            default:
                throw new \Exception("未支持的退款类型");
        }

        self::$payers[$refundType] = $payer;

        return $payer;
    }
}
