<?php
namespace wstmart\store\model;
/**
 * 某些较杂业务处理类
 */
use think\Db;
class Systems extends Base{
	/**
	 * 获取定时任务
	 */
	public function getSysMessages(){
		$tasks = strtolower(input('post.tasks'));
		$tasks = explode(',',$tasks);
		$userId = (int)session('WST_STORE.userId');
		$shopId = (int)session('WST_STORE.shopId');
		$storeId = (int)session('WST_STORE.storeId');
		$data = [];
		if(in_array('message',$tasks)){
		    //获取用户未读消息
		    $data['message']['num'] = Db::name('messages')->where(['receiveUserId'=>$userId,'msgStatus'=>0,'dataFlag'=>1])->count();
		    $data['message']['sid'] = 383;
		}
		if($storeId>0){
			//待发货
			if(in_array('storeorder371',$tasks)){
			    $data['storeorder']['371'] = Db::name('orders')->where(['shopId'=>$shopId,'storeId'=>$storeId,'storeType'=>1,'orderStatus'=>0,'dataFlag'=>1])->count();
			}
			//待付款
			if(in_array('storeorder370',$tasks)){
			    $data['storeorder']['370'] = Db::name('orders')->where(['shopId'=>$shopId,'storeId'=>$storeId,'storeType'=>1,'orderStatus'=>-2,'dataFlag'=>1])->count();
			}
			
		}
		
		return $data;
	}
}
