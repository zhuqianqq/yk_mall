<?php
namespace wstmart\api\controller;
use think\Loader;
use Env;
use util\Tools;
use wstmart\common\model\Payments as M;
use wstmart\common\model\Orders as OM;
use wstmart\common\model\LogMoneys as LM;
use wstmart\common\model\ChargeItems as CM;
/**
 * 微信支付控制器
 */
class Weixinpays extends Base{

    /**
     * 微信 APP 回调地址
     * @param bool $channel 渠道参见$params[appMultipleInfo]
     * @return string
     */
    public function notify($channel = false)
    {
        // 接收异步回调参数
        $raw_xml = file_get_contents("php://input");
        Tools::addLog("wxpay_notify", "wx回调开始, praram:" . $raw_xml);
        die;

        if(empty($raw_xml)){
            return;
        }

        $response = "<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>";
        $sign_passed = false;

        $data = [];

        try {
            // 处理数据
            $xmlobj = simplexml_load_string($raw_xml);
            foreach ($xmlobj as  $k => $v) {
                if (is_object($v)) {
                    $data[$k] = $v->__toString();
                } else {
                    $data[$k] = $v;
                }
            }
            unset($xmlobj);

            // appMultipleInfo
            $channel = $channel ?? $data['channel'] ?? false;

            // 生成签名验证
            $sign_passed = $this->backSign($data, $channel) == $data['sign'];

            if(!$sign_passed){
                throw new \Exception("验签失败");
            }

            $order_id = $data['out_trade_no'];
            $recharge = RechargeRecord::findOne(['merOrderId' => $order_id]);

            if (empty($recharge)) {
                throw new \Exception("Payment record #$order_id is not exists");
            }

            if ($recharge->tradeStatus != RechargeRecord::TRADE_STATUS_SUCCESS) {
                $payment_success = $data['return_code'] === "SUCCESS" && $data['result_code'] === "SUCCESS";

                if(!$payment_success){
                    throw new \Exception("通知代码错误 - " . $data['return_code'] . ' & ' . $data['result_code']);
                }

                $money = bcdiv($data['total_fee'], 100);
                switch ($data['trade_type']) {
                    case 'APP':
                        $recharge->payType = RechargeRecord::RECHARGE_TYPE_WX;
                        break;

                    case 'JSAPI':
                        $recharge->payType = RechargeRecord::RECHARGE_TYPE_JSAPI;
                        if ($recharge->source ==  UserSource::SOURCE_XCX) {
                            $recharge->payType = RechargeRecord::RECHARGE_TYPE_WXAPP;
                        }
                        break;

                    case 'NATIVE':
                        $recharge->payType = RechargeRecord::RECHARGE_TYPE_NATIVE;
                        break;
                }

                $recharge->success($recharge->payType, $data['transaction_id'], $money);

                if ($recharge->source == UserSource::SOURCE_SHENGTIAN) {
                    RechargeTicket::autoBuy($recharge->id, $recharge->userid);
                }
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $return_code = $sign_passed ? 'SUCCESS' : 'FAIL';
            $response = "<xml><return_code><![CDATA[$return_code]]></return_code><return_msg><![CDATA[$message]]></return_msg></xml>";

            if (is_object($recharge)) {
                $recharge->failure($recharge->payType, $data['transaction_id'], $e->getMessage());
            }
        }

        return $response;
    }

    // 生成回调验证签名+
    private function backSign($data, $channel = false)
    {
        $key = Yii::$app->params['key'];
        if (isset(Yii::$app->params['appMultipleInfo'][$channel])) {
            $key = Yii::$app->params['appMultipleInfo'][$channel]['key'];
        }

        if (isset($data['sign'])) {
            unset($data['sign']);
        }

        if (isset($data['channel'])) {
            unset($data['channel']);
        }

        $sign_1 = http_build_query($data);
        $sign_2 = $sign_1."&key=".$key;
        $sign_3 = strtoupper(MD5($sign_2));
        return $sign_3;
    }

	/**
	 * 初始化
	 */
	private $wxpayConfig;
	private $wxpay;
	public function initialize() {
		header ("Content-type: text/html; charset=utf-8");
	    require Env::get('root_path') . 'extend/wxpay/WxPayConf.php';
	    require Env::get('root_path') . 'extend/wxpay/WxQrcodePay.php';
	    require Env::get('root_path') . 'extend/wxpay/WxJsApiPay.php';
		
		$this->wxpayConfig = array();
		$m = new M();
		$this->wxpay = $m->getPayment("weixinpays");
		$this->wxpayConfig['appid'] = $this->wxpay['appId']; // 微信公众号身份的唯一标识
		$this->wxpayConfig['appsecret'] = $this->wxpay['appsecret']; // JSAPI接口中获取openid
		$this->wxpayConfig['mchid'] = $this->wxpay['mchId']; // 受理商ID
		$this->wxpayConfig['key'] = $this->wxpay['apiKey']; // 商户支付密钥Key
		$this->wxpayConfig['notifyurl'] = url("mobile/weixinpays/notify","",true,true);
		$this->wxpayConfig['returnurl'] = url("mobile/orders/index","",true,true);
		$this->wxpayConfig['curl_timeout'] = 30;
		
		// 初始化WxPayConf
		new \WxPayConf($this->wxpayConfig);
	}
	

	public function toWeixinPay(){
	    $data = [];
	    $payObj = input("payObj/s");
	    if($payObj=="recharge"){
	    	$returnurl = url("mobile/logmoneys/usermoneys","",true,true);
	    	$cm = new CM();
	    	$itemId = (int)input("itemId/d");
	    	$targetType = 0;
	    	$targetId = (int)session('WST_USER.userId');
	    	$out_trade_no = (int)input("trade_no");
	    	if($targetType==1){//商家
	    		$targetId = (int)session('WST_USER.shopId');
	    	}
	    	$lm = new LM();
	    	$log = $lm->getLogMoney(['targetType'=>$targetType,'targetId'=>$targetId,'dataId'=>$out_trade_no,'dataSrc'=>4]);
	    	if(!empty($log)){
	    		header("Location:".$returnurl);
				exit();
	    	}
	    	$needPay = 0;
	    	if($itemId>0){
	    		$item = $cm->getItemMoney($itemId);
	    		$needPay = isSet($item["chargeMoney"])?$item["chargeMoney"]:0;
	    	}else{
	    		$needPay = (int)input("needPay/d");
	    	}
	    	
	    	$body = "钱包充值";
	    	$data["status"] = $needPay>0?1:-1;
	    	$attach = $payObj."@".$targetId."@".$targetType."@".$needPay."@".$itemId;
	    	
	    	if($needPay==0){
				header("Location:".$returnurl);
				exit();
			}
	    }else{
	    	$pkey = WSTBase64urlDecode(input("pkey"));
	    	
            $pkey = explode('@',$pkey);
            $orderNo = $pkey[0];
            $isBatch = (int)$pkey[1];

	        $data['orderNo'] = $orderNo;
	        $data['isBatch'] = $isBatch;
	        $data['userId'] = (int)session('WST_USER.userId');
			$m = new OM();
			$rs = $m->getOrderPayInfo($data);
			$returnurl = url("mobile/orders/index","",true,true);
			if(empty($rs)){
				header("Location:".$returnurl);
				exit();
			}else{
				$m = new OM();
				$userId = (int)session('WST_USER.userId');
				$obj["userId"] = $userId;
				$obj["orderNo"] = $orderNo;
				$obj["isBatch"] = $isBatch;
		
				$rs = $m->getByUnique($userId,$orderNo,$isBatch);
				$this->assign('rs',$rs);
				$body = "支付订单";
				$order = $m->getPayOrders($obj);
				$needPay = $order["needPay"];
				$payRand = $order["payRand"];
				$out_trade_no = $obj["orderNo"]."a".$payRand;
				$attach = $userId."@".$obj["orderNo"]."@".$obj["isBatch"];
				
				if($needPay==0){
					header("Location:".$returnurl);
					exit();
				}
			}
	    }
	    
	    // 初始化WxPayConf
    	new \WxPayConf ( $this->wxpayConfig );
    	//使用统一支付接口
		$notify_url = $this->wxpayConfig ['notifyurl'];
    	$unifiedOrder = new \UnifiedOrder();
    	$unifiedOrder->setParameter("out_trade_no",$out_trade_no);//商户订单号
    	$unifiedOrder->setParameter("notify_url",$notify_url);//通知地址
    	$unifiedOrder->setParameter("trade_type","MWEB");//交易类型
		$unifiedOrder->setParameter("attach",$attach);//扩展参数
    	$unifiedOrder->setParameter("body",$body);//商品描述
    	$needPay = WSTBCMoney($needPay,0,2);
    	$unifiedOrder->setParameter("total_fee", $needPay * 100);//总金额

    	$wap_name = WSTConf('CONF.mallName');
    	$unifiedOrder->setParameter("scene_info", "{'h5_info': {'type':'Wap','wap_url': '".$notify_url."','wap_name': '".$wap_name."'}}");//总金额

	    $this->assign('needPay',$needPay);
	    $this->assign('returnUrl',$returnurl );
	    $this->assign('payObj',$payObj);
	   	$wxResult = $unifiedOrder->getResult();
	    $this->assign('mweb_url',$wxResult['mweb_url']."&redirect_url".urlencode($returnurl));
		return $this->fetch('users/orders/orders_wxpay');
	}
	
	
	public function notify() {
		// 使用通用通知接口
		$notify = new \Notify();
		// 存储微信的回调
		$xml = file_get_contents("php://input");
        Tools::addLog("wxpay_notify", "微信回调开始, praram:" . $xml);
		$notify->saveData ( $xml );
		if ($notify->checkSign () == FALSE) {
			$notify->setReturnParameter ( "return_code", "FAIL" ); // 返回状态码
			$notify->setReturnParameter ( "return_msg", "签名失败" ); // 返回信息
		} else {
			$notify->setReturnParameter ( "return_code", "SUCCESS" ); // 设置返回码
		}
		$returnXml = $notify->returnXml ();
		if ($notify->checkSign () == TRUE) {
			if ($notify->data ["return_code"] == "FAIL") {
				// 此处应该更新一下订单状态，商户自行增删操作
			} elseif ($notify->data ["result_code"] == "FAIL") {
				// 此处应该更新一下订单状态，商户自行增删操作
			} else {
				$order = $notify->getData ();
				$rs = $this->process($order);
				if($rs["status"]==1){
					echo "SUCCESS";
				}else{
					echo "FAIL";
				}
			}
		}
	}
	
	//订单处理
	private function process($order) {
	
		$obj = array();
		$obj["trade_no"] = $order['transaction_id'];
		
		$obj["total_fee"] = (float)$order["total_fee"]/100;
		$extras =  explode ( "@", $order ["attach"] );
		if($extras[0]=="recharge"){//充值
			$targetId = (int)$extras [1];
			$targetType = (int)$extras [2];
			$itemId = (int)$extras [4];

			$obj["out_trade_no"] = $order['out_trade_no'];
			$obj["targetId"] = $targetId;
			$obj["targetType"] = $targetType;
			$obj["itemId"] = $itemId;
			$obj["payFrom"] = 'weixinpays';
			// 支付成功业务逻辑
			$m = new LM();
			$rs = $m->complateRecharge ( $obj );
		}else{
			$obj["userId"] = $extras[0];
			$obj["out_trade_no"] = $extras[1];
			$obj["isBatch"] = $extras[2];
			$obj["payFrom"] = "weixinpays";
			// 支付成功业务逻辑
			$m = new OM();
			$rs = $m->complatePay ( $obj );
		}
		
		return $rs;
		
	}

}
