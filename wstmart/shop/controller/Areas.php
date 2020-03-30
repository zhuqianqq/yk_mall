<?php
namespace wstmart\shop\controller;
use wstmart\common\model\Areas as M;
/**
 * 地区控制器
 */
class Areas extends Base{
	
    /**
     * 获取分页
     */
    public function pageQuery(){
    	$m = new M();
    	$rs = $m->pageQuery();
    	return WSTGrid($rs);
    }
    
    /**
     * 列表查询
     */
    public function listQuery(){
    	$m = new M();
    	$list = $m->listQuery(Input("post.parentId/d",0));
    	return WSTReturn("", 1,$list);
    }
}
