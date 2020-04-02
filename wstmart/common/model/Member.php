<?php
/**
 * 会员表
 */

namespace wstmart\common\model;

use think\facade\Config;
use think\facade\Db;
use util\AccessKeyHelper;

class Member extends Base
{

    const DISPLAY_CODE_DIFF = 100000;

    const DEFAULT_AVATAR = 'https://img.ikstatic.cn/MTU4MzQ5MDczNTAwMCM0NjMjcG5n.png';

    protected $table = "mall_member";

    /**
     * 根据user_id
     * @param $user_id
     * @param $field
     */
    public static function getById($user_id, $field = "")
    {
        if (empty($field)) {
            $field = "user_id,phone,nick_name,sex,avatar,front_cover,openid,country,province,city,display_code,
                       is_broadcaster,audit_status,is_lock";
        }
        $data = self::where("user_id", $user_id)->field($field)->find();

        return $data ? $data->toArray() : null;
    }

    /**
     * 根据手机号
     * @param $phone
     * @param $field
     */
    public static function getByPhone($phone, $field = "")
    {
        if (empty($field)) {
            $field = "user_id,phone,nick_name,sex,avatar,front_cover,openid,country,province,city,display_code,
                       is_broadcaster,audit_status,is_lock";
        }
        $data = self::where("phone", $phone)->field($field)->find();

        return $data ? $data->toArray() : null;
    }


    /**
     * 根据unionid获取
     * @param string $openid
     * @param string $field
     * @return array|null
     */
    public static function getByUnionId($union_id, $field = "")
    {
        if (empty($field)) {
            $field = "id,user_id,nick_name,sex,avatar,front_cover,openid,unionid,country,province,city,display_code";
        }
        $data = self::where("unionid", $union_id)->field($field)->find();

        return $data ? $data->toArray() : [];
    }

    /**
     * 根据openid获取
     * @param string $openid
     * @param string $field
     * @return array|null
     */
    public static function getByOpenId($open_id, $field = "")
    {
        if (empty($field)) {
            $field = "id,user_id,nick_name,sex,avatar,front_cover,openid,unionid,country,province,city,display_code";
        }
        $data = self::where("openid", $open_id)->field($field)->find();

        return $data ? $data->toArray() : [];
    }

    /**
     * 生成昵称
     */
    public static function generateNick($display_code, $prefix = "映购")
    {
        return $prefix . $display_code;
    }


    /**
     * @param $user_id
     * @return int
     */
    public static function generateDisplayCode($user_id)
    {
        return self::DISPLAY_CODE_DIFF + intval($user_id);
    }

    /**
     * 根据display_code返回user_id
     * @param $display_code
     * @return int
     */
    public static function getUserIdByDisplayCode($display_code)
    {
        return intval($display_code) - self::DISPLAY_CODE_DIFF;
    }

    /**
     * 按手机号注册
     * @param $phone
     */
    public static function registerByPhone($phone)
    {
        $data = [
            'phone' => $phone,
            'last_login_time' => date("Y-m-d H:i:s"),
            'create_time' => date("Y-m-d H:i:s"),
            'avatar' => self::DEFAULT_AVATAR, //默认头像
        ];
        $user_id = self::insertGetId($data);

        if ($user_id) {
            self::updateOtherInfo($user_id);
        }

        return $user_id;
    }

    /**
     * 更新nick和display_code
     * @param $user_id
     */
    private static function updateOtherInfo($user_id)
    {
        $display_code = self::generateDisplayCode($user_id); //显示编码
        $nick_name = self::generateNick($display_code);
        $up_data = [
            "display_code" => $display_code,
            "nick_name" => $nick_name,
        ];
        self::where("id", $user_id)->update($up_data);
    }

    /**
     * 按union_id注册
     * @param $unionid
     * @return int|string
     */
    public static function registerByUnionId($unionid, $from = 0)
    {
        $data = [
            'unionid' => $unionid,
            'from' => (int)$from,
            'last_update_time' => date("Y-m-d H:i:s"),
            'create_time' => date("Y-m-d H:i:s"),
        ];
        $user_id = self::insertGetId($data);
        if ($user_id) {
            self::updateOtherInfo($user_id);
        }

        return $user_id;
    }

    /**
     * 按open_id注册
     * @param $unionid
     * @return int|string
     */
    public static function registerByOpenId($openid)
    {
        $data = [
            'openid' => $openid,
            'last_update_time' => date("Y-m-d H:i:s"),
            'create_time' => date("Y-m-d H:i:s"),
        ];

        $user_id = self::insertGetId($data);

        if ($user_id) {
            self::updateOtherInfo($user_id);
        }

        return $user_id;
    }

    /**
     * 禁播
     */
    public static function forbidMember($user_id, $room_id, $end_time, $reason, $oper_user = 'system')
    {
        TMember::where("user_id", $user_id)->update([
            "is_forbid" => 1,
            "forbid_reason" => $reason,
            "forbid_end_time" => date("Y-m-d H:i:s", $end_time),
        ]);
        $oper_log = new TRoomOperLog();
        $oper_log->save([
            "room_id" => $room_id,
            "user_id" => $user_id,
            "oper_user" => $oper_user,
            "oper" => 0, //0：禁播，1：解播
            "forbid_end_time" => date("Y-m-d H:i:s", $end_time),
            "reason" => $reason,
            "create_time" => date("Y-m-d H:i:s"),
        ]);
    }

    public static function unforbidMember($user_id, $reason, $oper_user = 'system')
    {
        TMember::where("user_id", $user_id)->update([
            "is_forbid" => 0,
            "forbid_reason" => '',
            "forbid_end_time" => null,
        ]);
        $oper_log = new TRoomOperLog();
        $oper_log->save([
            "room_id" => '',
            "user_id" => $user_id,
            "oper_user" => $oper_user,
            "oper" => 1, //0：禁播，1：解播
            "reason" => $reason,
            "create_time" => date("Y-m-d H:i:s"),
        ]);
    }

    /**
     * 设置其他信息
     * @param $data
     */
    public static function setOtherInfo(&$data, $need_old_key = 0)
    {
        if ($need_old_key == 1) {
            $data["access_key"] = AccessKeyHelper::getAccessKey($data["user_id"]); //生成access_key
            if (empty($data["access_key"])) {
                $data["access_key"] = AccessKeyHelper::generateAccessKey($data["user_id"]);
            }
        } else {
            $data["access_key"] = AccessKeyHelper::generateAccessKey($data["user_id"]); // 生成access_key
        }
    }

    /**
     * 开通主播&开通店铺
     * @param $user_id
     */
    public static function openBroadCast($user_id, $year = 1)
    {
        TMember::where(['user_id' => $user_id])->update([
            'is_broadcaster' => TMember::IS_BROADCASTER_YES,
            'expire_time' => date("Y-m-d H:i:s", strtotime("+{$year} years")), //过期时间
        ]);

        $user_info = TMember::getById($user_id);
        MallShop::openShop($user_id, $user_info);
    }
}
