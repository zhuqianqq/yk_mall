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
    private $rsaPrivateKey = 'MIIEowIBAAKCAQEA2U7NnLgTJyBCauK69ywRHsPgRNEGwGYMlH6pf6CTZWGKZmN5rigoTZsq5TvKa/n+quGUgoTW2aA93AZs6JcqVNGPvqHsGlWlUukRAuozNlefFiazfbOEY/XqW0nEbpSwtUnwFgn6OWBWz9LkK1lRuwfxoxA6BVnTRAYqjbyR+aiyu6iUB/WqhD03BtnutY07a+x5UDBfEExM7iGQCQbbSRIq99mgXHhOrNx4LXODvo+wJvp6YxYZufdy5BGIbK01BxizWC437jCJsR/2KnKgRdWnP5T0SaFB/7h1xztjznUOqNz5EpQYRN6lJy+NUw8Bx8Hn7cQUaops1ugOmQsoSwIDAQABAoIBAQCxZGXuiEmCSBBP5rRPb9at8aJTKvtC9ktQsTE2sWOBgCWVvaCoLbnw2DklFjEBAfwFjM5J2Zz13JyCz2/6UuRIhyW7rbzqJn4l94JeicaylsaUM5WUiIYLf8UHLmm/B3xVEX1+0DhGvEFBt9txE6Hndu2nemiS06flwM7eE1Bqpu0tcfubQwZVNLc8FyMbWlCWUucejUN8m2VTrd2fSkZQYYuzWgbKwjTh9Zt3Ynm6mg3R4zaEVt7YQ0bAKbWU3K9sAz1GyE+Pd5K6WQJrHx5AhTec4TeB1X0n9bjXV1v35TRRHV4TOf2FPnll28tSgLsJLRTnZowjbhtw8GRgQ7WxAoGBAPqmKKy4xJU94J+6875VT85pR7qTPVXNRTV3DeajFUYDGALxI9R3CdX3mF8BIXNTWjL2jwZyUkNkWi8y0AwHOfyy5d7ZSEXFRvE/jELzCRWMP38rtam8LzbEaHkYN8FpL4njcjpMso4t4QbvYe10gifhAADuY83JZjCY1cM3jO7zAoGBAN3ybbhD97JoddhHkRzzYrnrNB63Vt+B4YsC75NXwF/pQZpTECYSuQyxQPPUeHRp1PTZ8BSgpAx30WEYT6ME6J8626wVyjblAsE6ThstfXhe8vQ9YlSQEpIlfijnHTqDi9l8VckQHgePSzhl9HbuWvHFJWT+H//WmFogG5S1vCdJAoGAX1C0gwo4Z3CCbPuRGT4j/SB4EHFRj8FhojVF6gD2ZAtlJClDMNabvOxM20P4znxR/rNYLGFo+wu8Z9pw10IgQQJe++Gw5CdjFPbPhd4aBEGzG88pZASWB8Ok3vfgrAIt+8esdpMYC41S12pM2RnHLRcZA0GkXmtKGwzjrTsAsxUCgYA1vQkL4IkMzXYbE9jy5Ys18NkBxupl25C8k81mI40Uq+mScCIs1cOwawqK/xrbNPS3BD25ANw3mJs9oyFFG18njpy5od0ARZrRJkjbE7yZdIPm1yYDy45zjFhrlungzREDa7Npp1leAzf3Q9hZI4UpnM5dEZq120OwLq3+57HS4QKBgEEJb9hzVd1Txch4DmNaip9vfbnuIYzEepq3DJeBBlk8hnw7S4I51gkKdDcmOTFLA/HMLbg7oBmphSAhsyuDbCMMiJOn6/Tcbj7FkZcC2FnAvazzu1nBfpq6D8EaTv16ZIG5H7NQvAnoANF15CtlxxCz+YXure/vNKG3NJFxWY8n';

    // 公钥值
    private $rsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAwQ4afZIrcQ1Z9pmQoIffBARydo6MVJOlAdqj/OAFEKg8zoFDI6T/358BYBMK50ewnZ0epBBOnaFjhGS3LZBGtkl6xhjAlFJE8qQSLH0dbzR2GpkJZlxk7b+8z81KhRAgXFfOQQY6Z+a+3kew6PaKqCbqkWtRv9EeNWSNyeJMpafGRRkbM0VbxZmI8zOpdpnantJvIZ2XE74V7aUZZZ+Ir3GvrqMX/GfpiLUuT2XtJIMUw1N2+3Hhku/dbeDS2ZWbAvtauw675wNGw6rA9bMe9KwsddK2uXF3fGhD6tmmrZiCd17x5FLDPy6fcKly/WA18QXvmHvn32tz8esArl2OcwIDAQABB';

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
