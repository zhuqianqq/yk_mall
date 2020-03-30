<?php
/**
 * api接口签名工具类
 */
namespace util;

use think\facade\Cache;


class AccessKeyHelper
{
    /**
     * @var string key前缀
     */
    public static $prefix = "acc_key:";

    /**
     * 获取缓存key
     * @param int $user_id
     * @param string $from
     * @return string
     */
    public static function getCacheKey($user_id, $from = "")
    {
        $key = self::$prefix . "{$user_id}";
        if (!empty($from)) {
            $key .= ":" . $from;
        }
        return $key;
    }

    /**
     * 从缓存中获取用户的access_key
     * @param int $user_id 用户id
     * @param string $from 客户端来源
     * @return string|null
     */
    public static function getAccessKey($user_id, $from = "")
    {
        $key = self::getCacheKey($user_id, $from);
        return Cache::get($key, null);
    }

    /**
     * 校验用户的access_key
     * @param int $user_id 用户id
     * @param string $access_key
     * @param string $from 客户端来源
     * @return bool
     */
    public static function validateAccessKey($user_id, $access_key, $from = "")
    {
        $acc_key = self::getAccessKey($user_id, $from);
        //file_put_contents('/data/webroot/acc.log', $user_id . ":" . "访问key:" . $access_key . "服务端key:" . $acc_key);
        //Tools::addLog("access_token", $user_id . ":" . "访问key:" . $access_key . "服务端key:" . $acc_key);
        // if (APP_ENV == "test") {
        //     Tools::addLog("access_token", $user_id . ":" . "访问key:" . $access_key . "服务端key:" . $acc_key);
        // }
        return $acc_key == $access_key;
    }

    /**
     * 为用户生成access_key
     * @param int $user_id 用户id
     * @param string $from 客户端来源
     */
    public static function generateAccessKey($user_id, $from = "")
    {
        $key = self::getCacheKey($user_id, $from);
        $acc_key = base64_encode(random_bytes(32));

        Cache::set($key, $acc_key, 7 * 24 * 3600); //缓存时间为7天

        return $acc_key;
    }

    /**
     * 从缓存中删除用户的access_key
     * @param int $user_id
     * @param string $from
     * @return bool
     */
    public static function forgetAccessKey($user_id, $from = "")
    {
        $key = self::getCacheKey($user_id, $from);
        return Cache::delete($key);
    }
}
