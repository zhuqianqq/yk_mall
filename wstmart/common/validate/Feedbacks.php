<?php 
namespace wstmart\common\validate;
use think\Validate;
/**
 * 功能反馈验证器
 */
class Feedbacks extends Validate{
	protected $rule = [
		'feedbackType'=>'require',
		'feedbackContent' => 'require',
		'contactInfo' => 'require'
	];
	
	protected $message  =   [
		'feedbackType.require' => '请选择反馈类型',
		'feedbackContent.require' => '请输入反馈的内容',
		'contactInfo.require' => '请输入联系方式'
	];
	
	
    protected $scene = [
        'add' => ['feedbackType','feedbackContent','contactInfo'],
    ]; 
}