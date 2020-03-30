<?php
namespace wstmart\admin\controller;
use wstmart\admin\model\ShopFlows as M;
/**
 * 店铺入驻流程控制器
 */
class Shopflows extends Base{
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
    /*
    * 获取数据
    */
    public function get(){
        $m = new M();
        return $m->getById(Input("id/d",0));
    }
    /**
     * 新增
     */
    public function add(){
        $m = new M();
        return $m->add();
    }
    /**
     * 修改
     */
    public function edit(){
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
     * 修改排序
     */
    public function changeSort(){
        $m = new M();
        return $m->changeSort();
    }

    /**
     * 设置是否显示/隐藏
     */
    public function editiIsShow(){
        $m = new M();
        $rs = $m->editiIsShow();
        return $rs;
    }

    /*
     * 跳转到流程下的字段页面
     */
    public function toEditFlow(){
        $id = (int)input('id');
        $this->assign('flowId',$id);
        $this->assign("p",(int)input("p"));
        return $this->fetch("edit");
    }

    /**
     * 获取流程里的字段分页
     */
    public function fieldPageQuery(){
        $m = new M();
        return WSTGrid($m->fieldPageQuery());
    }

    /*
     * 保存流程的字段
     */
    public function saveField(){
        $m = new M();
        $rs = $m->saveField();
        return $rs;
    }

    /*
     * 删除流程的字段
     */
    public function delField(){
        $m = new M();
        $rs = $m->delField();
        return $rs;
    }

    /*
     * 获取流程的某个字段详情
     */
    public function getFieldById(){
        $m = new M();
        return $m->getFieldById((int)input("id",0));
    }
}
