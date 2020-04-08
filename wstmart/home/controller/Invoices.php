<?php
namespace wstmart\home\controller;
use wstmart\common\model\Invoices as M;

/**
 * 发票信息控制器
 */
class Invoices extends Base
{
    protected $beforeActionList = ['checkAuth'];

    /**
     * 发票管理列表页
     */
    public function invoiceList()
    {
        return $this->fetch('users/invoices/list');
    }

    /**
     * 新增发票页
     */
    public function toEdit()
    {
        $m = new M();
        $id = (int)input('id');
        $data = [];
        if($id>0){
            $data = $m->getById($id);
        }else{
            $data = $m->getEModel('invoices');
        }
        return $this->fetch('users/invoices/edit', ['data' => $data]);
    }


    /**
     * 发票页面
     */
    public function index()
    {
        $m = new M();
        $data = $m->pageQuery();
        $this->assign('invoiceId', (int)input('invoiceId'));
        $this->assign('isInvoice', (int)input('isInvoice'));
        $this->assign('invoiceType', (int)input('invoiceType'));
        $this->assign('data', $data);
        return $this->fetch('invoices');
    }


    /**
     * 查询
     */
    public function pageQuery()
    {
        $m = new M();
        return $m->pageQuery();
    }

    /**
     * 新增
     */
    public function add()
    {
        $m = new M();
        return $m->add();
    }

    /**
     * 修改
     */
    public function edit()
    {
        $m = new M();
        return $m->edit();
    }

    /**
     * 删除
     */
    public function del()
    {
        $m = new M();
        return $m->del();
    }
}
