<?php
namespace wstmart\admin\validate;
use think\Validate;
/**
 * 功能反馈验证器
 */
class Feedbacks extends Validate{
    protected $rule = [
        'handleContent'  => 'require',

    ];

    protected $message = [
        'handleContent.require' => '请输入反馈回复内容'
    ];

    protected $scene = [
        'edit'  =>  ['handleContent']
    ];

}