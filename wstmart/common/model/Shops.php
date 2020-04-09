<?php
namespace wstmart\common\model;
use wstmart\shop\model\ShopConfigs;
use think\Db;
use think\facade\Cache;
/**
 * 门店类
 */
class Shops extends Base{
	protected $pk = 'shopId';
    /**
     * 获取商家认证
     */
    public function shopAccreds($shopId){
        $accreds= Db::table("__SHOP_ACCREDS__")->alias('sa')
        ->join('__ACCREDS__ a','a.accredId=sa.accredId','left')
        ->field('a.accredName,a.accredImg')
        ->where(['sa.shopId'=> $shopId])
        ->select();
        return $accreds;
    }

    /**
     * 获取店铺评分
     */
    public function getShopScore($shopId){
        $shop = $this->alias('s')->join('__SHOP_SCORES__ cs','cs.shopId = s.shopId','left')
                    ->where(['s.shopId'=>$shopId,'s.shopStatus'=>1,'s.dataFlag'=>1])->field('s.shopAddress,s.shopKeeper,s.shopImg,s.shopQQ,s.shopId,s.shopName,s.shopTel,s.freight,s.freight,s.areaId,cs.*')->find();
        if(empty($shop))return [];
        $shop->toArray();
        $shop['totalScore'] = WSTScore($shop['totalScore']/3,$shop['totalUsers']);
        $shop['goodsScore'] = WSTScore($shop['goodsScore'],$shop['goodsUsers']);
        $shop['serviceScore'] = WSTScore($shop['serviceScore'],$shop['serviceUsers']);
        $shop['timeScore'] = WSTScore($shop['timeScore'],$shop['timeUsers']);
        WSTUnset($shop, 'totalUsers,goodsUsers,serviceUsers,timeUsers');
        return $shop;
    }
    /**
     * 获取店铺首页信息
     */
    public function getShopInfo($shopId,$uId = 0){
    	$rs = Db::name('shops')->alias('s')
        ->join('__SHOP_EXTRAS__ ser','ser.shopId=s.shopId','inner')
        ->where(['s.shopId'=>$shopId,'s.shopStatus'=>1,'s.dataFlag'=>1])
    	->field('s.shopNotice,s.shopId,s.shopImg,s.shopName,s.shopAddress,s.shopQQ,s.shopWangWang,s.shopTel,s.serviceStartTime,s.longitude,s.latitude,s.serviceEndTime,s.shopKeeper,mapLevel,s.areaId,s.isInvoice,s.freight,s.invoiceRemarks,ser.*')
        ->find();

    	if(empty($rs)){
    		//如果没有传id就获取自营店铺
    		$rs = Db::name('shops')->alias('s')
            ->join('__SHOP_EXTRAS__ ser','ser.shopId=s.shopId','inner')
            ->where(['s.shopStatus'=>1,'s.dataFlag'=>1,'s.isSelf'=>1])
    		->field('s.shopNotice,s.shopId,s.shopImg,s.shopName,s.shopAddress,s.shopQQ,s.shopWangWang,s.shopTel,s.serviceStartTime,s.longitude,s.latitude,s.serviceEndTime,s.shopKeeper,s.mapLevel,s.areaId,s.isInvoice,s.freight,s.invoiceRemarks,ser.*')
    		->find();
    		if(empty($rs))return [];
            $shopId = $rs['shopId'];
        }
        //仅仅是为了获取businessLicenceImg而写的，因为businessLicenceImg不排除被删除掉了
        WSTAllow($rs,'shopNotice,shopId,shopImg,shopName,shopAddress,shopQQ,shopWangWang,shopTel,serviceStartTime,longitude,latitude,serviceEndTime,shopKeeper,mapLevel,areaId,isInvoice,freight,invoiceRemarks,businessLicenceImg');
    	//评分
    	$score = $this->getShopScore($rs['shopId']);
    	$rs['scores'] = $score;
        //店铺地址
        $rs['areas'] = Db::name('areas')->alias('a')->join('__AREAS__ a1','a1.areaId=a.parentId','left')
        ->where([['a.areaId','=',$rs['areaId']]])->field('a.areaId,a.areaName areaName2,a1.areaName areaName1')->find();
    	//认证
    	$accreds = $this->shopAccreds($rs['shopId']);
    	$rs['accreds'] = $accreds;
    	//分类
    	$goodsCatMap = [];
    	$goodsCats = Db::name('cat_shops')->alias('cs')->join('__GOODS_CATS__ gc','cs.catId=gc.catId and gc.dataFlag=1','left')
    	->where(['shopId'=>$rs['shopId']])->field('cs.shopId,gc.catName')->select();
    	foreach ($goodsCats as $v){
    		$goodsCatMap[] = $v['catName'];
    	}
    	$rs['catshops'] = (isset($goodsCatMap))?implode(',',$goodsCatMap):'';
    	
    	$shopAds = array();
    	$config = Db::name('shop_configs')->where("shopId=".$rs['shopId'])->find();
    	//取出轮播广告
    	if($config["shopAds"]!=''){
    		$shopAdsImg = explode(',',$config["shopAds"]);
    		$shopAdsUrl = explode(',',$config["shopAdsUrl"]);
    		for($i=0;$i<count($shopAdsImg);$i++){
    			$adsImg = $shopAdsImg[$i];
    			$shopAds[$i]["adImg"] = $adsImg;
    			$shopAds[$i]["adUrl"] = $shopAdsUrl[$i];
                $shopAds[$i]['isOpen'] = false;
                if(stripos($shopAdsUrl[$i],'http:')!== false || stripos($shopAdsUrl[$i],'https:')!== false){
                    $shopAds[$i]['isOpen'] = true;
                }
    		}
            $rs['shopAds'] = $shopAds;
            unset($config['shopAds']);
        }
  
        $rs = array_merge($rs,$config);
        //热搜关键词
        $rs['shopHotWords'] = ($rs['shopHotWords']!='')?explode(',',$rs['shopHotWords']):[];
    	//关注
    	$f = model('common/Favorites');
    	$rs['isfollow'] = $f->checkFavorite($shopId,1,$uId);
        $followNum = $f->followNum($shopId,1);
        $rs['followNum'] = $followNum;
        //商铺商品数量
        $rs['goodsNum'] = Db::name('goods')
        ->where(['shopId'=>$shopId,'dataFlag'=>1,'isSale'=>1])
    	->count();
    	return $rs;
    }

    /**
     * 获取店铺信息
     */
    public function getFieldsById($shopId,$fields){
        return $this->where(['shopId'=>$shopId,'dataFlag'=>1])->field($fields)->find();
    }

    /*
     * 获取店铺主题(小程序端、app端)
     */
    public function getShopHomeTheme($shopId,$type=''){
        $rs = '';
        if($type=='weapp'){
            $rs = Db::name('shop_configs')->where(["shopId"=>$shopId])->value('weappShopHomeTheme');
        }else{
            $rs = Db::name('shop_configs')->where(["shopId"=>$shopId])->value('appShopHomeTheme');
        }
        return $rs;
    }

    /**
     * 清除店铺缓存
     */
    public function clearCache($shopId){
        Cache::clear('CACHETAG_'.$shopId);
    }
}
