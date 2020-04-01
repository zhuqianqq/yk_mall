<?php
namespace wstmart\api\controller;
use think\Db;
use think\Exception;
use util\AccessKeyHelper;
use util\SmsHelper;
use util\ValidateHelper;
use wstmart\common\model\Member;
use wstmart\common\model\Users;
/**
 * 登录控制器
 */
class Login extends Base{
    protected $middleware = [
        //'access_check' => ['only' => ['loginOut']],
    ];

    /**
     * 手机号登录&注册
     */
    public function loginByPhone()
    {
        $phone = $this->request->post("phone", '', "intval");
        $vcode = $this->request->post("vcode", '', "intval");
        $platform = $this->request->post("platform", '', "intval"); // 1苹果，2是安卓，3是小程序，4是web端。当前为0或为4不下发access_token

        if (ValidateHelper::isMobile($phone) == false || $vcode <= 0) {
            return $this->outJson(100, "参数错误");
        }

        if (SmsHelper::checkVcode($phone, $vcode, "login") == false) {
            return $this->outJson(100, "验证码无效");
        }

        try {
            Db::startTrans();
            $user = new Users();
            $data = $user->getUserByPhone($phone);

            if (empty($data)) {
                //没有数据，则进行注册
                $regData['phone'] = $phone;
                \wstmart\api\model\Users::register($regData); // 注册商城用户
                $data = $user->getUserByPhone($phone);
            }

            if ($platform > 0 && $platform < 4) {
                \wstmart\api\model\Users::setOtherInfo($data, 0);
            } else {
                \wstmart\api\model\Users::setOtherInfo($data, 1);
            }
            $data->access_key = $data['access_key'];
            $data->save();
            $data['mall_user_id'] = $data->userId;
            SmsHelper::clearCacheKey($phone, "login");
            Db::commit();
            return $this->outJson(0, "登录成功", $data);
        } catch (\Exception $ex) {
            Db::rollback();
            return $this->outJson(500, "接口异常:" . $ex->getMessage());
        }
    }

    /**
     * 小程序登录
     */
    public function loginByMinWechat()
    {
        Db::startTrans();
        try {
            $code = $this->request->post("code", '', "trim");
            $avatar = $this->request->post("avatar", '', "trim");
            $city = $this->request->post("city", '', "trim");
            $country = $this->request->post("country", '', "trim");
            $gender = $this->request->post("gender", 0, "intval");
            $nick_name = $this->request->post("nick_name", '', "trim");
            $province = $this->request->post("province", '', "trim");
            $iv = $this->request->post("iv", '', "trim");
            $encryptedData = $this->request->post("encryptedData", '', "trim");
            if (empty($iv) && empty($encryptedData)) {
                $loginInfo = WechatHelper::getOpenidByCode($code); //以code换取openid
                $openId = isset($loginInfo['openid']) ? $loginInfo['openid'] : '';
                $unionId = '';
            } else {
                $loginInfo = WechatHelper::getWechatLoginInfo($code, $iv, $encryptedData); //以code换取openid
                $loginInfo = json_decode($loginInfo, true);
                $unionId = isset($loginInfo['unionId']) ? $loginInfo['unionId'] : '';
                $openId = isset($loginInfo['openId']) ? $loginInfo['openId'] : '';
            }

            if (empty($loginInfo)) {
                return $this->outJson(200, "获取微信信息失败！");
            }
            if (empty($openId)) {
                return $this->outJson(200, "获取微信openId失败！");
            }
            if (!empty($unionId)) {
                $data = TMember::getByUnionId($unionId);
                if (empty($data)) {
                    // 如果unionID没有找到，则找openid
                    $data = TMember::getByOpenId($openId);
                }
            } else {
                $data = TMember::getByOpenId($openId);
            }

            if (empty($data)) {
                // 没有没有unionid存在，则新建
                if (!empty($unionId)) {
                    $user_id = TMember::registerByUnionId($unionId);
                    $data = TMember::getByUnionId($unionId);
                } else {
                    $user_id = TMember::registerByOpenId($openId);
                    $data = TMember::getByOpenId($openId);
                }

                if ($user_id <= 0) {
                    return $this->outJson(200, "注册失败");
                }

                $data["nick_name"] = empty($nick_name) ? $data['nick_name'] : $nick_name;
                $data["avatar"] = empty($avatar) ? $data['avatar'] : $avatar;
                $data["sex"] = $gender > 0 ? $gender : $data['sex'];
                $mall_user_id = MallUser::register($data); //注册商城用户
            } else {
                $mall_user_id = $data['user_id'];
            }

            if ($data["is_lock"] == 1) {
                return $this->outJson(200, "账号已被锁定");
            }

            TMember::where([
                "user_id" => $data["user_id"],
            ])->update([
                'nick_name' => empty($nick_name) ? $data['nick_name'] : $nick_name,
                'avatar' => empty($avatar) ? $data['avatar'] : $avatar,
                'city' => empty($city) ? $data['city'] : $city,
                'country' => empty($country) ? $data['country'] : $country,
                'sex' => $gender > 0 ? $gender : $data['sex'],
                'province' => empty($province) ? $data['province'] : $province,
                'openid' => empty($openId) ? $data['openid'] : $openId,
                'unionid' => empty($unionId) ? $data['unionid'] : $unionId,
                "last_login_time" => date("Y-m-d H:i:s"),
            ]);

            if (!empty($unionId)) {
                $data = TMember::getByUnionId($unionId);
            } else {
                $data = TMember::getByOpenId($openId);
            }

            $data['mall_user_id'] = $mall_user_id;
            TMember::setOtherInfo($data);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return $this->outJson(0, "登录失败", $e->getMessage() ?? '接口异常');
        }

        return $this->outJson(0, "登录成功", $data);
    }

