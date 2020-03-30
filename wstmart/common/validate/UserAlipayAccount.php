<?php 
namespace wstmart\common\validate;
use think\Validate;
/**
 * 支付宝账号验证器
 */
class UserAlipayAccount extends Validate{
	protected $rule = [
		'true_name'  => 'require|max:100',
		'account_num' => 'require|max:200',
	];
	
	protected $message  =   [
		'true_name.require'  => '请输入真实姓名',
		'true_name.max' => '真实姓名不能超过100个字符',
		'account_num.require' => '请输入支付宝账号',
        'account_num.max' => '支付宝账号不能超过200个字符',
	];

    protected $scene = [
        'add'   =>  ['true_name','account_num'],
    ];
}