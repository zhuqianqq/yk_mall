<?php
namespace wstmart\shop\controller;
use wstmart\common\model\GoodsAppraises as M;
use wstmart\shop\model\GoodsAppraises as N;
/**
 * 评价控制器
 */
class GoodsAppraises extends Base{
    protected $beforeActionList = ['checkAuth'];
    /**
     * 获取评价列表 商家
     */
    public function index(){
        return $this->fetch('goodsappraises/list');
    }
    // 获取评价列表 商家
    public function queryByPage(){
        $m = new N();
        return WSTGrid($m->queryByPage());
    }

    /**
     * 商家回复评价
     */
    public function shopReply(){
        $m = new M();
        return $m->shopReply();
    }
}
