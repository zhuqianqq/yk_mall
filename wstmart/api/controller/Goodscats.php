<?php
namespace wstmart\api\controller;
use wstmart\api\model\GoodsCats as M;
use wstmart\common\model\GoodsCats as CM;
use think\Db;
use wstmart\shop\model\Goods as GM;

/**
 * 商品分类控制器
 */
class GoodsCats extends Base{
	/**
     * 列表
     */
    public function index(){
    	$m = new M();
		$goodsCatList = $m->getGoodsCats();
    	$this->assign('list',$goodsCatList);
    	return $this->fetch('goods_category');
	}  

	 
	/**
     * 获取指定店铺经营的商城分类
     */
	public function catShopsApi($parentId = 0){
		$shopId = input('shopId/d',0);
   		$rs = Db::name('goods_cats')->alias('gc')
             ->join('cat_shops csp','gc.catId=csp.catId')
             ->where(['dataFlag'=>1, 'isShow' => 1,'gc.parentId'=>$parentId,'csp.shopId'=>$shopId])
             ->field("catName,simpleName,gc.catId,parentId")->order('catSort asc')->select();
		return WSTReturn("", 1,$rs);
	}
	
	/**
     * 获取指定店铺经营的商城分类下的子分类
     */
    public function listQueryApi(){
        $CM = new CM();
		$rs = $CM->listQuery(input('parentId/d',0));
        return WSTReturn("", 1,$rs);
	}
	

	/**
     * 获取指定店铺经营的商城分类下的子分类的 商品规格和商品属性
     */
    public function getSpecAttrs(){
		$GM = new GM();
        return $GM->getSpecAttrs();
    }
}
