<?php
namespace wstmart\api\controller;
use wstmart\common\model\Feedbacks as M;
/**
 * 功能反馈控制器
 */
class Feedbacks extends Base{
    /**
    * 跳去反馈问题页面
    */
    public function index(){
    	return $this->fetch('feedback');
    }

    /**
     * 保存反馈问题
     */
    public function add(){
        $m = new M();
        return $m->add();
    }
}
