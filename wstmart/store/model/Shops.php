<?php
namespace wstmart\store\model;
use wstmart\common\model\Shops as CShops;
use wstmart\store\validate\Shops as VShop;
use think\Db;
use think\Loader;
/**
 * 门店类
 */
class Shops extends CShops{
    
    /**
    * 获取店铺公告
    */
    public function getNotice(){
        $shopId = (int)session('WST_STORE.shopId');
        return model('shops')->where(['shopId'=>$shopId])->value('shopNotice');
    }
    
    /**
     * 获取卖家中心信息
     */
    public function getShopSummary(){
        $shopId = session('WST_STORE.shopId');
        $storeId = session('WST_STORE.storeId');
        $userId = session('WST_STORE.userId');

    	$shop = $this->alias('s')
        ->join("stores st","st.shopId=s.shopId")
        ->join('__SHOP_SCORES__ cs','cs.shopId = s.shopId','left')
        ->where(['s.shopId'=>$shopId,'s.dataFlag'=>1])
    	->field('s.shopMoney,s.noSettledOrderFee,s.paymentMoney,s.shopId,shopImg,shopName,shopAddress,shopQQ,shopTel,serviceStartTime,serviceEndTime,cs.*,st.storeName,st.storeAddress,st.storeImg')
    	->find();
    	//评分
    	$scores['totalScore'] = WSTScore($shop['totalScore'],$shop['totalUsers']);
    	$scores['goodsScore'] = WSTScore($shop['goodsScore'],$shop['goodsUsers']);
    	$scores['serviceScore'] = WSTScore($shop['serviceScore'],$shop['serviceUsers']);
    	$scores['timeScore'] = WSTScore($shop['timeScore'],$shop['timeUsers']);
    	WSTUnset($shop, 'totalUsers,goodsUsers,serviceUsers,timeUsers');
    	$shop['scores'] = $scores;
    	//认证
    	$accreds = $this->shopAccreds($shopId);
    	$shop['accreds'] = $accreds;
    	
       
        
        $stat = array();
        $date = date("Y-m-d");
        
        /**********今日动态**********/
        //待查看消息数
        $stat['messageCnt'] = Db::name('messages')->where(['receiveUserId'=>$userId,'msgStatus'=>0,'dataFlag'=>1])->count();
        //今日销售金额
        $stat['saleMoney'] = Db::name('orders')->where([['orderStatus','egt',0],['shopId','=',$shopId],['storeId','=',$storeId],['storeType','=',1],['dataFlag','=',1]])->whereTime('createTime', 'between', [$date.' 00:00:00', $date.' 23:59:59'])->sum("goodsMoney");
        //今日订单数
        $stat['orderCnt'] = Db::name('orders')->where([['orderStatus','egt',0],['shopId','=',$shopId],['storeId','=',$storeId],['storeType','=',1],['dataFlag','=',1]])->whereTime('createTime', 'between', [$date.' 00:00:00', $date.' 23:59:59'])->count();
        //待发货订单
        $stat['waitDeliveryCnt'] = Db::name('orders')->where(['shopId'=>$shopId,'storeId'=>$storeId,'storeType'=>1,'orderStatus'=>0,'dataFlag'=>1])->count();
        //待收货订单
        $stat['waitReceiveCnt'] = Db::name('orders')->where(['shopId'=>$shopId,'storeId'=>$storeId,'storeType'=>1,'orderStatus'=>1,'dataFlag'=>1])->count();
        //取消/拒收
        $stat['cancel'] = Db::name('orders')->where([['orderStatus','in',[-1,-3]],['shopId','=',$shopId],['storeType','=',1],['storeId','=',$storeId],['dataFlag','=',1]])->count();
    

        /**********商品信息**********/
        
        //上架商品
        $stat['onSaleCnt'] = Db::name('goods')->where(['shopId'=>$shopId,'dataFlag'=>1,'goodsStatus'=>1,'isSale'=>1])->cache('onSaleCnt'.$shopId,600)->count();
       
        /**********订单信息**********/
        //待付款订单
        $stat['orderNeedpayCnt'] = Db::name('orders')->where(['shopId'=>$shopId,'storeId'=>$storeId,'storeType'=>1,'orderStatus'=>-2,'dataFlag'=>1])->count();
        
        // 近30天销售排行
        $start = date('Y-m-d H:i:s',strtotime("-30 day"));
        $end = date('Y-m-d H:i:s');
        $prefix = config('database.prefix');
        $stat['goodsTop'] = $rs = Db::table($prefix.'order_goods')
                                    ->alias([$prefix.'order_goods'=>'og',$prefix.'orders'=>'o',$prefix.'goods'=>'g'])
                                    ->join($prefix.'orders','og.orderId=o.orderId')
                                    ->join($prefix.'goods','og.goodsId=g.goodsId')
                                    ->order('goodsNum desc')
                                    ->whereTime('o.createTime','between',[$start,$end])
                                    ->where(['o.shopId'=>$shopId,'o.storeId'=>$storeId,'o.storeType'=>1])
                                    ->where('(payType=0 or (payType=1 and isPay=1)) and o.dataFlag=1')
                                    ->group('og.goodsId')
                                    ->field('og.goodsId,g.goodsName,goodsSn,sum(og.goodsNum) goodsNum,g.goodsImg')
                                    ->limit(10)->select();
    	return ['shop'=>$shop,'stat'=>$stat];
    }    
    
    /**
     * 获取店铺指定字段
     */
    public function getFieldsById($shopId,$fields){
        return $this->where(['shopId'=>$shopId,'dataFlag'=>1])->field($fields)->find();
    }

    
}
