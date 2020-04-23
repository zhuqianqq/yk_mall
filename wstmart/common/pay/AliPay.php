<?php
namespace wstmart\common\pay;

use wstmart\common\alipay\AlipayTradeRefundContentBuilder;
use wstmart\common\alipay\AlipayTradeService;
use wstmart\common\alipay\config;
use wstmart\common\model\OrderRefunds;

class AliPay
{
    // 业务参数 交易金额
    public $tradeMoney;

    // 业务参数 交易订单号
    public $tradeNo;

    // 业务参数 交易标题
    public $tradeSubject = '映购';

    // 业务参数 交易有效时间
    public $tradeTimeOut = '30m';

    // 应用ID
    private $appId = '2018102461776835';

    // 返回数据格式
    private $format = 'json';

    // 签名类型
    private $signType = 'RSA2';

    // 请求参数
    private $queryData = [];

    // 商户号
    private $¡partner = '2088731762861139';

    // 网关
    private $gatewayUrl = 'https://openapi.alipay.com/gateway.do';

    // 支付方式
    public $payMethod = 'alipay.trade.app.pay';

    // api版本
    private $apiVersion = '1.0';

    // 表单提交字符集编码
    private $postCharset = 'UTF-8';

    private $fileCharset = 'UTF-8';

    private $alipaySdkVersion = 'alipay-sdk-php-20161101';

    private $notifyUrl = 'https://shop.wengyingwangluo.cn/api/pay/alipayNotify';

    // 私钥值
    private $rsaPrivateKey = 'MIIEowIBAAKCAQEAs3tMBlTsbJ/f2beNeJxKVpZMT802k8t50aWei7cFczDTqtBWo8WnsWcdjidSY9edjPftdgU1sWYWc2Zxk1u151jS19kpQVp44a2UNQ5GV3+HFwo49+3pd4gfaE3DzbrYkuhosR5HaN+fUvo4hR334Fc8hXZRdbWh9kH29WhvLWq3gDLlTr8zlA3GFwzpEOyxBfFkI3PAryhmw+z/plso7M3uI4xc1M8RcJvtF+RHoDe4BjVELMfpyn5LWqdaMstV4Iy+2SBJpFFdiSTGRF7F3a6zreWMEsMdi64ZZ/jUWkqi1V/40SexoZ2BedsEWazNoxWJwhUWg1R+fwUnGGuynwIDAQABAoIBAEbApSCd27GaeJkP9bIFEnz9tMmEoS4z+Sq++jgjhv940Qg2JuFaqpeRiaIfOChpuA75MV/j/92V2+XUDZPEhHnVlxBF6DB+JMb6MUGVBf+6IxvfCMQbwnw/afopJbX1ISkQeyzPPeFGvjzsrNk1DiEe4pnOIaCgYF9+c4cciy9AiGE8K/YKWmqC7VkgtT/n/0vaKxX3k3TocWKJmVT0FrF6xqL37DFY8Vpks0KZ46+Vzj2bGDiGHBVHs1uiNE2egFnUzL8SqmSBawtnNuTwNyrJb1k5hbiScznFpd+zTQpBc+vNMiuntkf5LUf4pVf+L7bC7IKEHKjeIejEa4r+pwECgYEA2LSx8n+fwysmHaOXIrDzdpd/1udotHYZHy4fc7jR6Fv9k4OFV6ccgrVICz1NZ3sd5i0YZE0/MHwXb9eTYn7Kv7SvrLsROpvutB6Tqs8+GfiO/a6Rq15Ek+wOy16oGwCQUTca3VBeACFiBkLUNRVg204w5jZLaWpug7SkKLt/QB8CgYEA1AavVzSRhG/yZBF1iIlSTvlu22fVpMwmg2+ajlSOZgQmgw530C6qz8gANC9hJ+Q9yAYviPHpfVAl3h/sLvUqgxVPIMOlCY0mCGf1S2pvR2CxinhoT4vvVYygvsIWYdaYvD8zlCCVCZDOPdY+6VdOjjsko338/krIUvjZWIBbPYECgYBJ7RKLbFg/BzhAgi8ryXu0qCgXUugYL/WP+ncGTjVldARET2ispziFqnwCQY5nT8u6WwXoKaX1z09bewovXpuh3GzVmxdcjBdZYNj1Oy+vQmjdR7Ev6b+xSqUdYZQtafrRid/jQehfWQQMqL2lwj5BciIiVsFRG9rJmVUrBBN79wKBgC74BCO3W30RJ4sPaxACC5+/FdW43NUS/H0lXgGlrZ3f0YA61kPh8qjwz0rALC+gGieTZzhZDrZZ6OmZ/MIyQWakc9D4RjklcYVvMiGwxFH1k57vKXxlrXEwI/PeHwMxfMhG3/Ayf5GM6IM+UNV0J8zOQUGOiVrzygjHY659ULkBAoGBALpRe0ewQDKWP329BTAGnJJR3NPn8o1YyzPngXxTahPFLvmK587huosu32FiJurYbrrltBNT7L58q8oS1Ulkf6rWhgTdig2Ms+0YelImAi1MppDH9YUFw2Kfu2wsOuj79PFqXnQkTaUqhHYVJBjpaPKuq+wPAruz7fC7jk5BQ4Ta';

