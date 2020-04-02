<?php
namespace util;

class Validate
{
    const CONNECTION_TIMEOUT = 1000;
    const TIMEOUT = 5000;

    const METHOD_GET = "GET";
    const METHOD_POST = "POST";
    const IDAUTH_APPID = 'FqPallhv';
    const IDAUTH_APPKEY  = 'yfVleyXj';

    /**
     * 实名认证
     * @param $truename 真实姓名
     * @param $idcard 身份证
     * @return bool
     */
    public static  function validateIdcard($truename, $idcard)
    {
        if (empty($truename) ||  empty($idcard)) {
            return false;
        }
        $appid = self::IDAUTH_APPID;
        $appkey = self::IDAUTH_APPKEY;
        $requestUrl = "https://api.253.com/open/idcard/id-card-auth/vs";
        $encryptStr = "appId" . $appid . "idNum" . $idcard . "name" . $truename;
        $sign = static::getSignature($encryptStr, $appkey);
        $requestData = [
            'appId' => $appid,
            'name' => $truename,
            'idNum' => $idcard,
            'sign' => $sign,
        ];
        $res = static::_http($requestUrl, $requestData, self::METHOD_POST);
        if (empty($res)) {
            return false;
        }
        if ($res->code != '200000') {
            return false;
        }
        // 继续判断data的值
        $data = $res->data;
        if ($data->result != '01') {
            return false;
        }
        return true;
    }

    /**
     * [HmacSHA1Encrypt HMAC-SHA1加密算法]
     * @param String $encryptText [加密文本源串]
     * @param String $encryptKey  [加密密钥]
     * @return 签名串值
     *
     */
    public  static function getSignature($str, $key) {
        if (function_exists('hash_hmac')) {
            $signature = base64_encode(hash_hmac("sha1", $str, $key, true));
        } else {
            $blocksize = 64;
            $hashfunc = 'sha1';
            if (strlen($key) > $blocksize) {
                $key = pack('H*', $hashfunc($key));
            }
            $key = str_pad($key, $blocksize, chr(0x00));
            $ipad = str_repeat(chr(0x36), $blocksize);
            $opad = str_repeat(chr(0x5c), $blocksize);
            $hmac = pack(
                'H*', $hashfunc(
                    ($key ^ $opad) . pack(
                        'H*', $hashfunc(
                            ($key ^ $ipad) . $str
                        )
                    )
                )
            );
            $signature = base64_encode($hmac);
        }
        return $signature;
    }


    //curl 请求
    public static function _http($url, array $params = [], $method = self::METHOD_GET, $is_json = false, $header = array())
    {
        if ($is_json) {
            array_push($header, "Content-Type:application/json");
            if (is_array($params)) {
                $params = json_encode($params);
            }
        }
        $ch = curl_init();

        if ($method == self::METHOD_POST) {
            curl_setopt($ch, CURLOPT_POST, 1);
            if ($params) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            }
        } else if ($method == self::METHOD_GET) {
            if ($params) {
                $url .= '?' . http_build_query($params);
            }
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header); // 设置头信息的地方
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);    // https
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, self::CONNECTION_TIMEOUT);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, self::TIMEOUT);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $ret = curl_exec($ch);

        if ($ret === false) {
            var_dump(curl_error($ch));
        }
        curl_close($ch);
        return json_decode($ret);
    }
}