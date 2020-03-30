<?php
namespace wstmart\store\controller;
/**
 * 商城消息控制器
 */
class Messages extends Base{
    protected $beforeActionList = ['checkAuth'];
    /**
    * 查看商城消息
    */
	public function index(){
        return $this->storeMessage();
	}
   /**
    * 查看商城消息
    */
    public function storeMessage(){
        $this->assign('p',(int)input('p'));
        return $this->fetch('messages/list');
    }
    /**
    * 获取数据
    */
    public function pageQuery(){
        $data = model('Messages')->pageQuery();
        return WSTGrid($data);
    }
    /**
    * 查看完整商城消息
    */
    public function showStoreMsg(){
        $data = model('Messages')->getById();
        return $this->fetch('messages/show',['data'=>$data,'p'=>(int)input('p')]);
    }
	
    /**
    * 删除
    */
    public function del(){
    	$m = model('store/Messages');
        $rs = $m->del();
        return $rs;
    }
    /**
    * 批量删除
    */
    public function batchDel(){
        $m = model('store/Messages');
        $rs = $m->batchDel();
        return $rs;
    }
    /**
    * 标记为已读
    */
    public function batchRead(){
        $m = model('store/Messages');
        $rs = $m->batchRead();
        return $rs;
    }


}
