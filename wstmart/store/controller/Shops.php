<?php
namespace wstmart\store\controller;
use wstmart\common\model\GoodsCats;
use wstmart\store\validate\Shops as Validate;
/**
 * 门店控制器
 */

class Shops extends Base{
    protected $beforeActionList = ['checkAuth'];
    /**
    * 店铺公告页
    */
    public function notice(){
        $notice = model('shops')->getNotice();
        $this->assign('notice',$notice);
        return $this->fetch('store/notice');
    }
    /**
    * 修改店铺公告
    */
    public function editNotice(){
        $s = model('shops');
        return $s->editNotice();
    }
    
    
    /**
     * 查看店铺设置
     */
    public function info(){
    	$s = model('shops');
    	$object = $s->getByView((int)session('WST_STORE.shopId'));
    	$this->assign('object',$object);
    	return $this->fetch('store/view');
    }

    /**
     * 编辑店铺资料
     */
    public function editInfo(){
        $rs = model('shops')->editInfo();
        return $rs;
    }


}
