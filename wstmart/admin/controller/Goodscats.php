<?php
namespace wstmart\admin\controller;
use wstmart\admin\model\GoodsCats as M;
/**
 * 商品分类控制器
 */
class GoodsCats extends Base{
	
    public function index(){
    	return $this->fetch("list");
    }
    
    /**
     * 获取分页
     */
    public function pageQuery(){
    	$m = new M();
    	return $m->pageQuery();
    }
    /**
     * 获取列表
     */
    public function listQuery(){
    	$m = new M();
    	$rs = $m->listQuery(input('parentId/d',0));
    	return WSTReturn("", 1,$rs);
    }
    /**
     * 获取商品分类
     */
    public function get(){
    	$m = new M();
    	return $m->get((int)Input("post.id"));
    }
    
    /**
     * 设置是否推荐/不推荐
     */
    public function editiIsFloor(){
    	$m = new M();
    	return $m->editiIsFloor();
    }
       
    /**
     * 设置是否显示/隐藏
     */
    public function editiIsShow(){
    	$m = new M();
    	return $m->editiIsShow();
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
     * 编辑分类名
     */
    public function editName(){
    	$m = new M();
    	return $m->editName();
    }
    /**
     * 编辑分类名缩写
     */
    public function editsimpleName(){
    	$m = new M();
    	return $m->editsimpleName();
    }
    /**
     * 编辑分类排序
     */
    public function editOrder(){
        $m = new M();
        return $m->editOrder();
    }
    
    /**
     * 删除
     */
    public function del(){
    	$m = new M();
    	return $m->del();
    }

    /**
     * 跳去新增/编辑页面
     */
    public function toEdit(){
        $id = Input("get.id/d",0);
        $pid = Input("get.pid/d",0);
        $m = new M();
        if($id>0){
            $object = $m->get($id);
        }else{
            if($pid > 0){
                $parentObject = $m->get($pid);
                $object = $m->getEModel('goods_cats');
                $object['parentId'] = $pid;
                $object['catListTheme'] = $parentObject['catListTheme'];
                $object['detailTheme'] = $parentObject['detailTheme'];
                $object['mobileCatListTheme'] = $parentObject['mobileCatListTheme'];
                $object['mobileDetailTheme'] = $parentObject['mobileDetailTheme'];
                $object['wechatCatListTheme'] = $parentObject['wechatCatListTheme'];
                $object['wechatDetailTheme'] = $parentObject['wechatDetailTheme'];
            }else{
                $object = $m->getEModel('goods_cats');
                $object['parentId'] = $pid;
            }
        }
        $this->assign('object',$object);
        return $this->fetch("edit");
    }
}
