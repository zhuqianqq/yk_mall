<?php
use think\Db;
use think\Session;
/**
 */
/**
* 根据商品id,返回是否已关注商品
*/
function WSTCheckFavorite($goodsId,$type=0){
	$userId = (int)session('WST_USER.userId');
	if($userId>0){
		return model('common/favorites')->checkFavorite($goodsId,$type);
	}
	return false;
}
