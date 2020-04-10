<?php
namespace wstmart\api\controller;
use wstmart\api\model\Index as M;
use wstmart\admin\model\Ads;
use wstmart\api\model\Shops;
use wstmart\api\model\Goods;
use think\facade\Cache;


/**
 * 默认控制器
 */
class Index extends Base{
	/**
     * 首页
     */
    public function index(){
    	$m = new M();
    	hook('mobileControllerIndexIndex',['getParams'=>input()]);
    	$news = $m->getSysMsg('msg');
    	$this->assign('news',$news);
    	$ads['count'] =  count(model("common/Tags")->listAds("mo-ads-index",99,86400));
    	$ads['width'] = 'width:'.$ads['count'].'00%';
    	$this->assign("ads", $ads);
        $gm = model("common/GoodsCats");
        $goodsCat = $gm->listQuery(0);
        $goodsCat = $goodsCat->toArray();
        array_unshift($goodsCat,['catId'=>0,'parentId'=>0,'catName'=>'热销商品','simpleName'=>'热销商品']);
        $this->assign('goodsCat',$goodsCat);
    	return $this->fetch('index');
    }

    /**
     * APP首页接口
     */

    public function api_index(){
        $Ads = new Ads();
        $Shops = new Shops();
        $Goods = new Goods();

        $ADS_CACHE_KEY = config('cachekeys.API_INDEX_ADS');
        $SHOPS_CACHE_KEY = config('cachekeys.API_INDEX_SHOPS');
        $HOTGOODS_CACHE_KEY = config('cachekeys.API_INDEX_HOTGOODS');

        //获取首页广告位数据
        $ads_info = Cache::get($ADS_CACHE_KEY,'');
        if (!$ads_info) {
            $ads_info = $Ads->api_index_ads(3)??[];
            Cache::set($ADS_CACHE_KEY,$ads_info);
        }
        //获取首页店铺数据
        $shops_info = Cache::get($SHOPS_CACHE_KEY,'');
        if (!$shops_info) {
            $shops_info = $Shops->api_index_shops()??[];
            Cache::set($SHOPS_CACHE_KEY,$shops_info);
        }
        //获取首页热卖商品数据
        $goods_info = Cache::get($HOTGOODS_CACHE_KEY,'');
        if (!$goods_info) {
            $goods_info = $Goods->api_index_hotgoods()??[];
            Cache::set($HOTGOODS_CACHE_KEY,$goods_info);
        }

        $return_data = [
            'ads_info'=>$ads_info,
            'shops_info'=>$shops_info,
            'goods_info'=>$goods_info
        ];

        return $this->outJson(0, "success", $return_data);
    }
    /**
     * 首页楼层商品列表
     */
    public function pageQuery(){
        $m = model("common/Goods");
        $rs = $m->homeCatPageQuery(3);
        return $rs;
    }

    /**
     * 转换url
     */
    public function transfor(){
        $data = input('param.');
        $url = $data['url'];
        unset($data['url']);
        echo Url($url,$data);
    }
    /**
     * 跳去登录之前的地址
     */
    public function sessionAddress(){
    	session('WST_MO_WlADDRESS',input('url'));
    	return WSTReturn("", 1);
    }


     /**
     * 清理首页缓存数据
     */
    public function delCache(){
        Cache::rm(config('cachekeys.API_INDEX_ADS')); 
        Cache::rm(config('cachekeys.API_INDEX_SHOPS')); 
        Cache::rm(config('cachekeys.API_INDEX_HOTGOODS')); 
    	return WSTReturn("", 1);
    }
}
