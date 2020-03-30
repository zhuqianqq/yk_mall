<?php

namespace wstmart\common\model;
/**
 * 快递业务处理类
 */

use think\Db;

class Express extends Base
{
    protected $pk = 'expressId';

    /**
     * 获取快递列表
     */
    public function listQuery()
    {
        return $this->where('dataFlag', 1)->select();
    }

    public function shopExpressList($sId = 0)
    {
        $shopId = $sId == 0 ? (int)session('WST_USER.shopId') : $sId;
        $where = [];
        $where[] = ["shopId", "=", $shopId];
        $where[] = ["isEnable", "=", 1];
        $where[] = ["se.dataFlag", "=", 1];
        $where[] = ["e.dataFlag", "=", 1];

        $rs = Db::name("shop_express se")
            ->join("express e", "se.expressId=e.expressId", "inner")
            ->field("se.id,e.expressName")
            ->where($where)
            ->select();

        return $rs;
    }
}
