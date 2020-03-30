<?php 
namespace wstmart\common\validate;
use think\Validate;
/**
 * 商家入驻验证器
 */
class ShopApplys extends Validate{
	protected $rule = [
		'linkman'=>'require',
		'linkPhone' => 'require',
		'applyIntention' => 'require'
	];
	
	protected $message  =   [
		'linkman.require' => '请输入联系人姓名',
        'linkPhone.require' => '请输入联系电话',
		'applyIntention.require' => '请输入营业范围'
	];
	
	
    protected $scene = [
        'add' => ['linkman','linkPhone','applyIntention'],
    ]; 
}