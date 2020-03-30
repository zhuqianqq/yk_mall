<?php
namespace wstmart\common\model;
use think\Db;
/**
 * 自提点
 */
class Stores extends Base{
    
    protected $pk = 'storeId';

    public function checkSupportStores($userId){
      $addressId = input("addressId");
      $address = Db::name("user_address")->where(["userId"=>$userId,"addressId"=>$addressId])->field("areaId")->find();
      $areaId = (int)$address["areaId"];
      $list = Db::name("carts c")->join("goods g","c.goodsId=g.goodsId")
                ->where(["userId"=>$userId,"isCheck"=>1])
                ->field("g.shopId")
                ->group("g.shopId")
                ->select();
      $shopIds = [];
      foreach ($list as $k => $v) {
        $shopIds[] = $v["shopId"];
      }
      $where = [];
      $where[] = ["areaId","=",$areaId];
      $where[] = ["shopId","in",$shopIds];
      $where[] = ["dataFlag","=",1];
      $where[] = ["storeStatus","=",1];
      $rs = Db::name("stores")->where($where)->field("shopId")->group("shopId")->select();
      $storeMap = [];
      foreach ($rs as $k => $v) {
        $storeMap[$v["shopId"]] = 1;
      }
      return $storeMap;
    }

    /**
    * 获取列表
    */
    public function shopStores($userId){
      $addressId = input("addressId");
      $address = Db::name("user_address")->where(["userId"=>$userId,"addressId"=>$addressId])->field("areaId")->find();
     
      $rs = [];
      if(!empty($address)){
        $where = [];
        $shopId = (int)input("shopId");
        $areaId = (int)$address['areaId'];
        $where[] = ["areaId","=",$areaId];
        $where[] = ["shopId","=",$shopId];
        $where[] = ["dataFlag","=",1];
        $where[] = ["storeStatus","=",1];
        $rs = Db::name("stores")->where($where)->field("storeId,shopId,areaIdPath,storeName,storeTel,storeAddress")->limit(100)->select();
      }
      return $rs;
    }

    /**
    * 获取列表
    */
    public function listQuery($userId){
      $where = [];
      $shopId = (int)input("shopId");
      $areaId = (int)input("areaId");
      $where[] = ["areaId","=",$areaId];
      $where[] = ["shopId","=",$shopId];
      $where[] = ["dataFlag","=",1];
      $where[] = ["storeStatus","=",1];
      $rs = Db::name("stores")->where($where)->field("storeId,shopId,areaIdPath,storeName,storeTel,storeAddress")->limit(100)->select();
      return $rs;
    }
    
}
