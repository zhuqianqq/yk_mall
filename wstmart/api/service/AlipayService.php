<?php
/**
 * 支付宝支付服务类(新接口方式)
 * 参考代码：
 * https://github.com/dcloudio/H5P.Server/tree/master/payment/alipayrsa2
 */

namespace wstmart\api\service;

use AlipayTradeAppPayRequest;
use AlipayTradeWapPayRequest;
use AopClient;
use think\Db;
use think\facade\Config;
use util\Tools;
use wstmart\common\model\Orders;
use wstmart\common\model\TAliMobilePay;
use wstmart\common\model\TInviteOrder;
use wstmart\common\pay\AliPay;

require_once 'alipay2/aop/AopClient.php';


class AlipayService
{
    /**
     * @var string 日志名
     */
    protected $logName = "ali_pay";

    /**
     * @var array 支付配置
     */
    private $alipay_config;

    /**
     * @var string 异步回调通知地址
     */
    private $notify_url = '';

    public function __construct()
    {
        $this->alipay_config = Config::get('alipay');
        $this->notify_url = $this->alipay_config["notify_url"];
    }

    /**
     * @return AopClient
     */
    public function getAopClient()
    {
        $aop = new AopClient ();
        $aop->appId = $this->alipay_config["app_id"];
        $aop->signType = $this->alipay_config["sign_type"];
        $aop->rsaPrivateKey = $this->getRsaPrivateKey();
        $aop->alipayrsaPublicKey = $this->getAlipayPublicKey();

        return $aop;
    }

    /**
     * 生成APP支付付款请求参数 https://docs.open.alipay.com/204/106541
     * @param array $map 请求参数
     * @return bool
     */
    public function pay($map)
    {
        $user_id = $map['user_id'];
        $order_num = $map['order_num']; //用户业务订单号
        $amount = floatval($map['amount']);  //付款金额（元）
        $subject = $map['subject'] ?? ''; //商品描述字符串
        $trade_busi_code = $map["trade_busi_code"] ?? ''; //交易业务code

        //实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
        require_once 'alipay2/aop/request/AlipayTradeAppPayRequest.php';

        $request = new AlipayTradeAppPayRequest();
        $it_b_pay = "120m"; //120分钟

        // 生成alipay_mobile_pay订单
        $alipayMobilePay = TAliMobilePay::where('out_trade_no', $order_num)->find();
        if (empty($alipayMobilePay)) {
            $this->createPay($order_num,$subject,$amount,$trade_busi_code,$request->getApiMethodName(),$it_b_pay);
        }else{
            if ($alipayMobilePay["notify_trade_status"] == 'TRADE_SUCCESS') {
                return Tools::outJson(200,"业务订单号已经为支付成功状态,无须再支付");
            }

            $alipayMobilePay->subject = $subject;
            $alipayMobilePay->total_fee = $amount;
            $alipayMobilePay->service = $request->getApiMethodName();//wap支付
            $alipayMobilePay->it_b_pay = $it_b_pay;
            $alipayMobilePay->save();
        }

        $aop = $this->getAopClient();
        //SDK已经封装掉了公共参数，这里只需要传入业务参数
        $bizcontent = "{\"body\":\"" . $subject . "\","
            . "\"subject\": \"" . $subject . "\","
            . "\"out_trade_no\": \"" . $order_num . "\","
            . "\"timeout_express\": \"" . $it_b_pay . "\","
            . "\"total_amount\": \"" . $amount . "\","
            . "\"product_code\":\"QUICK_MSECURITY_PAY\""
            . "}";  //product_code销售产品码，商家和支付宝签约的产品码，为固定值QUICK_MSECURITY_PAY

        $request->setNotifyUrl($this->notify_url); // 异步通知地址
        $request->setBizContent($bizcontent);
        $sign_body = $aop->sdkExecute($request);

        $data = [
            "user_id" => $user_id,
            "order_num" => $order_num, //业务订单号
            "sign_body" => $sign_body,  //该参数会提交给hbuilder的plus5+支付接口
        ];

        return Tools::outJson(0,"success",$data);
    }

    /**
     * 生成支付宝移动支付跳转form表单的html（包含自动提交脚本）
     * https://docs.open.alipay.com/203/107090/
     * @param array $map
     * @return array
     */
    public function wapPay($map)
    {
        $user_id = $map['user_id'];
        $order_num = $map['order_num']; //订单编号
        $amount = $map['amount'];  //付款金额（元）
        $subject = $map['subject'] ?? ''; //订单标题
        $trade_busi_code = $map["trade_busi_code"] ?? ''; //交易业务code

        //实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
        require_once 'alipay2/aop/request/AlipayTradeWapPayRequest.php';
        $request = new AlipayTradeWapPayRequest();
        $it_b_pay = "120m"; //120分钟

        // 生成alipay_mobile_pay订单
        $alipayMobilePay = TAliMobilePay::where('out_trade_no', $order_num)->find();
        if (empty($alipayMobilePay)) {
            $this->createPay($order_num,$subject,$amount,$trade_busi_code,$request->getApiMethodName(),$it_b_pay);
        }else{
            if ($alipayMobilePay["notify_trade_status"] == 'TRADE_SUCCESS') {
                return Tools::outJson(200,"业务订单号已经为支付成功状态,无须再支付");
            }
            $alipayMobilePay->subject = $subject;
            $alipayMobilePay->total_fee = $amount;
            $alipayMobilePay->service = $request->getApiMethodName();//wap支付
            $alipayMobilePay->it_b_pay = $it_b_pay;
            $alipayMobilePay->save();
        }

        $aop = $this->getAopClient();
        //SDK已经封装掉了公共参数，这里只需要传入业务参数
        $bizcontent = "{\"body\":\"" . $subject . "\","
            . "\"subject\": \"" . $subject . "\","
            . "\"out_trade_no\": \"" . $order_num . "\","
            . "\"timeout_express\": \"" . $it_b_pay . "\","
            . "\"total_amount\": " . $amount . ","
            . "\"product_code\":\"QUICK_WAP_WAY\""
            . "}";  //product_code销售产品码，商家和支付宝签约的产品码，为固定值QUICK_WAP_WAY

        $request->setNotifyUrl($this->notify_url); //异步回调通知地址
        $request->setBizContent($bizcontent);

        if (isset($map["return_url"]) && !empty($map["return_url"])) {
            $request->setReturnUrl($map["return_url"]);  //支付成功回跳页面
        }

        $sign_body = $aop->pageExecute($request, "GET"); //获取get请求支付url

        $data = [
            "user_id" => $user_id,
            "order_num" => $order_num, //业务订单号
            "sign_body" => $sign_body,  //前台回跳支付页面地址
        ];

        return Tools::outJson(0,"success",$data);
    }

