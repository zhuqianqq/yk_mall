<?php
namespace wstmart\api\controller;
use wstmart\common\model\UserAlipayAccount;
use wstmart\common\model\Shops as S;
use wstmart\common\model\Orders as O;
use wstmart\shop\model\Settlements as St;
use wstmart\common\model\TInviteOrder as Ti;
use wstmart\common\model\TMember as Tm;
use wstmart\common\model\UserValidates as UV;
use wstmart\common\model\UserAlipayAccount as UA;
use wstmart\common\model\CashDraws as CD;

/**
 * ============================================================================
 * WSTMart多用户商城
 * 版权所有 2016-2066 广州商淘信息科技有限公司，并保留所有权利。
 * 官网地址:http://www.wstmart.net
 * 交流社区:http://bbs.shangtao.net
 * 联系QQ:153289970
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！未经本公司授权您只能在不用于商业目的的前提下对程序代码进行修改和使用；
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * 用户控制器
 */
class Members extends Base{
	/**
	 * 用户信息
	 */
	public function userDetail()
    {
        $live_userid = input('param.mall_user_id'); // 直播用户ID
		$user_id = $live_userid;  //商城id
        // 查询用户信息
        $memberInfo = new Tm();
        $user = $memberInfo->getMemberInfo($user_id);
        if (empty($user)) {
            return $this->outJson(100, "没有找到数据");
        }
        // 查询店铺信息
		$shop = new S();
		$balanceData = $shop->where("userId = {$user_id}")->field("shopMoney, shopId")->find();
		// 可提现金额
        $balance = 0;
        $shopId = 0;
        if (!empty($balanceData)) {
            $balance = $balanceData['shopMoney'];
            $shopId = $balanceData['shopId'];
        }

        // 查询邀请信息
        $inviteInfo = new Ti();
        $inviteInfoData = $inviteInfo->getInviteOrderInfo($live_userid);
        $inviteCreateTime = '';
        $inviteUid = 0;
        $inviteUsername = '';
        if (!empty($inviteInfoData)) {
            $inviteUid = $inviteInfoData['inviter_uid'];
            $inviteCreateTime = date('Y-m-d', strtotime($inviteInfoData['update_time']));
        }
        if ($inviteUid) {
            $memberInfoData = $memberInfo->getMemberInfo($inviteUid);
            if (!empty($memberInfoData)) {
                $inviteUsername = $memberInfoData['nick_name'];
            }
        }

        // 查询订单信息
        $order = new O();
        $orderData = $order->getSellerOrders($shopId);
        // 今日支付金额
        $payMoney = 0;
        // 今日支付订单数
        $payCount = 0;
        if (!empty($orderData)) {
            $payMoney = floatval($orderData['totalMoney']);
            $payCount = intval($orderData['totalCount']);
        }
        // 未结算金额 查询未结算金额
        $settlement = new St();
        $unSettled = $settlement->pageUnSettledMoney($shopId);
        $unSettledMoney = 0;
        if ($payMoney >= 10000) {
            $payMoney = bcdiv($payMoney, 10000, 1) . 'W';
        } else {
            $payMoney = bcdiv($payMoney, 1, 2);
        }
        if (!empty($unSettled)) {
            $unSettledMoney = $unSettled['totalMoney'];
        }
        if ($unSettledMoney >= 10000) {
            $unSettledMoney = bcdiv($unSettledMoney, 10000, 1) . 'W';
        } else {
            $unSettledMoney = bcdiv($unSettledMoney, 1, 2);
        }
        $data['balance'] = bcdiv($balance, 1, 2);
        $data['unSettledMoney'] = $unSettledMoney;
        $data['payMoney'] = $payMoney;
        $data['payCount'] = $payCount;
        $data['userPhoto'] = $user['avatar'];
        $data['userName'] = $user['nick_name'];
        $data['inviteUsername'] = $inviteUsername;
        $data['inviteCreateTime'] = $inviteCreateTime;

		return $this->outJson(0, "success", $data);
	}

    /**
     * 实名认证-添加
     * @return array
     */
    public function userValidateAdd()
    {
        try {
            $m = new UV();
            $id = $m->add();
            return $this->outJson(0, "success", ['validateId' => $id]);
        } catch (\Exception $e) {
            return $this->outJson(100, $e->getMessage());
        }
    }

