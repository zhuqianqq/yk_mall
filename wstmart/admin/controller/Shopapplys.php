<?php
namespace wstmart\admin\controller;
use wstmart\admin\model\ShopApplys as M;
/**
 * 商家入驻控制器
 */
class Shopapplys extends Base{
    
    /**
     * 跳去新增/编辑页面
     */
    public function toHandleApply(){
        $id = (int)input("id");
        $m = new M();
        if($id>0){
            $object = $m->getById($id);
        }else{
            $object = $m->getEModel('shop_applys');
        }
        $this->assign('object',$object);
        $this->assign("p",(int)input('p'));
        return $this->fetch("edit");
    }

    /**
     * 修改
     */
    public function handleApply(){
        $m = new M();
        return $m->edit();
    }
    /**
     * 删除
     */
    public function del(){
        $m = new M();
        return $m->del();
    }

    /**
     * 获取数据
     */
    public function getById(){
        $m = new M();
        return $m->getById((int)input("id"));
    }

    /**
     * 获取分页
     */
    public function pageQuery(){
        $m = new M();
        $this->assign("p",(int)input('p'));
        return WSTGrid($m->pageQuery());
    }
    
}
