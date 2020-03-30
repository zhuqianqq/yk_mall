<?php
namespace wstmart\admin\controller;
use wstmart\admin\model\UserScores as M;
/**
 * 积分日志控制器
 */
class Userscores extends Base{
	
    public function toUserScores(){
        $m = new M();
        $this->assign("p",(int)input("p"));
        $object = $m->getUserInfo();
        $this->assign("object",$object);
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
     * 跳去新增界面
     */
    public function toAdd(){
        $m = new M();
        $object = $m->getUserInfo();
        $this->assign("object",$object);
        return $this->fetch("box");
    }

    /**
     * 新增
     */
    public function add(){
        $m = new M();
        return $m->addByAdmin();
    }

    /**
     * 签到排行
     */
    public function ranking(){
         $this->assign("p",(int)input("p"));
        return $this->fetch("ranking");
    }

    /**
     * 获取签到排行分页
     */
    public function pageQueryByRanking(){
        $m = new M();
        return WSTGrid($m->pageQueryByRanking());
    }
}
