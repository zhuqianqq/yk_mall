<?php
namespace wstmart\shop\controller;
use wstmart\shop\model\Settlements as M;
/**
 * 结算控制器
 */
class Settlements extends Base{
    protected $beforeActionList = ['checkAuth'];
    public function index(){
        $this->assign("p",(int)input("p"));
        return $this->fetch('settlements/list');
    }

    /**
     * 获取结算单
     */
    public function pageQuery(){
        $m = new M();
        $rs = $m->pageQuery();
        return WSTGrid($rs);
    }
    /**
     * 获取待结算订单
     */
    public function pageUnSettledQuery(){
        $m = new M();
        $rs = $m->pageUnSettledQuery();
        return WSTGrid($rs);
    }

    /**
     * 获取已结算订单
     */
    public function pageSettledQuery(){
        $m = new M();
        $rs = $m->pageSettledQuery();
        return WSTGrid($rs);
    }
    /**
     * 查看结算详情
     */
    public function view(){
        $m = new M();
        $rs = $m->getById();
        $this->assign('object',$rs);
        return $this->fetch('settlements/view');
    }
}
