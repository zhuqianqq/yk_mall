<?php
namespace wstmart\api\controller;
use wstmart\common\model\Carts as M;
use wstmart\common\model\UserAddress;
use wstmart\common\model\Payments;
use wstmart\common\model\TUserMap;

/**
 * 购物车控制器
 */
class Carts extends Base{

	// 前置方法执行列表
    // protected $beforeActionList = [
    //     'checkAuth'
    // ];
	/**
	 * 查看购物车列表
	 */
	public function index(){
		$m = new M();
		$carts = $m->getCarts(false,input('param.user_id'));
		return $this->outJson(0, "success", $carts);
	}
    /**
    * 加入购物车
    */
	public function addCart(){

		$m = new M();
		$rs = $m->addCart(input('param.user_id'));
		$rs['cartNum'] = WSTCartNum(input('param.user_id'));
		return $this->outJson(0, "success", $rs);
	}

	/**
    * 用户未登录时加入购物车，登录后处理
    */
	public function unlogin_addCart(){

		//dd(input('param.'));
		$data = input('param.');
		if(!$data['user_id']){
			return WSTReturn("参数错误", -1);
		}
		if($data['carts']){
			$data['carts'] = json_decode($data['carts'],true);
			$m = new M();
			foreach ($data['carts'] as $k => $v) {
				 $m->unlogin_addCart($data['user_id'],$v['goodsId'],$v['goodsSpecId'],$v['buyNum'],$v['shareId']);
			}
		}
		return $this->outJson(0, "success");
	}


	/**
	 * 修改购物车商品状态
	 */
	public function changeCartGoods(){
		$m = new M();
		$rs = $m->changeCartGoods(input('param.user_id'));
		return $this->outJson(0, "success", $rs);
	}
	/**
	 * 批量修改购物车状态
	 */
	public function batchChangeCartGoods(){
		$m = new M();
		return $this->outJson(0, "success", $m->batchChangeCartGoods(input('param.user_id')));
	}
	/**
	 * 删除购物车里的商品
	 */
	public function delCart(){
		$m = new M();
		$rs= $m->delCart(input('param.user_id'));
		return $this->outJson(0, "success", $rs);
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
	public function settlement()
    {
        $userId = (int)input('post.user_id', 0); //直播用户id
        if (empty($userId)) {
            return $this->outJson(100, '缺少参数');
        }
        $m = new M();
        //获取一个用户地址
        $addressId = (int)input('addressId');
        $result = [];
        $ua = new UserAddress();
        if ($addressId > 0) {
            $userAddress = $ua->getById($addressId);
        } else {
            $userAddress = $ua->getDefaultAddress();
        }
        //$this->assign('userAddress',$userAddress);
        $result['userAddress'] = $userAddress;
        //获取支付方式
        $pa = new Payments();
        $payments = $pa->getByGroup('2');
        //获取已选的购物车商品
        $carts = $m->getCarts(true, $userId);

        hook("mobileControllerCartsSettlement", ["carts" => $carts, "payments" => &$payments]);

        //$this->assign('payments',$payments);
        $result['payments'] = $payments;
        //获取用户积分
        $user = model('users')->getFieldsById($userId, 'userScore');
        //计算可用积分和金额
        $goodsTotalMoney = $carts['goodsTotalMoney'];
        $goodsTotalScore = WSTScoreToMoney($goodsTotalMoney, true);
        $useOrderScore = 0;
        $useOrderMoney = 0;
        if ($user['userScore'] > $goodsTotalScore) {
            $useOrderScore = $goodsTotalScore;
            $useOrderMoney = $goodsTotalMoney;
        } else {
            $useOrderScore = $user['userScore'];
            $useOrderMoney = WSTScoreToMoney($useOrderScore);
        }
        //$this->assign('userOrderScore',$useOrderScore);
        //$this->assign('userOrderMoney',$useOrderMoney);
        $result['userOrderScore'] = $useOrderScore;
        $result['userOrderMoney'] = $useOrderMoney;
        //$this->assign('carts',$carts);
        $result['carts'] = $carts;
        //return $this->fetch('settlement');
        return $this->outJson(0, '购物车信息', $result);
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
