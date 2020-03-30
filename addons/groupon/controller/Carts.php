<?php
namespace addons\groupon\controller;

use think\addons\Controller;
use addons\groupon\model\Groupons as M;
use wstmart\common\model\UserAddress;
/**
 * 团购商品插件
 */
class Carts extends Controller{
	public function __construct(){
		parent::__construct();
		$this->assign("v",WSTConf('CONF.wstVersion')."_".WSTConf('CONF.wstPCStyleId'));
	}

    /**
     * 下单
     */
    public function addCart(){
        $m = new M();
        return $m->addCart();
    }


	/**
	 * 结算页面
	 */
	public function settlement(){
	    $CARTS = session('GROUPON_CARTS'); 
		if(empty($CARTS)){
			header("Location:".addon_url('groupon://goods/lists')); 
			exit;
		}
		//获取一个用户地址
		$userAddress = model('common/UserAddress')->getDefaultAddress();
		$this->assign('userAddress',$userAddress);
		//获取省份
		$areas = model('common/Areas')->listQuery();
		$this->assign('areaList',$areas);
		$m = new M();
		$carts = $m->getCarts();
		$this->assign('carts',$carts);
		//获取用户积分
        $user = model('common/users')->getFieldsById((int)session('WST_USER.userId'),'userScore');
        $this->assign('userScore',$user['userScore']);
        //获取支付方式
		$onlineType = ($carts['goodsType']==1)?1:-1;
		$payments = model('common/payments')->getByGroup('1',$onlineType);
        $this->assign('payments',$payments);

		return $this->fetch("/home/index/settlement");
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
	 * 下单
	 */
	public function submit(){
		$m = new M();
		$rs = $m->submit((int)input('orderSrc'));
		if($rs["status"]==1){
			$pkey = WSTBase64urlEncode($rs["data"]."@1");
			$rs["pkey"] = $pkey;
		}
		return $rs;
	}
    
	/**
	 * 微信结算页面
	 */
	public function wxSettlement(){
		$CARTS = session('GROUPON_CARTS');
		if(empty($CARTS)){
			header("Location:".addon_url('groupon://goods/wxlists'));
			exit;
		}
		//获取一个用户地址
		$addressId = (int)input('addressId');
		$ua = new UserAddress();
		if($addressId>0){
			$userAddress = $ua->getById($addressId);
		}else{
			$userAddress = $ua->getDefaultAddress();
		}
		$this->assign('userAddress',$userAddress);
		//获取省份
		$areas = model('common/Areas')->listQuery();
		$this->assign('areaList',$areas);
		$m = new M();
		$carts = $m->getCarts();
		$this->assign('carts',$carts);
		//获取用户积分
		$user = model('common/users')->getFieldsById((int)session('WST_USER.userId'),'userScore');
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
		//获取支付方式
		$onlineType = ($carts['goodsType']==1)?1:-1;
		$payments = model('common/payments')->getByGroup('3',$onlineType);
		$this->assign('payments',$payments);
		return $this->fetch("/wechat/index/settlement");
	}
	
	/**
	 * 手机结算页面
	 */
	public function moSettlement(){
		$CARTS = session('GROUPON_CARTS');
		if(empty($CARTS)){
			header("Location:".addon_url('groupon://goods/molists'));
			exit;
		}
		//获取一个用户地址
		$addressId = (int)input('addressId');
		$ua = new UserAddress();
		if($addressId>0){
			$userAddress = $ua->getById($addressId);
		}else{
			$userAddress = $ua->getDefaultAddress();
		}
		$this->assign('userAddress',$userAddress);
		//获取省份
		$areas = model('common/Areas')->listQuery();
		$this->assign('areaList',$areas);
		$m = new M();
		$carts = $m->getCarts();
		$this->assign('carts',$carts);
		//获取用户积分
		$user = model('common/users')->getFieldsById((int)session('WST_USER.userId'),'userScore');
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
		//获取支付方式
		$onlineType = ($carts['goodsType']==1)?1:-1;
		$payments = model('common/payments')->getByGroup('2',$onlineType);
		$this->assign('payments',$payments);
		return $this->fetch("/mobile/index/settlement");
	}


	/**
     * 获取指定地址店铺是否支付自提
     */
    public function checkSupportStores(){
    	$m = new M();
    	$userId = (int)session('WST_USER.userId');
        $rs = $m->checkSupportStores($userId);
    	return WSTReturn("", 1,$rs);
    }
    /**
     * 获取店铺自提点【pc】
     */
    public function getStores(){
    	$m = new M();
    	$userId = (int)session('WST_USER.userId');
    	$rs = model("common/stores")->listQuery($userId);
    	return WSTReturn("", 1,$rs);
    }

    /**
     * 获取店铺自提点[移动]
     */
    public function getShopStores(){
    	$userId = (int)session('WST_USER.userId');
    	$rs = model("common/Stores")->shopStores($userId);
    	return WSTReturn("", 1,$rs);
    }


	/**************************************小程序**********************************/
	/**
     * 去下单
     */
    public function weAddCart(){
        $this->checkWeappAuth();
        $userId= model('weapp/index')->getUserId();
        $m = new M();
        $rs = $m->addCart($userId);
        return jsonReturn('success',1,$rs);
    }
    /**
     * 下单
     */
    public function weSubmit(){
        $this->checkWeappAuth();
        $userId= model('weapp/index')->getUserId();
        $m = new M();
        $rs = $m->submit(5,$userId);
        if($rs["status"]==1){
			$pkey = WSTBase64urlEncode($rs["data"]."@1");
			$rs["pkey"] = $pkey;
		}
        return jsonReturn('success',1,$rs);
    }
    /**
     * 计算运费、积分和总商品价格
     */
    public function weGetCartMoney(){
        $this->checkWeappAuth();
        $userId= model('weapp/index')->getUserId();
        $m = new M();
        $data = $m->getCartMoney($userId);
        return jsonReturn('success',1,$data);
    }

    /**
     * 结算页面
     */
    public function weSettlement(){
        $this->checkWeappAuth();
        $userId= model('weapp/index')->getUserId();
        $CARTS = session('GROUPON_CARTS');

        if(empty($CARTS)){
            return jsonReturn('请选择商品',-1);
            exit;
        }
        //获取一个用户地址
        $addressId = (int)input('addressId');
        $ua = new UserAddress();
        if($addressId>0){
            $userAddress = $ua->getById($addressId,$userId);
        }else{
            $userAddress = $ua->getDefaultAddress($userId);
        }
        
        //获取已选的购物车商品
        $m = new M();
        $carts = $m->getCarts($userId);
        if(empty($carts['carts'])) return jsonReturn('团购商品不存在',-1);
        if($carts['goodsTotalNum']>0){
            if(empty($carts['carts']))return jsonReturn('请选择商品',-1);
        }
        $carts['userAddress'] = $userAddress;
        //获取支付方式
        $onlineType = ($carts['goodsType']==1)?1:-1;
        $payments = model('common/payments')->getByGroup('3',$onlineType);
        $carts['payments'] = $payments;
        $carts['payNames'] = $carts['payCodes'] = $carts['isOnline'] =  [];
        if($payments){
            foreach ($payments as $key =>$v){
                foreach ($v as $key2 =>$v2){
                    $carts['payNames'][] = $v2['payName'];
                    $carts['payCodes'][] = $v2['payCode'];
                    $carts['isOnlines'][] = $v2['isOnline'];
                }
            }
        }else{
            $carts['payNames'] = ['没有支付方式'];
        }

        //获取用户积分
        $user = model('common/users')->getFieldsById($userId, 'userScore');
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
        $carts['userOrderScore'] = $useOrderScore;

        $carts['userOrderMoney'] = $useOrderMoney;
        // 是否开启积分支付
        $carts['isOpenScorePay'] = WSTConf('CONF.isOpenScorePay');
        return jsonReturn('success',1,$carts);
    }


}