    // 公钥值
    private $rsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAghUpw9j1H+/OmGqPepD6UaZ5BhfZg0shwiwVfL119oJXmC+iC827QY2XkQVqVZ29zuwd8VqgLXAhT5P/TsgQSuKBMIkCJyRHKP+bEXCPW+g0+D88aLK1Wo3IsS6xJsJo1tZUYqSs0PIAdScXxUpC8YZ+e6aCGtSG8w3iKcpWee41WA1P0TPZ8S70roDYel3qqktcqffX+GR7Er0oJsBrQXgr1cULsWBTHEdp4fRaaDzYp+3qJKHQjOJ6P9KIVzj7+IkJCLFdTLINggAIgrVsirJI8muCKmNyDDJwxlYZi7rERKJpdC4UDUwTHiNQWOkcQhyOA5I/ESFizmLN/MlafQIDAQAB';

    // 交易成功状态
    const SUCCESS_CODE = [
        'TRADE_SUCCESS',
        'TRADE_FINISHED',
    ];

    /**
     * 生成用于调用收银台SDK的字符串
     */
    public function sdkExecute($params)
    {
        if (!is_array($params)) {
            throw new \Exception('非法请求');
        }

        if (!isset($params['tradeMoney']) || !isset($params['tradeNo'])) {
            throw new \Exception('缺少参数');
        }

        if (empty($params['tradeNo'])) {
            throw new \Exception('缺少交易订单');
        }

        $this->tradeNo = $params['tradeNo'];

        if (empty($params['tradeMoney'])) {
            throw new \Exception('缺少交易金额');
        }

        $this->tradeMoney = $params['tradeMoney'];

        $this->queryData['app_id'] = $this->appId;
        $this->queryData['format'] = $this->format;
        $this->queryData['method'] = $this->payMethod;
        $this->queryData['charset'] = $this->postCharset;
        $this->queryData['version'] = $this->apiVersion;
        $this->queryData['sign_type'] = $this->signType;
        $this->queryData['timestamp'] = date('Y-m-d H:i:s');
        $this->queryData['notify_url'] = $this->notifyUrl;
        $this->queryData['alipay_sdk'] = $this->alipaySdkVersion;
        $this->queryData['biz_content'] = "{"
            . "\"subject\": \"{$this->tradeSubject}\","
            . "\"timeout_express\": \"{$this->tradeTimeOut}\","
            . "\"out_trade_no\": \"{$this->tradeNo}\","
            . "\"total_amount\": \"{$this->tradeMoney}\","
            . "\"product_code\":\"QUICK_MSECURITY_PAY\""
            . "}";

        ksort($this->queryData);
        $this->queryData['sign'] = $this->signData($this->queryData);
        foreach ($params as &$value) {
            $value = $this->character($value);
        }

        return http_build_query($this->queryData);
    }

