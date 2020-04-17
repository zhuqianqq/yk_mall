<?php
namespace wstmart\api\model;
use think\facade\Config;
use util\AccessKeyHelper;
use util\TLSSigAPIv2;
use util\Tools;
use wstmart\common\model\Users as CUsers;
use think\Db;
/**
 * 用户类
 */
class Users extends CUsers{
    /**
     * 普通会员
     */
    const USER_TYPE_NORMAL = 0;

    /**
     * 主播
     */
    const USER_TYPE_ANCHOR = 1;

    // 代理
    const USER_TYPE_PROXY = 2;

    /**
     * 获取商城用户信息
     * @param int $user_id 商城user_id
     * @param string $field
     */
    public static function getInfoByUserId($user_id,$field = "*")
    {
        return self::where("userId",$user_id)->field($field)->find();
    }

    /**
     * 获取商城用户信息
     * @param string $login_name 商城用户名或手机号
     * @param string $field
     */
    public static function getInfoByLoginName($login_name,$field = "*")
    {
        return self::where("loginName|userPhone",$login_name)->field($field)->find();
    }

    /**
     * 注册
     * @param array $data
     */
    public static function register($data, $from = 0)
    {
        $user_name = !empty($data["phone"]) ? $data["phone"] : $data["display_code"] ?? '';
        $nick_name = $data["nick_name"] ?? $data["phone"] ?? $data["display_code"] ?? '';
        $salt = mt_rand(1000,9999);
        $pwd = Tools::randStr(8); // 随机密码
        $insert_data = [
            "loginName" => $user_name ?: $nick_name, // 登录账号 手机号
            "userName" => $nick_name,
            "userType" => self::USER_TYPE_NORMAL, //会员类型: 0:普通会员 1 主播 2 代理
            "loginSecret" => $salt, // 密码盐值
            "loginPwd" => self::genPasswd($pwd, $salt),
            'userPhoto' => $data["avatar"] ?? '', // 头像
            'userPhone' => $data["phone"] ?? '',
            'userSex' => $data['sex'] ?? 0,
            "createTime" => date("Y-m-d H:i:s"),
            'lastTime' => date("Y-m-d H:i:s"),
            'lastIP' => Tools::getClientIp(),
            'plat' => $from, // 来源平台
        ];
        $user_id = self::insertGetId($insert_data);
        Tools::addLog("mall_user","register user_id:{$user_id}");
        return $user_id;
    }

    /**
     * 生成默认密码
     * @param $pwd
     * @return string
     */
    public static function genPasswd($pwd, $salt)
    {
        return md5($pwd . $salt);
    }

    /**
     * 设置其他信息
     * @param $data
     */
    public static function setOtherInfo(&$data, $need_old_key = 0)
    {
        if ($need_old_key == 1) {
            $data["access_key"] = AccessKeyHelper::getAccessKey($data["userId"]); //生成access_key
            if (empty($data["access_key"])) {
                $data["access_key"] = AccessKeyHelper::generateAccessKey($data["userId"]);
            }
        } else {
            $data["access_key"] = AccessKeyHelper::generateAccessKey($data["userId"]); // 生成access_key
        }
    }

    /**
     * 开通主播&开通店铺
     * @param $user_id
     */
    public static function openBroadCast($user_id, $year = 1)
    {
        static::where(['userId' => $user_id])->update([
            'userType' => static::USER_TYPE_ANCHOR,
            'expire_time' => date("Y-m-d H:i:s", strtotime("+{$year} years")), //过期时间
        ]);
    }

	/**
	* 验证用户支付密码
	*/ 
	function checkPayPwd(){
		$payPwd = input('payPwd');
		$decrypt_data = WSTRSA($payPwd);
		if($decrypt_data['status']==1){
			$payPwd = $decrypt_data['data'];
		}else{
			return WSTReturn('验证失败');
		}
		$userId = (int)session('WST_USER.userId');
		$rs = $this->field('payPwd,loginSecret')->find($userId);
		if($rs['payPwd']==md5($payPwd.$rs['loginSecret'])){
			return WSTReturn('',1);
		}
		return WSTReturn('支付密码错误',-1);
	}
}