    /**
     * APP微信登录
     * @return array
     */
    public function loginByWechat()
    {
        $avatar = $this->request->post("avatar", '', "trim");
        $city = $this->request->post("city", '', "trim");
        $country = $this->request->post("country", '', "trim");
        $gender = $this->request->post("gender", '', 'trim');
        $nick_name = $this->request->post("nick_name", '', 'trim');
        $province = $this->request->post("province", '', 'trim');
        $openid = $this->request->post("openid", '', "trim");
        $unionid = $this->request->post("unionid", '', "trim");

        Db::startTrans();
        try {
            $data = Member::getByUnionId($unionid);
            if (!$data) {
                $data['phone'] = $nick_name;
                $data['nick_name'] = $nick_name;
                $mall_user_id = \wstmart\api\model\Users::register($data); //注册商城用户
                if (!$mall_user_id) {
                    throw new Exception('注册失败');
                }
                $mid = Member::registerByUnionId($unionid);
                if ($mid <= 0) {
                    throw new Exception('注册失败');
                }
                $data = Member::getByUnionId($unionid);
                $data["nick_name"] = empty($nick_name) ? $data['nick_name'] : $nick_name;
                $data["avatar"] = empty($avatar) ? $data['avatar'] : $avatar;
                $data["sex"] = $gender > 0 ? $gender : $data['sex'];
                $data["user_id"] = $mall_user_id;
            } else {
                $mall_user_id = $data['user_id'];
                $mid = $data['id'];
            }

            Member::where([
                "id" => $mid,
            ])->update([
                'nick_name' => empty($nick_name) ? $data['nick_name'] : $nick_name,
                'avatar' => empty($avatar) ? $data['avatar'] : $avatar,
                'city' => empty($city) ? $data['city'] : $city,
                'country' => empty($country) ? $data['country'] : $country,
                'sex' => $gender > 0 ? $gender : $data['sex'],
                'province' => empty($province) ? $data['province'] : $province,
                'openid' => empty($openid) ? $data['openid'] : $openid,
                'user_id' => $mall_user_id,
            ]);

            $data = Member::getByUnionId($unionid);
            Member::setOtherInfo($data);
            Users::where([
                "userId" => $mall_user_id,
            ])->update([
                'access_key' => $data['access_key']
            ]);
            $data['mall_user_id'] = $mall_user_id;
            Db::commit();
            return $this->outJson(0, "登录成功", $data);
        } catch (\Exception $e) {
            Db::rollback();
            return $this->outJson(0, "登录失败", $e->getMessage() ?? '接口异常');
        }

    }

    /**
     * 绑定手机号
     * @return array
     */
    public function bindPhone()
    {
        $vcode = $this->request->post("vcode", 0, "intval");
        $phone = $this->request->post("phone", 0, "intval");
        $user_id = $this->request->post("user_id", 0, "intval");

        if (ValidateHelper::isMobile($phone) == false) {
            return $this->outJson(100, "手机号格式错误");
        }

        if (SmsHelper::checkVcode($phone, $vcode, "login") == false) {
            return $this->outJson(100, "验证码无效");
        }

        $exist_user= TMember::where('phone',$phone)->find();
        if($exist_user != null) {
            return $this->outJson(100, "此手机号已绑定其它账号！");
        }

        TMember::where([
            "user_id" => $user_id,
        ])->update([
            'phone' => $phone,
        ]);
        //同步更新商城用户表手机号
        MallUser::where(["userId"=>$user_id])->update(["userPhone" => $phone]);

        SmsHelper::clearCacheKey($phone,"login");

        return $this->outJson(0, "绑定成功");
    }

    /**
     * 退出登录
     */
    public function loginOut()
    {
        if ($this->user_id) {
            AccessKeyHelper::forgetAccessKey($this->user_id);
        }
        return $this->outJson(0, "success");
    }
}