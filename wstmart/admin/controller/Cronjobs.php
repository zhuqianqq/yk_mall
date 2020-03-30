<?php
namespace wstmart\admin\controller;
use wstmart\admin\model\CronJobs as M;
/**
 * 定时任务控制器
 */
class Cronjobs extends Base{
	/**
	 * 处理售后单
	 */
	public function autoDealOrderService(){
		$rs = model('common/OrderServices')->crontab();
		return json($rs);
	}
	/**
	 * 取消未付款订单
	 */
	public function autoCancelNoPay(){
		$m = new M();
        $rs = $m->autoCancelNoPay();
        return json($rs);
	}
	/**
	 * 自动好评
	 */
	public function autoAppraise(){
        $m = new M();
        $rs = $m->autoAppraise();
        return json($rs);
	}
	/**
	 * 自动确认收货
	 */
	public function autoReceive(){
	 	$m = new M();
        $rs = $m->autoReceive();
        return json($rs);
	}

	/**
	 * 发送队列消息
	 */
	public function autoSendMsg(){
	 	$m = new M();
        $rs = $m->autoSendMsg();
        return json($rs);
	}
	/**
	 * 生成sitemap.xml
	 */
	public function autoFileXml(){
		$m = new M();
		$rs = $m->autoFileXml();
		return json($rs);
	}

	/**
	 * 商家订单自动结算
	 */
	public function autoShopSettlement(){
		$m = new M();
		$rs = $m->autoShopSettlement();
		return json($rs);
	}

    /**
     * 主播邀请自动结算
     */
    public function autoInviteSettlement(){
        $m = new M();
        $rs = $m->autoInviteSettlement();
        return json($rs);
    }

	public  function clearPoster($value=''){
		$m = new M();
		$rs = $m->clearPoster();
		return json($rs);
	}
}