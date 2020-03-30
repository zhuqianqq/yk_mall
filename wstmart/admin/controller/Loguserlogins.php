<?php
namespace wstmart\admin\controller;
use wstmart\admin\model\LogUserLogins as M;
/**
 * 用户登录日志控制器
 */
class Loguserlogins extends Base{
	
    public function index(){
    	return $this->fetch("list");
    }
    
    /**
     * 获取分页
     */
    public function pageQuery(){
    	$m = new M();
    	return WSTGrid($m->pageQuery());
    }
}
