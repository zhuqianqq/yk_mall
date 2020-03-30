<?php
/**
 * 直播用户邀请表
 */
namespace wstmart\common\model;

class TInviteOrder extends ApiBaseModel
{
    const STATE_UNPAY = 0; // 未支付
    const STATE_PAYED = 1; // 已支付

    public static $stateLabels = [
        self::STATE_UNPAY => '未支付',
        self::STATE_PAYED => '已支付',
    ];

    protected $table = "t_invite_order";

    public function getInviteOrderInfo($userId)
    {
        $inviteInfo =  $this->where("user_id = {$userId}")->field("inviter_uid, update_time")->find();
        if (empty($inviteInfo)) {
            return [];
        }
        return $inviteInfo;
    }
}
