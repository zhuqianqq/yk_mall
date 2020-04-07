<?php
/**
 * 邀请页邀请关系
 */
namespace wstmart\common\model;

class TInviteRelation extends ApiBaseModel
{
    public function order()
    {
        return $this->hasOne(TInviteOrder::class, 'id', 'invite_order_id');
    }

    protected $table = "mall_t_invite_relation";

    public static function getInviterByUid($user_id)
    {
        $relations = TInviteRelation::where(['user_id' => $user_id])->select();
        if (empty($relations[0]->id)) {
            return [];
        }
        $map = array_column($relations->toArray(),'inviter_uid', 'invite_order_id');
        $orderIds = array_keys($map);
        $state = TInviteOrder::STATE_PAYED;
        $order = TInviteOrder::where(" id in (" . implode(',', $orderIds) . ") and `state` = {$state} ")->field('id')->find();
        if (empty($order)) {
            return [];
        }
        $inviter_uid = isset($map[$order['id']]) ? $map[$order['id']] : 0;
        if (empty($inviter_uid)) {
            return [];
        }
        $user = Users::where(['userId' => $inviter_uid])->find();
        return !empty($user) ? $user : [];
    }

}
