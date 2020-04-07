<?php
/**
 * 支付宝配置
 */
return [
    'partner' => '2088731762861139',// 合作者身份ID
    'seller_id' => '2549696164@qq.com',//卖家支付宝账号
    'app_id' => '2021001141623005', //应用appid
    'private_key_path' => 'alipay_key/rsa_private_key.pem',    // 商户的私钥
    'ali_public_key_path' => 'alipay_key/alipay_public_key.pem', // 支付宝公钥
    'sign_type' => 'RSA2',    // 签名方式
    'input_charset' => "UTF-8",    // 字符编码格式 目前支持 gbk 或 utf-8
    'notify_url' => env("APP_URL").'/pay/alipayNotify',
];
