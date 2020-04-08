<?php
namespace wstmart\home\model;
/**
 * 门店配置类
 */
use think\Db;
class ShopConfigs extends Base{
    /**
    * 店铺设置
    */
     public function getShopCfg($id){
        $rs = $this->where("shopId=".$id)->find();
        if($rs != ''){
            //图片
            $rs['shopAds'] = ($rs['shopAds']!='')?explode(',',$rs['shopAds']):null;
            //图片的广告地址
            $rs['shopAdsUrl'] = ($rs['shopAdsUrl']!='')?explode(',',$rs['shopAdsUrl']):null;
            return $rs;
        }
     }

     /**
      * 获取商城搜索关键字
      */
     public function searchShopkey($shopId){
     	$rs = $this->where('shopId='.$shopId)->field('configId,shopHotWords')->find();
     	$keys = [];
     	if($rs['shopHotWords']!='')$keys = explode(',',$rs['shopHotWords']);
     	return $keys;
     }
}
