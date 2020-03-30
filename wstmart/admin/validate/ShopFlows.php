<?php 
namespace wstmart\admin\validate;
use think\Validate;
/**
 * 店铺入驻流程验证器
 */
class ShopFlows extends Validate{
	protected $rule = [
	    'flowName' => 'require|max:30',
    ];
    protected $message  =   [
	    'flowName.require' => '请输入流程名称',
	    'flowName.max'     => '流程名称不能超过30个字符',
    ];
    protected $scene = [
        'add'   =>  ['flowName'],
        'edit'  =>  ['flowName'],
    ]; 
}