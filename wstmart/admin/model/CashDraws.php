<?php
namespace wstmart\admin\model;
use think\Db;
use think\Loader;
use Env;
/**
 * 提现分类业务处理
 */
class CashDraws extends Base{
	protected $pk = 'cashId';
	/**
	 * 分页
	 */
	public function pageQuery(){
		$where = [];
		$targetType = input('targetType',-1);
		$cashNo = input('cashNo');
		$cashSatus = input('cashSatus',-2);
        if(in_array($targetType,[0,1]))$where[] = ['targetType','=',$targetType];
        if(in_array($cashSatus,[0,1,-1]))$where[] = ['cashSatus','=', $cashSatus];
        if($cashNo!='')$where[] = ['cashNo','like','%'.$cashNo.'%'];
        // 排序
		$sort = input('sort');
		$order = [];
		if($sort!=''){
			$sortArr = explode('.',$sort);
			$order = $sortArr[0].' '.$sortArr[1];
			if($sortArr[0]=='cashNo'){
				$order ='createTime '.$sortArr[1];
			}
		}
        $page = $this->where($where)->order($order)->order('createTime desc')->paginate(input('limit/d'))->toArray();
	    if(count($page['data'])>0){
	    	$userIds = [];
	    	$shopIds = [];
	    	foreach ($page['data'] as $key => $v) {
	    		if($v['targetType']==0)$userIds[] = $v['targetId'];
	    		if($v['targetType']==1)$shopIds[] = $v['targetId'];
	    	}
	    	$userMap = [];
	    	if(count($userIds)>0){
	    		$user = Db::name('users')->where([['userId','in',$userIds]])->field('userId,loginName,userName')->select();
	    	    foreach ($user as $key => $v) {
	    	    	$userMap["0_".$v['userId']] = $v; 
	    	    }
	    	}
	    	if(count($shopIds)>0){
	    		$user = Db::name('shops')->alias('s')
	    		          ->join('__USERS__ u','u.userId=s.userId')
	    		          ->where([['shopId','in',$shopIds]])
	    		          ->field('s.shopId,u.loginName,s.shopName as userName')
	    		          ->select();
	    	    foreach ($user as $key => $v) {
	    	    	$userMap["1_".$v['shopId']] = $v; 
	    	    }
	    	}
	    	foreach ($page['data'] as $key => $v) {
	    	    if (!isset($userMap[$v['targetType']."_".$v['targetId']])) continue;
	    		$page['data'][$key]['loginName'] = $userMap[$v['targetType']."_".$v['targetId']]['loginName'];
	    		$page['data'][$key]['userName'] = $userMap[$v['targetType']."_".$v['targetId']]['userName'];
	    	}
	    }
	    return $page;
	}

	/**
	 * 获取提现详情
	 */
	public function getById(){
		$id = (int)input('id');
		$rs =  $this->get($id);
		$user = [];
		if($rs['targetType']==1){
			$user = Db::name('shops')->alias('s')
	    		      ->join('__USERS__ u','u.userId=s.userId')
	    		      ->where('shopId',$rs['targetId'])
	    		      ->field('s.shopId,u.loginName,s.shopName as userName')
	    		      ->find();
            
		}else{
			$user = Db::name('users')->where('userId',$rs['targetId'])->field('userId,loginName,userName')->find();   
		}
		$rs['userName'] = $user['userName'];
        $rs['loginName'] = $user['loginName'];
		return $rs;
	}

