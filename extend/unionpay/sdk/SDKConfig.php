<?php
include_once 'log.class.php';
include_once 'common.php';

class SDKConfig {
	
	static public $frontTransUrl;
	static public $backTransUrl;
	static public $singleQueryUrl;
	static public $batchTransUrl;
	static public $fileTransUrl;
	static public $appTransUrl;
	static public $cardTransUrl;
	static public $jfFrontTransUrl;
	static public $jfBackTransUrl;
	static public $jfSingleQueryUrl;
	static public $jfCardTransUrl;
	static public $jfAppTransUrl;
	
	static public $qrcBackTransUrl;
	static public $qrcB2cIssBackTransUrl;
	static public $qrcB2cMerBackTransUrl;
	
	static public $signMethod;
	static public $version;
	static public $ifValidateCNName;
	static public $ifValidateRemoteCert;
	
	static public $signCertPath;
	static public $signCertPwd;
	static public $validateCertDir;
	static public $encryptCertPath;
	static public $rootCertPath;
	static public $middleCertPath;
	static public $frontUrl;
	static public $backUrl;
	static public $secureKey;
	static public $logFilePath;
	static public $logLevel;

	function __construct($config = array()){

		self::$frontTransUrl = "https://gateway.95516.com/gateway/api/frontTransReq.do";
		self::$backTransUrl = "https://gateway.95516.com/gateway/api/backTransReq.do";
		self::$singleQueryUrl = "https://gateway.95516.com/gateway/api/singleQuery.do";
		self::$batchTransUrl = "https://gateway.95516.com/gateway/api/batchTrans.do";
		self::$fileTransUrl = "https://gateway.95516.com/gateway/api/fileTrans.do";
		self::$appTransUrl = "https://gateway.95516.com/gateway/api/appTransReq.do";
		self::$cardTransUrl = "https://gateway.95516.com/gateway/api/cardTransReq.do";
		
		self::$jfFrontTransUrl = "https://gateway.95516.com/jiaofei/api/frontTransReq.do";
		self::$jfBackTransUrl = "https://gateway.95516.com/jiaofei/api/backTransReq.do";
		self::$jfSingleQueryUrl = "https://gateway.95516.com/jiaofei/api/singleQuery.do";
		self::$jfCardTransUrl = "https://gateway.95516.com/jiaofei/api/cardTransReq.do";
		self::$jfAppTransUrl = "https://gateway.95516.com/jiaofei/api/appTransReq.do";
		
		self::$qrcBackTransUrl = null;
		self::$qrcB2cIssBackTransUrl = null;
		self::$qrcB2cMerBackTransUrl = null;

		
		self::$version = "5.1.0";
		self::$ifValidateCNName = "false"; // 是否验证验签证书的CN，测试环境请设置false，生产环境请设置true。非false的值默认都当true处理。;
		self::$ifValidateRemoteCert = "false";//是否验证https证书，测试环境请设置false，生产环境建议优先尝试true，不行再false。非true的值默认都当false处理。
					
		self::$signCertPath = WSTRootPath()."/extend/unionpay/certs/acp_sign.pfx";
		
		
		self::$validateCertDir = null;
		self::$encryptCertPath = WSTRootPath()."/extend/unionpay/certs/acp_enc.cer";
		self::$rootCertPath = WSTRootPath()."/extend/unionpay/certs/acp_root.cer";
		self::$middleCertPath =  WSTRootPath()."/extend/unionpay/certs/acp_middle.cer";
		
		self::$frontUrl =  $config["frontUrl"];
		self::$backUrl =  $config["backUrl"];;
		self::$signCertPwd = $config["signCertPwd"];
		self::$signMethod = $config["signMethod"];
		
		self::$secureKey =  null;
		self::$logFilePath =  WSTRootPath()."/extend/unionpay/logs/";
		self::$logLevel =  1;
		
	}

}


