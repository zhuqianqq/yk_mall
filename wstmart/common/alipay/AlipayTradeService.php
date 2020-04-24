<?php
/* *
 * 功能：支付宝手机网站alipay.trade.close (统一收单交易关闭接口)业务参数封装
 * 版本：2.0
 * 修改日期：2016-11-01
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 */
namespace  wstmart\common\alipay;
use util\Tools;
use   wstmart\common\alipay\config;
use wstmart\common\alipay\AlipayTradeQueryRequest;

class AlipayTradeService {

	//支付宝网关地址
	public $gateway_url = "https://openapi.alipay.com/gateway.do";

	//支付宝公钥
	public $alipay_public_key;

	//商户私钥
	public $private_key;

	//应用id
	public $appid;

	//编码格式
	public $charset = "UTF-8";

	public $token = NULL;
	
	//返回数据格式
	public $format = "json";

	//签名方式
	public $signtype = "RSA";

	function __construct($alipay_config){
		$this->gateway_url = $alipay_config['gatewayUrl'];
		$this->appid = $alipay_config['app_id'];
		$this->private_key = $alipay_config['merchant_private_key'];
		$this->alipay_public_key = $alipay_config['alipay_public_key'];
		$this->charset = $alipay_config['charset'];
		$this->signtype=$alipay_config['sign_type'];

		if(empty($this->appid)||trim($this->appid)==""){
			throw new \Exception("appid should not be NULL!");
		}
		if(empty($this->private_key)||trim($this->private_key)==""){
			throw new \Exception("private_key should not be NULL!");
		}
		if(empty($this->alipay_public_key)||trim($this->alipay_public_key)==""){
			throw new \Exception("alipay_public_key should not be NULL!");
		}
		if(empty($this->charset)||trim($this->charset)==""){
			throw new \Exception("charset should not be NULL!");
		}
		if(empty($this->gateway_url)||trim($this->gateway_url)==""){
			throw new \Exception("gateway_url should not be NULL!");
		}

	}
	function AlipayWapPayService($alipay_config) {
		$this->__construct($alipay_config);
	}

	/**
	 * alipay.trade.wap.pay
	 * @param $builder 业务参数，使用buildmodel中的对象生成。
	 * @param $return_url 同步跳转地址，公网可访问
	 * @param $notify_url 异步通知地址，公网可以访问
	 * @return $response 支付宝返回的信息
 	*/
	function wapPay($builder,$return_url,$notify_url) {
	
		$biz_content=$builder->getBizContent();
		//打印业务参数
		$this->writeLog($biz_content);
	
		$request = new AlipayTradeWapPayRequest();
	
		$request->setNotifyUrl($notify_url);
		$request->setReturnUrl($return_url);
		$request->setBizContent ( $biz_content );
	
		// 首先调用支付api
		$response = $this->aopclientRequestExecute ($request,true);
//		 $response = $response->alipay_trade_wap_pay_response;
		return $response;
	}

    //当面付2.0预下单(生成二维码,带轮询)
    public function qrPay($req, $config) {
        $bizContent = $req->getBizContent();
        $this->writeLog($bizContent);

        $request = new AlipayTradePrecreateRequest();
        $request->setBizContent ( $bizContent );
        $request->setNotifyUrl ( $config['scan_notify_url'] );
        // 首先调用支付api
        $response = $this->aopclientRequestExecute ( $request, NULL  );
        $response = $response->alipay_trade_precreate_response;
        return $response;
    }

	 function aopclientRequestExecute($request,$ispage=false) {

		$aop = new AopClient ();
		$aop->gatewayUrl = $this->gateway_url;
		$aop->appId = $this->appid;
		$aop->rsaPrivateKey =  $this->private_key;
		$aop->alipayrsaPublicKey = $this->alipay_public_key;
		$aop->apiVersion ="1.0";
		$aop->postCharset = $this->charset;
		$aop->format= $this->format;
		$aop->signType=$this->signtype;
		// 开启页面信息输出
		$aop->debugInfo=true;
		if($ispage)
		{
			$result = $aop->pageExecute($request,"post");
			echo $result;
		}
		else 
		{
			$result = $aop->Execute($request);
		}
        
		//打开后，将报文写入log文件
		$this->writeLog("response: ".var_export($result,true));
		return $result;
	}


	
	/**
	 * alipay.trade.refund (统一收单交易退款接口)
	 * @param $builder 业务参数，使用buildmodel中的对象生成。
	 * @return $response 支付宝返回的信息
	 */
	function Refund($builder){
		$biz_content=$builder->getBizContent();
		//打印业务参数
		$this->writeLog($biz_content);
		$request = new AlipayTradeRefundRequest();
		$request->setBizContent ( $biz_content );
	
		// 首先调用支付api
		$response = $this->aopclientRequestExecute ($request);
		$response = $response->alipay_trade_refund_response;
		return $response;
	}


	/**
	 * 验签方法
	 * @param $arr 验签支付宝返回的信息，使用支付宝公钥。
	 * @return boolean
	 */
	function check($arr){
		$aop = new AopClient();
		$aop->alipayrsaPublicKey = $this->alipay_public_key;
		$result = $aop->rsaCheckV1($arr, $this->alipay_public_key, $this->signtype);
		return $result;
	}
    // 当面付2.0消费查询
    public function queryTradeResult($req){
        $response = $this->query($req);
        $result = new AlipayF2FQueryResult($response);
        if($this->querySuccess($response)){
            // 查询返回该订单交易支付成功
            $result->setTradeStatus("SUCCESS");
        } else {
            //查询发生异常或无返回，交易状态未知
            $result->setTradeStatus("FAIL");
        }
        return $result;

    }
    // 查询返回“支付成功”
    protected function querySuccess($queryResponse){
        return !empty($queryResponse)&&
            $queryResponse->code == "10000"&&
            ($queryResponse->trade_status == "TRADE_SUCCESS"||
                $queryResponse->trade_status == "TRADE_FINISHED");
    }
    public function query($queryContentBuilder) {
        $biz_content = $queryContentBuilder->getBizContent();
        $this->writeLog($biz_content);
        $request = new AlipayTradeQueryRequest();
        $request->setBizContent ( $biz_content );
        $response = $this->aopclientRequestExecute ( $request , NULL );


        return $response->alipay_trade_query_response;
    }
	//请确保项目文件有可写权限，不然打印不了日志。
	function writeLog($text) {
		// $text=iconv("GBK", "UTF-8//IGNORE", $text);
		//$text = characet ( $text );
//        $filename = '/www/logs/alipay_web.log';
        Tools::addLog('alipay_web', date ( "Y-m-d H:i:s" ) . "  " . $text . "\r\n");
	}
}

?>