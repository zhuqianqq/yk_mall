<?php
namespace wstmart\admin\controller;
use wstmart\admin\model\GoodsConsult as M;
/**
 * 商品咨询控制器
 */
class GoodsConsult extends Base{
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
     * 跳去编辑页面
     */
    public function toEdit(){
        $m = new M();
        $data = $m->getById(input("get.id/d",0));
        $assign = ['data'=>$data];
        $this->assign("p",(int)input("p"));
        return $this->fetch("edit",$assign);
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

    
}
