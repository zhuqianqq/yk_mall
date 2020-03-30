<?php
namespace wstmart\shop\controller;
use wstmart\common\model\GoodsCats;
use wstmart\shop\validate\Shops as Validate;
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
        return $this->fetch('shop/notice');
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
    	$object = $s->getByView((int)session('WST_USER.shopId'));
    	$this->assign('object',$object);
    	return $this->fetch('shop/view');
    }

    /**
     * 编辑店铺资料
     */
    public function editInfo(){
        $rs = model('shops')->editInfo();
        return $rs;
    }

    /**
     * 获取店铺金额
     */
    public function getShopMoney(){
        $rs = model('shops')->getFieldsById((int)session('WST_USER.shopId'),'shopMoney,lockMoney,rechargeMoney');
        $urs = model('users')->getFieldsById((int)session('WST_USER.userId'),'payPwd');
        $rs['isSetPayPwd'] = ($urs['payPwd']=='')?0:1;
        $rs['isDraw'] = ((float)WSTConf('CONF.drawCashShopLimit')<=$rs['shopMoney'])?1:0;
        unset($urs);
        return WSTReturn('',1,$rs);
    }
}
