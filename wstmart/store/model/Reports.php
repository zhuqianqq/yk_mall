<?php
namespace wstmart\store\model;
use wstmart\common\model\Reports as CReports;
use think\Db;
use Env;
/**
 * 报表类
 */
class Reports extends Base{

    /**
     * 获取商品销售排行
     */
    public function getTopSaleGoods(){
        $startDate=input('startDate');
        $endDate=input('endDate');
        if(empty($startDate)&&empty($endDate)){
            $start=date('Y-m-d 00:00:00',strtotime("-1 months"));
            $end=date('Y-m-d 23:59:59');
        }else{
            $start = date('Y-m-d 00:00:00',strtotime($startDate));
            $end = date('Y-m-d 23:59:59',strtotime($endDate));
        }
        $shopId = (int)session('WST_STORE.shopId');
        $storeId = (int)session('WST_STORE.storeId');
        $prefix = config('database.prefix');
        $rs = Db::table($prefix.'order_goods')->alias([$prefix.'order_goods'=>'og',$prefix.'orders'=>'o',$prefix.'goods'=>'g'])
            ->join($prefix.'orders','og.orderId=o.orderId')
            ->join($prefix.'goods','og.goodsId=g.goodsId')
            ->order('goodsNum desc')
            ->whereTime('o.createTime','between',[$start,$end])
            ->where('(payType=0 or (payType=1 and isPay=1)) and o.dataFlag=1 and o.shopId='.$shopId.'and o.storeId='.$storeId)->group('og.goodsId')
            ->field('og.goodsId,g.goodsName,goodsSn,sum(og.goodsNum) goodsNum,g.goodsImg')
            ->paginate(input('limit/d'))->toArray();
        return $rs;
    }

    /**
     * 获取销售额统计
     * 【注意】商家电脑端统计报表及导出excel有引用
     */
    public function getStatSales(){
        $start = date('Y-m-d 00:00:00',strtotime(input('startDate')));
        $end = date('Y-m-d 23:59:59',strtotime(input('endDate')));
        $payType = (int)input('payType',-1);
        $shopId = (int)session('WST_STORE.shopId');
        $storeId = (int)session('WST_STORE.storeId');
        $rs = Db::field('left(createTime,10) createTime,sum(totalMoney) totalMoney,count(orderId) orderNum')->name('orders')->whereTime('createTime','between',[$start,$end])
                ->where('shopId',$shopId)
                ->where('storeId',$storeId)
                ->where('(payType=0 or (payType=1 and isPay=1)) and dataFlag=1 '.(in_array($payType,[0,1])?" and payType=".$payType:''))
                ->order('createTime asc')
                ->group('left(createTime,10)')->select();
        $rdata = [];
        if(count($rs)>0){
            $days = [];
            $tmp = [];
            foreach($rs as $key => $v){
                $days[] = $v['createTime'];
                $rdata['dayVals'][] = $v['totalMoney'];
                $rdata['list'][] = ['day'=>$v['createTime'],'val'=>$v['totalMoney'],'num'=>$v['orderNum']];
            }
            $rdata['days'] = $days;
        }
        return WSTReturn('',1,$rdata);
    }


}