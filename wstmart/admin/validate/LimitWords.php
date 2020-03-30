<?php 
namespace wstmart\admin\validate;
use think\Validate;
/**
 * 系统禁用关键字验证器
 */
class LimitWords extends Validate{
	protected $rule = [
	    'word' => 'require|max:50',
    ];
    
    protected $message = [
         'word.require' => '请输入系统禁用关键字',
         'word.max' => '系统禁用关键字不能超过50个字符',
    ];
    
    protected $scene = [
        'add'   =>  ['word'],
        'edit'  =>  ['word']
    ]; 
}