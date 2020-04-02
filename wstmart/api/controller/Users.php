<?php
namespace wstmart\api\controller;
use util\Validate;
use wstmart\api\model\Users as M;
use wstmart\common\model\LogSms;
use wstmart\common\model\Users as MUsers;
/**
 * 用户控制器
 */
class Users extends Base
{
    /***
     * 实名认证
     * @return array
     */
   public function vad()
   {
        $user_id = $this->user_id;
        $truename = input('true_name'); // 真实姓名
        $idcard = input('idcard'); // 身份证
        if (empty($user_id)) {
            return $this->outJson(100, "缺少参数");
        }
        $user = M::where("userId = {$user_id}")->find();
        if (empty($user)) {
            return $this->outJson(100, "没有数据");
        }
        // 是否实名 0 否 1 是
        if ($user->is_validate == 1) {
            return $this->outJson(100, "请勿重复实名");
        }
        if (empty($truename) || empty($idcard)) {
            return $this->outJson(100, "缺少参数");
        }
        $rs = Validate::validateIdcard($truename, $idcard);
        if (!$rs) {
            return $this->outJson(100, "认证失败");
        }
        $user->trueName = $truename;
        $user->idcard = $idcard;
        $user->is_validate = 1;
        if (!$user->save()) {
            return $this->outJson(100, "认证失败");
        }
       return $this->outJson(0, "认证成功");
   }

    /*
     * 更新用户信息
     */
    public function updateMember()
    {
        $user_id = $this->request->param("user_id", '', "intval");
        $nick_name = $this->request->post("nick_name", '');
        $avatar = $this->request->post("avatar", '');
        $sex = $this->request->post("sex", '', "intval");
        if (mb_strlen($nick_name,"utf-8") > 8) {
            $this->outJson(1, "昵称限制8个字符！");
        }
        $member = \wstmart\api\model\Users::where("userId", $user_id)->find();
        if (empty($member)) {
            return $this->outJson(1, "没有数据！");
        }
        $member->userName = empty($nick_name) ? $member->userName : $nick_name;
        $member->userPhoto = empty($avatar) ? $member->userPhoto : $avatar;
        $member->userSex = empty($sex) ? $member->userSex : $sex;
        $member->save();
        return $this->outJson(0, "保存成功！");
    }

    /*
     * 用户信息详情
     */
    public function memberDetail()
    {
        $user_id = $this->request->param("user_id", '', "intval");
        $member = \wstmart\api\model\Users::where("userId", $user_id)->field("userName, userSex, userPhoto, userPhone")->find();
        if (empty($member)) {
            return $this->outJson(1, "指定的用户不存在！");
        }
        return $this->outJson(0, "查找成功！", $member);
    }

}
