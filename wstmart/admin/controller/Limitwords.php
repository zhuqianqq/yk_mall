<?php
namespace wstmart\admin\controller;
use wstmart\admin\model\LimitWords as M;
/**
 * 系统禁用关键字控制器
 */
class Limitwords extends Base{
	
    public function index(){
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
     * 获取禁用关键字内容
     */
    public function get(){
        $m = new M();
        return $m->get((int)Input("post.id"));
    }

    /**
     * 新增
     */
    public function add(){
        $m = new M();
        return $m->add();
    }

    /**
     * 编辑
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
