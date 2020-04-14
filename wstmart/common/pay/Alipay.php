<?php
namespace wstmart\common\pay;

use wstmart\common\alipay\AlipayTradeRefundContentBuilder;
use wstmart\common\alipay\AlipayTradeService;
use wstmart\common\alipay\config;

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
    private $appId = '2021001141623005';

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
    private $rsaPrivateKey = 'MIIEvwIBADANBgkqhkiG9w0BAQEFAASCBKkwggSlAgEAAoIBAQCTDpMV/7tkJPB3w4JNSRhmmTrNeOVmgfeMeFu6gbv9gh3txWlNDmcxj5DvSprMUOuEPkbNFW2f01GdticLULq7eJUCnrHYElai/5E58EuurmvwocTqJDK5mGEXWZZmmDBxMmXP6wdabh3/gf94t1Gz/+a7UeE19JVis3yyCZelaRy4YhOr1WrENxuROT6MapP9Ds3QWnNdIWUPYdErMSYXUOTNaePvYNxs4MLoMF/N0xcDuX5/zwvZu0Rai6xWVOkrw68jjonhTepVqUqhTKbf621IqTCYx5rW7MOhKyeRtDYeLC0XMNt3ycB3Tk8E6172Tz9Auu2OBK3bYldaMkqFAgMBAAECggEAb9LWYBUdvvIj9T4zGGfr6SC9yT8UWdWckzF2tyUt+YD3FzZVc2XvbI16LawyeAlUfjQJDKwttyou1tmLaRxTUnlH/j0EiYSwYrQqD7+9HTC/HbU1ksJB3EWWFvZl2tABjiI/r1JOPiKcJw4IYRgtRc3i+zAxLCE3c11/BbcrnHheiTIjyl2MPsOqyaJQ5/d053yMZRCNpcqCT+IdU6J3C2WUkVm0LHywkKDtXhzTRVnpCFsIzhzR445X9795xG98GWXEBkK5AYvDoOuKzByTm5rW3raAkCiVR2cw+WwpsEVkLVcB9Gv9X+PMipMxBym+5tdFJFvybcPIUEVW3FsihQKBgQDfH0yThHwNQJqRvqFWibEng99LSplrS/+w/g1zDEpqTzzpa0fw3OcA6ylsvkh5R8dWnf10YllvYDS2gmuMAR2yc/xajKXwwNHP/XAuzkNrj67j5tu+Bh6PMKjqj17YglFS/Z9vuCgPip2sss4EfEGFQq9dL2GK0fGj13O+c+oLnwKBgQCouekok4wF1HJu3hQ+UfxRWb6/zZwnobh7kHjZZgijmPVuxRg3qObuGdBKuofWZygPL8U/zkUk4nEHDidXUGqd0S56busw4aAFAzIEGTsH3v11I724z+rwqPWiyhHzLaht0OKNDlVIHja5UcDWx/T/n/BZL3DjaWI+0VxKgt83WwKBgQC8R8YvyaGA8V491JaC1whmJvLbryTNlUE8EvY4ekulcB4ffscjatWIQekf+WZg6YA+CG4jswZzZMS2qlGkwCBWQWfW5U72XU/v6paq+KWN856KdHpD3RgWjuJLpRZNL5L+rETJWqZ7juZFIFGXGV+U21PuF5iBM25satgiA+ChUwKBgQCWdHF9RVTvPeptAot6pxEgWa7Gykoc7RDc3o9lDJ9XguYYyJg3yd1jJJGgkYTfo00NfcOeigkOQv4XFH/wVD0+TXHsq0v+YrOWxKqtDPKeqjHJav2a94zA3WlsqFQpTOWMR2A9+DIEBS03Q7d5zwShjbV9UdiQcUCyU27A4sK5UQKBgQCp3uTFM7Ump1ZyQfxIBtPyiavVGiqissZWEk1EnNTcG1FJs94kIiRpFpY1wr4sfLRtYE5dkD1dbQVEMvsj17U+bHiqB6aQ/4V+o4CKXv0ZTvaj27s+NEICv2kdopjjO+pXWIrFp6oiH+oTlZs3nqr34j1NDCAb+yF7SupYRErwqw==';

    // 公钥值
    private $rsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA0jtt5apUV7SoTZ+ftIG5Vu/1b95+rUDp2Ynuj0VbalwyqBuS0z6zehmFe7dw5oIsA3wa+unGAGMzWMgS3Cl3HG+c/N5Vx6tLN17m8LZgT+PYD86aH0Xlk5ZFeHgEuPPuRWh4+S5gnrHhy1PsJoSZ0BETx4+a6z3uOecEmrqOe99irCmMOuEg8uNStpZFKpTKLE9pL6ygCimtbTPXrqyMqFQgzRnemlchjqbPYpAm3tZdDmAIx1XXeaMzmvVbi1zHdWaN49WSfWXDci+D92mMjZm7LwJBDJk3lm91Pl8eAVsAkoBVpoad/nJZWgNFVJxetaQ9wC1Yho1feb9mACT8cQIDAQAB';

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
     * @param UserRefund $order
     * @return string
     * @throws \Exception
     */
    public function refund(UserRefund $order)
    {

        $out_trade_no = $order->trade_no;

        //退款金额，必填
        $refund_amount = $order->money;

        //退款说明
        $refund_reason = '';

        //退款单号
        $out_request_no = $order->refund_no;

        $RequestBuilder = new AlipayTradeRefundContentBuilder();
        $RequestBuilder->setOutTradeNo($out_trade_no);
        $RequestBuilder->setRefundAmount($refund_amount);
        $RequestBuilder->setRefundReason($refund_reason);
        $RequestBuilder->setOutRequestNo($out_request_no);

        $config = $this->getAlipayConfig();

        $Response = new AlipayTradeService($config);

        $result = $Response->Refund($RequestBuilder);

        if (!$result) {
            throw new \Exception('请求失败', Errors::RECHARGE_RETURN_ERROR);
        }


        if ($result->code != '10000' ) {
            throw new \Exception('交易失败: code:' . $result->code.'msg:'.$result->sub_msg, Errors::RECHARGE_RETURN_ERROR);
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
