<?php
/**
 * 微信支付配置
 */
return [
    'app' => [
        'mch_id' => '1576847761',// 商户号
        'app_id' => 'wx0d0ccc82736facc9', // 应用appid
        'app_secret' => '6fb0f8f7ef976b5293d4276a61856808',    // 商户的私钥
        'key' => '8alzWMJQfrerXApL7NeTe2qLtQbq4iHX', // 秘钥
        'notify_url' => env("APP_URL").'/api/weixinpays/notify/channel/app',
    ],
    'xcx' => [
        'mch_id' => '1586334111',// 商户号
        'app_id' => 'wxa5b8d4801deb10c0', // 应用appid
        'key' => 'NbOrbbt8rM0Dclceygv0DHB409dyozkt', // 秘钥
        'notify_url' => env("APP_URL").'/api/weixinpays/notify/channel/xcx',
    ]
];
