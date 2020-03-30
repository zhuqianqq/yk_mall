<?php
namespace wstmart\shop\controller;
use wstmart\shop\model\Stores as M;
/**
 * 门店控制器
 */

class Stores extends Base{
    protected $beforeActionList = ['checkAuth'];
    /**
    * 店铺公告页
    */
    public function index(){
        $this->assign("p",(int)input("p"));
        return $this->fetch('stores/list');
    }
    /**
    * 查询
    */
    public function pageQuery(){
        $m = new M();
        return WSTGrid($m->pageQuery());
    }
    
    /**
     * 新增店铺管理员
     */
    public function add(){
        $this->assign("p",(int)input("p"));
        return $this->fetch('stores/add');
    }
    
    /**
     * 新增店铺管理员
     */
    public function toAdd(){
        $m = new M();
        return $m->add();
    }
    
    /**
     * 修改门店管理员
     */
    public function edit(){
        $m = new M();
        $object = $m->getById();
        $this->assign("object",$object);
        $this->assign("p",(int)input("p"));
        return $this->fetch('stores/edit');
    }

    /**
     * 编辑店铺管理员
     */
    public function toEdit(){
        $m = new M();
        return $m->edit();
    }
    
    /**
     * 删除操作
     */
    public function del(){
        $m = new M();
        $rs = $m->del();
        return $rs;
    }

    /**
     * 启用关闭门店
     */
    public function setStoreStatus(){
        $m = new M();
        $rs = $m->setStoreStatus();
        return $rs;
    }

    /**
     * 门店销售统计 
     */
    public  function salestatistics(){
        return $this->fetch('stores/sale_statistics');
    }

    /**
    * 查询门店销售统计
    */
    public function pageQuerySalestatistics(){
        $m = new M();
        $rs = $m->pageQuerySalestatistics();
        return WSTReturn("", 1,$rs);
    }
    
}
