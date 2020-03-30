<?php 
namespace wstmart\admin\model;
use think\Db;
use think\Loader;
use Env;
/**
 * 结算业务处理
 */
class Settlements extends Base{
    protected $pk = 'settlementId';
    /**
	 * 获取结算列表
	 */
	public function pageQuery(){
        $where = [];
        $startDate = input('startDate');
		$endDate = input('endDate');
		$shopName = input('shopName');
        $settlementNo = input('settlementNo');
		$settlementStatus = (int)input('settlementStatus',-1);
		$sort = input('sort');
        $where = [];
        if($startDate!='')$where[] = ['st.createTime','>=',$startDate.' 00:00:00'];
        if($endDate!='')$where[] = ['st.createTime','<=',$endDate.' 23:59:59'];
		if($settlementNo!='')$where[] = ['settlementNo','like','%'.$settlementNo.'%'];
        if($shopName!='')$where[] = ['shopName|shopSn','like','%'.$shopName.'%']; 
        if($settlementStatus>=0)$where[] = ['settlementStatus','=',$settlementStatus];
        $order = 'st.settlementId desc';
        if($sort){
        	$sortArr = explode('.',$sort);
        	$order = $sortArr[0].' '.$sortArr[1];
        	if($sortArr[0]=='settlementNo'){
        		$order = $sortArr[0].'+0 '.$sortArr[1];
        	}
        }
		return Db::name('settlements')->alias('st')->join('__SHOPS__ s','s.shopId=st.shopId','left')->where($where)->field('s.shopName,settlementNo,settlementId,settlementMoney,commissionFee,backMoney,settlementStatus,settlementTime,st.createTime')->order($order)
			->paginate(input('limit/d'))->toArray();
	}

	/**
	 * 获取结算订单详情
	 */
	public function getById(){
        $settlementId = (int)input('id');
        $object =  Db::name('settlements')->alias('st')->where('settlementId',$settlementId)->join('__SHOPS__ s','s.shopId=st.shopId','left')->field('s.shopName,st.*')->find();
        if(!empty($object)){
        	$object['list'] = Db::name('orders')->where(['settlementId'=>$settlementId])
        	          ->field('orderId,orderNo,payType,goodsMoney,deliverMoney,realTotalMoney,totalMoney,commissionFee,scoreMoney,createTime')
        	          ->order('payType desc,orderId desc')->select();
        }
        return $object;
	}
	

	/**
	 * 获取订单商品
	 */
	public function pageGoodsQuery(){
        $id = (int)input('id');
        return Db::name('orders')->alias('o')->join('__ORDER_GOODS__ og','o.orderId=og.orderId')->where('o.settlementId',$id)
        ->field('orderNo,og.goodsPrice,og.goodsName,og.goodsSpecNames,og.goodsNum,og.commissionRate')->order('o.payType desc,o.orderId desc')->paginate(input('limit/d'))->toArray();
    }

    /**
     * 获取待结算商家
     */
    public function pageShopQuery(){
    	$areaIdPath = input('areaIdPath');
    	$shopName = input('shopName');
    	if($shopName!='')$where[] = ['s.shopName|s.shopSn','like','%'.$shopName.'%'];
    	if($areaIdPath !='')$where[] = ['s.areaIdPath','like',$areaIdPath."%"];
    	$where[] = ['s.dataFlag','=',1];
    	$where[] = ['s.noSettledOrderNum','>',0];
		$page = Db::table('__SHOPS__')->alias('s')->join('__AREAS__ a2','s.areaId=a2.areaId')
		       ->where($where)
		       ->field('shopId,shopSn,shopName,a2.areaName,shopkeeper,telephone,abs(noSettledOrderFee) noSettledOrderFee,noSettledOrderNum')
		       ->order('noSettledOrderFee desc')->paginate(input('limit/d'))->toArray();
        $shopIds = [];
        foreach ($page['data'] as $key => $v) {
            $shopIds[] = $v["shopId"];
        }
        $where = [];
        $where[] = ["orderStatus","=",2];
        $where[] = ["shopId","in",$shopIds];
        $where[] = ["settlementId","=",0];
        $olist = Db::name("orders")
                ->where($where)
                ->field("shopId,payType,realTotalMoney,scoreMoney,commissionFee")
                ->select();
        $omaps = [];
        foreach ($olist as $key => $vo) {
            $backMoney = 0;
            if($vo['payType']==1){
                 //在线支付的返还金额=实付金额+积分抵扣金额-佣金
                 $backMoney = $vo['realTotalMoney']+$vo['scoreMoney']-$vo['commissionFee'];
            }else{
                 //货到付款的返还金额=积分抵扣金额-佣金
                 $backMoney = $vo['scoreMoney']-$vo['commissionFee'];
            }
            $omoney = isset($omaps[$vo['shopId']])?$omaps[$vo['shopId']]:0;
            $omaps[$vo['shopId']] = $omoney + $backMoney;
        }
        foreach ($page['data'] as $key => $vo) {
            $page['data'][$key]['waitSettlMoney'] = isset($omaps[$vo['shopId']])?WSTBCMoney($omaps[$vo['shopId']],0):0;
        }
        return $page;
	}

