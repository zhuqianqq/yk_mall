<?php
namespace wstmart\mobile\controller;
use wstmart\common\model\Carts as M;
use wstmart\common\model\UserAddress;
use wstmart\common\model\Payments;

/**
 * 购物车控制器
 */
class Carts extends Base{

	// 前置方法执行列表
    protected $beforeActionList = [
        'checkAuth'
    ];
	/**
	 * 查看购物车列表
	 */
	public function index(){
		$m = new M();
		$carts = $m->getCarts(false);
		$this->assign('carts',$carts);
		//dd($carts);
		return $this->fetch('carts');
	}
    /**
    * 加入购物车
    */
	public function addCart(){
		$m = new M();
		$rs = $m->addCart();
		$rs['cartNum'] = WSTCartNum();
		return $rs;
	}
	/**
	 * 修改购物车商品状态
	 */
	public function changeCartGoods(){
		$m = new M();
		$rs = $m->changeCartGoods();
		return $rs;
	}
	/**
	 * 批量修改购物车状态 
	 */
	public function batchChangeCartGoods(){
		$m = new M();
		return $m->batchChangeCartGoods();
	}
	/**
	 * 删除购物车里的商品
	 */
	public function delCart(){
		$m = new M();
		$rs= $m->delCart();
		return $rs;
	}
	/**
	 * 计算运费、积分和总商品价格
	 */
	public function getCartMoney(){
		$m = new M();
		$data = $m->getCartMoney();
		return $data;
	}
	/**
	 * 计算运费、积分和总商品价格/虚拟商品
	 */
	public function getQuickCartMoney(){
		$m = new M();
		$data = $m->getQuickCartMoney();
		return $data;
	}
	/**
	 * 跳去购物车结算页面
	 */
	public function settlement(){
		$m = new M();
		//获取一个用户地址
		$addressId = (int)input('addressId');

		//获取省级地区信息
		$area = model('areas')->listQuery(0);
		$this->assign('area',$area);
	
		$ua = new UserAddress();
		if($addressId>0){
			$userAddress = $ua->getById($addressId);
		}else{
			$userAddress = $ua->getDefaultAddress();
		}
		$this->assign('userAddress',$userAddress);
		//获取支付方式
		$pa = new Payments();
		$payments = $pa->getByGroup('2');
		//获取已选的购物车商品
		$carts = $m->getCarts(true);
		//商品已经下架
		// if(isset($carts['status']) && $carts['status']==-1){
		// 	return "<script> alert('商品已下架！'); window.location.href='/mobile/carts'</script>";
		// 	die();
		// }
		//若该页面购物车信息为空直接跳转我的订单页面
		if(!count($carts['carts'])){
			header('location:/mobile/orders');
			die();
		}
		hook("mobileControllerCartsSettlement",["carts"=>$carts,"payments"=>&$payments]);
		
		$this->assign('payments',$payments);
		//获取用户积分
		$user = model('users')->getFieldsById((int)session('WST_USER.userId'),'userScore');
		//计算可用积分和金额
        $goodsTotalMoney = $carts['goodsTotalMoney'];
        $goodsTotalScore = WSTScoreToMoney($goodsTotalMoney,true);
        $useOrderScore =0;
        $useOrderMoney = 0;
        if($user['userScore']>$goodsTotalScore){
            $useOrderScore = $goodsTotalScore;
            $useOrderMoney = $goodsTotalMoney;
        }else{
        	$useOrderScore = $user['userScore'];
            $useOrderMoney = WSTScoreToMoney($useOrderScore);
        }
        $this->assign('userOrderScore',$useOrderScore);
        $this->assign('userOrderMoney',$useOrderMoney);
		
		$this->assign('carts',$carts);
		return $this->fetch('settlement');
	}
	/**
	 * 跳去虚拟商品购物车结算页面
	 */
	public function quickSettlement(){
		$m = new M();
		//获取支付方式
		$pa = new Payments();
		$payments = $pa->getByGroup('2');
		$this->assign('payments',$payments);
		//获取用户积分
		$user = model('users')->getFieldsById((int)session('WST_USER.userId'),'userScore');
		//获取已选的购物车商品
		$carts = $m->getQuickCarts();
		//计算可用积分和金额
		$goodsTotalMoney = $carts['goodsTotalMoney'];
		$goodsTotalScore = WSTScoreToMoney($goodsTotalMoney,true);
		$useOrderScore =0;
		$useOrderMoney = 0;
		if($user['userScore']>$goodsTotalScore){
			$useOrderScore = $goodsTotalScore;
			$useOrderMoney = $goodsTotalMoney;
		}else{
			$useOrderScore = $user['userScore'];
			$useOrderMoney = WSTScoreToMoney($useOrderScore);
		}
		$this->assign('userOrderScore',$useOrderScore);
		$this->assign('userOrderMoney',$useOrderMoney);
		
		$this->assign('carts',$carts);
		return $this->fetch('settlement_quick');
	}

    /**
     * 将购物车里选择的商品移入我的关注
     */
    public function moveToFavorites(){
        $m = new M();
        $rs= $m->moveToFavorites();
        return $rs;
    }

    /**
     * 获取店铺自提点
     */
    public function getStores(){
    	$userId = (int)session('WST_USER.userId');
    	$rs = model("common/Stores")->shopStores($userId);
    	return WSTReturn("", 1,$rs);
    }
}
