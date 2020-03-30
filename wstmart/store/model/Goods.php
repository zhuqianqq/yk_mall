<?php
namespace wstmart\store\model;
use wstmart\common\model\Goods as CGoods;
use think\Db;
/**
 * 商品类
 */
class Goods extends CGoods{
     /**
      *  上架商品列表
      */
	public function saleByPage(){
		$shopId = (int)session('WST_STORE.shopId');
		$where = [];
		$where[] = ['shopId',"=",$shopId];
		$where[] = ['goodsStatus',"=",1];
		$where[] = ['dataFlag',"=",1];
		$where[] = ['isSale',"=",1];
		$goodsType = input('goodsType');
		if($goodsType!='')$where[] = ['goodsType',"=",(int)$goodsType];
		$c1Id = (int)input('cat1');
		$c2Id = (int)input('cat2');
		$goodsName = input('goodsName');
		if($goodsName != ''){
			$where[] = ['goodsName|goodsSn','like',"%$goodsName%"];
		}
		if($c2Id!=0 && $c1Id!=0){
			$where[] = ['shopCatId2',"=",$c2Id];
		}else if($c1Id!=0){
			$where[] = ['shopCatId1',"=",$c1Id];
		}
		$rs = $this->where($where)
			->field('goodsId,goodsName,goodsImg,goodsType,goodsSn,isSale,isBest,isHot,isNew,isRecom,goodsStock,saleNum,shopPrice,isSpec')
			->order('saleTime', 'desc')
			->paginate(input('limit/d'))->toArray();
		foreach ($rs['data'] as $key => $v){
			$rs['data'][$key]['verfiycode'] = WSTShopEncrypt($shopId);
		}
		return $rs;
	}
	
}
