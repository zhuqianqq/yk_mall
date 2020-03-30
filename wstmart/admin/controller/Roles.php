<?php
namespace wstmart\admin\controller;
use wstmart\admin\model\Roles as M;
/**
 * 角色控制器
 */
class Roles extends Base{
	
    public function index(){
        $this->assign("p",(int)input("p"));
    	return $this->fetch("list");
    }
    
    /**
     * 获取分页
     */
    public function pageQuery(){
    	$m = new M();
    	return WSTGrid($m->pageQuery());
    }
    /**
     * 获取菜单
     */
    public function get(){
        
    	$m = new M();
    	return $m->get((int)Input("post.id"));
    }
    /**
     * 跳去编辑页面
     */
    public function toEdit(){
    	$m = new M();
    	$rs = $m->getById((int)Input("get.id"));
    	$this->assign("object",$rs);
        $this->assign("p",(int)input("p"));
    	return $this->fetch("edit");
    }
    /**
     * 新增菜单
     */
    public function add(){
    	$m = new M();
    	return $m->add();
    }
    /**
     * 编辑菜单
     */
    public function edit(){
    	$m = new M();
    	return $m->edit();
    }
    /**
     * 删除菜单
     */
    public function del(){
    	$m = new M();
    	return $m->del();
    }
}
