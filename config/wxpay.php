<?php
/**
 * 微信支付配置
 */
return [
    'mch_id' => '1576847761',// 商户号
    'app_id' => 'wx0d0ccc82736facc9', // 应用appid
    'app_secret' => '6fb0f8f7ef976b5293d4276a61856808',    // 商户的私钥
    'key' => '8alzWMJQfrerXApL7NeTe2qLtQbq4iHX', // 秘钥
    'notify_url' => env("APP_URL").'/api/weixinpays/notify',
];
