<?php
/**
 * 直播用户与商城用户映射关系表
 */
namespace wstmart\common\model;

use think\Db;
use think\Model;
use think\facade\Cache;

class TUserMap extends ApiBaseModel
{
    protected $table = "t_user_map";

    /**
     * redis key
     */
    const USER_MAP_PREFIX = "h:umap:";


    public static function getCacheKey($user_id)
    {
        return self::USER_MAP_PREFIX.$user_id;
    }

    /**
     * 获取商城用户id
     * @param int $user_id 主播用户id
     * @return int
     */
    public static function getMallUserId($user_id)
    {
        return $user_id;
    }

    /**
     * 获取商城用户店铺id
     * @param int $user_id 主播用户id
     * @return int
     */
    public static function getShopId($user_id)
    {
        $key = self::getCacheKey($user_id);
        $shop_id = Cache::hget($key,"shop_id");

        if($shop_id === null || $shop_id === false){
            $shop_id = self::where("user_id",$user_id)->value("shop_id");
            if($shop_id !== null){
                Cache::hset($key,"shop_id",$shop_id);
            }
        }

        return intval($shop_id);
    }

    /**
     * 获取商城用户id和shop_id
     * @param int $user_id 主播用户id
     * @return array
     */
    public static function getAllMapFields($user_id)
    {
        $key = self::getCacheKey($user_id);
        $data = Cache::hmget($key,["mall_user_id","shop_id"]);

        if(!$data || $data['mall_user_id'] === false || $data['shop_id'] === false){
            $data = self::where("user_id",$user_id)->find();
            if(!empty($data)){
                $data = $data->toArray();
                Cache::hmset($key,[
                    "mall_user_id" => $data['mall_user_id'],
                    "shop_id" => $data['shop_id'],
                ]);
            }
        }

        return $data;
    }

    /**
     * 更新shop_id映射
     * @param $user_id 主播用户id
     * @param $shop_id 店铺id
     */
    public static function updateShopId($user_id,$shop_id)
    {
        $ret = self::where("user_id",$user_id)->update([
            "shop_id" => $shop_id,
        ]);

        if($ret){
            $key = self::getCacheKey($user_id);
            Cache::hset($key,"shop_id",$shop_id);
        }

        return $ret;
    }
}
