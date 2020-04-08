<?php
namespace wstmart\home\controller;
use wstmart\common\model\CashDraws as M;
use wstmart\common\model\Users as MUsers;
/**
 * 提现记录控制器
 */
class Cashdraws extends Base{
    protected $beforeActionList = ['checkAuth'];
    /**
     * 查看用户资金流水
     */
	public function index(){
		return $this->fetch('users/cashdraws/list');
	}
    /**
     * 获取用户数据
     */
    public function pageQuery(){
        $userId = (int)session('WST_USER.userId');
        $data = model('CashDraws')->pageQuery(0,$userId);
        return WSTReturn("", 1,$data);
    }

    /**
     * 跳转提现页面
     */
    public function toEdit(){
        $userId = (int)session('WST_USER.userId');
        $this->assign('accs',model('CashConfigs')->listQuery(0,$userId));
        $m = new MUsers();
        $user = $m->getFieldsById($userId,["userMoney","rechargeMoney"]);
        $this->assign('user',$user);
        return $this->fetch('users/cashdraws/box_draw');
    }

    /**
     * 提现
     */ 
    public function drawMoney(){
        $m = new M();
        return $m->drawMoney();
    }
}
