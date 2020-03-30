<?php 
namespace wstmart\admin\validate;
use think\Validate;
/**
 * 商家入驻验证器
 */
class ShopApplys extends Validate{
	protected $rule = [
		'applyStatus'=>'in:1,-1',
        'shopName'=>'checkShopName:1',
        'handleReamrk'=>'checkStatus:1'
	];

    protected $message = [
		'applyStatus.in'=>'无效的申请状态',
        'handleReamrk.checkStatus'=>'',
    ];
    /**
     * 检测店铺名称
     */
    function checkShopName(){
       $applyStatus = (int)input('applyStatus');
       $shopName = input('shopName');
       if($applyStatus==1 && $shopName=='')return '请输入店铺名称';
       return true;
    }
    /**
     * 检测申请失败原因
     */
    function checkStatus(){
       $applyStatus = (int)input('applyStatus');
       $handleReamrk = input('handleReamrk');
       if($applyStatus==-1 && $handleReamrk=='')return '请输入审核不通过原因';
       return true;
    }
    protected $scene = [
        'edit'=>['applyStatus','handleReamrk']
    ];
}