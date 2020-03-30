<?php
namespace wstmart\admin\model;
use think\Db;
use think\Loader;
use Env;
/**
 * 提现认证业务处理
 */
class Uservalidates extends Base{
	protected $pk = 'validateId';
	protected $table = 'mall_user_validate';
	/**
	 * 分页
	 */
	public function pageQuery(){
		$where = [];
		// 身份证号
		$id_card = input('id_card', '');
		// 手机号
		$phone = input('phone');
		// 主播ID
		$user_id = input('user_id', 0);
		if (!empty($id_card)) {
            $where[] = ['id_card', '=', $id_card];
        }
        if (!empty($phone)) {
            $where[] = ['phone', '=', $phone];
        }
        if (!empty($user_id)) {
            $where[] = ['user_id', '=', $user_id];
        }

        $page = $this->where($where)->order('create_time desc')->paginate(input('limit/d'))->toArray();
	    return $page;
	}

	/**
	 * 获取提现详情
	 */
	public function getById(){
		$id = (int)input('id');
		$rs =  $this->get($id);
		return $rs;
	}

	/**
	 * 处理提现成功
	 */
	public function handle(){
        $id = (int)input('validateId');
        $cash = $this->get($id);
        if(empty($cash))return WSTReturn('无效的提现认证申请记录');
        $cash->status = 2;
        $result = $cash->save();
        if(false != $result){
            return WSTReturn('操作成功!',1);
        }
        return WSTReturn('操作失败!',-1);
	}

	/**
	 * 处理提现认证失败
	 */
	public function handleFail(){
		$id = (int)input('validateId');
		$cash = $this->get($id);
		if(empty($cash))return WSTReturn('无效的提现认证申请记录');
		if(input('remark')=='')return WSTReturn('请输入提现认证失败原因');
        $cash->status = 3;
        $cash->remark = input('remark');
        $result = $cash->save();
        if(false != $result){
            return WSTReturn('操作成功!',1);
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
