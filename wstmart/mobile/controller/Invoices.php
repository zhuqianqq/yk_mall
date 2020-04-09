<?php
namespace wstmart\mobile\controller;
use wstmart\common\model\Invoices as M;
/**
 * 发票信息控制器
 */
class Invoices extends Base{
    // 前置方法执行列表
    protected $beforeActionList = [
        'checkAuth',
    ];
    
    /**
     * 发票管理列表页
     */
    public function listQuery(){
        $m = new M();
        $data = $m->pageQuery();
        $this->assign('list', $data);
        return $this->fetch('users/invoices/list');
    }

    /**
     * 单条数据
     */
    public function get()
    {
        $m = new M();
        return WSTReturn("", 1,$m->getById(input('post.id')));
    }

    /**
     * 查询
     */
    public function pageQuery(){
        $m = new M();
        return $m->pageQuery();
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
}