   /**
    * 获取商家未结算的订单
    */
   public function pageShopOrderQuery(){
   	     $orderNo = input('orderNo');
   	     $payType = (int)input('payType',-1);
         $where[] = ['settlementId','=',0];
         $where[] = ['orderStatus','=',2];
         $where[] = ['shopId','=',(int)input('id')];
         $where[] = ['dataFlag','=',1];
         if($orderNo!='')$where[] = ['orderNo','like','%'.$orderNo.'%'];
         if(in_array($payType,[0,1]))$where[] = ['payType','=',$payType];
   	     $page = Db::name('orders')->where($where)
                      ->field('orderId,orderNo,payType,goodsMoney,deliverMoney,realTotalMoney,totalMoney,commissionFee,createTime,scoreMoney, useScore,
                               refundedPayMoney, refundedScoreMoney,refundedScore,refundedGetScore,refundedGetScoreMoney')
        	          ->order('payType desc,orderId desc')->paginate(input('limit/d'))->toArray();
        if(count($page['data'])>0){
        	foreach ($page['data'] as $key => $v) {
                $backMoney = 0;
                if($v['payType']==1){
                    $scoreRat = 0;
                    $surplusMoney = 0;
                    $refundedScore = $v["refundedScore"];
                    $refundedGetScore = $v["refundedGetScore"];
                    $refundedGetScoreMoney = $v["refundedGetScoreMoney"];
                    $refundedPayMoney = $v["refundedPayMoney"];
                    $refundedScoreMoney = $v["refundedScoreMoney"];
                    if($v['scoreMoney']>0){
                        $scoreRat = $v['scoreMoney']/$v['useScore'];
                        // 失效积分抵扣金额 = 失效的获得积分数 * 比例
                        $surplusMoney = $refundedGetScore * $scoreRat;
                        $page['data'][$key]['refundedGetScoreMoney'] = $surplusMoney;

                        // 是否为纯积分支付
                        if($v['realTotalMoney']==0){
                            // 退还积分抵扣金额 = 已退还的积分数 * 比例
                            $refundedScoreMoney = $refundedScore * $scoreRat;
                            // 纯积分支付时，已退还金额 = 退还积分可抵扣金额
                            $page['data'][$key]['refundedPayMoney'] = $refundedScoreMoney;
                        }
                    }
                     //在线支付的返还金额=实付金额+积分抵扣金额-佣金-                           已退款支付金额       - 已退款积分抵扣金额   - 失效积分抵扣金额
                     $backMoney = $v['realTotalMoney']+$v['scoreMoney']-$v['commissionFee'] - $refundedPayMoney - $refundedScoreMoney - $surplusMoney;
                     $backMoney = WSTBCMoney($backMoney, 0);
                }else{
                     //货到付款的返还金额=积分抵扣金额-佣金
                     $backMoney = $v['scoreMoney']-$v['commissionFee'];
                }
                $page['data'][$key]['waitSettlMoney'] = $backMoney;
        		$page['data'][$key]['payTypeName'] = WSTLangPayType($v['payType']);
        	}
        }
        return $page;
   }

   /**
    * 生成结算单
    */
	public function generateSettleByShop(){
		$shopId = (int)input('id');
        $where = [];
		$where[] = ['shopId','=',$shopId];
		$where[] = ['dataFlag','=',1];
		$where[] = ['orderStatus','=',2];
		$where[] = ['settlementId','=',0];
		$orders = Db::name('orders')->where($where)->field('orderId,payType,realTotalMoney,scoreMoney,commissionFee')->select();
    	if(empty($orders))return WSTReturn('没有需要结算的订单，请刷新后再核对!');
    	$settlementMoney = 0;
        $commissionFee = 0;    //平台要收的佣金
        $ids = [];
    	foreach ($orders as $key => $v) {
            $ids[] = $v['orderId'];
    		if($v['payType']==1){
                $settlementMoney += $v['realTotalMoney']+$v['scoreMoney'];
            }else{
                $settlementMoney += $v['scoreMoney'];
            }
            $commissionFee += abs($v['commissionFee']);
    	}
    	$backMoney = $settlementMoney-$commissionFee;
    	$shops = model('shops')->get($shopId);
    	if(empty($shops))WSTReturn('无效的店铺结算账号!');
    	Db::startTrans();
		try{
            $data = [];
            $data['settlementType'] = 0;
            $data['shopId'] = $shopId;
            $data['settlementMoney'] = $settlementMoney;
            $data['commissionFee'] = $commissionFee;
            $data['backMoney'] = $settlementMoney-$commissionFee;
            $data['settlementStatus'] = 1;
            $data['settlementTime'] = date('Y-m-d H:i:s');
            $data['createTime'] = date('Y-m-d H:i:s');
            $data['settlementNo'] = '';
            $result = $this->save($data);
            if(false !==  $result){
            	$this->settlementNo = $this->settlementId.(fmod($this->settlementId,7));
            	$this->save();
            	//修改商家订单情况
                Db::name('orders')->where([['orderId','in',$ids]])->update(['settlementId'=>$this->settlementId]);
                $shops->shopMoney = $shops->shopMoney + $backMoney;
                $shops->noSettledOrderNum = 0;
                $shops->noSettledOrderFee = 0;
                $shops->paymentMoney = 0;
                //修改商家充值金额
                $lockCashMoney = (($shops->rechargeMoney - $commissionFee)>=0)?($shops->rechargeMoney - $commissionFee):0;
                $shops->rechargeMoney = $lockCashMoney;
                $shops->save();
                
                //发消息
                $tpl = WSTMsgTemplates('SHOP_SETTLEMENT');
                if( $tpl['tplContent']!='' && $tpl['status']=='1'){
                    $find = ['${SETTLEMENT_NO}'];
                    $replace = [$this->settlementNo];
                    
                    $msg = array();
                    $msg["shopId"] = $shopId;
                    $msg["tplCode"] = $tpl["tplCode"];
                    $msg["msgType"] = 1;
                    $msg["content"] = str_replace($find,$replace,$tpl['tplContent']) ;
                    $msg["msgJson"] = ['from'=>4,'dataId'=>$this->settlementId];
                    model("common/MessageQueues")->add($msg);
                }
                //增加资金变动信息
                $lmarr = [];
                if($settlementMoney>0){
                    $lm = [];
                    $lm['targetType'] = 1;
                    $lm['targetId'] = $shopId;
                    $lm['dataId'] = $this->settlementId;
                    $lm['dataSrc'] = 2;
                    $lm['remark'] = '结算订单申请【'.$this->settlementNo.'】收入订单金额¥'.$settlementMoney."。";
                    $lm['moneyType'] = 1;
                    $lm['money'] = $settlementMoney;
                    $lm['payType'] = 0;
                    $lm['createTime'] = date('Y-m-d H:i:s');
                    $lmarr[] = $lm;
                }
                if($commissionFee>0){
    				$lm = [];
    				$lm['targetType'] = 1;
    				$lm['targetId'] = $shopId;
    				$lm['dataId'] = $this->settlementId;
    				$lm['dataSrc'] = 2;
    				$lm['remark'] = '结算订单申请【'.$this->settlementNo.'】收取订单佣金¥'.$commissionFee."。";
    				$lm['moneyType'] = 0;
    				$lm['money'] = $commissionFee;
    				$lm['payType'] = 0;
    				$lm['createTime'] = date('Y-m-d H:i:s');
                    $lmarr[] = $lm;
                }
				if(count($lmarr)>0)model('LogMoneys')->saveAll($lmarr);
				Db::commit();
            	return WSTReturn('生成结算单成功',1);
            }
		}catch (\Exception $e) {
            Db::rollback();
        }
        return WSTReturn('生成结算单失败',-1);
    }
	/**
     * 导出
     */
    public function toExport(){
        $where = [];
        $name='结算申请表';
        $settlementNo = input('settlementNo');
        $startDate = input('startDate');
        $endDate = input('endDate');
        $shopName = input('shopName');
        $settlementStatus = (int)input('settlementStatus',-1);
        $sort = input('sort');
        if($startDate!='')$where[] = ['st.createTime','>=',$startDate.' 00:00:00'];
        if($endDate!='')$where[] = ['st.createTime','<=',$endDate.' 23:59:59'];
        if($settlementNo!='')$where[] = ['settlementNo','like','%'.$settlementNo.'%'];
        if($shopName!='')$where[] = ['shopName|shopSn','like','%'.$shopName.'%']; 
        if($settlementStatus>=0)$where[] = ['settlementStatus','=',$settlementStatus];
        $order = 'st.settlementId desc';
        if($sort){
            $sortArr = explode('.',$sort);
            $order = $sortArr[0].' '.$sortArr[1];
            if($sortArr[0]=='settlementNo'){
                $order = $sortArr[0].'+0 '.$sortArr[1];
            }
        }
        $page = Db::name('settlements')->alias('st')
                ->join('__SHOPS__ s','s.shopId=st.shopId','left')
                ->where($where)
                ->field('s.shopName,settlementNo,settlementId,settlementMoney,commissionFee,backMoney,settlementStatus,settlementTime,st.createTime')
                ->order($order)
                ->select();
       
        require Env::get('root_path') . 'extend/phpexcel/PHPExcel/IOFactory.php';
        $objPHPExcel = new \PHPExcel();
        // 设置excel文档的属性
        $objPHPExcel->getProperties()->setCreator("WSTMart")//创建人
        ->setLastModifiedBy("WSTMart")//最后修改人
        ->setTitle($name)//标题
        ->setSubject($name)//题目
        ->setDescription($name)//描述
        ->setKeywords("结算");
    
        // 开始操作excel表
        $objPHPExcel->setActiveSheetIndex(0);
        // 设置工作薄名称
        $objPHPExcel->getActiveSheet()->setTitle(iconv('gbk', 'utf-8', 'Sheet'));
        // 设置默认字体和大小
        $objPHPExcel->getDefaultStyle()->getFont()->setName(iconv('gbk', 'utf-8', ''));
        $objPHPExcel->getDefaultStyle()->getFont()->setSize(11);
        $styleArray = array(
                'font' => array(
                        'bold' => true,
                        'color'=>array(
                                'argb' => 'ffffffff',
                        )
                )
        );
        //设置宽
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(12);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(12);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(12);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(12);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(12);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(12);
        $objRow = $objPHPExcel->getActiveSheet()->getStyle('A1:G1');
        $objRow->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
        $objRow->getFill()->getStartColor()->setRGB('666699');
        $objRow->getAlignment()->setVertical(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objRow->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);   
        $objPHPExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(25);
        
        $objPHPExcel->getActiveSheet()->setCellValue('A1', '结算单号')
        ->setCellValue('B1', '申请店铺')->setCellValue('C1', '结算金额')
        ->setCellValue('D1', '结算佣金')->setCellValue('E1', '返还金额')
        ->setCellValue('F1', '申请时间')->setCellValue('G1', '状态');
        $objPHPExcel->getActiveSheet()->getStyle('A1:G1')->applyFromArray($styleArray);
        $totalRow = 0;
        for ($row = 0; $row < count($page); $row++){
            $i = $row+2;
            $objPHPExcel->getActiveSheet()->setCellValue('A'.$i, $page[$row]['settlementNo'])
            ->setCellValue('B'.$i, $page[$row]['shopName'])->setCellValue('C'.$i, '￥'.$page[$row]['settlementMoney'])
            ->setCellValue('D'.$i, '￥'.$page[$row]['commissionFee'])->setCellValue('E'.$i, '￥'.$page[$row]['backMoney'])
            ->setCellValue('F'.$i, $page[$row]['createTime'])->setCellValue('G'.$i, $page[$row]['settlementStatus']==1?'已结算':'未结算');
        }
        $totalRow = count($page)+1;
        $objPHPExcel->getActiveSheet()->getStyle('A1:G'.$totalRow)->applyFromArray(array(
                'borders' => array (
                        'allborders' => array (
                                'style' => \PHPExcel_Style_Border::BORDER_THIN,  //设置border样式
                                'color' => array ('argb' => 'FF000000'),     //设置border颜色
                        )
                )
        ));
        $this->PHPExcelWriter($objPHPExcel,$name);
    }
}