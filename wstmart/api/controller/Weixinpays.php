<?php
namespace wstmart\api\controller;
use think\Loader;
use Env;
use util\Tools;
use wstmart\common\model\Payments as M;
use wstmart\common\model\Orders as OM;
use wstmart\common\model\LogMoneys as LM;
use wstmart\common\model\ChargeItems as CM;
/**
 * 微信支付控制器
 */
class Weixinpays extends Base
{
    /**
     * @var string 日志名
     */
    protected $logName = "wx_pay";
    /**
     * 微信 APP 回调地址
     * @param bool $channel 渠道参见$params[appMultipleInfo]
     * @return string
     */
    public function notify($channel = false)
    {
        // 接收异步回调参数
        $raw_xml = file_get_contents("php://input");
        Tools::addLog("wxpay_notify", "wx回调开始, praram:" . $raw_xml);

        if(empty($raw_xml)){
            return;
        }

        $response = "<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>";
        $sign_passed = false;

        $data = [];

        try {
            // 处理数据
            $xmlobj = simplexml_load_string($raw_xml);
            foreach ($xmlobj as  $k => $v) {
                if (is_object($v)) {
                    $data[$k] = $v->__toString();
                } else {
                    $data[$k] = $v;
                }
            }
            unset($xmlobj);

            // appMultipleInfo
            $channel = $channel ?? $data['channel'] ?? false;

            // 生成签名验证
            $sign_passed = $this->backSign($data, $channel) == $data['sign'];

            if(!$sign_passed){
                throw new \Exception("验签失败");
            }

            $order_num = $data['out_trade_no'] ?? ''; // 商户网站唯一订单号
            if (empty($order_num)) {
                $this->log('wxpayNotify: 缺失out_trade_no参数');
                return false;
            }

            $m = new \wstmart\common\model\Orders();
            $order = $m->where(['orderunique' => $order_num, 'isPay' => \wstmart\common\model\Orders::IS_PAY_WAIT])->find();
            if (empty($order)) {
                $this->log('wxpayNotify: 没有该订单');
                return false;
            }

            $payment_success = $data['return_code'] === "SUCCESS" && $data['result_code'] === "SUCCESS";

            if(!$payment_success){
                $this->log('wxpayNotify: ' . "通知代码错误 - " . $data['return_code'] . ' & ' . $data['result_code']);
                return false;
            }
            $money = bcdiv($data['total_fee'], 100, 2);

            $obj = [];
            $obj["trade_no"] = $order_num;
            $obj["isBatch"] = $order['isBatch'];
            $obj["out_trade_no"] = $order_num;
            $obj["userId"] = (int)$order['userId'];
            $obj["payFrom"] = $order['payFrom'];
            $obj["total_fee"] = (float)$money;
            $m->success($obj);
            $this->log('微信回调通知success');
        } catch (\Exception $e) {
            Tools::addLog("wxpay_notify", "微信回调通知处理失败");
            $message = $e->getMessage();
            $return_code = $sign_passed ? 'SUCCESS' : 'FAIL';
            $response = "<xml><return_code><![CDATA[$return_code]]></return_code><return_msg><![CDATA[$message]]></return_msg></xml>";
        }
        echo  $response;
        exit();
    }

    // 生成回调验证签名+
    private function backSign($data, $channel = false)
    {
        $key = config('wxpay.key');

        if (isset($data['sign'])) {
            unset($data['sign']);
        }

        if (isset($data['channel'])) {
            unset($data['channel']);
        }

        $sign_1 = http_build_query($data);
        $sign_2 = $sign_1."&key=".$key;
        $sign_3 = strtoupper(MD5($sign_2));
        return $sign_3;
    }

    /**
     * @param $msg
     */
    public function log($msg)
    {
        Tools::addLog($this->logName, $msg);
    }
}
