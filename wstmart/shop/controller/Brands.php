<?php
namespace wstmart\shop\controller;
use wstmart\common\model\Brands as M;
/**
 * 品牌控制器
 */
class Brands extends Base{
    protected $beforeActionList = ['checkAuth'];
    /**
     * 获取品牌列表
     */
    public function listQuery(){
        $m = new M();
        return ['status'=>1,'list'=>$m->listQuery(input('post.catId/d'))];
    }
}
