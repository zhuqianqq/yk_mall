<?php
namespace wstmart\common\model;
use wstmart\common\validate\UserAddress as Validate;
use think\Db;

class TShopUser extends Base
{
    protected $table = "mall_shop_users";

    /**
     * 获取商城用户id
     * @param int $user_id 主播用户id
     * @return int
     */
    public static function getShopIdwithUserId($mall_user_id)
    {
        $rs = self::where(["userId" => $mall_user_id])->find();
        if ($rs) {
            return $rs->shopId;
        }
        return 0;
    }
}