	/**
	 * 处理提现成功
	 */
	public function handle(){
		$id = (int)input('cashId');
		$cash = $this->get($id);
		if(empty($cash))return WSTReturn('无效的提现申请记录');
		Db::startTrans();
		try{
            if($cash->targetType==0){
		        $user = model('users')->get($cash->targetId);
				if($user->lockMoney<$cash->money)return WSTReturn('操作失败，被冻结的金额小于提现金额');
				$user->lockMoney = $user->lockMoney-$cash->money;
            	$user->save();
            	$targetId = $user->userId;
            }else{
                $shop = model('shops')->get($cash->targetId);
				if($shop->lockMoney<$cash->money)return WSTReturn('操作失败，被冻结的金额小于提现金额');
                $shop->lockMoney = $shop->lockMoney-$cash->money;
            	$shop->save();
            	$targetId = $shop->userId;
            }
            $cash->cashSatus = 1;
            $cash->cashRemarks = input('cashRemarks');
            $result = $cash->save();
            $cashCommission = (float)$cash->commission;
            $commissionMoney = 0;
            if($cashCommission>0)$commissionMoney = $cash->money*$cashCommission*0.01;

            if(false != $result){
                $commissionLog = '';
                if($commissionMoney>0){
                    $commissionLog = '，手续费¥'.$commissionMoney.',实际到账金额¥'.$cash->actualMoney;
                }
            	//创建一条流水记录
            	$lm = [];
				$lm['targetType'] = $cash->targetType;
				$lm['targetId'] = $cash->targetId;
				$lm['dataId'] = $id;
				$lm['dataSrc'] = 3;
				$lm['remark'] = '提现申请单【'.$cash->cashNo.'】申请提现¥'.$cash->money.$commissionLog.'。'.(($cash->cashRemarks!='')?"【操作备注】：".$cash->cashRemarks:'');
				$lm['moneyType'] = 0;
				$lm['money'] = $cash->money;
				$lm['payType'] = 0;
				$lm['createTime'] = date('Y-m-d H:i:s');
				model('LogMoneys')->insert($lm);
				//发送信息信息
				$tpl = WSTMsgTemplates('CASH_DRAW_SUCCESS');
		        if( $tpl['tplContent']!='' && $tpl['status']=='1'){
		            $find = ['${CASH_NO}'];
		            $replace = [$cash->cashNo];
		            WSTSendMsg($targetId,str_replace($find,$replace,$tpl['tplContent']),['from'=>5,'dataId'=>$id]);
		        } 
				//微信消息
				if((int)WSTConf('CONF.wxenabled')==1){
					$params = [];
					$params['CASH_NO'] = $cash->cashNo;
					$params['MONEY'] = $cash->money;
					$params['CASH_TYPE'] = '银行提现';           
					$params['CASH_TIME'] = $cash['createTime'];
					$params['CASH_RESULT'] = "审核通过。【备注：".((input('cashRemarks')=='')?"无":input('cashRemarks'))."】";
					$params['EXAMINE_TIME'] = date('Y-m-d H:i:s');
					WSTWxMessage(['CODE'=>'WX_CASH_DRAW_SUCCESS','userId'=>$targetId,'params'=>$params]);
				}
				Db::commit();
				return WSTReturn('操作成功!',1);
            }
		}catch (\Exception $e) {
            Db::rollback();
        }
		return WSTReturn('操作失败!',-1);
	}

