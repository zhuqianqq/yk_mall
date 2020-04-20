<?php
namespace wstmart\common\pay;

use util\Tools;

class WeixinPay
{
    const API_URL = 'https://api.mch.weixin.qq.com/pay/unifiedorder';

    // 退款地址
    const REFUND_URL = 'https://api.mch.weixin.qq.com/secapi/pay/refund';

    // Native
    const PEM_CERT = '/data/cert/wx/apiclient_cert.pem';
    const PEM_KEY = '/data/cert/wx/apiclient_key.pem';

    // JSAPI
    const PEM_JS_CERT = '/data/cert/wx/apiclient_jsapi_cert.pem';
    const PEM_JS_KEY = '/data/cert/wx/apiclient_jsapi_key.pem';

    private $appId;
    private $mchId;
    private $key;
    private $notifyUrl;
    private $deviceInfo;
    private $certPath;
    private $keyPath;

    public function __construct($appId, $mchId, $key, $notifyUrl = '', $deviceInfo = 'WEB')
    {
        $this->appId = $appId;
        $this->mchId = $mchId;
        $this->key = $key;
        $this->notifyUrl = $notifyUrl;
        $this->deviceInfo = $deviceInfo;
    }

    public function createNonceString()
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for($i = 0; $i < 32; $i ++) {
            $str .= substr ( $chars, mt_rand ( 0, strlen ( $chars ) - 1 ), 1 );
        }
        return $str;
    }

    public function sign(array $params)
    {
        ksort($params);

        $string = "";
        foreach ($params as $key => $value) {
            if ($value != '' && !is_array($value) && $key != 'sign') {
                $string .= "$key=$value&";
            }
        }
        $string = $string . 'key=' . $this->key;

        return strtoupper(md5($string));
    }

    /**
     * 微信支付统一预下单
     * @param $data
     * @param string $tradeType
     * @param null $ip
     * @param null $openId
     * @return array
     * @throws \Exception
     */
    public function prepay($record, $tradeType = 'APP', $ip = null, $openId = null)
    {
        $ip = $ip ?: Tools::getClientIp();
        $tradeType = strtoupper($tradeType);

        $params = [
            'appid' => $this->appId,
            'mch_id' => $this->mchId,
            'device_info' => $this->deviceInfo,
            'body' => '映购-购买商品',
            'trade_type' => $tradeType,
            'spbill_create_ip' => $ip,
            'total_fee' => intval(bcmul($record['money'], 100)),
            'out_trade_no' => $record['merOrderId'],
            'nonce_str' => $this->createNonceString(),
            'notify_url' => $this->notifyUrl,
        ];

        if ($tradeType == 'JSAPI') {
            $params['openid'] = $openId;
        }
        $params['sign'] = $this->sign($params);

        $xml = '<xml>';
        foreach ($params as $key => $value) {
            $xml .= "<$key>$value</$key>";
        }
        $xml .= '</xml>';

        $responseXml = Tools::my_curl(static::API_URL, 'post', $xml);

        // 处理数据
        $result = $this->xmlToArray($responseXml);

        if (empty($result)) {
            throw new \Exception('请求网关超时', 1001);
        }

        if ($result['return_code'] != 'SUCCESS') {
            throw new \Exception('支付下单失败-预支付错误: ' . $result['return_msg'], 1002);
        }

        if ($result['result_code'] != 'SUCCESS') {
            throw new \Exception('支付下单失败-微信平台错误: ' . $result['err_code_des'], 1003);
        }

        if ($result['sign'] != $this->sign($result)) {
            throw new \Exception('支付下单失败-响应签名错误', 1004);
        }

        return $result;
    }

    /**
     * 生成微信小程序支付js参数
     *
     * @see https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1455784134
     *
     * @param RechargeRecord $record
     * @param $openId
     * @param $ip
     * @return array
     * @throws AppException
     */
    public function getXcxJsApiParams(RechargeRecord $record, $openId, $ip)
    {
        $wxOrder = $this->prepay($record, 'JSAPI', $ip, $openId);

        //生成页面调用参数
        $params = [
            'appId' => $this->appId,
            'timeStamp' => time() . "",
            'nonceStr' => $this->createNonceString(),
            'package' => "prepay_id=" . $wxOrder['prepay_id'],
            'signType' => 'MD5',
        ];

        $params["paySign"] = $this->sign($params);

        // 支付签名时间戳，注意微信jssdk中的所有使用timestamp字段均为小写。但最新版的支付后台生成签名使用的timeStamp字段名需大写其中的S字符
        $params['timestamp'] = $params['timeStamp'];
        unset($params['timeStamp']);

        return $params;
    }

    private function xmlToArray($xml)
    {
        $xmlObj = simplexml_load_string($xml);
        $data = [];
        foreach ($xmlObj as  $k => $v) {
            if (is_object($v)) {
                $data[$k] = $v->__toString();
            } else {
                $data[$k] = $v;
            }
        }

        return $data;
    }

    /**
     * 双向认证
     */
    private function refundRequest($data = null)
    {
        if (empty($this->certPath) || empty($this->keyPath)) {
            die('Without cert');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::REFUND_URL);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch,CURLOPT_SSLCERT, $this->certPath);
        curl_setopt($ch,CURLOPT_SSLKEY, $this->keyPath);
        $result = curl_exec($ch);

        if (false === $result) {
            die(curl_error($ch));
        }

        curl_close($ch);
        return $result;
    }
}
