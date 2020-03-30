<?php
namespace wstmart\admin\controller;
use think\Db;
use wstmart\admin\model\Staffs;
use wstmart\admin\model\Menus;
use wstmart\admin\model\Index as M;
/**
 * 首页控制器
 */
class Index extends Base{
	/**
	 * 跳去登录页
	 */
	public function login(){
        model('CronJobs')->autoByAdmin();
		return $this->fetch("/login");
	}
	
    public function index(){
    	$m = new Menus();
    	$ms = $m->getMenus();
    	$this->assign("sysMenus",$ms);
    	return $this->fetch("/index");
    }
    
    /**
     * 登录验证
     */
    public function checkLogin(){
    	$m = new Staffs();
    	return $m->checkLogin();
    }
    
    /**
     * 退出系统
     */
    public function logout(){
    	session('WST_STAFF',null);
    	return WSTReturn("退出成功，正在跳转页面", 1);
    }
    
    /**
     * 系统预览
     */
    public function main(){
    	$m = new M();
    	$rs = $m->summary();
    	$this->assign("object",$rs);
    	return $this->fetch("/main");
    }
    
    /**
     * 获取用户权限
     */
    public function getGrants(){
    	$rs = session('WST_STAFF');
    	if(empty($rs))return WSTReturn("您未登录，请先登录系统",-1);
    	$rs = $rs['privileges'];
    	$grants = [];
    	foreach ($rs as $v){
    		$grants[$v] = true;
    	}
    	return WSTReturn("权限加载成功",1, $grants);
    }
    /**
     * 清除缓存
     */
    public function clearcache(){
    	WSTClearAllCache();
    	return WSTReturn("清除成功!", 1);
    }

    /**
     * 系统预览
     */
    public function getSysMessages(){
        $m = new M();
        $rs = $m->getSysMessages();
        return WSTReturn("", 1,$rs);
    }
    
}