    /**
     * 实名认证-修改
     * @return array
     */
    public function userValidateEdit()
    {
        try {
            $validateId = input('param.validateId/d'); // 认证ID
            $m = new UV();
            $m->edit($validateId);
            return $this->outJson(0, "success");
        } catch (\Exception $e) {
            return $this->outJson(100, $e->getMessage());
        }
    }

    /**
     * 详情
     */
    public function userValidateDetail()
    {
        try {
            $m = new UV();
            $data = $m->pageQuery();
            return $this->outJson(0, "success", $data);
        } catch (\Exception $e) {
            return $this->outJson(100, $e->getMessage());
        }
    }

    /**
     * 支付宝账号添加/修改
     * @return array
     */
    public function alipayAccountAdd()
    {
        try {
            $m = new UA();
            $data = input('post.');
            $userid = isset($data['mall_user_id']) ? $data['mall_user_id'] : 0;
            if (empty($userid)) {
                throw new \Exception('缺少参数');
            }
            $record = UserAlipayAccount::where("user_id = {$userid}")->find();
            if (empty($record)) {
                // 没有则新建 否则修改
                $id = $m->add();
                return $this->outJson(0, "success", ['accountId' => $id]);
            } else {
                $accountId = $record['account_id'];
                $m->edit($accountId);
                return $this->outJson(0, "success");
            }
        } catch (\Exception $e) {
            return $this->outJson(100, $e->getMessage());
        }
    }

    /**
     * 详情
     */
    public function alipayAccountDetail()
    {
        try {
            $m = new UA();
            $data = $m->pageQuery();
            return $this->outJson(0, "success", $data);
        } catch (\Exception $e) {
            return $this->outJson(100, $e->getMessage());
        }
    }

    /**
     * 返回用户状态
     */
    public function userValidateInfo()
    {
        try {
            $userid = input('param.mall_user_id'); // 直播用户ID
            if (empty($userid)) {
                throw new \Exception('缺少参数');
            }
            $validateRecord = UV::where("user_id = {$userid}")->find();
            $alipayRecord = UA::where("user_id = {$userid}")->find();
            $userValidateStatus = -1; //  -1 没有记录 0 待审核 1 审核中 2 审核通过 3 审核失败
            $userPayStatus = 0; // 0 没有记录（新增） 1 可以修改
            $drawStatus = 0;
            if (!empty($validateRecord)) {
                // 如果认证
                if ($validateRecord['status'] == UV::STATUS_PASS) {
                    $drawStatus = 1;
                }
                $userValidateStatus = $validateRecord['status'];
            }
            if (!empty($alipayRecord)) {
                $userPayStatus = 1;
            }

            // 查询店铺信息
            $shop = new S();
            $balanceData = $shop->where("userId = {$userid}")->field("shopMoney, shopId")->find();
            // 可提现金额
            $balance = 0;
            if (!empty($balanceData)) {
                $balance = $balanceData['shopMoney'];
            }
            $data = [
                'userValidateStatus' => $userValidateStatus,
                'userPayStatus' => $userPayStatus,
                'cashStatus' => $drawStatus && $userPayStatus ? 1 : 0,
                'balance' => bcdiv($balance, 1,  2),
            ];
            return $this->outJson(0, '查询成功', $data);
        } catch (\Exception $e) {
            return $this->outJson(100, $e->getMessage());
        }
    }

    /**
     * 提现
     */
    public function drawMoneyByShop(){
        try {
            // 判断是否可以提现
            $userid = input('param.mall_user_id'); // 直播用户ID
            if (empty($userid)) {
                throw new \Exception('缺少参数');
            }
            $validateRecord = UV::where("user_id = {$userid}")->find();
            $alipayRecord = UA::where("user_id = {$userid}")->find();
            $userPayStatus = 0; // 0 没有记录（新增） 1 可以修改
            $drawStatus = 0;
            if (!empty($validateRecord)) {
                // 如果认证
                if ($validateRecord['status'] == UV::STATUS_PASS) {
                    $drawStatus = 1;
                }
            }
            if (!empty($alipayRecord)) {
                $userPayStatus = 1;
            }
            $cashStatus = $drawStatus && $userPayStatus ? 1 : 0;
            if (!$cashStatus) {
                throw new \Exception('请先完成认证与添加提现账户');
            }

            $m = new CD();
            $m->drawMoneyByShopByUserid($userid);
            return $this->outJson(0, '提现申请成功，我们将在5个工作日内，打款至您的支付宝账户，请注意查收，如有疑问请联系客户');
        } catch (\Exception $e) {
            return $this->outJson(100, $e->getMessage());
        }
    }
}
