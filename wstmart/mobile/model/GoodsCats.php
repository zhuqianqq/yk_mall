<?php
namespace wstmart\mobile\model;
use think\Cache;
/**
 * 商品分类类
 */
class GoodsCats extends Base{
	/**
	 * 列表
	 */
	public function getGoodsCats(){
		$list = cache('WST_CACHE_GOODS_CAT_MOB');
		if(!$list){
			//查询一级分类
			$trs1s = $this->where(["dataFlag"=>1,"isShow"=>1,"parentId"=>0])->field('catId,parentId,simpleName')->order('catSort asc')->select();
			$trs1 = array();
			$list = array();
			$rs2 = array();
			$maprs = array();
			$ids = array();
			foreach ($trs1s as $key =>$v){
				$trs1[$key]['catId'] = $v['catId'];
				$trs1[$key]['parentId'] = $v['parentId'];
				$trs1[$key]['catName'] = $v['simpleName'];
				$ids[] = $v['catId'];
			}
			$ids[] = -1;
			//查询二级分类
			$trs2s = $this->where("dataFlag=1 and isShow=1 and parentId in(".implode(',',$ids).")")->field('catId,parentId,catName')->order('catSort asc')->select();
			$trs2 = array();
			$ids = array();
			foreach ($trs2s as $key =>$v){
				$trs2[$key]['catId'] = $v['catId'];
				$trs2[$key]['parentId'] = $v['parentId'];
				$trs2[$key]['catName'] = $v['catName'];
			}
			foreach ($trs2 as $v2){
				$ids[] = $v2['catId'];
				$maprs[$v2['parentId']][] = $v2;
			}
			$ids[] = -1;
			//查询三级分类
			$trs3s = $this->where("dataFlag=1 and isShow=1 and parentId in(".implode(',',$ids).")")->field('catId,parentId,catName,catImg')->order('catSort asc')->select();
			$trs3 = array();
			$ids = array();
			foreach ($trs3s as $key =>$v){
				$trs3[$key]['catId'] = $v['catId'];
				$trs3[$key]['parentId'] = $v['parentId'];
				$trs3[$key]['catName'] = $v['catName'];
				$trs3[$key]['catImg'] = strval(WSTImg($v['catImg'],3,'goodsLogo'));
			}
			foreach ($trs3 as $v2){
				$maprs[$v2['parentId']][] = $v2;
			}
			//倒序建立樹形
			foreach ($trs2 as $v2){
				$v2['childList'] = [];
				if(isset($maprs[$v2['catId']]))$v2['childList'] = $maprs[$v2['catId']];
				$rs2[] = $v2;
			}
			foreach ($trs1 as $v2){
				foreach ($rs2 as $vv2){
					if($vv2['parentId']==$v2['catId']){
						$v2['childList'][] = $vv2;
					}
				}
				$list[] = $v2;
			}
			cache('WST_CACHE_GOODS_CAT_MOB',$list,86400);
		}
		return $list;
	}
	    
}
