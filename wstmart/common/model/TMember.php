<?php
/**
 * 直播用户表
 */
namespace wstmart\common\model;

class TMember extends ApiBaseModel
{
    protected $table = "t_member";

    public function getMemberInfo($userId)
    {
        $memberInfo =  $this->where("user_id = {$userId}")->field("nick_name, avatar")->find();
        if (empty($memberInfo)) {
            return [];
        }
        return $memberInfo;
    }
}