	/**
	 * 处理提现失败
	 */
	public function handleFail(){
		$id = (int)input('cashId');
		$cash = $this->get($id);
		if(empty($cash))return WSTReturn('无效的提现申请记录');
		if(input('cashRemarks')=='')return WSTReturn('请输入提现失败原因');
		Db::startTrans();
		try{
			
            if($cash->targetType==0){
		        $user = model('users')->get($cash->targetId);
				if($user->lockMoney<$cash->money)return WSTReturn('操作失败，无效的冻结的金额');
				$user->userMoney = $user->userMoney + $cash->money;
				$user->lockMoney = $user->lockMoney-$cash->money;
            	$user->save();
            	$targetId = $user->userId;
            }else{
                $shop = model('shops')->get($cash->targetId);
				if($shop->lockMoney<$cash->money)return WSTReturn('操作失败，无效的冻结的金额');
                $shop->shopMoney = $shop->shopMoney + $cash->money;
                $shop->lockMoney = $shop->lockMoney-$cash->money;
            	$shop->save();
            	$targetId = $shop->userId;
            }
            $cash->cashSatus = -1;
            $cash->cashRemarks = input('cashRemarks');
            $result = $cash->save();

            if(false != $result){
				//发送信息信息
				$tpl = WSTMsgTemplates('CASH_DRAW_FAIL');
		        if( $tpl['tplContent']!='' && $tpl['status']=='1'){
		            $find = ['${CASH_NO}','${CASH_RESULT}'];
		            $replace = [$cash->cashNo,input('cashRemarks')];
		            WSTSendMsg($targetId,str_replace($find,$replace,$tpl['tplContent']),['from'=>5,'dataId'=>$id]);
		        } 
				//微信消息
				if((int)WSTConf('CONF.wxenabled')==1){
					$params = [];
					$params['CASH_NO'] = $cash->cashNo;
					$params['MONEY'] = $cash->money;
					$params['CASH_TYPE'] = '银行提现';           
					$params['CASH_TIME'] = $cash['createTime'];
					$params['CASH_RESULT'] = "审核不通过。【备注：".((input('cashRemarks')=='')?"无":input('cashRemarks'))."】";
					$params['EXAMINE_TIME'] = date('Y-m-d H:i:s');
					WSTWxMessage(['CODE'=>'WX_CASH_DRAW_FAIL','userId'=>$targetId,'params'=>$params]);
				}
				Db::commit();
				return WSTReturn('操作成功!',1);
            }
		}catch (\Exception $e) {
            Db::rollback();
        }
		return WSTReturn('操作失败!',-1);
	}
	/**
	 * 导出提现申请
	 */
	public function toExport(){
		$where = [];
		$name='提现申请表';
		$targetType = input('targetType',-1);
		$cashNo = input('cashNo');
		$cashSatus = input('cashSatus',-1);
        if(in_array($targetType,[0,1]))$where[] = ['targetType','=',$targetType];
        if(in_array($cashSatus,[0,1]))$where[] = ['cashSatus','=',$cashSatus];
        if($cashNo!='')$where[] = ['cashNo','like','%'.$cashNo.'%'];
        // 排序
		$sort = input('sort');
		$order = [];
		if($sort!=''){
			$sortArr = explode('.',$sort);
			$order = $sortArr[0].' '.$sortArr[1];
			if($sortArr[0]=='cashNo'){
				$order = $sortArr[0].'+0 '.$sortArr[1];
			}
		}
        $page = $this->where($where)->order($order)->order('createTime desc')->select();
	    if(count($page)>0){
	    	$userIds = [];
	    	$shopIds = [];
	    	foreach ($page as $key => $v) {
	    		if($v['targetType']==0)$userIds[] = $v['targetId'];
	    		if($v['targetType']==1)$shopIds[] = $v['targetId'];
	    	}
	    	$userMap = [];
	    	if(count($userIds)>0){
	    		$user = Db::name('users')->where([['userId','in',$userIds]])->field('userId,loginName,userName')->select();
	    	    foreach ($user as $key => $v) {
	    	    	$userMap["0_".$v['userId']] = $v; 
	    	    }
	    	}
	    	if(count($shopIds)>0){
	    		$user = Db::name('shops')->alias('s')
	    		          ->join('__USERS__ u','u.userId=s.userId')
	    		          ->where([['shopId','in',$shopIds]])
	    		          ->field('s.shopId,u.loginName,s.shopName as userName')
	    		          ->select();
	    	    foreach ($user as $key => $v) {
	    	    	$userMap["1_".$v['shopId']] = $v; 
	    	    }
	    	}
	    	foreach ($page as $key => $v) {
	    		$page[$key]['userType'] = ($v['targetType']==1)?"【商家】":"【会员】";
	    		$page[$key]['loginName'] = $userMap[$v['targetType']."_".$v['targetId']]['loginName'];
	    		$page[$key]['userName'] = $userMap[$v['targetType']."_".$v['targetId']]['userName'];
	    		$page[$key]['cashSatus'] = ($page[$key]['cashSatus']==1)?'提现成功':(($page[$key]['cashSatus']==-1)?'提现失败':'待处理');
	    	}
	    }
	   

		require Env::get('root_path') . 'extend/phpexcel/PHPExcel/IOFactory.php';
		$objPHPExcel = new \PHPExcel();
		// 设置excel文档的属性
		$objPHPExcel->getProperties()->setCreator("WSTMart")//创建人
		->setLastModifiedBy("WSTMart")//最后修改人
		->setTitle($name)//标题
		->setSubject($name)//题目
		->setDescription($name)//描述
		->setKeywords("提现");//种类
	
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
		$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
		$objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(12);
        $objRow = $objPHPExcel->getActiveSheet()->getStyle('A1:I1');
		$objRow->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
		$objRow->getFill()->getStartColor()->setRGB('666699');
		$objRow->getAlignment()->setVertical(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objRow->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
		$objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);	
		$objPHPExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(25);
		
		$objPHPExcel->getActiveSheet()->setCellValue('A1', '提现单号')
		->setCellValue('B1', '会员类型')->setCellValue('C1', '会员名称')
		->setCellValue('D1', '提现银行')->setCellValue('E1', '银行卡号')
		->setCellValue('F1', '持卡人')->setCellValue('G1', '提现金额')
		->setCellValue('H1', '提现时间')->setCellValue('I1', '状态');
		$objPHPExcel->getActiveSheet()->getStyle('A1:I1')->applyFromArray($styleArray);
	    $totalRow = 0;
		for ($row = 0; $row < count($page); $row++){
			$i = $row+2;
			$objPHPExcel->getActiveSheet()->setCellValue('A'.$i, $page[$row]['cashNo'])
			->setCellValue('B'.$i, $page[$row]['userType'])->setCellValue('C'.$i, $page[$row]['userName'].'('.$page[$row]['loginName'].')' )
			->setCellValue('D'.$i, $page[$row]['accTargetName'])->setCellValue('E'.$i, $page[$row]['accNo'].' ')
			->setCellValue('F'.$i, $page[$row]['accUser'])->setCellValue('G'.$i, '￥'.$page[$row]['money'])
			->setCellValue('H'.$i, $page[$row]['createTime'])->setCellValue('I'.$i, $page[$row]['cashSatus']);
			$totalRow = $row;
		}
		$totalRow = (count($page)==0)?1:$totalRow+2;
	    $objPHPExcel->getActiveSheet()->getStyle('A1:I'.$totalRow)->applyFromArray(array(
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