    /**
     * @param $order_num
     * @param $subject
     * @param $amount
     * @param $trade_busi_code
     * @param string $service
     * @param string $it_b_pay
     */
    public function createPay($order_num,$subject,$amount,$trade_busi_code = '',$service = '',$it_b_pay = "120m")
    {
        $model = new TAliMobilePay();
        $model->out_trade_no = $order_num;
        $model->app_id = $this->alipay_config['app_id'];
        $model->subject = $subject;
        $model->seller_id = $this->alipay_config['seller_id'];
        $model->total_fee = $amount;
        $model->service = $service;
        $model->notify_url = $this->notify_url;
        $model->pay_status = TAliMobilePay::PAY_STATUS_WAIT_PAY; //待支付状态
        $model->trade_busi_code = $trade_busi_code;
        $model->it_b_pay = $it_b_pay;
        $model->create_time = date("Y-m-d H:i:s");

        $model->save();
    }

    /**
     * @return string 读取rsa私钥文件内容,去头去尾去回车，一行字符串
     */
    public function getRsaPrivateKey()
    {
        $key_path = $this->alipay_config["private_key_path"];
        return file_get_contents($key_path);
    }

    /**
     * @return string 读取支付宝公钥文件内容,去头去尾去回车，一行字符串
     */
    public function getAlipayPublicKey()
    {
        $key_path = $this->alipay_config["ali_public_key_path"];
        return file_get_contents($key_path);
    }

    /**
     * 支付宝异步回调通知，支付宝是用POST方式发送通知信息，
     * 程序执行完后必须打印输出“success”（不包含引号）。如果商户反馈给支付宝的字符不是success这7个字符，
     * 支付宝服务器会不断重发通知，直到超过24小时22分钟。
     * 一般情况下，25小时以内完成8次通知（通知的间隔频率一般是：4m,10m,10m,1h,2h,6h,15h）；
     * https://docs.open.alipay.com/59/103666/
     * @param array $map 通知参数
     * @return bool 成功返回true/false
     */
    public function alipayNotify($map)
    {
        try {
            $order_num = $map['out_trade_no'] ?? ''; // 商户网站唯一订单号
            if (empty($order_num)) {
                $this->log('alipayNotify: 缺失out_trade_no参数');
                return false;
            }

            $m = new Orders();
            $order = $m->where(['orderunique' => $order_num, 'isPay' => Orders::IS_PAY_WAIT])->find();
            if (empty($order)) {
                $this->log('alipayNotify: 没有该订单');
                return false;
            }

            // 交易正常关闭,支付宝通常会在30分钟后关闭未支付的订单
            if (strtoupper($map['trade_status']) == 'TRADE_CLOSED') {
                // 若充值记录状态为等待支付则更新为已失败
                $m->failure($order_num);
                return 'success';
            }
            if (!in_array(strtoupper($map['trade_status']), AliPay::SUCCESS_CODE)) {
                // 如果状态不为成功
                $this->log('alipayNotify: 交易状态不为成功状态');
                return false;
            }
            $obj = [];
            $obj["trade_no"] = $order_num;
            $obj["isBatch"] = $order['isBatch'];
            $obj["out_trade_no"] = $order_num;
            $obj["userId"] = (int)$order['userId'];
            $obj["payFrom"] = $order['payFrom'];
            $obj["total_fee"] = (float)$map["total_amount"];
            $m->success($obj);
            $this->log('支付宝回调通知success');
            return true;
        } catch (\Exception $e) {
            $this->log('支付宝回调通知消息：系统故障:' . $e->getMessage() . PHP_EOL . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * 针对notify_url验证消息是否是支付宝发出的合法消息
     * https://docs.open.alipay.com/58/103597
     * @return 验证结果
     */
    public function verifyNotify()
    {
        if (empty($_POST)) {
            return false;
        } else {
            $aop = new AopClient();
            $aop->alipayrsaPublicKey = $this->getAlipayPublicKey();
            $flag = $aop->rsaCheckV1($_POST, NULL, $this->alipay_config["sign_type"]);
            return $flag;
        }
    }


    /**
     * @param $msg
     */
    public function log($msg)
    {
        Tools::addLog($this->logName, $msg);
    }
}