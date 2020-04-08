<?php
namespace wstmart\home\controller;
/**
 * 商城消息控制器
 */
class Messages extends Base{
    protected $beforeActionList = ['checkAuth'];
    /**
    * 查看商城消息
    */
	public function index(){
        $this->assign('p',(int)input('p'));
		return $this->fetch('users/messages/list');
	}
    /**
    * 获取数据
    */
    public function pageQuery(){
        $data = model('Messages')->pageQuery();
        return WSTReturn("", 1,$data);
    }
    /**
    * 查看完整商城消息
    */
    public function showMsg(){
        $data = model('Messages')->getById();
        return $this->fetch('users/messages/show',['data'=>$data,'p'=>(int)input('p')]);
    }
    /**
    * 删除
    */
    public function del(){
    	$m = model('Home/Messages');
        $rs = $m->del();
        return $rs;
    }
    /**
    * 批量删除
    */
    public function batchDel(){
        $m = model('Home/Messages');
        $rs = $m->batchDel();
        return $rs;
    }
    /**
    * 标记为已读
    */
    public function batchRead(){
        $m = model('Home/Messages');
        $rs = $m->batchRead();
        return $rs;
    }


}
