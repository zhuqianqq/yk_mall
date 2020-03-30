<?php

namespace wstmart\shop\controller;

use wstmart\common\model\Users as MUsers;
use wstmart\common\model\LogSms;
use wstmart\shop\model\HomeMenus as HM;
use think\facade\Cache;
use util\Tools;
use util\SmsHelper;

/**
 * 默认控制器
 */
class Index extends Base
{
    protected $beforeActionList = ['checkAuth' => ['only' => 'index,main,getsysmessages,clearcache,main']];

    /**
     * 店铺主页
     */
    public function index()
    {
        $m = new HM();
        $ms = $m->getShopMenus();
        $this->assign("sysMenus", $ms[1]);
        return $this->fetch('/index');
    }

    /**
     * 去登录
     */
    public function login()
    {
        $USER = session('WST_USER');
        //如果已经登录了则直接跳去用户中心
        if (!empty($USER) && $USER['userId'] != '' && $USER['userType'] == 1) {
            $this->index();
        }
        $loginName = cookie("loginName");
        if (!empty($loginName)) {
            $this->assign('loginName', cookie("loginName"));
        } else {
            $this->assign('loginName', '');
        }

        return $this->fetch('/login');
    }

    /**
     * 获取用户信息
     */
    public function getSysMessages()
    {
        $rs = model('Systems')->getSysMessages();
        return $rs;
    }

    public function clearCache()
    {
        model('common/shops')->clearCache((int)session('WST_USER.shopId'));
        return WSTReturn("清除成功", 1);
    }

    /**
     * 卖家登录验证
     */
    public function checkLogin()
    {
        $m = new MUsers();
        $rs = $m->checkLogin();
        return $rs;
    }

    /**
     * 用户退出
     */
    public function logout()
    {
        $rs = model('Users')->logout();
        return $rs;
        session('WST_USER', null);
        return WSTReturn("退出成功，正在跳转页面", 1);
    }

    /**
     * 系统预览
     */
    public function main()
    {
        $s = model('shop/shops');
        $data = $s->getShopSummary((int)session('WST_USER.shopId'));
        $this->assign('data', $data);
        return $this->fetch("/main");
    }

    /**
     * 发送登录短信验证码
     */
    public function sendSmsCode()
    {
        if ($this->request->isAjax()) {
            $userPhone = $this->request->param("userPhone", '', "trim");
            if (empty($userPhone)) {
                return $this->outJson(100, "手机号不能为空");
            }

            $model_user = model('Users');
            $user = $model_user->getUserByPhone($userPhone);

            if (empty($user)) {
                return $this->outJson(100, "手机号不存在");
            }

            $mobile = $user['userPhone'];
            $cache_key = SmsHelper::getCacheKey($mobile);
            $vcode = Cache::get($cache_key);
            $mask_mobile = Tools::maskMobile($mobile);
            //判断是否已发送过
            if ($vcode) {
                return $this->outJson(0, "验证码已发送到{$mask_mobile}，请注意查收");
            }

            $vcode = mt_rand(1000, 999999);
            $result = SmsHelper::sendSmsMessage($mobile, "短信验证码：{$vcode}，有效期10分钟");
            if ($result['code'] != 0) {
                return $this->outJson($result["code"], $result['msg']);
            }
            $expire = 10 * 60; //10分钟有效
            Cache::set($cache_key, $vcode, $expire);

            //写入session兼容checkLoginByPhone
            session('VerifyCode_userPhone2', $userPhone);
            session('VerifyCode_userPhone_Verify2', $vcode); //验证码
            session('VerifyCode_userPhone_Time2', time());

            return $this->outJson(0, "验证码已发送到{$mask_mobile}，请注意查收");
        } else {
            return "非法请求";
        }
    }

    /**
     * 手机验证码登录
     */
    public function checkLoginByPhone()
    {
        $m = new MUsers();
        $rs = $m->checkLoginByPhone(0);

        return $rs;
    }
}
