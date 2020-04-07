<?php
/**
 * 邀请页邀请订单
 */

namespace wstmart\common\model;

use util\Tools;

class TInviteOrder extends ApiBaseModel
{
    /**
     * 交易订单业务code
     */
    const TRADE_BUSI_CODE = "invite_order";

    const STATE_UNPAY = 0; // 未支付
    const STATE_PAYED = 1; // 已支付

    public static $stateLabels = [
        self::STATE_UNPAY => '未支付',
        self::STATE_PAYED => '已支付',
    ];

    protected $table = "mall_t_invite_order";

    public static function createOrderNo($source)
    {
        return strtoupper($source) . date('YmdHis') . self::randomkeys(8);
    }

    public static function randomkeys($length)
    {
        $pattern = '1234567890abcdefghijklmnopqrstuvwxyz';
        $key = '';
        for($i=0;$i<$length;$i++)
        {
            $key .= $pattern{mt_rand(0,35)};    //生成php随机数
        }
        return $key;
    }

    /**
     * 主播付款成功回调处理
     * @param $order_no
     * @param int $state
     * @return bool
     */
    public static function finishInviteOrder($order_no, $state = self::STATE_PAYED)
    {
        // 支付失败不做操作
        if ($state != self::STATE_PAYED) {
            Tools::addLog("invite", 'finishInviteOrder state not payed :  params: ' . json_encode(['order_no' => $order_no,'state' => $state]));
            return true;
        }
        $now_time = date('Y-m-d H:i:s');
        $order = self::where(['order_no' => $order_no])->find();
        if (empty($order->id)) {
            Tools::addLog("invite", 'finishInviteOrder order not exist : params: ' . json_encode(['order_no' => $order_no,'state' => $state]));
            return true;
        }
        // 更新订单信息
        self::where(['order_no' => $order_no])->update(['state' => $state, 'update_time' => $now_time]);
        // 更新用户信息
        $relation = TInviteRelation::where(['invite_order_id' => $order->id])->field('user_id')->find();
        if (empty($relation['user_id'])) {
            Tools::addLog("invite", 'finishInviteOrder relation not exist : params: ' . json_encode(['order_no' => $order_no,'order_id' => $order->id]));
            return true;
        }
        TMember::openBroadCast($relation['user_id'],1);
        return true;
    }
}
