<?php
namespace wstmart\shop\controller;
use wstmart\shop\model\ShopStyles as M;
/**
 * 风格设置控制器
 */
class Shopstyles extends Base{
	protected $beforeActionList = ['checkAuth'];
    /**
    * 查看风格管理
    */
	public function index(){
        $m = new M();
        $rs = $m->getCats();
        $this->assign('cats',$rs);
		return $this->fetch('shopstyles/index');
	}
    /**
     * 获取风格列表
     */
    public function listQueryBySys(){
        $m = new M();
        $rs = $m->listQuery();
        return WSTReturn('',1,$rs);
    }

    /**
     * 保存
     */
    public function changeStyle(){
        $m = new M();
        return $m->changeStyle();
    }
}
