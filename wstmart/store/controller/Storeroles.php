<?php
namespace wstmart\store\controller;
use wstmart\store\model\StoreRoles as M;
/**
 * 门店角色控制器
 */
class Storeroles extends Base{
    protected $beforeActionList = ['checkAuth'];

	/**
	 * 列表
	 */
	public function index(){
		$m = new M();
		$list = $m->pageQuery();
		$this->assign('list',$list);
		$this->assign("p",(int)input("p"));
		return $this->fetch("storeroles/list");
	}
	
    /**
    * 查询
    */
    public function pageQuery(){
        $m = new M();
        return WSTGrid($m->pageQuery());
    }
    
    /**
     * 新增角色
     */
    public function add(){
    	$m = new M();
    	$object = $m->getEModel('shop_roles');
		$data = ['object'=>$object];
        $this->assign("p",(int)input("p"));
    	return $this->fetch('storeroles/edit',$data);
    }
	
	/**
     * 新增角色
     */
    public function toAdd(){
    	$m = new M();
    	return $m->add();
    }
	
    /**
     * 修改角色
     */
    public function edit(){
    	$m = new M();
    	$object = $m->getById((int)input('get.id'));
		$data = ['object'=>$object];
        $this->assign("p",(int)input("p"));
    	return $this->fetch('storeroles/edit',$data);
    }

	/**
     * 修改角色
     */
    public function toEdit(){
    	$m = new M();
    	return $m->edit();
    }
	
    /**
     * 删除操作
     */
    public function del(){
    	$m = new M();
    	$rs = $m->del();
    	return $rs;
    }
    
}
