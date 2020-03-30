<?php
namespace addons\kuaidi\controller;

use think\addons\Controller;
use addons\kuaidi\model\Kuaidi as M;
/**
 * 快递查询控制器
 */
class Weapp extends Controller{
	public function __construct(){
		parent::__construct();
	}


	/**
	 * 获取物流详情
	 */
	public function checkExpress(){
		$userId = model('weapp/index')->getUserId();
        $orderId = (int)input("orderId");
		$m = new M();
        $hasExpress = $m->checkHasExpress($orderId);
        $expressLogs = [];
        if($hasExpress) {
            $express = $m->getExpress($orderId,$userId);
            foreach ($express as $v) {
                if ($v["expressNo"] != "" && $v['expressId']>0) {
                    $res = $m->getOrderExpresses($orderId, $v['expressId'], $v['expressNo']);
                    $res['expressId'] = $v['expressId'];
                    $res['expressNo'] = $v['expressNo'];
                    $expressLogs['expressData'][] = $res;
                }
            }
            foreach($expressLogs["expressData"] as $k => $v){
                $state = isset($v["logs"]["state"])?$v["logs"]["state"]:'-1';
                $expressLogs['express'][$k]["stateText"] = $m->getExpressState($state);
                $expressLogs['express'][$k]["expressId"] = $v["expressId"];
                $expressLogs['express'][$k]["expressNo"] = $v["expressNo"];
                $expressLogs['express'][$k]["expressName"] = $res['expressName'];
            }
            $expressLogs["goodlist"] = $m->getOrderInfo();
        }
        return jsonReturn('',1,['data'=>$expressLogs]);
	}	
}