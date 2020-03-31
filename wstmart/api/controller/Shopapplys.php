<?php
namespace wstmart\api\controller;
use wstmart\common\model\ShopApplys as M;
use wstmart\common\model\Users as UM;
/**
 * 商家入驻控制器
 */
class Shopapplys extends Base{
    // 前置方法执行列表
    protected $beforeActionList = [
        'checkAuth',
    ];
    /**
    * 跳去商家入驻页面
    */
    public function index(){
        if((int)WSTConf('CONF.isOpenShopApply')!=1)return;
        $m = new M();
        $um = new UM();
        $userId = (int)session('WST_USER.userId');
        // 获取是否已经填写商家入驻
        $isApply = $m->isApply();
        $rs = $um->getFieldsById($userId,'userPhone');
        $this->assign('isApply',$isApply);
        $this->assign('userPhone',$rs['userPhone']);
    	return $this->fetch('users/shopapplys/shop_applys');
    }

    /**
     * 保存商家入驻
     */
    public function add(){
        if((int)WSTConf('CONF.isOpenShopApply')!=1)return WSTReturn('未开启商家入驻');
        $m = new M();
        return $m->add();
    }
}
