<?php
namespace wstmart\shop\model;
use think\Db;
/**
 * 结算类
 */
class Settlements extends Base{
  protected $pk = 'settlementId';
    /**
     * 获取已结算的结算单列表
     */
    public function pageQuery(){
        $shopId = (int)session('WST_USER.shopId');
        $where = [];
        $where[] = ['shopId',"=",$shopId];
        if(input('settlementNo')!='')$where[] = ['settlementNo','like','%'.input('settlementNo').'%'];
        if((int)input('isFinish')>=0)$where[] = ['settlementStatus',"=",(int)input('isFinish')];
        return Db::name('settlements')->alias('s')->where($where)->order('settlementId', 'desc')
            ->paginate(input('limit/d'))->toArray();
    }
    /**
     *  获取未结算订单列表
     */
    public function pageUnSettledQuery(){
        $where = [];
        if(input('orderNo')!='')$where[] = ['orderNo','like','%'.input('orderNo').'%'];
        $where[] = ['dataFlag',"=",1];
        $where[] = ['orderStatus',"=",2];
        $where[] = ['settlementId',"=",0];
        $where[] = ['shopId',"=",(int)session('WST_USER.shopId')];
        $page =  Db::name('orders')->where($where)->order('orderId', 'desc')
                   ->field('orderId,orderNo,createTime,payType,goodsMoney,deliverMoney,totalMoney,commissionFee,realTotalMoney,
                            refundedPayMoney,refundedScoreMoney,refundedGetScoreMoney')
                   ->paginate(input('limit/d'))->toArray();
        if(count($page['data'])){
            foreach ($page['data'] as $key => $v) {
                $page['data'][$key]['payTypeNames'] = WSTLangPayType($v['payType']);
            }
        }
        return $page;
    }

    /**
     *  获取未结算总额
     */
    public function pageUnSettledMoney($shopId){
        $where = [];
        $where[] = ['dataFlag',"=",1];
        $where[] = ['orderStatus',"=",2];
        $where[] = ['settlementId',"=",0];
        $where[] = ['shopId', "=", $shopId];
        $total =  Db::name('orders')->where($where)->order('orderId', 'desc')
            ->field('sum(realTotalMoney) totalMoney')->find();
        return $total;
    }
    

    /**
     * 获取已结算订单
     */
    public function pageSettledQuery(){
        $where = [];
        if(input('settlementNo')!='')$where[] = ['settlementNo','like','%'.input('settlementNo').'%'];
        if(input('orderNo')!='')$where[] = ['orderNo','like','%'.input('orderNo').'%'];
        if((int)input('isFinish')>=0)$where[] = ['settlementStatus',"=",(int)input('isFinish')];
        $where[] = ['dataFlag',"=",1];
        $where[] = ['orderStatus',"=",2];
        $where[] = ['o.shopId',"=",(int)session('WST_USER.shopId')];
        $page = Db::name('orders')->alias('o')->join('__SETTLEMENTS__ s','o.settlementId=s.settlementId')
        ->where($where)
        ->field('orderId,orderNo,payType,goodsMoney,deliverMoney,totalMoney,o.commissionFee,realTotalMoney,
                 s.settlementTime,s.settlementNo,refundedPayMoney,refundedScoreMoney,refundedGetScoreMoney')
        ->order('s.settlementTime desc')
        ->paginate(input('limit/d'))->toArray();
        if(count($page['data'])){
            foreach ($page['data'] as $key => $v) {
                $page['data'][$key]['commissionFee'] = abs($v['commissionFee']);
                $page['data'][$key]['payTypeNames'] = WSTLangPayType($v['payType']);
            }
        }
        return $page;
    }

    /**
     * 获取结算订单详情
     */
    public function getById(){
        $shopId = (int)session('WST_USER.shopId');
        $settlementId = (int)input('id');
        $object =  Db::name('settlements')->alias('st')->where(['settlementId'=>$settlementId,'st.shopId'=>$shopId])->join('__SHOPS__ s','s.shopId=st.shopId','left')->field('s.shopName,st.*')->find();
        if(!empty($object)){
            $object['list'] = Db::name('orders')->where(['settlementId'=>$settlementId])
                      ->field('orderId,orderNo,payType,goodsMoney,deliverMoney,realTotalMoney,totalMoney,scoreMoney,commissionFee,createTime')
                      ->order('payType desc,orderId desc')->select();
        }
        return $object;
    }
}