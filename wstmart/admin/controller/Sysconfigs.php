<?php
namespace wstmart\admin\controller;
use wstmart\admin\model\SysConfigs as M;
/**
 * 商城配置控制器
 */
class Sysconfigs extends Base{
	
    public function index(){
    	$m = new M();
    	$object = $m->getSysConfigs();
    	$this->assign("object",$object);
    	return $this->fetch("edit");
    }
    
    /**
     * 保存
     */
    public function edit(){
    	$m = new M();
    	return $m->edit();
    }

    /**
     * 查看签到设置
     */
    public function sign(){
        $m = new M();
        $object = $m->getSysConfigsByType(3);
        $this->assign("object",$object);
        return $this->fetch("sign");
    }
    /**
     * 签到设置
     */
    public function editSign(){
        $m = new M();
        return $m->edit(3);
    }

    /**
     * 购物设置
     */
    public function buyConfig(){
        $m = new M();
        $object = $m->getSysConfigsByType(4);
        $this->assign("object",$object);
        return $this->fetch("buy");
    }
    /**
     * 购物设置
     */
    public function editBuyConfig(){
        $m = new M();
        return $m->edit(4);
    }

    /**
     * 通知设置
     */
    public function notifyConfig(){
        $m = new M();
        $object = $m->getSysConfigsByType(5);
        $this->assign("object",$object);
        $list = model('admin/staffs')->listQuery();
        $this->assign("list",$list);
        return $this->fetch("notify");
    }
    /**
     * 通知设置
     */
    public function editNotifyConfig(){
        $m = new M();
        return $m->edit(5);
    }
}
