<?php
namespace wstmart\shop\controller;
use wstmart\common\model\GoodsConsult as M;
use wstmart\shop\model\GoodsConsult as N;
/**
 * 商品咨询控制器
 */
class Goodsconsult extends Base{
    protected $beforeActionList = ['checkAuth'];

    /**
     * 修改
     */
    public function edit(){
        $m = new M();
        return $m->edit();
    }
    /**
     * 根据店铺id获取商品咨询
     */
    public function pageQuery(){
        $m = new N();
        $rs = $m->pageQuery();

        return WSTGrid($rs);
    }
    /**
     * 获取商品咨询 商家
     */
    public function shopReplyConsult(){
        $this->assign("p",(int)input("p"));
        return $this->fetch('goodsconsult/list');
    }
    /**
     * 商家回复
     */
    public function reply(){
        $m = new M();
        return $m->reply();
    }
}
