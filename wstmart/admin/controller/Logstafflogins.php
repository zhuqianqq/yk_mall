<?php
namespace wstmart\admin\controller;
use wstmart\admin\model\LogStaffLogins as M;
/**
 * 登录日志控制器
 */
class Logstafflogins extends Base{
	
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
