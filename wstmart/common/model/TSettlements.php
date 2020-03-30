<?php
namespace wstmart\common\model;
use think\Db;
/**
 * 结算类
 */
class TSettlements extends ApiBaseModel{
	protected $pk = 'settlementId';
    protected $table = "t_settlements";
	/**
     * 即时计算
     */
    public function speedySettlement($order){
        $shops = model('common/shops')->get(['userId' => $order->inviter_uid]);
        if(empty($shops))return WSTReturn('结算失败，商家不存在');

        // 奖励金额
        $settlementMoney = $order["reward_amount"];
        $backMoney = $settlementMoney;

        $settlementMoney = WSTBCMoney($settlementMoney, 0);
        $backMoney = WSTBCMoney($backMoney, 0);
        $data = [];
        $data['settlementType'] = 1;
        $data['shopId'] = $shops->shopId;
        $data['settlementMoney'] = $settlementMoney;
        $data['commissionFee'] = 0;
        $data['backMoney'] = $backMoney;
        $data['settlementStatus'] = 1;
        $data['settlementTime'] = date('Y-m-d H:i:s');
        $data['createTime'] = date('Y-m-d H:i:s');
        $data['settlementNo'] = '';
        $settlementDb = new self();
        $settlementId = $settlementDb->insertGetId($data);
        if ($settlementId > 0) {
            $settlementNo = $settlementId.(fmod($settlementId,7));
            $settlementDb->where('settlementId',$settlementId)->update(['settlementNo'=>$settlementNo]);
            $order->settlementId = $settlementId;
            $order->save();
            //修改商家钱包
            $shops->shopMoney = $shops['shopMoney'] + $backMoney;
            $shops->save();
            //返还金额
            $lmarr = [];

            //在线支付的话，记录商家应得的钱的流水
            if ($backMoney >0 ){
                $lm = [];
                $lm['targetType'] = 1;
                $lm['targetId'] = $shops->shopId;
                $lm['dataId'] = $settlementId;
                $lm['dataSrc'] = 2;
                $lm['remark'] = '邀请结算订单申请【'.$settlementNo.'】返还金额¥'.$backMoney;
                $lm['moneyType'] = 1;
                $lm['money'] =$backMoney;
                $lm['payType'] = 0;
                $lm['createTime'] = date('Y-m-d H:i:s');
                $lmarr[] = $lm;
            }
            model('common/LogMoneys')->saveAll($lmarr);
        }
        return WSTReturn('结算失败');
    }
}
