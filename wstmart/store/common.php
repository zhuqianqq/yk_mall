<?php
use think\Db;
use think\Session;
/**
 */
/**
 * 获取指定父级的商家店铺分类
 */
function WSTStoreCats($parentId){
	$shopId = (int)session('WST_STORE.shopId');
	$dbo = Db::table('__SHOP_CATS__')->where(['dataFlag'=>1, 'isShow' => 1,'parentId'=>$parentId,'shopId'=>$shopId]);
	return $dbo->field("catName,catId")->order('catSort asc')->select();
}

/**
 * 判断门店访问权限
 */
function WSTStoreGrant($url){
    $SHOP = session('WST_STORE');
    if($SHOP['userType']!=2)return false;
    if($SHOP['roleId']==0)return true;
    $privilegeUrl = $SHOP['privilegeUrls'];
    $hasPrivilege = false;
    if($privilegeUrl){
    	$url = strtolower($url);
    	$privilegeUrl = json_decode($privilegeUrl);
    	foreach ($privilegeUrl as $key => $rv) {
    		foreach ($rv as $rkey => $vv) {
    		    if(in_array($url,$vv->urls))$hasPrivilege = true;
    	    }
    	}
    }
    return $hasPrivilege;
}
