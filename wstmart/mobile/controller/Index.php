<?php
namespace wstmart\mobile\controller;
use wstmart\mobile\model\Index as M;
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
}