    /**
     * 验证签名
     */
    public function verifyData($data)
    {
        $sign = $data['sign'];

        $data['sign'] = null;
        $data['sign_type'] = null;

        return $this->verify($this->getSignContent($data), $sign);
    }

    /**
     * 申请退款
     * @param OrderRefunds $order
     * @return string
     * @throws \Exception
     */
    public function refund(OrderRefunds $order)
    {

        $out_trade_no = $order->trade_no;

        //退款金额，必填
        $refund_amount = $order->backMoney;

        //退款说明
        $refund_reason = '';

        //退款单号
        $out_request_no = $order->refundTradeNo;

        $RequestBuilder = new AlipayTradeRefundContentBuilder();
        $RequestBuilder->setOutTradeNo($out_trade_no);
        $RequestBuilder->setRefundAmount($refund_amount);
        $RequestBuilder->setRefundReason($refund_reason);
        $RequestBuilder->setOutRequestNo($out_request_no);

        $config = $this->getAlipayConfig();

        $Response = new AlipayTradeService($config);

        $result = $Response->Refund($RequestBuilder);

        if (!$result) {
            throw new \Exception('请求失败', 7002);
        }


        if ($result->code != '10000' ) {
            throw new \Exception('交易失败: code:' . $result->code.'msg:'.$result->sub_msg, 7002);
        }

        return json_encode($result);
    }

    /**
     * 获取支付宝WEB配置
     * @return array
     */
    private function getAlipayConfig()
    {
        $configObj = new config();
        $config = array (
            //应用ID,您的APPID。
            'app_id' => $configObj->appId,

            //商户私钥，您的原始格式RSA私钥
            'merchant_private_key' => $configObj->merchant_private_key,

            //异步通知地址
            'notify_url' => $configObj->notify_url,

            //同步跳转
            'return_url' => $configObj->return_url,

            //编码格式
            'charset' => $configObj->charset,

            //签名方式
            'sign_type' => $configObj->sign_type,

            //支付宝网关
            'gatewayUrl' => $configObj->gatewayUrl,

            //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
            'alipay_public_key' => $configObj->alipay_public_key,


        );
        return $config;
    }
    /**
     * 获取签名
     */
    protected function signData($data)
    {
        return $this->sign($this->getSignContent($data));
    }

    /**
     * 获取签名内容
     */
    protected function getSignContent($data)
    {
        $i = 0;
        ksort($data);
        $stringToBeSigned = '';
        foreach ($data as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {

                // 转换成目标字符集
                $v = $this->character($v);

                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }

                $i++;
            }
        }
        return $stringToBeSigned;
    }

    /**
     * 开始签名
     */
    protected function sign($data)
    {
        $result = "-----BEGIN RSA PRIVATE KEY-----\n" . wordwrap($this->rsaPrivateKey, 64, "\n", true) . "\n-----END RSA PRIVATE KEY-----";

        if ('RSA2' == $this->signType) {
            openssl_sign($data, $sign, $result, OPENSSL_ALGO_SHA256);
        } else {
            openssl_sign($data, $sign, $result);
        }

        return base64_encode($sign);
    }

    /**
     * 签名验证
     */
    protected function verify($data, $sign)
    {
        $publicKey = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($this->rsaPublicKey, 64, "\n", true) . "\n-----END PUBLIC KEY-----";

        if ('RSA2' == $this->signType) {
            $result = (bool)openssl_verify($data, base64_decode($sign), $publicKey, OPENSSL_ALGO_SHA256);
        } else {
            $result = (bool)openssl_verify($data, base64_decode($sign), $publicKey);
        }

        return $result;
    }

    /**
     * 转换字符集编码
     */
    protected function character($data)
    {
        if (!empty($data)) {
            $fileType = $this->fileCharset;

            if (strcasecmp($fileType, $this->postCharset) != 0) {
                $data = mb_convert_encoding($data, $this->postCharset, $fileType);
            }
        }

        return $data;
    }

    /**
     * 校验 $value 是否非空
     */
    protected function checkEmpty($value)
    {
        if (!isset($value)) {
            return true;
        }

        if ($value === null) {
            return true;
        }

        if (trim($value) === '') {
            return true;
        }

        return false;
    }
}
