<?php
namespace  wstmart\common\alipay;

class config
{
    //应用ID,您的APPID。
    public $appId = '2021001141623005';

    //商户私钥，您的原始格式RSA私钥
    public $merchant_private_key = 'MIIEowIBAAKCAQEA2U7NnLgTJyBCauK69ywRHsPgRNEGwGYMlH6pf6CTZWGKZmN5rigoTZsq5TvKa/n+quGUgoTW2aA93AZs6JcqVNGPvqHsGlWlUukRAuozNlefFiazfbOEY/XqW0nEbpSwtUnwFgn6OWBWz9LkK1lRuwfxoxA6BVnTRAYqjbyR+aiyu6iUB/WqhD03BtnutY07a+x5UDBfEExM7iGQCQbbSRIq99mgXHhOrNx4LXODvo+wJvp6YxYZufdy5BGIbK01BxizWC437jCJsR/2KnKgRdWnP5T0SaFB/7h1xztjznUOqNz5EpQYRN6lJy+NUw8Bx8Hn7cQUaops1ugOmQsoSwIDAQABAoIBAQCxZGXuiEmCSBBP5rRPb9at8aJTKvtC9ktQsTE2sWOBgCWVvaCoLbnw2DklFjEBAfwFjM5J2Zz13JyCz2/6UuRIhyW7rbzqJn4l94JeicaylsaUM5WUiIYLf8UHLmm/B3xVEX1+0DhGvEFBt9txE6Hndu2nemiS06flwM7eE1Bqpu0tcfubQwZVNLc8FyMbWlCWUucejUN8m2VTrd2fSkZQYYuzWgbKwjTh9Zt3Ynm6mg3R4zaEVt7YQ0bAKbWU3K9sAz1GyE+Pd5K6WQJrHx5AhTec4TeB1X0n9bjXV1v35TRRHV4TOf2FPnll28tSgLsJLRTnZowjbhtw8GRgQ7WxAoGBAPqmKKy4xJU94J+6875VT85pR7qTPVXNRTV3DeajFUYDGALxI9R3CdX3mF8BIXNTWjL2jwZyUkNkWi8y0AwHOfyy5d7ZSEXFRvE/jELzCRWMP38rtam8LzbEaHkYN8FpL4njcjpMso4t4QbvYe10gifhAADuY83JZjCY1cM3jO7zAoGBAN3ybbhD97JoddhHkRzzYrnrNB63Vt+B4YsC75NXwF/pQZpTECYSuQyxQPPUeHRp1PTZ8BSgpAx30WEYT6ME6J8626wVyjblAsE6ThstfXhe8vQ9YlSQEpIlfijnHTqDi9l8VckQHgePSzhl9HbuWvHFJWT+H//WmFogG5S1vCdJAoGAX1C0gwo4Z3CCbPuRGT4j/SB4EHFRj8FhojVF6gD2ZAtlJClDMNabvOxM20P4znxR/rNYLGFo+wu8Z9pw10IgQQJe++Gw5CdjFPbPhd4aBEGzG88pZASWB8Ok3vfgrAIt+8esdpMYC41S12pM2RnHLRcZA0GkXmtKGwzjrTsAsxUCgYA1vQkL4IkMzXYbE9jy5Ys18NkBxupl25C8k81mI40Uq+mScCIs1cOwawqK/xrbNPS3BD25ANw3mJs9oyFFG18njpy5od0ARZrRJkjbE7yZdIPm1yYDy45zjFhrlungzREDa7Npp1leAzf3Q9hZI4UpnM5dEZq120OwLq3+57HS4QKBgEEJb9hzVd1Txch4DmNaip9vfbnuIYzEepq3DJeBBlk8hnw7S4I51gkKdDcmOTFLA/HMLbg7oBmphSAhsyuDbCMMiJOn6/Tcbj7FkZcC2FnAvazzu1nBfpq6D8EaTv16ZIG5H7NQvAnoANF15CtlxxCz+YXure/vNKG3NJFxWY8n';

    //异步通知地址WEB
    public $notify_url = 'https://shop.wengyingwangluo.cn/api/pay/alipayNotify';

    //异步通知地址--扫码支付
    public $scan_notify_url = "";

    //同步跳转
    public $return_url = "";

    //编码格式
    public $charset = "UTF-8";

    //签名方式
    public $sign_type = "RSA2";

    //支付宝网关
    public $gatewayUrl = "https://openapi.alipay.com/gateway.do";

    //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
    public $alipay_public_key = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAwQ4afZIrcQ1Z9pmQoIffBARydo6MVJOlAdqj/OAFEKg8zoFDI6T/358BYBMK50ewnZ0epBBOnaFjhGS3LZBGtkl6xhjAlFJE8qQSLH0dbzR2GpkJZlxk7b+8z81KhRAgXFfOQQY6Z+a+3kew6PaKqCbqkWtRv9EeNWSNyeJMpafGRRkbM0VbxZmI8zOpdpnantJvIZ2XE74V7aUZZZ+Ir3GvrqMX/GfpiLUuT2XtJIMUw1N2+3Hhku/dbeDS2ZWbAvtauw675wNGw6rA9bMe9KwsddK2uXF3fGhD6tmmrZiCd17x5FLDPy6fcKly/WA18QXvmHvn32tz8esArl2OcwIDAQAB";


    public function __construct()
    {
        $config['appId'] = $this->appId;
        $config['merchant_private_key'] = $this->merchant_private_key;
        $config['notify_url'] = $this->notify_url;
        $config['scan_notify_url'] = $this->scan_notify_url;
        $config['return_url'] = $this->return_url;
        $config['charset'] = $this->charset;
        $config['sign_type'] = $this->sign_type;
        $config['gatewayUrl'] = $this->gatewayUrl;
        $config['alipay_public_key'] = $this->alipay_public_key;
    }





}