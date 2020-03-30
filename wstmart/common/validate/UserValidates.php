<?php 
namespace wstmart\common\validate;
use think\Validate;
/**
 * 实名认证验证器
 */
class UserValidates extends Validate{
	protected $rule = [
		'true_name'  => 'require|max:100',
		'id_card' => 'require',
		'id_card_positive' => 'require',
		'id_card_back' => 'require',
		'phone' => 'require',
	];
	
	protected $message  =   [
		'true_name.require'  => '请输入真实姓名',
		'true_name.max' => '真实姓名不能超过100个字符',
		'id_card.require' => '请输入身份证号',
		'id_card_positive.require' => '请上传身份证正面照',
		'id_card_back.require' => '请上传身份证反面照',
		'phone.require' => '请输入注册的手机号',
	];

    protected $scene = [
        'add'   =>  ['true_name','id_card', 'id_card_positive', 'id_card_back', 'phone'],
    ];
}