<?php 
namespace wstmart\admin\validate;
use think\Validate;
/**
 * 银行验证器
 */
class Banks extends Validate{
	protected $rule = [
        'bankName' => 'require|max:150',
        'bankImg' => 'require'
    ];
    
    protected $message = [
        'bankName.require' => '请输入银行名称',
        'bankName.max' => '银行名称不能超过50个字符',
        'bankImg.require' => '请上传银行图标'
    ];
    protected $scene = [
        'add'   =>  ['bankName','bankImg'],
        'edit'  =>  ['bankName','bankImg']
    ]; 
}