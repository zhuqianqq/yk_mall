<?php

namespace wstmart\api\controller;

use think\facade\Cache;
use think\facade\Config;
use util\AccessKeyHelper;
use wstmart\common\model\TUserMap;

class Test extends Base
{
    //注册中件间
    protected $middleware = [
        //'AccessCheck' => ['only' => ['index']],
    ];

    public function index()
    {
        $ret = TUserMap::getMallUserId("1153");
        echo $ret.PHP_EOL;

        $ret = TUserMap::getShopId("1153");
        echo $ret;

        $ret = TUserMap::getAllMapFields("1153");

        print_r($ret);

//        echo "主播用户id:".$this->request->user_id.PHP_EOL;
//        echo "商城用户id:".$this->request->mall_user_id.PHP_EOL;
//
//        //return $this->outJson(0,"success");
//
//        $acc_key = base64_encode(random_bytes(32));
//
//        //Cache::set("acc_key:1159",$acc_key);
//
//        echo Cache::get("acc_key:1159");
    }
}
