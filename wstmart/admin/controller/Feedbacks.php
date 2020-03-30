<?php
namespace wstmart\admin\controller;
use wstmart\admin\model\Feedbacks as M;
/**
 * 功能反馈控制器
 */
class Feedbacks extends Base{
	
    public function index(){
        $this->assign("p",(int)input("p"));
    	return $this->fetch("list");
    }
    /**
    * 查看反馈信息
    */
    public function toEdit(){
        $m = new M();
        $rs = $m->getById((int)input("feedbackId",0));
        $this->assign("object",$rs);
        $this->assign("p",(int)input('p'));
        return $this->fetch("edit");
    }

    /**
     * 获取分页
     */
    public function pageQuery(){
        $m = new M();
        return WSTGrid($m->pageQuery());
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
