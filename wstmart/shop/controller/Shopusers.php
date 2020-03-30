<?php
namespace wstmart\shop\controller;
use wstmart\shop\model\ShopUsers as M;
/**
 * 门店角色控制器
 */
class Shopusers extends Base{
    protected $beforeActionList = ['checkAuth'];

	/**
	 * 列表
	 */
	public function index(){
		$m = new M();
		$list = $m->pageQuery();
		$this->assign("p",(int)input("p"));
		$this->assign('list',$list);
		return $this->fetch("shopusers/list");
	}
	
    /**
    * 查询
    */
    public function pageQuery(){
        $m = new M();
        return WSTGrid($m->pageQuery());
    }
    
    /**
     * 新增店铺管理员
     */
    public function add(){
    	$m = new M();
    	$object = $m->getEModel('shop_roles');
        $roles = model("ShopRoles")->listQuery();
		$data = ['object'=>$object,"roles"=>$roles];
        $this->assign("p",(int)input("p"));
    	return $this->fetch('shopusers/add',$data);
    }
	
	/**
     * 新增店铺管理员
     */
    public function toAdd(){
    	$m = new M();
    	return $m->add();
    }
	
    /**
     * 修改店铺管理员
     */
    public function edit(){
    	$m = new M();
    	$object = $m->getById(input('get.id'));
		$roles = model("ShopRoles")->listQuery();
        $data = ['object'=>$object,"roles"=>$roles];
        $this->assign("p",(int)input("p"));
    	return $this->fetch('shopusers/edit',$data);
    }

	/**
     * 编辑店铺管理员
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
    
    /**
     * 列表
     */
    public function notify(){
        $m = new M();
        $id = (int)input('id');
        $object = $m->getFieldsById($id,"id,privilegeMsgTypes,privilegeMsgs,privilegePhoneMsgs");
        $object['privilegeMsgTypes'] = explode(",",$object['privilegeMsgTypes']);
        $object['privilegeMsgs'] = explode(",",$object['privilegeMsgs']);
        $object['privilegePhoneMsgs'] = explode(",",$object['privilegePhoneMsgs']);
        $this->assign("object",$object);
        $this->assign("p",(int)input("p"));
        return $this->fetch("shopusers/notify");
    }

    /**
     * 通知设置
     */
    public function editNotifyConfig(){
        $m = new M();
        return $m->editNotifyConfig();
    }
}
