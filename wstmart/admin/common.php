<?php
use think\Db;
/**
 */

/**
* 删除app端
*/
function delAppToken($userId){
	try{
		$prefix = config('database.prefix');
		
		// 删除app端的token
		$appTableName = $prefix."app_session";
		$rs = Db::query("SHOW TABLES like '{$appTableName}'");
		if(!empty($rs))Db::name('app_session')->where(['userId'=>$userId])->delete();

		// 删除小程序端的token
		$weAppTableName = $prefix."weapp_session";
		$rs = Db::query("SHOW TABLES like '{$weAppTableName}'");
		if(!empty($rs))Db::name('weapp_session')->where(['userId'=>$userId])->delete();
	}catch(\Exception $e){

	}
}


/**
 * 微信配置
 */
function WXAdmin(){
	$wechat = new \wechat\WSTWechat(WSTConf('CONF.wxAppId'),WSTConf('CONF.wxAppKey'));
	return $wechat;
}
