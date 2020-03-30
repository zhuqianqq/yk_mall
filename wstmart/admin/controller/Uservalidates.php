<?php
namespace wstmart\admin\controller;
use wstmart\admin\model\Uservalidates as M;
/**
 * 提现认证控制器
 */
class Uservalidates extends Base{

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
    public function toHandle(){
        //获取该记录信息
        $m = new M();
        $this->assign('object', $m->getById());
        $this->assign("p",(int)input("p"));
        return $this->fetch("edit");
    }
    
    /**
    * 修改
    */
    public function handle(){
        $status = (int)input('status',3);
        $m = new M();
        if($status == 2){
            // 通过
            return $m->handle();
        }else{
            // 失败
            return $m->handleFail();
        }
    }

    /**
     * 查看提现内容
     */
    public function toView(){
        $m = new M();
        $this->assign('object', $m->getById());
        $this->assign("p",(int)input("p"));
        return $this->fetch("view");
    }
}
